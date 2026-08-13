<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Application\GetReportFollowUpState;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Application\GetReportFollowUpState\GetReportFollowUpState;
use App\Reporting\Domain\FollowUpEntryContent;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportFollowUpEntry;
use App\Reporting\Domain\ReportFollowUpEntryRepository;
use App\Reporting\Domain\ReportStatus;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class GetReportFollowUpStateTest extends TestCase
{
    public function testItMapsEveryReportFieldAndTheReportsFollowUpEntries(): void
    {
        $organisation = new Organisation(
            Uuid::fromString('0192a5c0-1111-7000-8000-000000000001'),
            'IES Valle Sereno',
            PublicReportingIdentifier::fromString(
                'ORG_7M4K9T2W6N8Q3R5X',
            ),
        );
        $description = SituationDescription::fromString(
            'A student is being excluded repeatedly during break time.',
        );

        $report = Report::create(
            $organisation,
            $description,
            SituationContext::InPerson,
        )->report;

        $entries = [
            ReportFollowUpEntry::addedByReporter(
                $report,
                FollowUpEntryContent::fromString('There is a new witness.'),
                new DateTimeImmutable(),
            ),
        ];

        $followUpEntryRepository = $this->createMock(
            ReportFollowUpEntryRepository::class,
        );
        $followUpEntryRepository
            ->expects(self::once())
            ->method('findByReportOrderedByCreatedAt')
            ->with($report)
            ->willReturn($entries);

        $getReportFollowUpState = new GetReportFollowUpState(
            $followUpEntryRepository,
        );

        $state = $getReportFollowUpState($report);

        self::assertSame($report->publicReference(), $state->publicReference);
        self::assertSame(
            $description->toString(),
            $state->situationDescription,
        );
        self::assertSame(SituationContext::InPerson, $state->situationContext);
        self::assertSame('andalucia-v1', $state->taxonomyVersion);
        self::assertSame(ReportStatus::Received, $state->status);
        self::assertSame($report->createdAt(), $state->createdAt);
        self::assertSame($entries, $state->followUpEntries);
    }
}
