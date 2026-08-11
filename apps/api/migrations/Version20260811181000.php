<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811181000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow minimised case record export audit events (#49)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql('ALTER TABLE case_audit_events DROP CONSTRAINT chk_case_audit_event_action');
        $this->addSql(<<<'SQL'
ALTER TABLE case_audit_events ADD CONSTRAINT chk_case_audit_event_action CHECK (action IN (
    'case_created', 'report_linked', 'assignment_created', 'assignment_revoked',
    'task_created', 'task_completed', 'task_marked_not_applicable',
    'evidence_download_authorised', 'audit_exported', 'case_record_exported'
))
SQL);
        $this->addSql('ALTER TABLE case_audit_events DROP CONSTRAINT chk_case_audit_event_target');
        $this->addSql(<<<'SQL'
ALTER TABLE case_audit_events ADD CONSTRAINT chk_case_audit_event_target CHECK (target IN (
    'case', 'triage_decision', 'assignment', 'task', 'attachment', 'audit_trail', 'case_record'
))
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql('ALTER TABLE case_audit_events DROP CONSTRAINT chk_case_audit_event_action');
        $this->addSql(<<<'SQL'
ALTER TABLE case_audit_events ADD CONSTRAINT chk_case_audit_event_action CHECK (action IN (
    'case_created', 'report_linked', 'assignment_created', 'assignment_revoked',
    'task_created', 'task_completed', 'task_marked_not_applicable',
    'evidence_download_authorised', 'audit_exported'
))
SQL);
        $this->addSql('ALTER TABLE case_audit_events DROP CONSTRAINT chk_case_audit_event_target');
        $this->addSql(<<<'SQL'
ALTER TABLE case_audit_events ADD CONSTRAINT chk_case_audit_event_target CHECK (target IN (
    'case', 'triage_decision', 'assignment', 'task', 'attachment', 'audit_trail'
))
SQL);
    }
}
