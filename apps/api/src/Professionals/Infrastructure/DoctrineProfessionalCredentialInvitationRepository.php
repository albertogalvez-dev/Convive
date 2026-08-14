<?php

declare(strict_types=1);

namespace App\Professionals\Infrastructure;

use App\Professionals\Domain\ProfessionalCredentialInvitation;
use App\Professionals\Domain\ProfessionalCredentialInvitationRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineProfessionalCredentialInvitationRepository implements ProfessionalCredentialInvitationRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(ProfessionalCredentialInvitation $invitation): void
    {
        $this->entityManager->persist($invitation);
        $this->entityManager->flush();
    }

    public function findBySecret(string $secret): ?ProfessionalCredentialInvitation
    {
        $invitation = $this->entityManager->getRepository(ProfessionalCredentialInvitation::class)->findOneBy([
            'secretHash' => hash('sha256', $secret),
        ]);

        return $invitation instanceof ProfessionalCredentialInvitation ? $invitation : null;
    }
}
