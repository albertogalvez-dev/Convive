<?php

declare(strict_types=1);

namespace App\Cases\Infrastructure;

use App\Cases\Domain\CaseAuditEvent;
use App\Cases\Domain\CaseAuditEventRepository;
use App\Cases\Domain\ManagedCase;
use DateTimeImmutable;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineCaseAuditEventRepository implements CaseAuditEventRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function append(CaseAuditEvent $event): void
    {
        $this->entityManager->persist($event);
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }

    public function findByCase(ManagedCase $managedCase): array
    {
        /** @var list<CaseAuditEvent> */
        return $this->entityManager->getRepository(CaseAuditEvent::class)->findBy(
            ['managedCase' => $managedCase],
            ['occurredAt' => 'ASC', 'id' => 'ASC'],
        );
    }

    public function purgeBefore(DateTimeImmutable $cutoff, int $limit): int
    {
        return $this->entityManager->wrapInTransaction(function () use ($cutoff, $limit): int {
            $connection = $this->entityManager->getConnection();
            $connection->executeStatement(
                "SELECT set_config('app.case_audit_retention_purge', 'on', true)",
            );

            return (int) $connection->executeStatement(<<<'SQL'
DELETE FROM case_audit_events
WHERE id IN (
    SELECT id
    FROM case_audit_events
    WHERE occurred_at < :cutoff
    ORDER BY occurred_at ASC, id ASC
    LIMIT :limit
)
SQL, [
                'cutoff' => $cutoff,
                'limit' => $limit,
            ], [
                'cutoff' => Types::DATETIMETZ_IMMUTABLE,
                'limit' => ParameterType::INTEGER,
            ]);
        });
    }
}
