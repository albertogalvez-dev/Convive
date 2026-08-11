<?php

declare(strict_types=1);

namespace App\Cases\Application;

use App\Cases\Domain\CaseAccessDenied;
use App\Cases\Domain\CaseAssignment;
use App\Cases\Domain\CaseAssignmentRepository;
use App\Cases\Domain\CasePermission;
use App\Cases\Domain\ManagedCase;
use App\Professionals\Domain\OrganisationMembershipRepository;
use App\Professionals\Domain\Professional;

final readonly class AuthoriseCaseAccess
{
    public function __construct(
        private OrganisationMembershipRepository $memberships,
        private CaseAssignmentRepository $assignments,
    ) {
    }

    public function require(
        ManagedCase $managedCase,
        Professional $professional,
        CasePermission $permission,
    ): CaseAssignment {
        if (!$professional->isActive() || !$this->hasActiveOrganisationMembership($managedCase, $professional)) {
            throw new CaseAccessDenied();
        }

        $assignment = $this->assignments->findActive($managedCase, $professional);

        if (!$assignment instanceof CaseAssignment || !$assignment->permits($permission)) {
            throw new CaseAccessDenied();
        }

        return $assignment;
    }

    private function hasActiveOrganisationMembership(
        ManagedCase $managedCase,
        Professional $professional,
    ): bool {
        foreach ($this->memberships->findActiveByProfessional($professional) as $membership) {
            if ($membership->organisation()->id()->equals($managedCase->organisation()->id())) {
                return true;
            }
        }

        return false;
    }
}
