<?php

declare(strict_types=1);

namespace App\Tests\Organisations\Infrastructure;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Organisations\Infrastructure\DoctrineOrganisationRepository;
use App\Tests\Shared\Infrastructure\Persistence\PostgreSqlTestCase;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\Uid\Uuid;

final class DoctrineOrganisationRepositoryTest extends PostgreSqlTestCase
{
    private DoctrineOrganisationRepository $organisationRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisationRepository = new DoctrineOrganisationRepository(
            $this->entityManager,
        );
    }

    public function testItSavesAndFindsAnOrganisationById(): void
    {
        $id = Uuid::v7();
        $publicReportingIdentifier = PublicReportingIdentifier::generate();
        $organisation = new Organisation(
            $id,
            'IES Horizonte',
            $publicReportingIdentifier,
        );

        $this->organisationRepository->save($organisation);
        $this->entityManager->clear();

        $persistedOrganisation = $this->organisationRepository->findById($id);

        self::assertNotNull($persistedOrganisation);
        self::assertNotSame($organisation, $persistedOrganisation);
        self::assertSame(
            $id->toRfc4122(),
            $persistedOrganisation->id()->toRfc4122(),
        );
        self::assertSame(
            'IES Horizonte',
            $persistedOrganisation->name(),
        );
        self::assertTrue(
            $publicReportingIdentifier->equals(
                $persistedOrganisation->publicReportingIdentifier(),
            ),
        );
    }

    public function testItFindsAnOrganisationByPublicReportingIdentifier(): void
    {
        $id = Uuid::v7();
        $publicReportingIdentifier = PublicReportingIdentifier::fromString(
            'ORG_0123456789ABCDEF',
        );
        $organisation = new Organisation(
            $id,
            'IES Horizonte',
            $publicReportingIdentifier,
        );

        $this->organisationRepository->save($organisation);
        $this->entityManager->clear();

        $persistedOrganisation = $this->organisationRepository
            ->findByPublicReportingIdentifier(
                PublicReportingIdentifier::fromString(
                    'org_0123456789abcdef',
                ),
            );

        self::assertNotNull($persistedOrganisation);
        self::assertSame(
            $id->toRfc4122(),
            $persistedOrganisation->id()->toRfc4122(),
        );
    }

    public function testItReturnsNullWhenTheOrganisationDoesNotExist(): void
    {
        self::assertNull(
            $this->organisationRepository->findById(Uuid::v7()),
        );
        self::assertNull(
            $this->organisationRepository
                ->findByPublicReportingIdentifier(
                    PublicReportingIdentifier::generate(),
                ),
        );
    }

    public function testItRejectsDuplicatePublicReportingIdentifiers(): void
    {
        $publicReportingIdentifier = PublicReportingIdentifier::generate();

        $this->organisationRepository->save(
            new Organisation(
                Uuid::v7(),
                'IES Horizonte',
                $publicReportingIdentifier,
            ),
        );

        $this->expectException(UniqueConstraintViolationException::class);

        $this->organisationRepository->save(
            new Organisation(
                Uuid::v7(),
                'IES Nuevo Horizonte',
                $publicReportingIdentifier,
            ),
        );
    }
}
