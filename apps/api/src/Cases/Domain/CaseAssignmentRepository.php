<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use App\Professionals\Domain\Professional;

interface CaseAssignmentRepository
{
    public function findActive(ManagedCase $managedCase, Professional $professional): ?CaseAssignment;

    /** @return list<CaseAssignment> */
    public function findActiveByCase(ManagedCase $managedCase): array;

    public function save(CaseAssignment $assignment): void;

    public function replaceLead(CaseAssignment $formerLead, CaseAssignment $newLead): void;
}
