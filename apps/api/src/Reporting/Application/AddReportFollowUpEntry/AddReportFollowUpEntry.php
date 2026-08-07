<?php

declare(strict_types=1);

namespace App\Reporting\Application\AddReportFollowUpEntry;

use App\Reporting\Domain\FollowUpEntryContent;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportFollowUpEntry;
use App\Reporting\Domain\ReportFollowUpEntryRepository;
use DateTimeImmutable;

final readonly class AddReportFollowUpEntry
{
    public function __construct(
        private ReportFollowUpEntryRepository $followUpEntryRepository,
    ) {
    }

    public function __invoke(
        Report $report,
        FollowUpEntryContent $content,
    ): ReportFollowUpEntry {
        $entry = ReportFollowUpEntry::addedByReporter(
            $report,
            $content,
            DateTimeImmutable::createFromTimestamp(microtime(true)),
        );

        $this->followUpEntryRepository->save($entry);

        return $entry;
    }
}
