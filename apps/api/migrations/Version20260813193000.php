<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260813193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store minimised reasons for explicit case assignment, role-change and revocation decisions.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql('ALTER TABLE case_assignments ADD assignment_reason VARCHAR(500) DEFAULT NULL, ADD role_change_reason VARCHAR(500) DEFAULT NULL, ADD revocation_reason VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE case_audit_events DROP CONSTRAINT chk_case_audit_event_action');
        $this->addSql(<<<'SQL'
ALTER TABLE case_audit_events ADD CONSTRAINT chk_case_audit_event_action CHECK (action IN (
    'case_created', 'report_linked', 'assignment_created', 'assignment_changed', 'assignment_revoked',
    'task_created', 'task_completed', 'task_marked_not_applicable',
    'evidence_download_authorised', 'audit_exported', 'case_record_exported'
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
    'evidence_download_authorised', 'audit_exported', 'case_record_exported'
))
SQL);
        $this->addSql('ALTER TABLE case_assignments DROP assignment_reason, DROP role_change_reason, DROP revocation_reason');
    }
}
