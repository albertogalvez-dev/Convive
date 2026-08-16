<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260816120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Record an administrator's correction of a professional email as its own account audit action (#223).";
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');

        $this->addSql('ALTER TABLE professional_account_audit_events DROP CONSTRAINT chk_professional_account_audit_action');
        $this->addSql("ALTER TABLE professional_account_audit_events ADD CONSTRAINT chk_professional_account_audit_action CHECK (action IN ('invited', 'credential_accepted', 'password_reset_issued', 'suspended', 'reactivated', 'deactivated', 'membership_granted', 'membership_role_changed', 'membership_suspended', 'membership_resumed', 'membership_removed', 'profile_name_changed', 'profile_email_changed', 'email_corrected'))");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');

        $this->addSql("DELETE FROM professional_account_audit_events WHERE action = 'email_corrected'");
        $this->addSql('ALTER TABLE professional_account_audit_events DROP CONSTRAINT chk_professional_account_audit_action');
        $this->addSql("ALTER TABLE professional_account_audit_events ADD CONSTRAINT chk_professional_account_audit_action CHECK (action IN ('invited', 'credential_accepted', 'password_reset_issued', 'suspended', 'reactivated', 'deactivated', 'membership_granted', 'membership_role_changed', 'membership_suspended', 'membership_resumed', 'membership_removed', 'profile_name_changed', 'profile_email_changed'))");
    }
}
