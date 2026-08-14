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

    public function findActiveByCase(ManagedCase $managedCase): array
    {
        return $this->entityManager->getRepository(CaseAssignment::class)->findBy([
            'managedCase' => $managedCase,
            'revokedAt' => null,
        ], ['assignedAt' => 'ASC', 'id' => 'ASC']);
    }

    public function save(CaseAssignment $assignment): void
    {
        $this->entityManager->persist($assignment);
        $this->entityManager->flush();
    }

    public function replaceLead(CaseAssignment $formerLead, CaseAssignment $newLead): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $connection->executeQuery(
                'SELECT id FROM managed_cases WHERE id = :caseId FOR UPDATE',
                ['caseId' => $formerLead->managedCase()->id()->toRfc4122()],
            );
            $formerLeadIsAlreadyRevoked = $connection->fetchOne(
                'SELECT revoked_at FROM case_assignments WHERE id = :assignmentId',
                ['assignmentId' => $formerLead->id()->toRfc4122()],
            );
            if ($formerLeadIsAlreadyRevoked !== false && $formerLeadIsAlreadyRevoked !== null) {
                throw new \LogicException('The former lead has already been handed over or revoked.');
            }
            $this->entityManager->persist($formerLead);
            $this->entityManager->flush();
            $this->entityManager->persist($newLead);
            $this->entityManager->flush();
            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }
    }
}
