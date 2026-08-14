<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260814043000 extends AbstractMigration
{
    public function getDescription(): string { return 'Preserve corrected and removed minimised case people and audit actions (#175).'; }
    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');
        $this->addSql('ALTER TABLE case_involved_people ADD corrected_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE case_involved_people ADD removed_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE case_audit_events DROP CONSTRAINT chk_case_audit_event_action');
        $this->addSql("ALTER TABLE case_audit_events ADD CONSTRAINT chk_case_audit_event_action CHECK (action IN ('case_created', 'report_linked', 'assignment_created', 'assignment_changed', 'assignment_revoked', 'task_created', 'task_completed', 'task_marked_not_applicable', 'evidence_download_authorised', 'audit_exported', 'case_record_exported', 'person_added', 'person_corrected', 'person_removed'))");
        $this->addSql('ALTER TABLE case_audit_events DROP CONSTRAINT chk_case_audit_event_target');
        $this->addSql("ALTER TABLE case_audit_events ADD CONSTRAINT chk_case_audit_event_target CHECK (target IN ('case', 'triage_decision', 'assignment', 'task', 'attachment', 'audit_trail', 'case_record', 'person'))");
    }
    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');
        $this->addSql('ALTER TABLE case_audit_events DROP CONSTRAINT chk_case_audit_event_action');
        $this->addSql("ALTER TABLE case_audit_events ADD CONSTRAINT chk_case_audit_event_action CHECK (action IN ('case_created', 'report_linked', 'assignment_created', 'assignment_changed', 'assignment_revoked', 'task_created', 'task_completed', 'task_marked_not_applicable', 'evidence_download_authorised', 'audit_exported', 'case_record_exported'))");
        $this->addSql('ALTER TABLE case_audit_events DROP CONSTRAINT chk_case_audit_event_target');
        $this->addSql("ALTER TABLE case_audit_events ADD CONSTRAINT chk_case_audit_event_target CHECK (target IN ('case', 'triage_decision', 'assignment', 'task', 'attachment', 'audit_trail', 'case_record'))");
        $this->addSql('ALTER TABLE case_involved_people DROP corrected_at');
        $this->addSql('ALTER TABLE case_involved_people DROP removed_at');
    }
}
