<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Presentation\Http;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAccessCapability;
use App\Reporting\Domain\ReportAccessGrant;
use App\Reporting\Domain\ReportAccessGrantRepository;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use App\Reporting\Presentation\Http\ReportAccessCookieFactory;
use App\Reporting\Presentation\Http\ReportAccessGuard;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;

final class ReportAccessGuardTest extends TestCase
{
    public function testItResolvesTheGrantForAValidCookie(): void
    {
        $report = $this->createReport();
        $capability = ReportAccessCapability::generate();
        $grant = ReportAccessGrant::issue(
            $report,
            $capability,
            new DateTimeImmutable('-2 minutes'),
        );

        $grantRepository = $this->createMock(
            ReportAccessGrantRepository::class,
        );
        $grantRepository
            ->expects(self::once())
            ->method('findByCapability')
            ->willReturn($grant);
        $grantRepository->expects(self::once())->method('save');

        $guard = new ReportAccessGuard(
            $this->cookieFactory(),
            $grantRepository,
        );

        $resolved = $guard->resolve(
            $this->requestWithCookie($capability->reveal()),
        );

        self::assertSame($grant, $resolved);
        self::assertSame($report, $resolved->report());
    }

    public function testItDoesNotPersistActivityForEveryValidRead(): void
    {
        $capability = ReportAccessCapability::generate();
        $grant = ReportAccessGrant::issue(
            $this->createReport(),
            $capability,
            new DateTimeImmutable(),
        );
        $grantRepository = $this->createMock(ReportAccessGrantRepository::class);
        $grantRepository->method('findByCapability')->willReturn($grant);
        $grantRepository->expects(self::never())->method('save');

        $guard = new ReportAccessGuard($this->cookieFactory(), $grantRepository);

        self::assertSame(
            $grant,
            $guard->resolve($this->requestWithCookie($capability->reveal())),
        );
    }

    public function testItReturnsNullWhenNoCookieIsPresent(): void
    {
        $grantRepository = $this->createMock(
            ReportAccessGrantRepository::class,
        );
        $grantRepository->expects(self::never())->method('findByCapability');

        $guard = new ReportAccessGuard(
            $this->cookieFactory(),
            $grantRepository,
        );

        self::assertNull($guard->resolve(Request::create('/')));
    }

    public function testItReturnsNullForAMalformedCookieValue(): void
    {
        $grantRepository = $this->createMock(
            ReportAccessGrantRepository::class,
        );
        $grantRepository->expects(self::never())->method('findByCapability');

        $guard = new ReportAccessGuard(
            $this->cookieFactory(),
            $grantRepository,
        );

        self::assertNull(
            $guard->resolve($this->requestWithCookie('too-short')),
        );
    }

    public function testItReturnsNullForAnUnknownCapability(): void
    {
        $grantRepository = $this->createMock(
            ReportAccessGrantRepository::class,
        );
        $grantRepository
            ->expects(self::once())
            ->method('findByCapability')
            ->willReturn(null);
        $grantRepository->expects(self::never())->method('save');

        $guard = new ReportAccessGuard(
            $this->cookieFactory(),
            $grantRepository,
        );

        self::assertNull(
            $guard->resolve(
                $this->requestWithCookie(
                    ReportAccessCapability::generate()->reveal(),
                ),
            ),
        );
    }

    public function testItReturnsNullForAnExpiredGrant(): void
    {
        $capability = ReportAccessCapability::generate();
        $grant = ReportAccessGrant::issue(
            $this->createReport(),
            $capability,
            new DateTimeImmutable('-3 hours'),
        );

        $grantRepository = $this->createMock(
            ReportAccessGrantRepository::class,
        );
        $grantRepository
            ->expects(self::once())
            ->method('findByCapability')
            ->willReturn($grant);
        $grantRepository->expects(self::never())->method('save');

        $guard = new ReportAccessGuard(
            $this->cookieFactory(),
            $grantRepository,
        );

        self::assertNull(
            $guard->resolve(
                $this->requestWithCookie($capability->reveal()),
            ),
        );
    }

    public function testItReturnsNullForARevokedGrant(): void
    {
        $capability = ReportAccessCapability::generate();
        $grant = ReportAccessGrant::issue(
            $this->createReport(),
            $capability,
            new DateTimeImmutable(),
        );
        $grant->revokeAt(new DateTimeImmutable());

        $grantRepository = $this->createMock(
            ReportAccessGrantRepository::class,
        );
        $grantRepository
            ->expects(self::once())
            ->method('findByCapability')
            ->willReturn($grant);
        $grantRepository->expects(self::never())->method('save');

        $guard = new ReportAccessGuard(
            $this->cookieFactory(),
            $grantRepository,
        );

        self::assertNull(
            $guard->resolve(
                $this->requestWithCookie($capability->reveal()),
            ),
        );
    }

    private function cookieFactory(): ReportAccessCookieFactory
    {
        return new ReportAccessCookieFactory('report_access', false);
    }

    private function requestWithCookie(string $value): Request
    {
        return Request::create(
            '/',
            cookies: ['report_access' => $value],
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
