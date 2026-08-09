<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Domain;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAccessCapability;
use App\Reporting\Domain\ReportAccessGrant;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ReportAccessGrantTest extends TestCase
{
    public function testItIsValidImmediatelyAfterIssuance(): void
    {
        $now = new DateTimeImmutable('2026-08-06T10:00:00+00:00');

        $grant = ReportAccessGrant::issue(
            $this->createReport(),
            ReportAccessCapability::generate(),
            $now,
        );

        self::assertTrue($grant->isValidAt($now));
    }

    public function testItBecomesInvalidAfterTheIdleTimeout(): void
    {
        $issuedAt = new DateTimeImmutable('2026-08-06T10:00:00+00:00');

        $grant = ReportAccessGrant::issue(
            $this->createReport(),
            ReportAccessCapability::generate(),
            $issuedAt,
        );

        $justBeforeIdleTimeout = $issuedAt
            ->add(new DateInterval('PT15M'))
            ->modify('-1 second');
        $justAfterIdleTimeout = $issuedAt
            ->add(new DateInterval('PT15M'))
            ->modify('+1 second');

        self::assertTrue($grant->isValidAt($justBeforeIdleTimeout));
        self::assertFalse($grant->isValidAt($justAfterIdleTimeout));
    }

    public function testRecordingUseExtendsTheIdleWindow(): void
    {
        $issuedAt = new DateTimeImmutable('2026-08-06T10:00:00+00:00');

        $grant = ReportAccessGrant::issue(
            $this->createReport(),
            ReportAccessCapability::generate(),
            $issuedAt,
        );

        $tenMinutesLater = $issuedAt->add(new DateInterval('PT10M'));
        self::assertTrue($grant->recordUseAt($tenMinutesLater));

        $twentyMinutesAfterIssuance = $issuedAt->add(new DateInterval('PT20M'));

        self::assertTrue($grant->isValidAt($twentyMinutesAfterIssuance));
    }

    public function testActivityIsPersistedAtMostOncePerMinute(): void
    {
        $issuedAt = new DateTimeImmutable('2026-08-06T10:00:00+00:00');
        $grant = ReportAccessGrant::issue(
            $this->createReport(),
            ReportAccessCapability::generate(),
            $issuedAt,
        );

        self::assertFalse(
            $grant->recordUseAt($issuedAt->modify('+59 seconds')),
        );
        self::assertSame($issuedAt, $grant->lastUsedAt());
        self::assertTrue(
            $grant->recordUseAt($issuedAt->modify('+60 seconds')),
        );
        self::assertEquals(
            $issuedAt->modify('+60 seconds'),
            $grant->lastUsedAt(),
        );
    }

    public function testItBecomesInvalidAfterTheAbsoluteLifetimeRegardlessOfActivity(): void
    {
        $issuedAt = new DateTimeImmutable('2026-08-06T10:00:00+00:00');

        $grant = ReportAccessGrant::issue(
            $this->createReport(),
            ReportAccessCapability::generate(),
            $issuedAt,
        );

        $justBeforeTwoHours = $issuedAt
            ->add(new DateInterval('PT2H'))
            ->modify('-1 second');
        self::assertTrue($grant->recordUseAt($justBeforeTwoHours));

        $justAfterTwoHours = $issuedAt
            ->add(new DateInterval('PT2H'))
            ->modify('+1 second');

        self::assertFalse($grant->isValidAt($justAfterTwoHours));
    }

    public function testARevokedGrantIsNeverValid(): void
    {
        $now = new DateTimeImmutable('2026-08-06T10:00:00+00:00');

        $grant = ReportAccessGrant::issue(
            $this->createReport(),
            ReportAccessCapability::generate(),
            $now,
        );

        $grant->revokeAt($now);

        self::assertFalse($grant->isValidAt($now));
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
