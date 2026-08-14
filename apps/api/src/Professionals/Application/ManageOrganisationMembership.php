<?php

declare(strict_types=1);

namespace App\Professionals\Application;

use App\Organisations\Domain\Organisation;
use App\Professionals\Domain\OrganisationMembership;
use App\Professionals\Domain\OrganisationMembershipRepository;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalAccountAuditAction;
use App\Professionals\Domain\ProfessionalAccountAuditEvent;
use App\Professionals\Domain\ProfessionalAccountAuditEventRepository;
use App\Professionals\Domain\ProfessionalRole;
use App\Professionals\Domain\ProfessionalRepository;
use DateTimeImmutable;
use LogicException;
use Symfony\Component\Uid\Uuid;

final readonly class ManageOrganisationMembership
{
    public function __construct(
        private OrganisationMembershipRepository $memberships,
        private ProfessionalRepository $professionals,
        private ProfessionalAccountAuditEventRepository $auditEvents,
    ) {
    }

    public function grant(Organisation $organisation, Professional $target, ProfessionalRole $role, Professional $actor, DateTimeImmutable $now): OrganisationMembership
    {
        $this->requireAdministrator($organisation, $actor);
        if (!$target->isActive() || $this->memberships->findActiveByProfessionalAndOrganisation($target, $organisation, $role) !== null) {
            throw new LogicException('The professional is unavailable for this membership.');
        }
        $membership = new OrganisationMembership(Uuid::v7(), $target, $organisation, $role, $now);
        $this->memberships->save($membership);
        $this->changed($target, $actor, ProfessionalAccountAuditAction::MembershipGranted, $now);

        return $membership;
    }

    public function changeRole(OrganisationMembership $membership, ProfessionalRole $role, Professional $actor, DateTimeImmutable $now): void
    {
        $this->requireAdministrator($membership->organisation(), $actor);
        $this->requireManageable($membership, $actor);
        $this->requireAdministratorRemains($membership, $role);
        if ($this->memberships->findActiveByProfessionalAndOrganisation($membership->professional(), $membership->organisation(), $role) !== null) {
            throw new LogicException('The professional already has this active role in this organisation.');
        }
        $membership->changeRole($role);
        $this->memberships->save($membership);
        $this->changed($membership->professional(), $actor, ProfessionalAccountAuditAction::MembershipRoleChanged, $now);
    }

    public function suspend(OrganisationMembership $membership, Professional $actor, DateTimeImmutable $now): void
    {
        $this->requireAdministrator($membership->organisation(), $actor);
        $this->requireManageable($membership, $actor);
        $this->requireAdministratorRemains($membership);
        $membership->suspendAt($now);
        $this->memberships->save($membership);
        $this->changed($membership->professional(), $actor, ProfessionalAccountAuditAction::MembershipSuspended, $now);
    }

    public function resume(OrganisationMembership $membership, Professional $actor, DateTimeImmutable $now): void
    {
        $this->requireAdministrator($membership->organisation(), $actor);
        if ($membership->professional()->id()->equals($actor->id())) {
            throw new LogicException('An administrator cannot resume their own membership.');
        }
        $membership->resume();
        $this->memberships->save($membership);
        $this->changed($membership->professional(), $actor, ProfessionalAccountAuditAction::MembershipResumed, $now);
    }

    public function remove(OrganisationMembership $membership, Professional $actor, DateTimeImmutable $now): void
    {
        $this->requireAdministrator($membership->organisation(), $actor);
        $this->requireManageable($membership, $actor);
        $this->requireAdministratorRemains($membership);
        $membership->revokeAt($now);
        $this->memberships->save($membership);
        $this->changed($membership->professional(), $actor, ProfessionalAccountAuditAction::MembershipRemoved, $now);
    }

    private function requireAdministrator(Organisation $organisation, Professional $actor): void
    {
        if (!$actor->isActive() || $this->memberships->findActiveByProfessionalAndOrganisation($actor, $organisation, ProfessionalRole::Administrator) === null) {
            throw new LogicException('The professional cannot administer this organisation.');
        }
    }

    private function requireManageable(OrganisationMembership $membership, Professional $actor): void
    {
        if (!$membership->isActive() || $membership->professional()->id()->equals($actor->id())) {
            throw new LogicException('The organisation membership is unavailable.');
        }
    }

    private function requireAdministratorRemains(OrganisationMembership $membership, ?ProfessionalRole $nextRole = null): void
    {
        if ($membership->role() !== ProfessionalRole::Administrator || $nextRole === ProfessionalRole::Administrator) {
            return;
        }
        $administrators = array_filter(
            $this->memberships->findActiveByOrganisation($membership->organisation()),
            static fn (OrganisationMembership $candidate): bool => $candidate->role() === ProfessionalRole::Administrator,
        );
        if (count($administrators) <= 1) {
            throw new LogicException('An organisation must retain an active administrator.');
        }
    }

    private function changed(Professional $target, Professional $actor, ProfessionalAccountAuditAction $action, DateTimeImmutable $now): void
    {
        $target->invalidateSessions();
        $this->professionals->save($target);
        $this->auditEvents->append(new ProfessionalAccountAuditEvent(Uuid::v7(), $target, $actor, $action, $now));
    }
}
