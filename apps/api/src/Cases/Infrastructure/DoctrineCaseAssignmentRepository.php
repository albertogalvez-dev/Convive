<?php

declare(strict_types=1);

namespace App\Cases\Infrastructure;

use App\Cases\Domain\CaseAssignment;
use App\Cases\Domain\CaseAssignmentRepository;
use App\Cases\Domain\ManagedCase;
use App\Professionals\Domain\Professional;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineCaseAssignmentRepository implements CaseAssignmentRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findActive(ManagedCase $managedCase, Professional $professional): ?CaseAssignment
    {
        return $this->entityManager->getRepository(CaseAssignment::class)->findOneBy([
            'managedCase' => $managedCase,
            'professional' => $professional,
            'revokedAt' => null,
        ]);
    }
}
