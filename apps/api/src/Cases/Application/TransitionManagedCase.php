<?php

declare(strict_types=1);

namespace App\Cases\Application;

use App\Cases\Domain\CaseAuditAction;
use App\Cases\Domain\CaseAuditEvent;
use App\Cases\Domain\CaseAuditEventRepository;
use App\Cases\Domain\CaseAuditTarget;
use App\Cases\Domain\CasePermission;
use App\Cases\Domain\CaseStatus;
use App\Cases\Domain\ManagedCase;
use App\Cases\Domain\CaseAssignmentRepository;
use App\Professionals\Domain\Professional;
use App\Professionals\Application\IssueProfessionalNotification;
use App\Professionals\Domain\ProfessionalNotificationType;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class TransitionManagedCase
{
    public function __construct(
        private AuthoriseCaseAccess $authorise,
        private CaseAuditEventRepository $auditEvents,
        private ?CaseAssignmentRepository $assignments = null,
        private ?IssueProfessionalNotification $notifications = null,
    ) {
    }

    public function transition(ManagedCase $managedCase, CaseStatus $status, string $reason, string $evidence, Professional $actor, DateTimeImmutable $now): void
    {
        $this->authorise->require($managedCase, $actor, CasePermission::ManageAssignments);
        $managedCase->transitionTo($status, $reason, $evidence, $now);
        $this->auditEvents->append(new CaseAuditEvent(Uuid::v7(), $managedCase, $actor, CaseAuditAction::StatusChanged, CaseAuditTarget::Case, $managedCase->id(), $now));
        $this->auditEvents->flush();
        foreach ($this->assignments?->findActiveByCase($managedCase) ?? [] as $assignment) {
            if (!$assignment->professional()->id()->equals($actor->id())) $this->notifications?->issue($assignment->professional(), $managedCase, ProfessionalNotificationType::CaseLifecycleChanged, $now);
        }
    }
}
