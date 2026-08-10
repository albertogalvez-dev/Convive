<?php

declare(strict_types=1);

namespace App\Reporting\Application\ProcessAttachments;

use App\Reporting\Application\AttachmentScanner;
use App\Reporting\Application\AttachmentScanVerdict;
use App\Reporting\Application\AttachmentStorage;
use App\Reporting\Domain\ReportAttachment;
use App\Reporting\Domain\ReportAttachmentPolicy;
use App\Reporting\Domain\ReportAttachmentRepository;
use App\Reporting\Domain\ReportAttachmentStatus;
use App\Shared\Infrastructure\Logging\SecurityEventLogger;
use DateInterval;
use DateTimeImmutable;

final readonly class ProcessReportAttachment
{
    public function __construct(
        private AttachmentStorage $storage,
        private AttachmentScanner $scanner,
        private ReportAttachmentRepository $attachments,
        private SecurityEventLogger $securityEventLogger,
    ) {
    }

    public function __invoke(ReportAttachment $attachment, DateTimeImmutable $now): void
    {
        if ($attachment->status() === ReportAttachmentStatus::Quarantined) {
            $attachment->beginScanning($now);
            $this->attachments->save($attachment);
        }

        if ($attachment->status() !== ReportAttachmentStatus::Scanning) {
            return;
        }

        $startedAt = $attachment->scanStartedAt();

        if ($startedAt === null) {
            throw new \LogicException('A scanning attachment must record its start time.');
        }

        if ($startedAt->add(new DateInterval(ReportAttachmentPolicy::SCAN_WINDOW)) <= $now) {
            $attachment->reject($now);
            $this->attachments->save($attachment);
            $this->securityEventLogger->attachmentScanTimedOut();

            return;
        }

        $content = null;

        try {
            $content = $this->storage->open($attachment);
            $verdict = $this->scanner->scan($content);
        } catch (\Throwable) {
            $this->securityEventLogger->attachmentScanUnavailable();

            return;
        } finally {
            if (is_resource($content)) {
                fclose($content);
            }
        }

        match ($verdict) {
            AttachmentScanVerdict::Clean => $this->markAvailable($attachment, $now),
            AttachmentScanVerdict::Infected => $this->reject($attachment, $now),
            AttachmentScanVerdict::Unavailable => $this->securityEventLogger->attachmentScanUnavailable(),
        };
    }

    private function markAvailable(ReportAttachment $attachment, DateTimeImmutable $now): void
    {
        // Promote the private object first. If persistence then fails, a retry
        // observes the already-promoted object but it remains unreadable until
        // the metadata transition succeeds.
        $this->storage->promoteToAvailable($attachment);
        $attachment->markAvailable($now);
        $this->attachments->save($attachment);
    }

    private function reject(ReportAttachment $attachment, DateTimeImmutable $now): void
    {
        $attachment->reject($now);
        $this->attachments->save($attachment);
        $this->securityEventLogger->attachmentScanRejected();
    }
}
