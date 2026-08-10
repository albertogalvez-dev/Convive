<?php

declare(strict_types=1);

namespace App\Reporting\Application\ProcessAttachments;

use App\Reporting\Application\AttachmentStorage;
use App\Reporting\Domain\ReportAttachment;
use App\Reporting\Domain\ReportAttachmentPolicy;
use App\Reporting\Domain\ReportAttachmentRepository;
use App\Reporting\Domain\ReportAttachmentStatus;
use App\Shared\Infrastructure\Logging\SecurityEventLogger;
use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class CleanExpiredReportAttachments
{
    public function __construct(
        private AttachmentStorage $storage,
        private ReportAttachmentRepository $attachments,
        private SecurityEventLogger $securityEventLogger,
    ) {
    }

    public function __invoke(int $limit): int
    {
        if ($limit < 1 || $limit > 200) {
            throw new InvalidArgumentException('The attachment cleanup limit is invalid.');
        }

        $now = DateTimeImmutable::createFromTimestamp(microtime(true));
        $quarantineDeadline = $now->sub(
            new DateInterval(ReportAttachmentPolicy::QUARANTINE_RETENTION),
        );
        $availableDeadline = $now->sub(
            new DateInterval(ReportAttachmentPolicy::FICTIONAL_AVAILABLE_RETENTION),
        );
        $attachments = $this->attachments->findForCleanup(
            $quarantineDeadline,
            $availableDeadline,
            $limit,
        );

        foreach ($attachments as $attachment) {
            $this->clean($attachment, $now);
        }

        $this->storage->deleteQuarantineObjectsOlderThan($quarantineDeadline);

        return count($attachments);
    }

    private function clean(ReportAttachment $attachment, DateTimeImmutable $now): void
    {
        if (in_array(
            $attachment->status(),
            [ReportAttachmentStatus::Quarantined, ReportAttachmentStatus::Scanning],
            true,
        )) {
            $attachment->reject($now);
            $this->attachments->save($attachment);
            $this->securityEventLogger->attachmentScanTimedOut();
        }

        if (in_array(
            $attachment->status(),
            [ReportAttachmentStatus::Rejected, ReportAttachmentStatus::Available],
            true,
        )) {
            $attachment->requestDeletion($now);
            $this->attachments->save($attachment);
        }

        if ($attachment->status() !== ReportAttachmentStatus::DeletionPending) {
            return;
        }

        try {
            $this->storage->delete($attachment);
            $attachment->markDeleted($now);
            $this->attachments->save($attachment);
        } catch (\Throwable) {
            $this->securityEventLogger->attachmentDeletionFailed();
        }
    }
}
