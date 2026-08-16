<?php

declare(strict_types=1);

namespace App\Cases\Application;

use App\Cases\Domain\CaseContinuityReason;
use App\Cases\Domain\CaseWorkspaceRepository;
use App\Organisations\Domain\Organisation;
use App\Professionals\Domain\OrganisationMembershipRepository;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalAbsenceRepository;
use App\Professionals\Domain\ProfessionalRole;
use DateTimeImmutable;

/**
 * The operational continuity list for an organisation's administrators.
 *
 * It answers one organisational question — which open cases currently have
 * nobody effectively looking after them — using references and status only.
 * Appearing on this list grants the reader no access to the case: restoring
 * continuity means performing an explicit reassignment, which is separately
 * authorised and audited. There is deliberately no pathway from here to case
 * content, matching ADR-0008: administration does not confer case access.
 */
final readonly class ListCaseContinuity
{
    public function __construct(
        private CaseWorkspaceRepository $workspace,
        private OrganisationMembershipRepository $memberships,
        private ProfessionalAbsenceRepository $absences,
    ) {
    }

    /** @return list<CaseContinuityEntry> */
    public function list(Organisation $organisation, Professional $administrator, DateTimeImmutable $now): array
    {
        $membership = $this->memberships->findActiveByProfessionalAndOrganisation(
            $administrator,
            $organisation,
            ProfessionalRole::Administrator,
        );
        if ($membership === null) {
            return [];
        }

        $responsibilities = $this->workspace->findOpenCaseResponsibilities($organisation, $now);
        $absent = $this->absences->findAbsentIdentifiers(
            array_map(static fn (array $row): Professional => $row['lead'], $responsibilities),
            $now,
        );

        $entries = [];
        foreach ($responsibilities as $row) {
            $reason = CaseContinuityReason::of(
                in_array($row['lead']->id()->toRfc4122(), $absent, true),
                $row['earliestOverdueAt'] !== null,
            );
            if ($reason === null) {
                continue;
            }

            $entries[] = new CaseContinuityEntry(
                $row['managedCase'],
                $row['lead'],
                $reason,
                $row['earliestOverdueAt'],
            );
        }

        // Oldest overdue work first; cases flagged only for absence follow,
        // ordered by identifier so the list is deterministic.
        usort($entries, static function (CaseContinuityEntry $left, CaseContinuityEntry $right): int {
            if ($left->earliestOverdueAt !== $right->earliestOverdueAt) {
                if ($left->earliestOverdueAt === null) {
                    return 1;
                }
                if ($right->earliestOverdueAt === null) {
                    return -1;
                }

                return $left->earliestOverdueAt <=> $right->earliestOverdueAt;
            }

            return strcmp($left->managedCase->id()->toRfc4122(), $right->managedCase->id()->toRfc4122());
        });

        return $entries;
    }
}
