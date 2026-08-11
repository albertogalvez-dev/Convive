<?php

declare(strict_types=1);

namespace App\Cases\Infrastructure;

use App\Cases\Domain\CaseAssignment;
use App\Cases\Domain\CaseInvolvedPerson;
use App\Cases\Domain\CaseTask;
use App\Cases\Domain\CaseTaskStatus;
use App\Cases\Domain\CaseOperationalView;
use App\Cases\Domain\CaseWorkspaceQuery;
use App\Cases\Domain\CaseWorkspaceRepository;
use App\Cases\Domain\ManagedCase;
use App\Organisations\Domain\Organisation;
use App\Professionals\Domain\Professional;
use App\Reporting\Domain\ReportTriageDecision;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineCaseWorkspaceRepository implements CaseWorkspaceRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findActiveAssignmentsForProfessional(
        Professional $professional,
        array $organisations,
        CaseWorkspaceQuery $query,
    ): array
    {
        if ($organisations === []) {
            return [];
        }

        $builder = $this->entityManager->createQueryBuilder()
            ->select('assignment', 'managedCase')
            ->from(CaseAssignment::class, 'assignment')
            ->join('assignment.managedCase', 'managedCase')
            ->where('assignment.professional = :professional')
            ->andWhere('assignment.revokedAt IS NULL')
            ->andWhere('managedCase.organisation IN (:organisations)')
            ->setParameter('professional', $professional)
            ->setParameter('organisations', $organisations)
            ->setMaxResults($query->limit + 1);

        if ($query->status !== null) {
            $builder->andWhere('managedCase.status = :status')->setParameter('status', $query->status->value);
        }

        if ($query->modality !== null) {
            $builder->andWhere('managedCase.modality = :modality')->setParameter('modality', $query->modality->value);
        }

        if ($query->publicReference !== null) {
            if (Uuid::isValid($query->publicReference)) {
                $builder->andWhere('managedCase.id = :caseId')->setParameter('caseId', Uuid::fromString($query->publicReference));
            } else {
                $builder
                    ->leftJoin(ReportTriageDecision::class, 'sourceDecision', Join::WITH, 'sourceDecision.managedCase = managedCase')
                    ->leftJoin('sourceDecision.report', 'sourceReport')
                    ->andWhere('sourceReport.publicReference = :publicReference')
                    ->setParameter('publicReference', $query->publicReference);
            }
        }

        if ($query->view === CaseOperationalView::Overdue || $query->view === CaseOperationalView::Upcoming) {
            $this->applyTaskView($builder, $query);
        } else {
            $this->applyCaseView($builder, $query);
        }

        /** @var list<CaseAssignment> $assignments */
        $assignments = $builder->getQuery()->getResult();

        return $assignments;
    }

    public function countOperationalCasesForProfessional(
        Professional $professional,
        array $organisations,
        DateTimeImmutable $now,
    ): array {
        if ($organisations === []) {
            return ['assigned' => 0, 'overdue' => 0, 'upcoming' => 0];
        }

        $base = $this->entityManager->createQueryBuilder()
            ->from(CaseAssignment::class, 'assignment')
            ->join('assignment.managedCase', 'managedCase')
            ->where('assignment.professional = :professional')
            ->andWhere('assignment.revokedAt IS NULL')
            ->andWhere('managedCase.organisation IN (:organisations)')
            ->setParameter('professional', $professional)
            ->setParameter('organisations', $organisations);

        $assigned = (clone $base)
            ->select('COUNT(DISTINCT managedCase.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $taskBase = (clone $base)
            ->join(CaseTask::class, 'task', Join::WITH, 'task.managedCase = managedCase AND task.status = :pending')
            ->setParameter('pending', CaseTaskStatus::Pending->value);

        $overdue = (clone $taskBase)
            ->select('managedCase.id')
            ->groupBy('managedCase.id')
            ->having('MIN(task.dueAt) < :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getScalarResult();
        $upcoming = (clone $taskBase)
            ->select('managedCase.id')
            ->groupBy('managedCase.id')
            ->having('MIN(task.dueAt) >= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getScalarResult();

        return ['assigned' => (int) $assigned, 'overdue' => count($overdue), 'upcoming' => count($upcoming)];
    }

    private function applyCaseView(\Doctrine\ORM\QueryBuilder $builder, CaseWorkspaceQuery $query): void
    {
        $field = $query->view === CaseOperationalView::Recent
            ? 'managedCase.operationalUpdatedAt'
            : 'managedCase.createdAt';

        if ($query->cursor !== null) {
            $builder
                ->andWhere(sprintf('%s < :cursorSortAt OR (%s = :cursorSortAt AND managedCase.id < :cursorCaseId)', $field, $field))
                ->setParameter('cursorSortAt', $query->cursor->sortAt)
                ->setParameter('cursorCaseId', $query->cursor->caseId);
        }

        $builder->orderBy($field, 'DESC')->addOrderBy('managedCase.id', 'DESC');
    }

    private function applyTaskView(\Doctrine\ORM\QueryBuilder $builder, CaseWorkspaceQuery $query): void
    {
        $builder
            ->join(CaseTask::class, 'task', Join::WITH, 'task.managedCase = managedCase AND task.status = :pending')
            ->setParameter('pending', CaseTaskStatus::Pending->value)
            ->groupBy('assignment.id')
            ->addGroupBy('managedCase.id');

        $conditions = $query->view === CaseOperationalView::Overdue
            ? ['MIN(task.dueAt) < :now']
            : ['MIN(task.dueAt) >= :now'];
        $builder->setParameter('now', $query->now);

        if ($query->cursor !== null) {
            $conditions[] = '(MIN(task.dueAt) > :cursorSortAt OR (MIN(task.dueAt) = :cursorSortAt AND managedCase.id > :cursorCaseId))';
            $builder
                ->setParameter('cursorSortAt', $query->cursor->sortAt)
                ->setParameter('cursorCaseId', $query->cursor->caseId);
        }

        $builder
            ->having(implode(' AND ', $conditions))
            ->orderBy('MIN(task.dueAt)', 'ASC')
            ->addOrderBy('managedCase.id', 'ASC');
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
