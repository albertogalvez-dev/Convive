<?php

declare(strict_types=1);

namespace App\Reporting\Application\SubmitAnonymousReport;

use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use App\Reporting\Domain\ReporterAttentionCue;
use App\Reporting\Domain\ReporterRecurrence;

final readonly class SubmitAnonymousReportCommand
{
    public function __construct(
        public PublicReportingIdentifier $organisationIdentifier,
        public SituationDescription $situationDescription,
        public SituationContext $situationContext,
        public ReporterRecurrence $reporterRecurrence = ReporterRecurrence::Unknown,
        public ReporterAttentionCue $reporterAttentionCue = ReporterAttentionCue::Unknown,
    ) {
    }
}
