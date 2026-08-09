<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

interface ReportFollowUpEntryRepository
{
    public function saveIfReportHasCapacity(
        ReportFollowUpEntry $entry,
        int $maximumEntries,
    ): bool;

    /**
     * @return list<ReportFollowUpEntry> ordered oldest first
     */
    public function findByReportOrderedByCreatedAt(
        Report $report,
        int $maximumEntries,
    ): array;
}
