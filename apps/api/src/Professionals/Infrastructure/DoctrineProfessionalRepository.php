<?php

declare(strict_types=1);

namespace App\Professionals\Infrastructure;

use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Domain\ProfessionalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

final class DoctrineProfessionalRepository implements ProfessionalRepository, PasswordUpgraderInterface
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

    public function upgradePassword(
        PasswordAuthenticatedUserInterface $user,
        string $newHashedPassword,
    ): void {
        if (!$user instanceof Professional) {
            throw new UnsupportedUserException(sprintf(
                'Instances of "%s" are not supported.',
                $user::class,
            ));
        }

        $user->replacePasswordHash($newHashedPassword);
        $this->entityManager->flush();
    }
}
