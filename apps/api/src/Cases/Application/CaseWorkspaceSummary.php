<?php

declare(strict_types=1);

namespace App\Cases\Application;

use App\Cases\Domain\CaseAssignment;
use App\Cases\Domain\ManagedCase;
use DateTimeImmutable;

final readonly class CaseWorkspaceSummary
{
    public function __construct(
        public ManagedCase $managedCase,
        public CaseAssignment $assignment,
        public int $pendingTasks,
        public int $overdueTasks,
        public ?DateTimeImmutable $nextDueAt,
    ) {
    }
}
