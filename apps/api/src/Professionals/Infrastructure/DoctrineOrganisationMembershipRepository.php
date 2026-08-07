<?php

declare(strict_types=1);

namespace App\Professionals\Infrastructure;

use App\Organisations\Domain\Organisation;
use App\Professionals\Domain\OrganisationMembership;
use App\Professionals\Domain\OrganisationMembershipRepository;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalRole;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineOrganisationMembershipRepository implements OrganisationMembershipRepository
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function save(OrganisationMembership $membership): void
    {
        $this->entityManager->persist($membership);
        $this->entityManager->flush();
    }

    public function findActiveByProfessional(Professional $professional): array
    {
        return $this->entityManager
            ->getRepository(OrganisationMembership::class)
            ->findBy([
                'professional' => $professional,
                'revokedAt' => null,
            ]);
    }

    public function findActiveByProfessionalAndOrganisation(
        Professional $professional,
        Organisation $organisation,
        ProfessionalRole $role,
    ): ?OrganisationMembership {
        return $this->entityManager
            ->getRepository(OrganisationMembership::class)
            ->findOneBy([
                'professional' => $professional,
                'organisation' => $organisation,
                'role' => $role,
                'revokedAt' => null,
            ]);
    }
}
