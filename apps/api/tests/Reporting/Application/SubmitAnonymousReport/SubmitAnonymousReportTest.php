<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Application\SubmitAnonymousReport;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\OrganisationRepository;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Application\SubmitAnonymousReport\ReportingOrganisationNotFound;
use App\Reporting\Application\SubmitAnonymousReport\SubmitAnonymousReport;
use App\Reporting\Application\SubmitAnonymousReport\SubmitAnonymousReportCommand;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportRepository;
use App\Reporting\Domain\ReportStatus;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SubmitAnonymousReportTest extends TestCase
{
    public function testItSubmitsAnAnonymousReportForAReportingOrganisation(): void
    {
        $identifier = PublicReportingIdentifier::fromString(
            'ORG_7M4K9T2W6N8Q3R5X',
        );
        $organisation = new Organisation(
            Uuid::fromString('0192a5c0-1111-7000-8000-000000000001'),
            'IES Valle Sereno',
            $identifier,
        );
        $description = SituationDescription::fromString(
            'A student is being excluded repeatedly during break time.',
        );

        $organisationRepository = $this->createMock(
            OrganisationRepository::class,
        );
        $organisationRepository
            ->expects(self::once())
            ->method('findByPublicReportingIdentifier')
            ->with(
                self::callback(
                    static fn (
                        PublicReportingIdentifier $receivedIdentifier,
                    ): bool => $identifier->equals($receivedIdentifier),
                ),
            )
            ->willReturn($organisation);

        $reportRepository = $this->createMock(ReportRepository::class);
        $reportRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                self::callback(
                    static fn (Report $report): bool =>
                        $report->organisation() === $organisation
                        && $description->equals(
                            $report->situationDescription(),
                        )
                        && $report->situationContext()
                        === SituationContext::InPerson,
                ),
            );

        $submitAnonymousReport = new SubmitAnonymousReport(
            $organisationRepository,
            $reportRepository,
        );

        $result = $submitAnonymousReport(
            new SubmitAnonymousReportCommand(
                $identifier,
                $description,
                SituationContext::InPerson,
            ),
        );

        self::assertMatchesRegularExpression(
            '/^[A-F0-9]{20}$/',
            $result->publicReference,
        );
        self::assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $result->plainAccessSecret,
        );
        self::assertSame(ReportStatus::Received, $result->status);
        self::assertSame('+00:00', $result->createdAt->format('P'));
    }

    public function testItRejectsAnUnknownReportingOrganisation(): void
    {
        $identifier = PublicReportingIdentifier::fromString(
            'ORG_7M4K9T2W6N8Q3R5X',
        );

        $organisationRepository = $this->createMock(
            OrganisationRepository::class,
        );
        $organisationRepository
            ->expects(self::once())
            ->method('findByPublicReportingIdentifier')
            ->with(
                self::callback(
                    static fn (
                        PublicReportingIdentifier $receivedIdentifier,
                    ): bool => $identifier->equals($receivedIdentifier),
                ),
            )
            ->willReturn(null);

        $reportRepository = $this->createMock(ReportRepository::class);
        $reportRepository
            ->expects(self::never())
            ->method('save');

        $submitAnonymousReport = new SubmitAnonymousReport(
            $organisationRepository,
            $reportRepository,
        );

        $this->expectException(ReportingOrganisationNotFound::class);
        $this->expectExceptionMessageIs(
            'No reporting organisation was found for identifier '
            .'"ORG_7M4K9T2W6N8Q3R5X".',
        );

        $submitAnonymousReport(
            new SubmitAnonymousReportCommand(
                $identifier,
                SituationDescription::fromString(
                    'A situation has been observed during break time.',
                ),
                SituationContext::InPerson,
            ),
        );
    }
}
