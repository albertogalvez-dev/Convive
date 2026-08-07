<?php

declare(strict_types=1);

namespace App\Professionals\Infrastructure;

use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Domain\ProfessionalRepository;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineProfessionalRepository implements ProfessionalRepository
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function save(Professional $professional): void
    {
        $this->entityManager->persist($professional);
        $this->entityManager->flush();
    }

    public function findByEmail(ProfessionalEmail $email): ?Professional
    {
        return $this->entityManager
            ->getRepository(Professional::class)
            ->findOneBy(['email' => $email->toString()]);
    }
}
