<?php

declare(strict_types=1);

namespace App\Organisations\Infrastructure;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\OrganisationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class DoctrineOrganisationRepository implements OrganisationRepository
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function save(Organisation $organisation): void
    {
        $this->entityManager->persist($organisation);
        $this->entityManager->flush();
    }

    public function findById(Uuid $id): ?Organisation
    {
        return $this->entityManager->find(Organisation::class, $id);
    }
}
