<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Application\AddReportFollowUpEntry;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Application\AddReportFollowUpEntry\AddReportFollowUpEntry;
use App\Reporting\Application\AddReportFollowUpEntry\ReportFollowUpEntryLimitReached;
use App\Reporting\Domain\FollowUpAuthorType;
use App\Reporting\Domain\FollowUpEntryContent;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportFollowUpEntry;
use App\Reporting\Domain\ReportFollowUpEntryRepository;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AddReportFollowUpEntryTest extends TestCase
{
    public function testItPersistsAReporterAuthoredEntryForTheGivenReport(): void
    {
        $report = $this->createReport();
        $content = FollowUpEntryContent::fromString(
            'There is a new witness.',
        );

        $repository = $this->createMock(ReportFollowUpEntryRepository::class);
        $repository
            ->expects(self::once())
            ->method('saveIfReportHasCapacity')
            ->with(
                self::callback(
                    static fn (ReportFollowUpEntry $entry): bool =>
                        $entry->report() === $report
                        && $entry->authorType() === FollowUpAuthorType::Reporter
                        && $content->equals($entry->content()),
                ),
                100,
            )
            ->willReturn(true);

        $addReportFollowUpEntry = new AddReportFollowUpEntry($repository);

        $entry = $addReportFollowUpEntry($report, $content);

        self::assertSame($report, $entry->report());
        self::assertTrue($content->equals($entry->content()));
    }

    public function testItRejectsAnEntryWhenTheReportIsAtCapacity(): void
    {
        $repository = $this->createMock(ReportFollowUpEntryRepository::class);
        $repository
            ->expects(self::once())
            ->method('saveIfReportHasCapacity')
            ->willReturn(false);

        $addReportFollowUpEntry = new AddReportFollowUpEntry($repository);

        $this->expectException(ReportFollowUpEntryLimitReached::class);

        $addReportFollowUpEntry(
            $this->createReport(),
            FollowUpEntryContent::fromString('One entry too many.'),
        );
    }

    private function createReport(): Report
    {
        $organisation = new Organisation(
            Uuid::fromString('0192a5c0-1111-7000-8000-000000000001'),
            'IES Valle Sereno',
            PublicReportingIdentifier::fromString(
                'ORG_7M4K9T2W6N8Q3R5X',
            ),
        );

        return Report::create(
            $organisation,
            SituationDescription::fromString(
                'A student is being excluded repeatedly during break time.',
            ),
            SituationContext::InPerson,
        )->report;
    }
}
