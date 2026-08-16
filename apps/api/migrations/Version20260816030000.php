<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260816030000 extends AbstractMigration
{
    private const ACTIONS_BEFORE = "'case_created', 'report_linked', 'assignment_created', 'assignment_changed', 'assignment_revoked', 'task_created', 'task_completed', 'task_marked_not_applicable', 'evidence_download_authorised', 'audit_exported', 'case_record_exported', 'person_added', 'person_corrected', 'person_removed', 'status_changed', 'communication_recorded', 'communication_corrected'";
    private const TARGETS_BEFORE = "'case', 'triage_decision', 'assignment', 'task', 'attachment', 'audit_trail', 'case_record', 'person', 'communication'";

    public function getDescription(): string
    {
        return 'Record controlled case document generation in the case audit trail (#181).';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');

        $this->addSql('ALTER TABLE case_audit_events DROP CONSTRAINT chk_case_audit_event_action');
        $this->addSql("ALTER TABLE case_audit_events ADD CONSTRAINT chk_case_audit_event_action CHECK (action IN (".self::ACTIONS_BEFORE.", 'document_generated'))");
        $this->addSql('ALTER TABLE case_audit_events DROP CONSTRAINT chk_case_audit_event_target');
        $this->addSql("ALTER TABLE case_audit_events ADD CONSTRAINT chk_case_audit_event_target CHECK (target IN (".self::TARGETS_BEFORE.", 'document'))");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');

        $this->addSql("DELETE FROM case_audit_events WHERE action = 'document_generated' OR target = 'document'");
        $this->addSql('ALTER TABLE case_audit_events DROP CONSTRAINT chk_case_audit_event_action');
        $this->addSql("ALTER TABLE case_audit_events ADD CONSTRAINT chk_case_audit_event_action CHECK (action IN (".self::ACTIONS_BEFORE."))");
        $this->addSql('ALTER TABLE case_audit_events DROP CONSTRAINT chk_case_audit_event_target');
        $this->addSql("ALTER TABLE case_audit_events ADD CONSTRAINT chk_case_audit_event_target CHECK (target IN (".self::TARGETS_BEFORE."))");
    }
}
