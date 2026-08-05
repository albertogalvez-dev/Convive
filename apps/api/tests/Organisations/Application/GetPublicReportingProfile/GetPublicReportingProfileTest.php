<?php

declare(strict_types=1);

namespace App\Tests\Organisations\Application\GetPublicReportingProfile;

use App\Organisations\Application\GetPublicReportingProfile\GetPublicReportingProfile;
use App\Organisations\Application\GetPublicReportingProfile\PublicReportingOrganisationNotFound;
use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\OrganisationRepository;
use App\Organisations\Domain\PublicReportingIdentifier;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class GetPublicReportingProfileTest extends TestCase
{
    public function testItReturnsThePublicReportingProfile(): void
    {
        $identifier = PublicReportingIdentifier::fromString(
            'ORG_7M4K9T2W6N8Q3R5X',
        );
        $organisation = new Organisation(
            Uuid::fromString('0192a5c0-1111-7000-8000-000000000001'),
            'IES Valle Sereno',
            $identifier,
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

        $getPublicReportingProfile = new GetPublicReportingProfile(
            $organisationRepository,
        );

        $profile = $getPublicReportingProfile($identifier);

        self::assertSame('IES Valle Sereno', $profile->name);
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

        $getPublicReportingProfile = new GetPublicReportingProfile(
            $organisationRepository,
        );

        $this->expectException(
            PublicReportingOrganisationNotFound::class,
        );
        $this->expectExceptionMessageIs(
            'No reporting organisation was found for identifier '
            .'"ORG_7M4K9T2W6N8Q3R5X".',
        );

        $getPublicReportingProfile($identifier);
    }
}
