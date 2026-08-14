<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add suspended organisation memberships and minimised membership lifecycle audit actions (#172).';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');

        $this->addSql('ALTER TABLE organisation_memberships ADD suspended_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE professional_account_audit_events DROP CONSTRAINT chk_professional_account_audit_action');
        $this->addSql("ALTER TABLE professional_account_audit_events ADD CONSTRAINT chk_professional_account_audit_action CHECK (action IN ('invited', 'credential_accepted', 'password_reset_issued', 'suspended', 'reactivated', 'deactivated', 'membership_granted', 'membership_role_changed', 'membership_suspended', 'membership_resumed', 'membership_removed'))");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');

        $this->addSql('ALTER TABLE professional_account_audit_events DROP CONSTRAINT chk_professional_account_audit_action');
        $this->addSql("ALTER TABLE professional_account_audit_events ADD CONSTRAINT chk_professional_account_audit_action CHECK (action IN ('invited', 'credential_accepted', 'password_reset_issued', 'suspended', 'reactivated', 'deactivated'))");
        $this->addSql('ALTER TABLE organisation_memberships DROP suspended_at');
    }
}
