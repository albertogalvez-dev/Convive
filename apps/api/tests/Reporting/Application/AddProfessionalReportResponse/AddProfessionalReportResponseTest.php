<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Application\AddProfessionalReportResponse;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Application\AddReportFollowUpEntry\ReportFollowUpEntryLimitReached;
use App\Reporting\Application\AddProfessionalReportResponse\AddProfessionalReportResponse;
use App\Reporting\Domain\FollowUpAuthorType;
use App\Reporting\Domain\FollowUpEntryContent;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportFollowUpEntry;
use App\Reporting\Domain\ReportFollowUpEntryRepository;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AddProfessionalReportResponseTest extends TestCase
{
    public function testItAppendsAnAuditableReporterVisibleProfessionalEntry(): void
    {
        $report = $this->createReport();
        $professionalId = Uuid::fromString('0192a5c0-2222-7000-8000-000000000002');
        $repository = $this->createMock(ReportFollowUpEntryRepository::class);
        $repository->expects(self::once())->method('saveIfReportHasCapacity')
            ->with(self::callback(
                static fn (ReportFollowUpEntry $entry): bool =>
                    $entry->report() === $report
                    && $entry->authorType() === FollowUpAuthorType::Professional
                    && $professionalId->equals($entry->professionalAuthorId()),
            ), 100)
            ->willReturn(true);

        $entry = (new AddProfessionalReportResponse($repository))(
            $report,
            $professionalId,
            FollowUpEntryContent::fromString('We have received your information.'),
        );

        self::assertSame(FollowUpAuthorType::Professional, $entry->authorType());
    }

    public function testItRejectsAResponseWhenTheConversationIsAtCapacity(): void
    {
        $repository = $this->createMock(ReportFollowUpEntryRepository::class);
        $repository->expects(self::once())
            ->method('saveIfReportHasCapacity')
            ->willReturn(false);

        $this->expectException(ReportFollowUpEntryLimitReached::class);

        (new AddProfessionalReportResponse($repository))(
            $this->createReport(),
            Uuid::fromString('0192a5c0-2222-7000-8000-000000000002'),
            FollowUpEntryContent::fromString('One professional response too many.'),
        );
    }

    private function createReport(): Report
    {
        return Report::create(
            new Organisation(
                Uuid::v7(),
                'IES Test',
                PublicReportingIdentifier::generate(),
            ),
            SituationDescription::fromString('A fictional situation.'),
            SituationContext::Unknown,
        )->report;
    }
}
