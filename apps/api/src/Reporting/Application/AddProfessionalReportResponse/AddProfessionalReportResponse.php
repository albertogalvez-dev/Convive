<?php

declare(strict_types=1);

namespace App\Reporting\Application\AddProfessionalReportResponse;

use App\Reporting\Application\AddReportFollowUpEntry\ReportFollowUpEntryLimitReached;
use App\Reporting\Domain\FollowUpEntryContent;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportFollowUpEntry;
use App\Reporting\Domain\ReportFollowUpEntryRepository;
use App\Reporting\Domain\ReportFollowUpPolicy;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class AddProfessionalReportResponse
{
    public function __construct(
        private ReportFollowUpEntryRepository $followUpEntries,
    ) {
    }

    public function __invoke(
        Report $report,
        Uuid $professionalId,
        FollowUpEntryContent $content,
    ): ReportFollowUpEntry {
        $entry = ReportFollowUpEntry::addedByProfessional(
            $report,
            $professionalId,
            $content,
            DateTimeImmutable::createFromTimestamp(microtime(true)),
        );

        if (!$this->followUpEntries->saveIfReportHasCapacity(
            $entry,
            ReportFollowUpPolicy::MAXIMUM_ENTRIES,
        )) {
            throw new ReportFollowUpEntryLimitReached();
        }

        return $entry;
    }
}
