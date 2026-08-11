<?php

declare(strict_types=1);

namespace App\Cases\Infrastructure;

use App\Cases\Domain\CaseAssignment;
use App\Cases\Domain\CaseInvolvedPerson;
use App\Cases\Domain\CaseTask;
use App\Cases\Domain\CaseWorkspaceRepository;
use App\Cases\Domain\ManagedCase;
use App\Professionals\Domain\Professional;
use App\Reporting\Domain\ReportTriageDecision;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineCaseWorkspaceRepository implements CaseWorkspaceRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findActiveAssignmentsForProfessional(Professional $professional, int $limit): array
    {
        /** @var list<CaseAssignment> */
        return $this->entityManager->createQueryBuilder()
            ->select('assignment', 'managedCase')
            ->from(CaseAssignment::class, 'assignment')
            ->join('assignment.managedCase', 'managedCase')
            ->where('assignment.professional = :professional')
            ->andWhere('assignment.revokedAt IS NULL')
            ->setParameter('professional', $professional)
            ->orderBy('managedCase.createdAt', 'DESC')
            ->addOrderBy('managedCase.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findCase(Uuid $id): ?ManagedCase
    {
        return $this->entityManager->find(ManagedCase::class, $id);
    }

    public function findPeople(ManagedCase $managedCase): array
    {
        /** @var list<CaseInvolvedPerson> */
        return $this->entityManager->createQueryBuilder()
            ->select('person')
            ->from(CaseInvolvedPerson::class, 'person')
            ->where('person.managedCase = :managedCase')
            ->setParameter('managedCase', $managedCase)
            ->orderBy('person.addedAt', 'ASC')
            ->addOrderBy('person.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveAssignments(ManagedCase $managedCase): array
    {
        /** @var list<CaseAssignment> */
        return $this->entityManager->createQueryBuilder()
            ->select('assignment', 'professional')
            ->from(CaseAssignment::class, 'assignment')
            ->join('assignment.professional', 'professional')
            ->where('assignment.managedCase = :managedCase')
            ->andWhere('assignment.revokedAt IS NULL')
            ->setParameter('managedCase', $managedCase)
            ->orderBy('assignment.assignedAt', 'ASC')
            ->addOrderBy('assignment.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findTasks(ManagedCase $managedCase): array
    {
        /** @var list<CaseTask> */
        return $this->entityManager->createQueryBuilder()
            ->select('task', 'owner', 'source', 'resolver')
            ->from(CaseTask::class, 'task')
            ->join('task.owner', 'owner')
            ->join('task.source', 'source')
            ->leftJoin('task.resolvedBy', 'resolver')
            ->where('task.managedCase = :managedCase')
            ->setParameter('managedCase', $managedCase)
            ->orderBy('task.dueAt', 'ASC')
            ->addOrderBy('task.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findSourceDecision(ManagedCase $managedCase): ?ReportTriageDecision
    {
        /** @var ReportTriageDecision|null $decision */
        $decision = $this->entityManager->createQueryBuilder()
            ->select('decision', 'report')
            ->from(ReportTriageDecision::class, 'decision')
            ->join('decision.report', 'report')
            ->where('decision.managedCase = :managedCase')
            ->setParameter('managedCase', $managedCase)
            ->getQuery()
            ->getOneOrNullResult();

        return $decision;
    }
}
