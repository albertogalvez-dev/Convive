<?php

declare(strict_types=1);

namespace App\Reporting\Application\SubmitAnonymousReport;

use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;

final readonly class SubmitAnonymousReportCommand
{
    public function __construct(
        public PublicReportingIdentifier $organisationIdentifier,
        public SituationDescription $situationDescription,
        public SituationContext $situationContext,
    ) {
    }
}
