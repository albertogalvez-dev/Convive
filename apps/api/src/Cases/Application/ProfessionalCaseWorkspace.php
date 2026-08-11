<?php

declare(strict_types=1);

namespace App\Cases\Application;

use App\Cases\Domain\CaseAccessDenied;
use App\Cases\Domain\CasePermission;
use App\Cases\Domain\CaseTaskStatus;
use App\Cases\Domain\CaseTask;
use App\Cases\Domain\CaseWorkspaceRepository;
use App\Professionals\Domain\Professional;
use App\Reporting\Domain\ReportAttachmentRepository;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class ProfessionalCaseWorkspace
{
    private const MAXIMUM_CASES = 50;

    public function __construct(
        private AuthoriseCaseAccess $authorise,
        private CaseWorkspaceRepository $cases,
        private ReportAttachmentRepository $attachments,
    ) {
    }

    /** @return list<CaseWorkspaceSummary> */
    public function list(Professional $professional, DateTimeImmutable $now): array
    {
        if (!$professional->isActive()) {
            return [];
        }

        $summaries = [];
        foreach ($this->cases->findActiveAssignmentsForProfessional($professional, self::MAXIMUM_CASES) as $assignment) {
            $managedCase = $assignment->managedCase();

            try {
                $this->authorise->require($managedCase, $professional, CasePermission::View);
            } catch (CaseAccessDenied) {
                continue;
            }

            $tasks = $this->cases->findTasks($managedCase);
            $pending = array_values(array_filter(
                $tasks,
                static fn (CaseTask $task): bool => $task->status() === CaseTaskStatus::Pending,
            ));
            $overdue = array_filter($pending, static fn (CaseTask $task): bool => $task->isOverdue($now));

            $summaries[] = new CaseWorkspaceSummary(
                $managedCase,
                $assignment,
                count($pending),
                count($overdue),
                $pending === [] ? null : $pending[0]->dueAt(),
            );
        }

        return $summaries;
    }

    public function detail(Uuid $id, Professional $professional): ?CaseWorkspaceDetail
    {
        $managedCase = $this->cases->findCase($id);
        if ($managedCase === null) {
            return null;
        }

        try {
            $assignment = $this->authorise->require($managedCase, $professional, CasePermission::View);
        } catch (CaseAccessDenied) {
            return null;
        }

        $sourceDecision = $this->cases->findSourceDecision($managedCase);
        $sourceReport = $sourceDecision?->report();
        $evidence = $sourceReport === null
            ? []
            : array_values(array_filter(
                $this->attachments->findByReport($sourceReport),
                static fn ($attachment): bool => $attachment->isAvailable(),
            ));

        return new CaseWorkspaceDetail(
            $managedCase,
            $assignment,
            $this->cases->findPeople($managedCase),
            $this->cases->findActiveAssignments($managedCase),
            $this->cases->findTasks($managedCase),
            $sourceReport,
            $sourceDecision,
            $evidence,
        );
    }
}
