<?php

declare(strict_types=1);

namespace App\Cases\Application;

use App\Cases\Domain\CasePermission;
use App\Cases\Domain\CaseAuditAction;
use App\Cases\Domain\CaseAuditEvent;
use App\Cases\Domain\CaseAuditEventRepository;
use App\Cases\Domain\CaseAuditTarget;
use App\Cases\Domain\CaseTask;
use App\Cases\Domain\CaseTaskRepository;
use App\Professionals\Domain\Professional;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class MarkCaseTaskNotApplicable
{
    public function __construct(
        private AuthoriseCaseAccess $authorise,
        private CaseTaskRepository $tasks,
        private CaseAuditEventRepository $auditEvents,
    ) {
    }

    public function mark(
        CaseTask $task,
        Professional $actor,
        DateTimeImmutable $now,
        string $reason,
    ): void {
        $this->authorise->require($task->managedCase(), $actor, CasePermission::Manage);
        $task->markNotApplicable($actor, $now, $reason);
        $this->auditEvents->append(new CaseAuditEvent(
            Uuid::v7(),
            $task->managedCase(),
            $actor,
            CaseAuditAction::TaskMarkedNotApplicable,
            CaseAuditTarget::Task,
            $task->id(),
            $now,
        ));
        $this->tasks->save($task);
    }
}
