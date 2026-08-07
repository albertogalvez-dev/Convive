<?php

declare(strict_types=1);

namespace App\Tests\Professionals\Domain;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Professionals\Domain\OrganisationMembership;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Domain\ProfessionalRole;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class OrganisationMembershipTest extends TestCase
{
    public function testANewGrantIsActive(): void
    {
        $membership = $this->createMembership();

        self::assertTrue($membership->isActive());
        self::assertNull($membership->revokedAt());
    }

    public function testRevokingClearsActiveState(): void
    {
        $membership = $this->createMembership();
        $revokedAt = new DateTimeImmutable('2026-08-07T12:00:00+00:00');

        $membership->revokeAt($revokedAt);

        self::assertFalse($membership->isActive());
        self::assertSame($revokedAt, $membership->revokedAt());
    }

    private function createMembership(): OrganisationMembership
    {
        $professional = new Professional(
            Uuid::v7(),
            'Alex Rivera',
            ProfessionalEmail::fromString('alex.rivera@example.com'),
            new DateTimeImmutable(),
        );
        $organisation = new Organisation(
            Uuid::fromString('0192a5c0-1111-7000-8000-000000000001'),
            'IES Valle Sereno',
            PublicReportingIdentifier::fromString(
                'ORG_7M4K9T2W6N8Q3R5X',
            ),
        );

        return new OrganisationMembership(
            Uuid::v7(),
            $professional,
            $organisation,
            ProfessionalRole::Triage,
            new DateTimeImmutable(),
        );
    }
}
