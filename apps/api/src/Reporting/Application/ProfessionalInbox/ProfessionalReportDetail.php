<?php

declare(strict_types=1);

namespace App\Reporting\Application\ProfessionalInbox;

use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportFollowUpEntry;

final readonly class ProfessionalReportDetail
{
    /** @param list<ReportFollowUpEntry> $followUpEntries */
    public function __construct(
        public Report $report,
        public array $followUpEntries,
    ) {
    }
}
