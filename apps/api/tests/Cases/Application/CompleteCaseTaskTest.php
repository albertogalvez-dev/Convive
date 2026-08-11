<?php

declare(strict_types=1);

namespace App\Tests\Cases\Application;

use App\Cases\Application\AuthoriseCaseAccess;
use App\Cases\Application\CompleteCaseTask;
use App\Cases\Application\CreateCaseTask;
use App\Cases\Application\MarkCaseTaskNotApplicable;
use App\Cases\Domain\CaseAccessDenied;
use App\Cases\Domain\CaseAssignment;
use App\Cases\Domain\CaseAssignmentRepository;
use App\Cases\Domain\CaseAssignmentRole;
use App\Cases\Domain\CaseAuditEvent;
use App\Cases\Domain\CaseAuditEventRepository;
use App\Cases\Domain\CaseModality;
use App\Cases\Domain\CaseProtocolStage;
use App\Cases\Domain\CaseTask;
use App\Cases\Domain\CaseTaskKind;
use App\Cases\Domain\CaseTaskRepository;
use App\Cases\Domain\CaseTaskStatus;
use App\Cases\Domain\ManagedCase;
use App\Cases\Domain\WorkflowSourceAuthority;
use App\Cases\Domain\WorkflowSourceVersion;
use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Professionals\Domain\OrganisationMembership;
use App\Professionals\Domain\OrganisationMembershipRepository;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Domain\ProfessionalRole;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CompleteCaseTaskTest extends TestCase
{
    public function testCompletionRequiresAndRecordsAnExplicitAuthorisedActor(): void
    {
        [$task, $professional, $membership, $assignment] = $this->scope(CaseAssignmentRole::Contributor);
        $auditEvents = new InMemoryCaseAuditEventRepository();
        $service = new CompleteCaseTask(
            new AuthoriseCaseAccess($this->memberships([$membership]), $this->assignments([$assignment])),
            $this->tasks(),
            $auditEvents,
        );
        $completedAt = new DateTimeImmutable('2026-08-11T12:00:00+00:00');

        $service->complete($task, $professional, $completedAt);

        self::assertSame(CaseTaskStatus::Completed, $task->status());
        self::assertSame($professional, $task->resolvedBy());
        self::assertSame($completedAt, $task->resolvedAt());
        self::assertCount(1, $auditEvents->events);
        self::assertSame('task_completed', $auditEvents->events[0]->action()->value);
        self::assertTrue($auditEvents->events[0]->targetId()->equals($task->id()));
    }

    public function testAnObserverCannotCompleteACommunicationTask(): void
    {
        [$task, $professional, $membership, $assignment] = $this->scope(CaseAssignmentRole::Observer);
        $service = new CompleteCaseTask(
            new AuthoriseCaseAccess($this->memberships([$membership]), $this->assignments([$assignment])),
            $this->tasks(),
            $this->auditEvents(),
        );

        try {
            $service->complete($task, $professional, new DateTimeImmutable('2026-08-11T12:00:00+00:00'));
            self::fail('An observer must not complete a case task.');
        } catch (CaseAccessDenied) {
            self::assertSame(CaseTaskStatus::Pending, $task->status());
            self::assertNull($task->resolvedAt());
        }
    }

    public function testCreationAndNotApplicableResolutionRecordTheirMinimalTaskEvents(): void
    {
        [$existingTask, $professional, $membership, $assignment] = $this->scope(CaseAssignmentRole::Lead);
        $auditEvents = new InMemoryCaseAuditEventRepository();
        $authorise = new AuthoriseCaseAccess(
            $this->memberships([$membership]),
            $this->assignments([$assignment]),
        );
        $tasks = $this->tasks();
        $createdAt = new DateTimeImmutable('2026-08-11T12:00:00+00:00');
        $task = (new CreateCaseTask($authorise, $tasks, $auditEvents))->create(
            Uuid::v7(),
            $existingTask->managedCase(),
            $professional,
            $existingTask->source(),
            CaseProtocolStage::InformationCollection,
            CaseTaskKind::InternalAction,
            'Collect fictional follow-up information',
            $createdAt->modify('+1 day'),
            $professional,
            $createdAt,
        );

        (new MarkCaseTaskNotApplicable($authorise, $tasks, $auditEvents))->mark(
            $task,
            $professional,
            $createdAt->modify('+1 hour'),
            'The fictional follow-up is no longer required.',
        );

        self::assertSame(CaseTaskStatus::NotApplicable, $task->status());
        self::assertSame(['task_created', 'task_marked_not_applicable'], array_map(
            static fn (CaseAuditEvent $event): string => $event->action()->value,
            $auditEvents->events,
        ));
    }

    /** @return array{CaseTask, Professional, OrganisationMembership, CaseAssignment} */
    private function scope(CaseAssignmentRole $role): array
    {
        $professional = new Professional(
            Uuid::v7(),
            'Fictional Professional',
            ProfessionalEmail::fromString(Uuid::v7()->toRfc4122().'@example.invalid'),
            new DateTimeImmutable('2026-08-11T08:00:00+00:00'),
        );
        $organisation = new Organisation(Uuid::v7(), 'Fictional School', PublicReportingIdentifier::generate());
        $managedCase = new ManagedCase(
            Uuid::v7(),
            $organisation,
            $professional,
            new DateTimeImmutable('2026-08-11T09:00:00+00:00'),
            CaseModality::Digital,
        );
        $membership = new OrganisationMembership(
            Uuid::v7(),
            $professional,
            $organisation,
            ProfessionalRole::Triage,
            new DateTimeImmutable('2026-08-11T08:30:00+00:00'),
        );
        $assignment = new CaseAssignment(
            Uuid::v7(),
            $managedCase,
            $professional,
            $role,
            $professional,
            new DateTimeImmutable('2026-08-11T09:00:00+00:00'),
        );
        $source = new WorkflowSourceVersion(
            Uuid::v7(),
            'ES-AN-ORDER-2011-06-20-ANNEX-I',
            'BOJA-132-2011',
            'Andalusian school bullying protocol',
            'https://www.juntadeandalucia.es/boja/2011/132/1',
            'ES-AN',
            WorkflowSourceAuthority::Binding,
            new DateTimeImmutable('2011-07-07'),
            new DateTimeImmutable('2026-08-11'),
        );
        $task = new CaseTask(
            Uuid::v7(),
            $managedCase,
            $professional,
            $source,
            CaseProtocolStage::InspectionCommunication,
            CaseTaskKind::ExternalCommunication,
            'Confirm communication with the Education Inspectorate',
            new DateTimeImmutable('2026-08-11T11:00:00+00:00'),
            $professional,
            new DateTimeImmutable('2026-08-11T10:00:00+00:00'),
        );

        return [$task, $professional, $membership, $assignment];
    }

    /** @param list<OrganisationMembership> $memberships */
    private function memberships(array $memberships): OrganisationMembershipRepository
    {
        $repository = $this->createStub(OrganisationMembershipRepository::class);
        $repository->method('findActiveByProfessional')->willReturn($memberships);

        return $repository;
    }

    /** @param list<CaseAssignment> $assignments */
    private function assignments(array $assignments): CaseAssignmentRepository
    {
        return new class($assignments) implements CaseAssignmentRepository {
            /** @param list<CaseAssignment> $assignments */
            public function __construct(private array $assignments)
            {
            }

            public function findActive(ManagedCase $managedCase, Professional $professional): ?CaseAssignment
            {
                foreach ($this->assignments as $assignment) {
                    if ($assignment->managedCase()->id()->equals($managedCase->id())
                        && $assignment->professional()->id()->equals($professional->id())) {
                        return $assignment;
                    }
                }

                return null;
            }
        };
    }

    private function tasks(): CaseTaskRepository
    {
        return new class implements CaseTaskRepository {
            public function save(CaseTask $task): void
            {
            }
        };
    }

    private function auditEvents(): CaseAuditEventRepository
    {
        return new InMemoryCaseAuditEventRepository();
    }
}

final class InMemoryCaseAuditEventRepository implements CaseAuditEventRepository
{
    /** @var list<CaseAuditEvent> */
    public array $events = [];

    public function append(CaseAuditEvent $event): void
    {
        $this->events[] = $event;
    }

    public function flush(): void
    {
    }

    public function findByCase(ManagedCase $managedCase): array
    {
        return [];
    }

    public function purgeBefore(DateTimeImmutable $cutoff, int $limit): int
    {
        return 0;
    }
}
