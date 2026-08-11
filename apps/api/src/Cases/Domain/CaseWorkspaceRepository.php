<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use App\Professionals\Domain\Professional;
use App\Reporting\Domain\ReportTriageDecision;
use Symfony\Component\Uid\Uuid;

interface CaseWorkspaceRepository
{
    /** @return list<CaseAssignment> */
    public function findActiveAssignmentsForProfessional(Professional $professional, int $limit): array;

    public function findCase(Uuid $id): ?ManagedCase;

    /** @return list<CaseInvolvedPerson> */
    public function findPeople(ManagedCase $managedCase): array;

    /** @return list<CaseAssignment> */
    public function findActiveAssignments(ManagedCase $managedCase): array;

    /** @return list<CaseTask> */
    public function findTasks(ManagedCase $managedCase): array;

    public function findSourceDecision(ManagedCase $managedCase): ?ReportTriageDecision;
}
