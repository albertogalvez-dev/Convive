<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetReportFollowUpState;

use App\Reporting\Domain\ReportFollowUpEntry;
use App\Reporting\Domain\ReportStatus;
use App\Reporting\Domain\SituationContext;
use DateTimeImmutable;

final readonly class ReportFollowUpState
{
    /**
     * @param list<ReportFollowUpEntry> $followUpEntries ordered oldest
     *                                                    first; professional
     *                                                    entries remain
     *                                                    reserved for #33/#34
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
