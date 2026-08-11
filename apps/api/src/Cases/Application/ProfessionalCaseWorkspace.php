<?php

declare(strict_types=1);

namespace App\Cases\Application;

use App\Cases\Domain\CaseAccessDenied;
use App\Cases\Domain\CaseOperationalView;
use App\Cases\Domain\CasePermission;
use App\Cases\Domain\CaseTaskStatus;
use App\Cases\Domain\CaseTask;
use App\Cases\Domain\CaseWorkspaceCursor;
use App\Cases\Domain\CaseWorkspaceQuery;
use App\Cases\Domain\CaseWorkspaceRepository;
use App\Professionals\Domain\OrganisationMembershipRepository;
use App\Professionals\Domain\Professional;
use App\Reporting\Domain\ReportAttachmentRepository;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class ProfessionalCaseWorkspace
{
    public function __construct(
        private AuthoriseCaseAccess $authorise,
        private CaseWorkspaceRepository $cases,
        private ReportAttachmentRepository $attachments,
        private OrganisationMembershipRepository $memberships,
    ) {
    }

    public function page(Professional $professional, CaseWorkspaceQuery $query): CaseWorkspacePage
    {
        if (!$professional->isActive()) {
            return new CaseWorkspacePage([], null);
        }

        $organisations = array_map(
            static fn ($membership) => $membership->organisation(),
            $this->memberships->findActiveByProfessional($professional),
        );
        if ($organisations === []) {
            return new CaseWorkspacePage([], null);
        }

        $summaries = [];
        foreach ($this->cases->findActiveAssignmentsForProfessional($professional, $organisations, $query) as $assignment) {
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
            $overdue = array_filter($pending, static fn (CaseTask $task): bool => $task->isOverdue($query->now));

            $summaries[] = new CaseWorkspaceSummary(
                $managedCase,
                $assignment,
                count($pending),
                count($overdue),
                $pending === [] ? null : $pending[0]->dueAt(),
            );
        }

        $hasNextPage = count($summaries) > $query->limit;
        if ($hasNextPage) {
            array_pop($summaries);
        }

        $last = $summaries === [] ? null : $summaries[array_key_last($summaries)];

        return new CaseWorkspacePage(
            $summaries,
            $hasNextPage && $last !== null
                ? new CaseWorkspaceCursor(
                    $query->view,
                    $this->cursorSortAt($last, $query->view),
                    $last->managedCase->id(),
                )
                : null,
        );
    }

    /** @return array{assigned: int, overdue: int, upcoming: int} */
    public function operationalCounts(Professional $professional, DateTimeImmutable $now): array
    {
        if (!$professional->isActive()) {
            return ['assigned' => 0, 'overdue' => 0, 'upcoming' => 0];
        }

        $organisations = array_map(
            static fn ($membership) => $membership->organisation(),
            $this->memberships->findActiveByProfessional($professional),
        );

        return $this->cases->countOperationalCasesForProfessional($professional, $organisations, $now);
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

    private function cursorSortAt(CaseWorkspaceSummary $summary, CaseOperationalView $view): DateTimeImmutable
    {
        return match ($view) {
            CaseOperationalView::Assigned => $summary->managedCase->createdAt(),
            CaseOperationalView::Recent => $summary->managedCase->operationalUpdatedAt(),
            CaseOperationalView::Overdue, CaseOperationalView::Upcoming => $summary->nextDueAt
                ?? throw new \LogicException('Task views require a pending task due time.'),
        };
    }
}
