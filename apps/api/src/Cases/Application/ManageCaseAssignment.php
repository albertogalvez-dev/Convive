<?php

declare(strict_types=1);

namespace App\Cases\Application;

use App\Cases\Domain\CaseAssignment;
use App\Cases\Domain\CaseAssignmentRepository;
use App\Cases\Domain\CaseAssignmentRole;
use App\Cases\Domain\CaseAuditAction;
use App\Cases\Domain\CaseAuditEvent;
use App\Cases\Domain\CaseAuditEventRepository;
use App\Cases\Domain\CaseAuditTarget;
use App\Cases\Domain\CasePermission;
use App\Cases\Domain\ManagedCase;
use App\Professionals\Application\IssueProfessionalNotification;
use App\Professionals\Domain\OrganisationMembershipRepository;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalNotificationType;
use DateTimeImmutable;
use LogicException;
use Symfony\Component\Uid\Uuid;

final readonly class ManageCaseAssignment
{
    public function __construct(
        private AuthoriseCaseAccess $authorise,
        private CaseAssignmentRepository $assignments,
        private OrganisationMembershipRepository $memberships,
        private CaseAuditEventRepository $auditEvents,
        private IssueProfessionalNotification $notifications,
    ) {
    }

    public function assign(ManagedCase $case, Professional $target, CaseAssignmentRole $role, string $reason, Professional $actor, DateTimeImmutable $now): CaseAssignment
    {
        $this->authorise->require($case, $actor, CasePermission::ManageAssignments);
        if ($role === CaseAssignmentRole::Lead) {
            throw new LogicException('Lead changes require an explicit handover.');
        }
        if (!$target->isActive() || !$this->memberships->hasActiveMembership($target, $case->organisation())) {
            throw new LogicException('The target professional is unavailable in this organisation.');
        }
        if ($this->assignments->findActive($case, $target) !== null) {
            throw new LogicException('The target professional already has an active assignment.');
        }
        $assignment = new CaseAssignment(Uuid::v7(), $case, $target, $role, $actor, $now, $reason);
        $this->assignments->save($assignment);
        $this->auditEvents->append(new CaseAuditEvent(Uuid::v7(), $case, $actor, CaseAuditAction::AssignmentCreated, CaseAuditTarget::Assignment, $assignment->id(), $now));
        $this->auditEvents->flush();
        $this->notifications->issue($target, $case, ProfessionalNotificationType::CaseAssigned, $now);

        return $assignment;
    }

    public function handover(CaseAssignment $formerLead, Professional $target, string $reason, Professional $actor, DateTimeImmutable $now): CaseAssignment
    {
        $case = $formerLead->managedCase();
        $this->authorise->require($case, $actor, CasePermission::ManageAssignments);
        if ($formerLead->role() !== CaseAssignmentRole::Lead || !$formerLead->isActive()) {
            throw new LogicException('Only an active lead can be handed over.');
        }
        if (!$target->isActive() || !$this->memberships->hasActiveMembership($target, $case->organisation())) {
            throw new LogicException('The target professional is unavailable in this organisation.');
        }
        if ($this->assignments->findActive($case, $target) !== null) {
            throw new LogicException('The new lead must not already hold an active assignment.');
        }
        $newLead = new CaseAssignment(Uuid::v7(), $case, $target, CaseAssignmentRole::Lead, $actor, $now, $reason);
        $formerLead->revokeAt($now, $reason);
        $this->assignments->replaceLead($formerLead, $newLead);
        $this->auditEvents->append(new CaseAuditEvent(Uuid::v7(), $case, $actor, CaseAuditAction::AssignmentCreated, CaseAuditTarget::Assignment, $newLead->id(), $now));
        $this->auditEvents->append(new CaseAuditEvent(Uuid::v7(), $case, $actor, CaseAuditAction::AssignmentRevoked, CaseAuditTarget::Assignment, $formerLead->id(), $now));
        $this->auditEvents->flush();
        $this->notifications->issue($target, $case, ProfessionalNotificationType::CaseAssigned, $now);

        return $newLead;
    }

    public function revoke(CaseAssignment $assignment, string $reason, Professional $actor, DateTimeImmutable $now): void
    {
        $this->authorise->require($assignment->managedCase(), $actor, CasePermission::ManageAssignments);
        if ($assignment->role() === CaseAssignmentRole::Lead) {
            throw new LogicException('Lead responsibility can only be removed through an explicit handover.');
        }
        $assignment->revokeAt($now, $reason);
        $this->assignments->save($assignment);
        $this->auditEvents->append(new CaseAuditEvent(Uuid::v7(), $assignment->managedCase(), $actor, CaseAuditAction::AssignmentRevoked, CaseAuditTarget::Assignment, $assignment->id(), $now));
        $this->auditEvents->flush();
    }

    public function changeRole(CaseAssignment $assignment, CaseAssignmentRole $role, string $reason, Professional $actor, DateTimeImmutable $now): void
    {
        $this->authorise->require($assignment->managedCase(), $actor, CasePermission::ManageAssignments);
        $assignment->changeRole($role, $now, $reason);
        $this->assignments->save($assignment);
        $this->auditEvents->append(new CaseAuditEvent(Uuid::v7(), $assignment->managedCase(), $actor, CaseAuditAction::AssignmentChanged, CaseAuditTarget::Assignment, $assignment->id(), $now));
        $this->auditEvents->flush();
    }
}
