<?php

declare(strict_types=1);

namespace App\Cases\Application;

use App\Cases\Domain\CasePermission;
use App\Cases\Domain\CaseTask;
use App\Cases\Domain\CaseTaskRepository;
use App\Professionals\Domain\Professional;
use DateTimeImmutable;

final readonly class CompleteCaseTask
{
    public function __construct(
        private AuthoriseCaseAccess $authorise,
        private CaseTaskRepository $tasks,
    ) {
    }

    public function complete(CaseTask $task, Professional $actor, DateTimeImmutable $now): void
    {
        $this->authorise->require($task->managedCase(), $actor, CasePermission::Manage);
        $task->complete($actor, $now);
        $this->tasks->save($task);
    }
}
