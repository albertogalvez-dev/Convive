<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811182000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow bounded fictional retention of aggregate export audit events (#49)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql(<<<'SQL'
CREATE OR REPLACE FUNCTION prevent_professional_export_event_mutation()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    IF TG_OP = 'UPDATE' THEN
        RAISE EXCEPTION 'Professional export events are append-only.';
    END IF;

    IF current_setting('app.case_audit_retention_purge', true) IS DISTINCT FROM 'on' THEN
        RAISE EXCEPTION 'Professional export events can only be removed by the retention process.';
    END IF;

    RETURN OLD;
END;
$$
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql(<<<'SQL'
CREATE OR REPLACE FUNCTION prevent_professional_export_event_mutation()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    RAISE EXCEPTION 'Professional export events are append-only.';
END;
$$
SQL);
    }
}
