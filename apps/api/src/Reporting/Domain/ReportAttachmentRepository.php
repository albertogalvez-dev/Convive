<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

interface ReportAttachmentRepository
{
    public function save(ReportAttachment $attachment): void;

    /** @param list<ReportAttachment> $attachments */
    public function saveQuarantinedWithReportCapacity(array $attachments): void;

    public function findByIdForReport(Uuid $id, Report $report): ?ReportAttachment;

    /** @return list<ReportAttachment> */
    public function findByReport(Report $report): array;

    /** @return list<ReportAttachment> */
    public function findAwaitingScan(int $limit): array;

    /** @return list<ReportAttachment> */
    public function findForCleanup(
        DateTimeImmutable $quarantineDeadline,
        DateTimeImmutable $availableDeadline,
        int $limit,
    ): array;
}
