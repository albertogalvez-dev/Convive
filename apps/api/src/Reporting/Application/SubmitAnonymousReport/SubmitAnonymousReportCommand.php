<?php

declare(strict_types=1);

namespace App\Reporting\Application\SubmitAnonymousReport;

use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use App\Reporting\Domain\ReporterAttentionCue;
use App\Reporting\Domain\ReportedPeople;
use App\Reporting\Domain\ReporterPerspective;
use App\Reporting\Domain\ReporterRecurrence;
use App\Reporting\Domain\ReporterTiming;

final readonly class SubmitAnonymousReportCommand
{
    public function __construct(
        public PublicReportingIdentifier $organisationIdentifier,
        public SituationDescription $situationDescription,
        public SituationContext $situationContext,
        public ReporterRecurrence $reporterRecurrence = ReporterRecurrence::Unknown,
        public ReporterAttentionCue $reporterAttentionCue = ReporterAttentionCue::Unknown,
        public ReporterTiming $reporterTiming = ReporterTiming::Unknown,
        public ?ReportedPeople $reportedPeople = null,
        public ReporterPerspective $reporterPerspective = ReporterPerspective::Experienced,
    ) {
    }
}
