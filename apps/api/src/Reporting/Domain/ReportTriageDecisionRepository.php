<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

use App\Professionals\Domain\Professional;
use DateTimeImmutable;

interface ReportTriageDecisionRepository
{
    public function record(
        Report $report,
        Professional $professional,
        ReportTriageOutcome $outcome,
        ReportTriageReason $reason,
        DateTimeImmutable $decidedAt,
    ): ReportTriageDecision;

    /** @return list<ReportTriageDecision> */
    public function findByReport(Report $report): array;
}
