<?php

declare(strict_types=1);

namespace App\Tests\Cases\Infrastructure;

use App\Cases\Application\PurgeExpiredFictionalCaseAuditEvents;
use App\Cases\Domain\CaseAuditAction;
use App\Cases\Domain\CaseAuditEvent;
use App\Cases\Domain\CaseAuditEventRepository;
use App\Cases\Domain\CaseAuditTarget;
use App\Cases\Domain\CaseModality;
use App\Cases\Domain\ProfessionalExportEventRepository;
use App\Cases\Domain\ManagedCase;
use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use App\Tests\Shared\Infrastructure\Persistence\PostgreSqlTestCase;
use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Symfony\Component\Uid\Uuid;

final class CaseAuditEventPersistenceTest extends PostgreSqlTestCase
{
    private CaseAuditEventRepository $events;
    private ProfessionalExportEventRepository $professionalExportEvents;

    protected function setUp(): void
    {
        parent::setUp();

        $events = self::getContainer()->get(CaseAuditEventRepository::class);
        $professionalExportEvents = self::getContainer()->get(ProfessionalExportEventRepository::class);
        self::assertInstanceOf(CaseAuditEventRepository::class, $events);
        self::assertInstanceOf(ProfessionalExportEventRepository::class, $professionalExportEvents);
        $this->events = $events;
        $this->professionalExportEvents = $professionalExportEvents;
    }

    public function testEventsRoundTripInOrderWithOnlyTheMinimisedPersistentShape(): void
    {
        [$managedCase] = $this->persistEvent(new DateTimeImmutable('2026-07-11T09:00:00+00:00'));
        [, $laterEvent] = $this->persistEvent(new DateTimeImmutable('2026-07-11T10:00:00+00:00'), $managedCase);

        $stored = $this->events->findByCase($managedCase);

        self::assertCount(2, $stored);
        self::assertSame('case_created', $stored[0]->action()->value);
        self::assertSame($laterEvent->id()->toRfc4122(), $stored[1]->id()->toRfc4122());
        self::assertSame([
            'id',
            'case_id',
            'organisation_id',
            'actor_professional_id',
            'action',
            'target',
            'target_id',
            'occurred_at',
        ], $this->entityManager->getConnection()->fetchFirstColumn(<<<'SQL'
SELECT column_name
FROM information_schema.columns
WHERE table_schema = 'public' AND table_name = 'case_audit_events'
ORDER BY ordinal_position
SQL));
    }

    public function testPostgreSqlRejectsDirectAuditUpdates(): void
    {
        [, $event] = $this->persistEvent(new DateTimeImmutable('2026-07-11T09:00:00+00:00'));

        $this->expectException(Exception::class);
        $this->entityManager->getConnection()->executeStatement(
            "UPDATE case_audit_events SET action = 'audit_exported' WHERE id = :id",
            ['id' => $event->id()->toRfc4122()],
        );
    }

    public function testPostgreSqlRejectsOrdinaryAuditDeletes(): void
    {
        [, $event] = $this->persistEvent(new DateTimeImmutable('2026-07-11T09:00:00+00:00'));

        $this->expectException(Exception::class);
        $this->entityManager->getConnection()->executeStatement(
            'DELETE FROM case_audit_events WHERE id = :id',
            ['id' => $event->id()->toRfc4122()],
        );
    }

    public function testExplicitFictionalRetentionPurgesOnlyExpiredEventsWithinItsBoundedBatch(): void
    {
        [, $expired] = $this->persistEvent(new DateTimeImmutable('2026-07-01T09:00:00+00:00'));
        [, $fresh] = $this->persistEvent(new DateTimeImmutable('2026-07-20T09:00:00+00:00'));

        $cleaned = (new PurgeExpiredFictionalCaseAuditEvents(
            $this->events,
            $this->professionalExportEvents,
            true,
        ))(
            1,
            new DateTimeImmutable('2026-08-01T09:00:00+00:00'),
        );

        self::assertSame(1, $cleaned);
        self::assertFalse($this->exists($expired));
        self::assertTrue($this->exists($fresh));
    }

    /** @return array{ManagedCase, CaseAuditEvent} */
    private function persistEvent(DateTimeImmutable $occurredAt, ?ManagedCase $existingCase = null): array
    {
        if ($existingCase !== null) {
            $professional = $existingCase->createdBy();
            $managedCase = $existingCase;
        } else {
            $professional = new Professional(
                Uuid::v7(),
                'Fictional Audit Professional',
                ProfessionalEmail::fromString(Uuid::v7()->toRfc4122().'@example.invalid'),
                $occurredAt,
            );
            $organisation = new Organisation(
                Uuid::v7(),
                'Fictional Audit School',
                PublicReportingIdentifier::generate(),
            );
            $managedCase = new ManagedCase(
                Uuid::v7(),
                $organisation,
                $professional,
                $occurredAt,
                CaseModality::Digital,
            );
            $this->entityManager->persist($organisation);
            $this->entityManager->persist($professional);
            $this->entityManager->persist($managedCase);
        }

        $event = new CaseAuditEvent(
            Uuid::v7(),
            $managedCase,
            $professional,
            CaseAuditAction::CaseCreated,
            CaseAuditTarget::Case,
            $managedCase->id(),
            $occurredAt,
        );
        $this->events->append($event);
        $this->events->flush();

        return [$managedCase, $event];
    }

    private function exists(CaseAuditEvent $event): bool
    {
        return (bool) $this->entityManager->getConnection()->fetchOne(
            'SELECT EXISTS(SELECT 1 FROM case_audit_events WHERE id = :id)',
            ['id' => $event->id()->toRfc4122()],
        );
    }
}
