<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Application\RevokeReportAccess;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Application\RevokeReportAccess\RevokeReportAccess;
use App\Reporting\Application\RevokeReportAccess\RevokeReportAccessCommand;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAccessCapability;
use App\Reporting\Domain\ReportAccessGrant;
use App\Reporting\Domain\ReportAccessGrantRepository;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class RevokeReportAccessTest extends TestCase
{
    public function testItRevokesAnExistingGrant(): void
    {
        $capability = ReportAccessCapability::generate();
        $grant = ReportAccessGrant::issue(
            $this->createReport(),
            $capability,
            new DateTimeImmutable('2026-08-06T10:00:00+00:00'),
        );

        $grantRepository = $this->createMock(
            ReportAccessGrantRepository::class,
        );
        $grantRepository
            ->expects(self::once())
            ->method('findByCapability')
            ->willReturn($grant);
        $grantRepository
            ->expects(self::once())
            ->method('save')
            ->with($grant);

        $revokeReportAccess = new RevokeReportAccess($grantRepository);

        $revokeReportAccess(
            new RevokeReportAccessCommand($capability->reveal()),
        );

        self::assertFalse(
            $grant->isValidAt(new DateTimeImmutable('2026-08-06T10:01:00+00:00')),
        );
    }

    public function testItIsANoOpForAnUnknownCapability(): void
    {
        $grantRepository = $this->createMock(
            ReportAccessGrantRepository::class,
        );
        $grantRepository
            ->expects(self::once())
            ->method('findByCapability')
            ->willReturn(null);
        $grantRepository
            ->expects(self::never())
            ->method('save');

        $revokeReportAccess = new RevokeReportAccess($grantRepository);

        $revokeReportAccess(
            new RevokeReportAccessCommand(
                ReportAccessCapability::generate()->reveal(),
            ),
        );
    }

    public function testItIsANoOpForAMalformedCapabilityHandle(): void
    {
        $grantRepository = $this->createMock(
            ReportAccessGrantRepository::class,
        );
        $grantRepository
            ->expects(self::never())
            ->method('findByCapability');
        $grantRepository
            ->expects(self::never())
            ->method('save');

        $revokeReportAccess = new RevokeReportAccess($grantRepository);

        $revokeReportAccess(
            new RevokeReportAccessCommand(''),
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
