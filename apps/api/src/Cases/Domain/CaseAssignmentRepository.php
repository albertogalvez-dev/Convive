<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use App\Professionals\Domain\Professional;

interface CaseAssignmentRepository
{
    public function findActive(ManagedCase $managedCase, Professional $professional): ?CaseAssignment;
}
