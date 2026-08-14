<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814013000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add professional account lifecycle and one-time local credential invitations (#171).';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');

        $this->addSql("ALTER TABLE professionals ADD account_status VARCHAR(20) DEFAULT 'active' NOT NULL");
        $this->addSql('ALTER TABLE professionals ALTER account_status DROP DEFAULT');
        $this->addSql("ALTER TABLE professionals ADD CONSTRAINT chk_professionals_account_status CHECK (account_status IN ('invited', 'active', 'suspended', 'deactivated'))");
        $this->addSql(<<<'SQL'
CREATE TABLE professional_credential_invitations (
    id UUID NOT NULL,
    professional_id UUID NOT NULL,
    issued_by_professional_id UUID NOT NULL,
    purpose VARCHAR(20) NOT NULL,
    secret_hash VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    used_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
    PRIMARY KEY (id),
    CONSTRAINT "UNIQ_D7335120B390A88D" UNIQUE (secret_hash),
    CONSTRAINT fk_professional_credential_invitation_professional FOREIGN KEY (professional_id) REFERENCES professionals (id),
    CONSTRAINT fk_professional_credential_invitation_issuer FOREIGN KEY (issued_by_professional_id) REFERENCES professionals (id),
    CONSTRAINT chk_professional_credential_invitation_purpose CHECK (purpose IN ('activation', 'password_reset'))
)
SQL);
        $this->addSql('CREATE INDEX idx_professional_credential_invitation_hash ON professional_credential_invitations (secret_hash)');
        $this->addSql(<<<'SQL'
CREATE TABLE professional_account_audit_events (
    id UUID NOT NULL,
    target_professional_id UUID NOT NULL,
    actor_professional_id UUID NOT NULL,
    action VARCHAR(32) NOT NULL,
    occurred_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_professional_account_audit_target FOREIGN KEY (target_professional_id) REFERENCES professionals (id),
    CONSTRAINT fk_professional_account_audit_actor FOREIGN KEY (actor_professional_id) REFERENCES professionals (id),
    CONSTRAINT chk_professional_account_audit_action CHECK (action IN ('invited', 'credential_accepted', 'password_reset_issued', 'suspended', 'reactivated', 'deactivated'))
)
SQL);
        $this->addSql('CREATE INDEX idx_professional_account_audit_target_occurred ON professional_account_audit_events (target_professional_id, occurred_at)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');

        $this->addSql('DROP TABLE professional_account_audit_events');
        $this->addSql('DROP TABLE professional_credential_invitations');
        $this->addSql('ALTER TABLE professionals DROP CONSTRAINT chk_professionals_account_status');
        $this->addSql('ALTER TABLE professionals DROP account_status');
    }
}
