<?php

declare(strict_types=1);

namespace App\Reporting\Application\ProcessAttachments;

use App\Reporting\Domain\ReportAttachmentRepository;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class ProcessPendingReportAttachments
{
    public function __construct(
        private ReportAttachmentRepository $attachments,
        private ProcessReportAttachment $processAttachment,
    ) {
    }

    public function __invoke(int $limit): int
    {
        if ($limit < 1 || $limit > 200) {
            throw new InvalidArgumentException('The attachment processing limit is invalid.');
        }

        $now = DateTimeImmutable::createFromTimestamp(microtime(true));
        $attachments = $this->attachments->findAwaitingScan($limit);

        foreach ($attachments as $attachment) {
            ($this->processAttachment)($attachment, $now);
        }

        return count($attachments);
    }
}
