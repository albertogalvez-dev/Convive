<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

interface ReportFollowUpEntryRepository
{
    public function save(ReportFollowUpEntry $entry): void;

    /**
     * @return list<ReportFollowUpEntry> ordered oldest first
     */
    public function findByReportOrderedByCreatedAt(Report $report): array;
}
