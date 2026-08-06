<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetReportFollowUpState;

use App\Reporting\Domain\ReportStatus;
use App\Reporting\Domain\SituationContext;
use DateTimeImmutable;

final readonly class ReportFollowUpState
{
    /**
     * @param list<never> $followUpEntries Always empty until #25/#33 model
     *                                      and deliver follow-up messages.
     */
    public function __construct(
        public string $publicReference,
        public string $situationDescription,
        public SituationContext $situationContext,
        public ReportStatus $status,
        public DateTimeImmutable $createdAt,
        public array $followUpEntries,
    ) {
    }
}
