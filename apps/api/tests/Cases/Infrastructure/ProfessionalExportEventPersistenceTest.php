<?php

declare(strict_types=1);

namespace App\Tests\Cases\Infrastructure;

use App\Cases\Domain\ProfessionalExportEvent;
use App\Cases\Domain\ProfessionalExportEventRepository;
use App\Cases\Domain\ProfessionalExportKind;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use App\Tests\Shared\Infrastructure\Persistence\PostgreSqlTestCase;
use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Symfony\Component\Uid\Uuid;

final class ProfessionalExportEventPersistenceTest extends PostgreSqlTestCase
{
    private ProfessionalExportEventRepository $events;

    protected function setUp(): void
    {
        parent::setUp();

        $events = self::getContainer()->get(ProfessionalExportEventRepository::class);
        self::assertInstanceOf(ProfessionalExportEventRepository::class, $events);
        $this->events = $events;
    }

    public function testEventHasOnlyTheMinimisedPersistentShape(): void
    {
        $event = $this->persistEvent(new DateTimeImmutable('2026-07-11T09:00:00+00:00'));

        self::assertSame([
            'id',
            'professional_id',
            'kind',
            'occurred_at',
        ], $this->entityManager->getConnection()->fetchFirstColumn(<<<'SQL'
SELECT column_name
FROM information_schema.columns
WHERE table_schema = 'public' AND table_name = 'professional_export_events'
ORDER BY ordinal_position
SQL));
    }

    public function testPostgreSqlRejectsDirectEventUpdatesAndDeletes(): void
    {
        $event = $this->persistEvent(new DateTimeImmutable('2026-07-11T09:00:00+00:00'));

        try {
            $this->entityManager->getConnection()->executeStatement(
                "UPDATE professional_export_events SET kind = 'operational_overview' WHERE id = :id",
                ['id' => $event->id()->toRfc4122()],
            );
            self::fail('A direct export event update should be rejected.');
        } catch (Exception) {
        }

        $this->expectException(Exception::class);
        $this->entityManager->getConnection()->executeStatement(
            'DELETE FROM professional_export_events WHERE id = :id',
            ['id' => $event->id()->toRfc4122()],
        );
    }

    public function testControlledRetentionPurgesOnlyExpiredEvents(): void
    {
        $expired = $this->persistEvent(new DateTimeImmutable('2026-07-01T09:00:00+00:00'));
        $fresh = $this->persistEvent(new DateTimeImmutable('2026-07-20T09:00:00+00:00'));

        self::assertSame(1, $this->events->purgeBefore(new DateTimeImmutable('2026-07-15T00:00:00+00:00'), 1));
        self::assertFalse($this->exists($expired));
        self::assertTrue($this->exists($fresh));
    }

    private function persistEvent(DateTimeImmutable $occurredAt): ProfessionalExportEvent
    {
        $professional = new Professional(
            Uuid::v7(),
            'Fictional Export Professional',
            ProfessionalEmail::fromString(Uuid::v7()->toRfc4122().'@example.invalid'),
            $occurredAt,
        );
        $event = new ProfessionalExportEvent(
            Uuid::v7(),
            $professional,
            ProfessionalExportKind::OperationalOverview,
            $occurredAt,
        );
        $this->entityManager->persist($professional);
        $this->events->append($event);
        $this->events->flush();

        return $event;
    }

    private function exists(ProfessionalExportEvent $event): bool
    {
        return (bool) $this->entityManager->getConnection()->fetchOne(
            'SELECT EXISTS(SELECT 1 FROM professional_export_events WHERE id = :id)',
            ['id' => $event->id()->toRfc4122()],
        );
    }
}
