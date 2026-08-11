<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Record minimised professional aggregate export events (#49)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql(<<<'SQL'
CREATE TABLE professional_export_events (
    id UUID NOT NULL,
    professional_id UUID NOT NULL,
    kind VARCHAR(40) NOT NULL,
    occurred_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    PRIMARY KEY(id),
    CONSTRAINT fk_professional_export_event_actor
        FOREIGN KEY (professional_id) REFERENCES professionals (id),
    CONSTRAINT chk_professional_export_event_kind
        CHECK (kind IN ('operational_overview'))
)
SQL);
        $this->addSql(
            'CREATE INDEX idx_professional_export_event_actor_occurred ON professional_export_events (professional_id, occurred_at)',
        );
        $this->addSql(<<<'SQL'
CREATE FUNCTION prevent_professional_export_event_mutation()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    RAISE EXCEPTION 'Professional export events are append-only.';
END;
$$
SQL);
        $this->addSql(<<<'SQL'
CREATE TRIGGER protect_professional_export_events
BEFORE UPDATE OR DELETE ON professional_export_events
FOR EACH ROW EXECUTE FUNCTION prevent_professional_export_event_mutation()
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql('DROP TRIGGER protect_professional_export_events ON professional_export_events');
        $this->addSql('DROP FUNCTION prevent_professional_export_event_mutation()');
        $this->addSql('DROP TABLE professional_export_events');
    }
}
