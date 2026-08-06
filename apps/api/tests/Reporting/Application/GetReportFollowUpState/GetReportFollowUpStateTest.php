<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Application\GetReportFollowUpState;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Application\GetReportFollowUpState\GetReportFollowUpState;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportStatus;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class GetReportFollowUpStateTest extends TestCase
{
    public function testItMapsEveryFieldFromTheReport(): void
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

        $getReportFollowUpState = new GetReportFollowUpState();

        $state = $getReportFollowUpState($report);

        self::assertSame($report->publicReference(), $state->publicReference);
        self::assertSame(
            $description->toString(),
            $state->situationDescription,
        );
        self::assertSame(SituationContext::InPerson, $state->situationContext);
        self::assertSame(ReportStatus::Received, $state->status);
        self::assertSame($report->createdAt(), $state->createdAt);
        self::assertSame([], $state->followUpEntries);
    }
}
