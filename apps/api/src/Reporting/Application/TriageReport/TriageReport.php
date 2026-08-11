<?php

declare(strict_types=1);

namespace App\Reporting\Application\TriageReport;

use App\Professionals\Domain\Professional;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportMustBeReviewedBeforeTriage;
use App\Reporting\Domain\ReportStatus;
use App\Reporting\Domain\ReportTriageDecision;
use App\Reporting\Domain\ReportTriageDecisionRepository;
use App\Reporting\Domain\ReportTriageOutcome;
use App\Reporting\Domain\ReportTriageReason;
use DateTimeImmutable;

final readonly class TriageReport
{
    public function __construct(private ReportTriageDecisionRepository $decisions)
    {
    }

    public function decide(
        Report $report,
        Professional $professional,
        ReportTriageOutcome $outcome,
        ReportTriageReason $reason,
    ): ReportTriageDecision {
        if ($report->status() !== ReportStatus::Reviewed) {
            throw new ReportMustBeReviewedBeforeTriage();
        }

        return $this->decisions->record(
            $report,
            $professional,
            $outcome,
            $reason,
            DateTimeImmutable::createFromTimestamp(microtime(true)),
        );
    }

    /** @return list<ReportTriageDecision> */
    public function history(Report $report): array
    {
        return $this->decisions->findByReport($report);
    }
}
