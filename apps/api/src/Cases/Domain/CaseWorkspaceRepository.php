<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use App\Professionals\Domain\Professional;
use App\Organisations\Domain\Organisation;
use App\Reporting\Domain\ReportTriageDecision;
use Symfony\Component\Uid\Uuid;

interface CaseWorkspaceRepository
{
    /**
     * @param list<Organisation> $organisations
     * @return list<CaseAssignment>
     */
    public function findActiveAssignmentsForProfessional(
        Professional $professional,
        array $organisations,
        CaseWorkspaceQuery $query,
    ): array;

    /**
     * @param list<Organisation> $organisations
     * @return array{assigned: int, overdue: int, upcoming: int}
     */
    public function countOperationalCasesForProfessional(
        Professional $professional,
        array $organisations,
        \DateTimeImmutable $now,
    ): array;

    public function findCase(Uuid $id): ?ManagedCase;

    /** @return list<CaseInvolvedPerson> */
    public function findPeople(ManagedCase $managedCase): array;

    /** @return list<CaseAssignment> */
    public function findActiveAssignments(ManagedCase $managedCase): array;

    /** @return list<CaseTask> */
    public function findTasks(ManagedCase $managedCase): array;

    /** @return list<CaseCommunication> */
    public function findCommunications(ManagedCase $managedCase): array;

    public function findSourceDecision(ManagedCase $managedCase): ?ReportTriageDecision;
}
