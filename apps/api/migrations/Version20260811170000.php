<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track the latest operational activity for deterministic protected case views (#48)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql('ALTER TABLE managed_cases ADD operational_updated_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql(<<<'SQL'
UPDATE managed_cases AS managed_case
SET operational_updated_at = GREATEST(
    managed_case.created_at,
    COALESCE((
        SELECT MAX(assignment.assigned_at)
        FROM case_assignments AS assignment
        WHERE assignment.case_id = managed_case.id
    ), managed_case.created_at),
    COALESCE((
        SELECT MAX(GREATEST(task.created_at, COALESCE(task.resolved_at, task.created_at)))
        FROM case_tasks AS task
        WHERE task.case_id = managed_case.id
    ), managed_case.created_at)
)
SQL);
        $this->addSql('ALTER TABLE managed_cases ALTER operational_updated_at SET NOT NULL');
        $this->addSql('CREATE INDEX idx_managed_case_operational_updated ON managed_cases (operational_updated_at DESC, id DESC)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql('DROP INDEX idx_managed_case_operational_updated');
        $this->addSql('ALTER TABLE managed_cases DROP operational_updated_at');
    }
}
