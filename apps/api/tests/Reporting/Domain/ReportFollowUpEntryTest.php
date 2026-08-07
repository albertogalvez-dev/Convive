<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Domain;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Domain\FollowUpAuthorType;
use App\Reporting\Domain\FollowUpEntryContent;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportFollowUpEntry;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

final class ReportFollowUpEntryTest extends TestCase
{
    public function testAddedByReporterProducesAReporterAuthoredEntry(): void
    {
        $report = $this->createReport();
        $content = FollowUpEntryContent::fromString(
            'There is a new witness.',
        );
        $now = new DateTimeImmutable('2026-08-07T10:00:00+00:00');

        $entry = ReportFollowUpEntry::addedByReporter($report, $content, $now);

        self::assertInstanceOf(UuidV7::class, $entry->id());
        self::assertSame($report, $entry->report());
        self::assertSame(FollowUpAuthorType::Reporter, $entry->authorType());
        self::assertTrue($content->equals($entry->content()));
        self::assertSame($now, $entry->createdAt());
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
