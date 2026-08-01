<?php

declare(strict_types=1);

namespace App\Tests\Organisations\Infrastructure;

use App\Organisations\Domain\Organisation;
use App\Organisations\Infrastructure\DoctrineOrganisationRepository;
use App\Tests\Shared\Infrastructure\Persistence\PostgreSqlTestCase;
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
        $organisation = new Organisation(
            $id,
            'IES Horizonte',
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
    }

    public function testItReturnsNullWhenTheOrganisationDoesNotExist(): void
    {
        $organisation = $this->organisationRepository->findById(Uuid::v7());

        self::assertNull($organisation);
    }
}
