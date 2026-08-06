<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetReportFollowUpState;

use App\Reporting\Domain\Report;

/**
 * Deliberately has no collaborators yet: it will need a follow-up message
 * repository once #25/#33 exist. Kept as an injectable service now so that
 * addition does not also require rewiring the controller.
 */
final readonly class GetReportFollowUpState
{
    public function __invoke(Report $report): ReportFollowUpState
    {
        return new ReportFollowUpState(
            $report->publicReference(),
            $report->situationDescription()->toString(),
            $report->situationContext(),
            $report->status(),
            $report->createdAt(),
            [],
        );
    }
}
