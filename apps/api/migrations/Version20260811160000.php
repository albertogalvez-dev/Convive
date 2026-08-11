<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add minimised append-only case audit events (#47)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql(<<<'SQL'
CREATE TABLE case_audit_events (
    id UUID NOT NULL,
    case_id UUID NOT NULL,
    organisation_id UUID NOT NULL,
    actor_professional_id UUID NOT NULL,
    action VARCHAR(40) NOT NULL,
    target VARCHAR(30) NOT NULL,
    target_id UUID NOT NULL,
    occurred_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    PRIMARY KEY(id),
    CONSTRAINT fk_case_audit_event_case FOREIGN KEY (case_id) REFERENCES managed_cases (id),
    CONSTRAINT fk_case_audit_event_organisation FOREIGN KEY (organisation_id) REFERENCES organisations (id),
    CONSTRAINT fk_case_audit_event_actor FOREIGN KEY (actor_professional_id) REFERENCES professionals (id),
    CONSTRAINT chk_case_audit_event_action CHECK (action IN (
        'case_created', 'report_linked', 'assignment_created', 'assignment_revoked',
        'task_created', 'task_completed', 'task_marked_not_applicable',
        'evidence_download_authorised', 'audit_exported'
    )),
    CONSTRAINT chk_case_audit_event_target CHECK (target IN (
        'case', 'triage_decision', 'assignment', 'task', 'attachment', 'audit_trail'
    ))
)
SQL);
        $this->addSql('CREATE INDEX idx_case_audit_event_case_occurred ON case_audit_events (case_id, occurred_at)');
        $this->addSql('CREATE INDEX idx_case_audit_event_organisation_occurred ON case_audit_events (organisation_id, occurred_at)');
        $this->addSql(<<<'SQL'
CREATE FUNCTION prevent_case_audit_event_mutation()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    IF TG_OP = 'UPDATE' THEN
        RAISE EXCEPTION 'Case audit events are append-only.';
    END IF;

    IF current_setting('app.case_audit_retention_purge', true) IS DISTINCT FROM 'on' THEN
        RAISE EXCEPTION 'Case audit events can only be removed by the retention process.';
    END IF;

    RETURN OLD;
END;
$$
SQL);
        $this->addSql(<<<'SQL'
CREATE TRIGGER protect_case_audit_events
BEFORE UPDATE OR DELETE ON case_audit_events
FOR EACH ROW EXECUTE FUNCTION prevent_case_audit_event_mutation()
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql('DROP TRIGGER protect_case_audit_events ON case_audit_events');
        $this->addSql('DROP FUNCTION prevent_case_audit_event_mutation()');
        $this->addSql('DROP TABLE case_audit_events');
    }
}
