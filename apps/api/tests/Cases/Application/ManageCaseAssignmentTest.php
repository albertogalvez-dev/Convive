<?php

declare(strict_types=1);

namespace App\Tests\Cases\Application;

use App\Cases\Application\AuthoriseCaseAccess;
use App\Cases\Application\ManageCaseAssignment;
use App\Cases\Domain\CaseAssignment;
use App\Cases\Domain\CaseAssignmentRepository;
use App\Cases\Domain\CaseAssignmentRole;
use App\Cases\Domain\CaseAuditEvent;
use App\Cases\Domain\CaseAuditEventRepository;
use App\Cases\Domain\CaseModality;
use App\Cases\Domain\ManagedCase;
use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Professionals\Domain\OrganisationMembership;
use App\Professionals\Domain\OrganisationMembershipRepository;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Domain\ProfessionalRole;
use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ManageCaseAssignmentTest extends TestCase
{
    public function testHandoverReplacesTheOnlyLeadAndAuditsBothSides(): void
    {
        [$case, $formerLead, $membership, $formerAssignment] = $this->scope();
        $nextLead = $this->professional('next-lead');
        $nextMembership = new OrganisationMembership(Uuid::v7(), $nextLead, $case->organisation(), ProfessionalRole::Triage, new DateTimeImmutable('2026-08-11T08:00:00+00:00'));
        $assignments = new InMemoryAssignments([$formerAssignment]);
        $audit = new InMemoryAssignmentAuditEvents();
        $service = new ManageCaseAssignment(
            new AuthoriseCaseAccess($this->memberships([$membership, $nextMembership]), $assignments),
            $assignments,
            $this->memberships([$membership, $nextMembership]),
            $audit,
        );

        $newLead = $service->handover($formerAssignment, $nextLead, 'Fictional planned absence handover.', $formerLead, new DateTimeImmutable('2026-08-11T12:00:00+00:00'));

        self::assertSame(CaseAssignmentRole::Lead, $newLead->role());
        self::assertFalse($formerAssignment->isActive());
        self::assertCount(1, $assignments->findActiveByCase($case));
        self::assertSame($nextLead, $assignments->findActiveByCase($case)[0]->professional());
        self::assertSame(['assignment_created', 'assignment_revoked'], array_map(static fn (CaseAuditEvent $event): string => $event->action()->value, $audit->events));
    }

    public function testOrdinaryAssignmentCannotCreateAnotherLead(): void
    {
        [$case, $lead, $membership, $assignment] = $this->scope();
        $candidate = $this->professional('candidate');
        $candidateMembership = new OrganisationMembership(Uuid::v7(), $candidate, $case->organisation(), ProfessionalRole::Triage, new DateTimeImmutable());
        $assignments = new InMemoryAssignments([$assignment]);
        $service = new ManageCaseAssignment(new AuthoriseCaseAccess($this->memberships([$membership, $candidateMembership]), $assignments), $assignments, $this->memberships([$membership, $candidateMembership]), new InMemoryAssignmentAuditEvents());

        $this->expectException(LogicException::class);
        $service->assign($case, $candidate, CaseAssignmentRole::Lead, 'Fictional duplicate lead.', $lead, new DateTimeImmutable());
    }

    public function testLeadCanChangeAContributorToObserverWithAnAuditEvent(): void
    {
        [$case, $lead, $membership, $leadAssignment] = $this->scope();
        $contributor = $this->professional('contributor');
        $contributorMembership = new OrganisationMembership(Uuid::v7(), $contributor, $case->organisation(), ProfessionalRole::Triage, new DateTimeImmutable());
        $assignment = new CaseAssignment(Uuid::v7(), $case, $contributor, CaseAssignmentRole::Contributor, $lead, new DateTimeImmutable());
        $assignments = new InMemoryAssignments([$leadAssignment, $assignment]);
        $audit = new InMemoryAssignmentAuditEvents();
        $service = new ManageCaseAssignment(new AuthoriseCaseAccess($this->memberships([$membership, $contributorMembership]), $assignments), $assignments, $this->memberships([$membership, $contributorMembership]), $audit);

        $service->changeRole($assignment, CaseAssignmentRole::Observer, 'Fictional consultation-only access.', $lead, new DateTimeImmutable());

        self::assertSame(CaseAssignmentRole::Observer, $assignment->role());
        self::assertSame('Fictional consultation-only access.', $assignment->roleChangeReason());
        self::assertSame(['assignment_changed'], array_map(static fn (CaseAuditEvent $event): string => $event->action()->value, $audit->events));
    }

    /** @return array{ManagedCase, Professional, OrganisationMembership, CaseAssignment} */
    private function scope(): array
    {
        $lead = $this->professional('lead');
        $organisation = new Organisation(Uuid::v7(), 'Fictional School', PublicReportingIdentifier::generate());
        $case = new ManagedCase(Uuid::v7(), $organisation, $lead, new DateTimeImmutable('2026-08-11T09:00:00+00:00'), CaseModality::Mixed);
        $membership = new OrganisationMembership(Uuid::v7(), $lead, $organisation, ProfessionalRole::Triage, new DateTimeImmutable('2026-08-11T08:00:00+00:00'));
        $assignment = new CaseAssignment(Uuid::v7(), $case, $lead, CaseAssignmentRole::Lead, $lead, new DateTimeImmutable('2026-08-11T09:00:00+00:00'));

        return [$case, $lead, $membership, $assignment];
    }

    private function professional(string $name): Professional
    {
        return new Professional(Uuid::v7(), 'Fictional '.$name, ProfessionalEmail::fromString(Uuid::v7()->toRfc4122().'@example.invalid'), new DateTimeImmutable('2026-08-11T08:00:00+00:00'));
    }

    /** @param list<OrganisationMembership> $memberships */
    private function memberships(array $memberships): OrganisationMembershipRepository
    {
        return new class($memberships) implements OrganisationMembershipRepository {
            /** @param list<OrganisationMembership> $memberships */
            public function __construct(private array $memberships) {}
            public function save(OrganisationMembership $membership): void {}
            public function findActiveByProfessional(Professional $professional): array { return array_values(array_filter($this->memberships, static fn (OrganisationMembership $membership): bool => $membership->professional()->id()->equals($professional->id()) && $membership->isActive())); }
            public function hasActiveMembership(Professional $professional, Organisation $organisation): bool { return $this->findActiveByProfessionalAndOrganisation($professional, $organisation, ProfessionalRole::Triage) !== null; }
            public function findActiveByOrganisation(Organisation $organisation): array { return array_values(array_filter($this->memberships, static fn (OrganisationMembership $membership): bool => $membership->organisation()->id()->equals($organisation->id()) && $membership->isActive())); }
            public function findActiveByProfessionalAndOrganisation(Professional $professional, Organisation $organisation, ProfessionalRole $role): ?OrganisationMembership { foreach ($this->memberships as $membership) { if ($membership->professional()->id()->equals($professional->id()) && $membership->organisation()->id()->equals($organisation->id()) && $membership->role() === $role && $membership->isActive()) return $membership; } return null; }
        };
    }
}

final class InMemoryAssignments implements CaseAssignmentRepository
{
    /** @param list<CaseAssignment> $assignments */
    public function __construct(private array $assignments) {}
    public function findActive(ManagedCase $case, Professional $professional): ?CaseAssignment { foreach ($this->assignments as $assignment) if ($assignment->managedCase()->id()->equals($case->id()) && $assignment->professional()->id()->equals($professional->id()) && $assignment->isActive()) return $assignment; return null; }
    public function findActiveByCase(ManagedCase $case): array { return array_values(array_filter($this->assignments, static fn (CaseAssignment $assignment): bool => $assignment->managedCase()->id()->equals($case->id()) && $assignment->isActive())); }
    public function save(CaseAssignment $assignment): void { $this->assignments[] = $assignment; }
    public function replaceLead(CaseAssignment $formerLead, CaseAssignment $newLead): void { $this->assignments[] = $newLead; }
}

final class InMemoryAssignmentAuditEvents implements CaseAuditEventRepository
{
    /** @var list<CaseAuditEvent> */ public array $events = [];
    public function append(CaseAuditEvent $event): void { $this->events[] = $event; }
    public function flush(): void {}
    public function findByCase(ManagedCase $managedCase): array { return []; }
    public function purgeBefore(DateTimeImmutable $cutoff, int $limit): int { return 0; }
}
