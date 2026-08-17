<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetReportFollowUpState;

use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportFollowUpEntryRepository;
use App\Reporting\Domain\ReportFollowUpPolicy;
use App\Reporting\Domain\ReportTriageDecisionRepository;
use App\Reporting\Domain\ReporterProgress;

final readonly class GetReportFollowUpState
{
    public function __construct(
        private ReportFollowUpEntryRepository $followUpEntryRepository,
        private ReportTriageDecisionRepository $triageDecisionRepository,
    ) {
    }

    public function __invoke(Report $report): ReportFollowUpState
    {
        // The triage decisions are read only to derive the reporter's own
        // progress stage. Nothing from them -- not the outcome, not the
        // reason, not the deciding professional -- leaves this method.
        $progressStage = ReporterProgress::stageFor(
            $report,
            $this->triageDecisionRepository->findByReport($report),
        );

        return new ReportFollowUpState(
            $report->publicReference(),
            $report->situationDescription()->toString(),
            $report->situationContext(),
            $report->reporterRecurrence(),
            $report->reporterAttentionCue(),
            $report->taxonomyVersion(),
            $report->status(),
            $progressStage,
            $report->createdAt(),
            $this->followUpEntryRepository
                ->findByReportOrderedByCreatedAt(
                    $report,
                    ReportFollowUpPolicy::MAXIMUM_ENTRIES,
                ),
        );
    }
}
