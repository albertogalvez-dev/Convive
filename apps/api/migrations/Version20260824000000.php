<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add supporting indexes for every foreign-key reference (#360)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql('CREATE INDEX idx_case_audit_event_actor ON case_audit_events (actor_professional_id)');
        $this->addSql('CREATE INDEX idx_case_assignment_assigned_by ON case_assignments (assigned_by_professional_id)');
        $this->addSql('CREATE INDEX idx_case_communication_created_by ON case_communications (created_by_professional_id)');
        $this->addSql('CREATE INDEX idx_case_communication_responsible ON case_communications (responsible_professional_id)');
        $this->addSql('CREATE INDEX idx_case_communication_supersedes ON case_communications (supersedes_communication_id)');
        $this->addSql('CREATE INDEX idx_case_involved_person_added_by ON case_involved_people (added_by_professional_id)');
        $this->addSql('CREATE INDEX idx_case_task_created_by ON case_tasks (created_by_professional_id)');
        $this->addSql('CREATE INDEX idx_case_task_resolved_by ON case_tasks (resolved_by_professional_id)');
        $this->addSql('CREATE INDEX idx_case_task_source_version ON case_tasks (source_version_id)');
        $this->addSql('CREATE INDEX idx_case_workflow_template_source ON case_workflow_task_templates (source_version_id)');
        $this->addSql('CREATE INDEX idx_managed_case_created_by ON managed_cases (created_by_professional_id)');
        $this->addSql('CREATE INDEX idx_professional_account_audit_actor ON professional_account_audit_events (actor_professional_id)');
        $this->addSql('CREATE INDEX idx_professional_credential_invitation_issued_by ON professional_credential_invitations (issued_by_professional_id)');
        $this->addSql('CREATE INDEX idx_professional_credential_invitation_professional ON professional_credential_invitations (professional_id)');
        $this->addSql('CREATE INDEX idx_professional_notification_case ON professional_notifications (case_id)');
        $this->addSql('CREATE INDEX idx_reporter_notification_outbox_contact ON reporter_notification_outbox (contact_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql('DROP INDEX idx_reporter_notification_outbox_contact');
        $this->addSql('DROP INDEX idx_professional_notification_case');
        $this->addSql('DROP INDEX idx_professional_credential_invitation_professional');
        $this->addSql('DROP INDEX idx_professional_credential_invitation_issued_by');
        $this->addSql('DROP INDEX idx_professional_account_audit_actor');
        $this->addSql('DROP INDEX idx_managed_case_created_by');
        $this->addSql('DROP INDEX idx_case_workflow_template_source');
        $this->addSql('DROP INDEX idx_case_task_source_version');
        $this->addSql('DROP INDEX idx_case_task_resolved_by');
        $this->addSql('DROP INDEX idx_case_task_created_by');
        $this->addSql('DROP INDEX idx_case_involved_person_added_by');
        $this->addSql('DROP INDEX idx_case_communication_supersedes');
        $this->addSql('DROP INDEX idx_case_communication_responsible');
        $this->addSql('DROP INDEX idx_case_communication_created_by');
        $this->addSql('DROP INDEX idx_case_assignment_assigned_by');
        $this->addSql('DROP INDEX idx_case_audit_event_actor');
    }
}
