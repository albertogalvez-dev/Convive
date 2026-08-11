<?php

declare(strict_types=1);

namespace App\Cases\Infrastructure;

use App\Cases\Domain\ProfessionalExportEvent;
use App\Cases\Domain\ProfessionalExportEventRepository;
use DateTimeImmutable;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineProfessionalExportEventRepository implements ProfessionalExportEventRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function append(ProfessionalExportEvent $event): void
    {
        $this->entityManager->persist($event);
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }

    public function purgeBefore(DateTimeImmutable $cutoff, int $limit): int
    {
        return $this->entityManager->wrapInTransaction(function () use ($cutoff, $limit): int {
            $connection = $this->entityManager->getConnection();
            $connection->executeStatement(
                "SELECT set_config('app.case_audit_retention_purge', 'on', true)",
            );

            return (int) $connection->executeStatement(<<<'SQL'
DELETE FROM professional_export_events
WHERE id IN (
    SELECT id
    FROM professional_export_events
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
