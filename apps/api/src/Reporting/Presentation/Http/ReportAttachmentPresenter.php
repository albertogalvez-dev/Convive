<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

use App\Reporting\Domain\ReportAttachment;
use App\Reporting\Domain\ReportAttachmentStatus;

final class ReportAttachmentPresenter
{
    /** @return array{id: string, status: string, createdAt: string, description: ?string, mediaType?: string, byteSize?: int} */
    public function reporter(ReportAttachment $attachment): array
    {
        $response = [
            'id' => $attachment->id()->toRfc4122(),
            'status' => $this->reporterStatus($attachment),
            'createdAt' => $attachment->createdAt()->format(DATE_RFC3339_EXTENDED),
            'description' => $attachment->description()?->toString(),
        ];

        if (!$attachment->isAvailable()) {
            return $response;
        }

        return [
            ...$response,
            'mediaType' => $attachment->mediaType()->value,
            'byteSize' => $attachment->byteSize(),
        ];
    }

    /** @return array{id: string, mediaType: string, byteSize: int, createdAt: string, description: ?string} */
    public function professional(ReportAttachment $attachment): array
    {
        if (!$attachment->isAvailable()) {
            throw new \LogicException('Only available attachments may be presented to a professional.');
        }

        return [
            'id' => $attachment->id()->toRfc4122(),
            'mediaType' => $attachment->mediaType()->value,
            'byteSize' => $attachment->byteSize(),
            'createdAt' => $attachment->createdAt()->format(DATE_RFC3339_EXTENDED),
            'description' => $attachment->description()?->toString(),
        ];
    }

    private function reporterStatus(ReportAttachment $attachment): string
    {
        return match ($attachment->status()) {
            ReportAttachmentStatus::Quarantined,
            ReportAttachmentStatus::Scanning => 'processing',
            ReportAttachmentStatus::Available => 'available',
            ReportAttachmentStatus::Rejected,
            ReportAttachmentStatus::DeletionPending,
            ReportAttachmentStatus::Deleted => 'unavailable',
        };
    }
}
