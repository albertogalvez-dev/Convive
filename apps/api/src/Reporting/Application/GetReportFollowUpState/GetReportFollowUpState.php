<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetReportFollowUpState;

use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportFollowUpEntryRepository;
use App\Reporting\Domain\ReportFollowUpPolicy;

final readonly class GetReportFollowUpState
{
    public function __construct(
        private ReportFollowUpEntryRepository $followUpEntryRepository,
    ) {
    }

    public function __invoke(Report $report): ReportFollowUpState
    {
        return new ReportFollowUpState(
            $report->publicReference(),
            $report->situationDescription()->toString(),
            $report->situationContext(),
            $report->reporterRecurrence(),
            $report->reporterAttentionCue(),
            $report->taxonomyVersion(),
            $report->status(),
            $report->createdAt(),
            $this->followUpEntryRepository
                ->findByReportOrderedByCreatedAt(
                    $report,
                    ReportFollowUpPolicy::MAXIMUM_ENTRIES,
                ),
        );
    }
}
