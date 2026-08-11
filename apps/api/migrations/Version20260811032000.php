<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811032000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add separated verified reporter email contacts and notification outbox (#40)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql(<<<'SQL'
CREATE TABLE reporter_email_contacts (
    id UUID NOT NULL,
    report_id UUID NOT NULL,
    email VARCHAR(254) NOT NULL,
    status VARCHAR(16) NOT NULL,
    consent_notice_version VARCHAR(32) NOT NULL,
    consented_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    verification_token_hash CHAR(64) DEFAULT NULL,
    verification_expires_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
    verified_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
    created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    PRIMARY KEY(id),
    CONSTRAINT fk_reporter_email_contact_report FOREIGN KEY (report_id) REFERENCES reports (id) ON DELETE CASCADE,
    CONSTRAINT uq_reporter_email_contact_report UNIQUE (report_id),
    CONSTRAINT uq_reporter_email_verification_hash UNIQUE (verification_token_hash),
    CONSTRAINT chk_reporter_email_contact_status CHECK (status IN ('pending', 'verified')),
    CONSTRAINT chk_reporter_email_verification CHECK (
        (status = 'pending' AND (verification_token_hash IS NULL) = (verification_expires_at IS NULL) AND verified_at IS NULL)
        OR (status = 'verified' AND verification_token_hash IS NULL AND verification_expires_at IS NULL AND verified_at IS NOT NULL)
    )
)
SQL);
        $this->addSql(<<<'SQL'
CREATE TABLE reporter_notification_outbox (
    id UUID NOT NULL,
    contact_id UUID NOT NULL,
    kind VARCHAR(20) NOT NULL,
    deduplication_key VARCHAR(96) NOT NULL,
    status VARCHAR(16) NOT NULL,
    attempts SMALLINT NOT NULL DEFAULT 0,
    available_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    processing_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
    completed_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
    created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    PRIMARY KEY(id),
    CONSTRAINT fk_reporter_notification_contact FOREIGN KEY (contact_id) REFERENCES reporter_email_contacts (id) ON DELETE CASCADE,
    CONSTRAINT uq_reporter_notification_deduplication UNIQUE (deduplication_key),
    CONSTRAINT chk_reporter_notification_kind CHECK (kind IN ('verification', 'report_update')),
    CONSTRAINT chk_reporter_notification_status CHECK (status IN ('pending', 'processing', 'delivered', 'failed')),
    CONSTRAINT chk_reporter_notification_attempts CHECK (attempts BETWEEN 0 AND 3)
)
SQL);
        $this->addSql('CREATE INDEX idx_reporter_notification_delivery ON reporter_notification_outbox (status, available_at, created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql('DROP TABLE reporter_notification_outbox');
        $this->addSql('DROP TABLE reporter_email_contacts');
    }
}
