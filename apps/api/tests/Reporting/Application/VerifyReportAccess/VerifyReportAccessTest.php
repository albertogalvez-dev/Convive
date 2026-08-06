<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Application\VerifyReportAccess;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Application\VerifyReportAccess\ReportAccessDenied;
use App\Reporting\Application\VerifyReportAccess\VerifyReportAccess;
use App\Reporting\Application\VerifyReportAccess\VerifyReportAccessCommand;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAccessCapability;
use App\Reporting\Domain\ReportAccessGrant;
use App\Reporting\Domain\ReportAccessGrantRepository;
use App\Reporting\Domain\ReportAccessSecret;
use App\Reporting\Domain\ReportRepository;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class VerifyReportAccessTest extends TestCase
{
    public function testItIssuesACapabilityForAValidAccessSecret(): void
    {
        $creationResult = Report::create(
            $this->createOrganisation(),
            SituationDescription::fromString(
                'A student is receiving threatening messages.',
            ),
            SituationContext::Digital,
        );
        $report = $creationResult->report;
        $plainAccessSecret = $creationResult->plainAccessSecret;

        $reportRepository = $this->createMock(ReportRepository::class);
        $reportRepository
            ->expects(self::once())
            ->method('findByAccessSecret')
            ->with(
                self::callback(
                    static fn (ReportAccessSecret $secret): bool =>
                        $secret->reveal() === $plainAccessSecret,
                ),
            )
            ->willReturn($report);

        $grantRepository = $this->createMock(
            ReportAccessGrantRepository::class,
        );
        $grantRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                self::callback(
                    static fn (ReportAccessGrant $grant): bool =>
                        $grant->report() === $report,
                ),
            );

        $verifyReportAccess = new VerifyReportAccess(
            $reportRepository,
            $grantRepository,
        );

        $result = $verifyReportAccess(
            new VerifyReportAccessCommand($plainAccessSecret),
        );

        self::assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $result->plainCapabilityHandle,
        );
        self::assertNotSame(
            $plainAccessSecret,
            $result->plainCapabilityHandle,
        );
    }

    public function testItDeniesAccessForAMalformedSecretWithoutQueryingTheRepository(): void
    {
        $reportRepository = $this->createMock(ReportRepository::class);
        $reportRepository
            ->expects(self::never())
            ->method('findByAccessSecret');

        $grantRepository = $this->createMock(
            ReportAccessGrantRepository::class,
        );
        $grantRepository
            ->expects(self::never())
            ->method('save');

        $verifyReportAccess = new VerifyReportAccess(
            $reportRepository,
            $grantRepository,
        );

        $this->expectException(ReportAccessDenied::class);

        $verifyReportAccess(
            new VerifyReportAccessCommand('too-short'),
        );
    }

    public function testItDeniesAccessForAnUnknownSecretWithTheSameFailure(): void
    {
        $reportRepository = $this->createMock(ReportRepository::class);
        $reportRepository
            ->expects(self::once())
            ->method('findByAccessSecret')
            ->willReturn(null);

        $grantRepository = $this->createMock(
            ReportAccessGrantRepository::class,
        );
        $grantRepository
            ->expects(self::never())
            ->method('save');

        $verifyReportAccess = new VerifyReportAccess(
            $reportRepository,
            $grantRepository,
        );

        $this->expectException(ReportAccessDenied::class);

        $verifyReportAccess(
            new VerifyReportAccessCommand(
                str_repeat('a', 64),
            ),
        );
    }

    public function testItRevokesThePreviouslyPresentedGrantWhenUnlockingAnotherReport(): void
    {
        $previousReport = Report::create(
            $this->createOrganisation(),
            SituationDescription::fromString(
                'A previous report already unlocked in this browser.',
            ),
            SituationContext::InPerson,
        )->report;
        $previousCapability = ReportAccessCapability::generate();
        $previousGrant = ReportAccessGrant::issue(
            $previousReport,
            $previousCapability,
            new DateTimeImmutable('-1 minute'),
        );

        $newCreationResult = Report::create(
            $this->createOrganisation(),
            SituationDescription::fromString(
                'Threatening messages have been received through a group chat.',
            ),
            SituationContext::Digital,
        );

        $reportRepository = $this->createMock(ReportRepository::class);
        $reportRepository
            ->expects(self::once())
            ->method('findByAccessSecret')
            ->willReturn($newCreationResult->report);

        $grantRepository = $this->createMock(
            ReportAccessGrantRepository::class,
        );
        $grantRepository
            ->expects(self::once())
            ->method('findByCapability')
            ->with(
                self::callback(
                    static fn (ReportAccessCapability $capability): bool =>
                        $capability->reveal()
                        === $previousCapability->reveal(),
                ),
            )
            ->willReturn($previousGrant);
        $grantRepository
            ->expects(self::exactly(2))
            ->method('save')
            ->with(
                self::callback(
                    static fn (ReportAccessGrant $grant): bool =>
                        $grant === $previousGrant
                        || $grant->report() === $newCreationResult->report,
                ),
            );

        $verifyReportAccess = new VerifyReportAccess(
            $reportRepository,
            $grantRepository,
        );

        $verifyReportAccess(
            new VerifyReportAccessCommand(
                $newCreationResult->plainAccessSecret,
                $previousCapability->reveal(),
            ),
        );

        self::assertNotNull($previousGrant->revokedAt());
    }

    public function testItIgnoresAMissingOrMalformedPreviousCapability(): void
    {
        $creationResult = Report::create(
            $this->createOrganisation(),
            SituationDescription::fromString(
                'A student is receiving threatening messages.',
            ),
            SituationContext::Digital,
        );

        $reportRepository = $this->createMock(ReportRepository::class);
        $reportRepository
            ->expects(self::once())
            ->method('findByAccessSecret')
            ->willReturn($creationResult->report);

        $grantRepository = $this->createMock(
            ReportAccessGrantRepository::class,
        );
        $grantRepository
            ->expects(self::never())
            ->method('findByCapability');
        $grantRepository->expects(self::once())->method('save');

        $verifyReportAccess = new VerifyReportAccess(
            $reportRepository,
            $grantRepository,
        );

        $verifyReportAccess(
            new VerifyReportAccessCommand(
                $creationResult->plainAccessSecret,
                'too-short',
            ),
        );
    }

    private function createOrganisation(): Organisation
    {
        return new Organisation(
            Uuid::fromString('0192a5c0-1111-7000-8000-000000000001'),
            'IES Valle Sereno',
            PublicReportingIdentifier::fromString(
                'ORG_7M4K9T2W6N8Q3R5X',
            ),
        );
    }
}
