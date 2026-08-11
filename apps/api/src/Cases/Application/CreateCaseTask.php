<?php

declare(strict_types=1);

namespace App\Cases\Application;

use App\Cases\Domain\CasePermission;
use App\Cases\Domain\CaseAuditAction;
use App\Cases\Domain\CaseAuditEvent;
use App\Cases\Domain\CaseAuditEventRepository;
use App\Cases\Domain\CaseAuditTarget;
use App\Cases\Domain\CaseProtocolStage;
use App\Cases\Domain\CaseTask;
use App\Cases\Domain\CaseTaskKind;
use App\Cases\Domain\CaseTaskRepository;
use App\Cases\Domain\ManagedCase;
use App\Cases\Domain\WorkflowSourceVersion;
use App\Professionals\Domain\Professional;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class CreateCaseTask
{
    public function __construct(
        private AuthoriseCaseAccess $authorise,
        private CaseTaskRepository $tasks,
        private CaseAuditEventRepository $auditEvents,
    ) {
    }

    public function create(
        Uuid $id,
        ManagedCase $managedCase,
        Professional $owner,
        WorkflowSourceVersion $source,
        CaseProtocolStage $stage,
        CaseTaskKind $kind,
        string $title,
        DateTimeImmutable $dueAt,
        Professional $actor,
        DateTimeImmutable $now,
    ): CaseTask {
        $this->authorise->require($managedCase, $actor, CasePermission::Manage);
        $this->authorise->require($managedCase, $owner, CasePermission::View);

        $task = new CaseTask(
            $id,
            $managedCase,
            $owner,
            $source,
            $stage,
            $kind,
            $title,
            $dueAt,
            $actor,
            $now,
        );
        $this->auditEvents->append(new CaseAuditEvent(
            Uuid::v7(),
            $managedCase,
            $actor,
            CaseAuditAction::TaskCreated,
            CaseAuditTarget::Task,
            $task->id(),
            $now,
        ));
        $this->tasks->save($task);

        return $task;
    }
}
