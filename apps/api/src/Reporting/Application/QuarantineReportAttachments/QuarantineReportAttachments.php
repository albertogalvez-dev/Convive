<?php

declare(strict_types=1);

namespace App\Reporting\Application\QuarantineReportAttachments;

use App\Reporting\Application\AttachmentStorage;
use App\Reporting\Application\QuarantinedAttachmentUpload;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAttachment;
use App\Reporting\Domain\ReportAttachmentPolicy;
use App\Reporting\Domain\ReportAttachmentRepository;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class QuarantineReportAttachments
{
    public function __construct(
        private AttachmentStorage $storage,
        private ReportAttachmentRepository $attachments,
    ) {
    }

    /**
     * @param list<QuarantinedAttachmentUpload> $uploads
     *
     * @return list<ReportAttachment>
     */
    public function __invoke(Report $report, array $uploads): array
    {
        if ($uploads === [] || count($uploads) > ReportAttachmentPolicy::MAXIMUM_ATTACHMENTS_PER_WRITE) {
            throw new AttachmentUploadCountExceeded('The attachment count is outside the accepted boundary.');
        }

        $attachments = [];
        $now = DateTimeImmutable::createFromTimestamp(microtime(true));

        try {
            foreach ($uploads as $upload) {
                $attachmentId = Uuid::v7();
                $stored = $this->storage->storeQuarantine(
                    $attachmentId,
                    $upload->sourcePath,
                );

                $attachments[] = ReportAttachment::quarantine(
                    $attachmentId,
                    $report,
                    $upload->mediaType,
                    $stored->byteSize,
                    $stored->contentHash,
                    $now,
                    $upload->description,
                );
            }

            $this->attachments->saveQuarantinedWithReportCapacity($attachments);
        } catch (\Throwable $exception) {
            foreach ($attachments as $attachment) {
                try {
                    $this->storage->delete($attachment);
                } catch (\Throwable) {
                    // Preserve the primary failure. The later lifecycle cleanup
                    // increment reconciles private orphaned quarantine objects.
                }
            }

            throw $exception;
        }

        return $attachments;
    }
}
