<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260810211000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add private report attachment metadata and report-scoped quotas (#37)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql('ALTER TABLE reports ADD attachment_count INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE reports ADD attachment_bytes INT DEFAULT 0 NOT NULL');
        $this->addSql(
            'ALTER TABLE reports ADD CONSTRAINT chk_reports_attachment_capacity '
            .'CHECK (attachment_count >= 0 AND attachment_count <= 10 '
            .'AND attachment_bytes >= 0 AND attachment_bytes <= 20971520)',
        );
        $this->addSql(
            'CREATE TABLE report_attachments '
            .'(id UUID NOT NULL, report_id UUID NOT NULL, media_type VARCHAR(64) NOT NULL, '
            .'byte_size INT NOT NULL, content_hash VARCHAR(64) NOT NULL, '
            .'storage_key VARCHAR(255) NOT NULL, status VARCHAR(32) NOT NULL, '
            .'created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, '
            .'scan_started_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, '
            .'resolved_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, '
            .'deletion_requested_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, '
            .'deleted_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, '
            .'PRIMARY KEY(id))',
        );
        $this->addSql('CREATE UNIQUE INDEX uniq_report_attachments_storage_key ON report_attachments (storage_key)');
        $this->addSql('CREATE INDEX idx_report_attachments_report_status_created ON report_attachments (report_id, status, created_at, id)');
        $this->addSql('ALTER TABLE report_attachments ADD CONSTRAINT fk_report_attachments_report FOREIGN KEY (report_id) REFERENCES reports (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql(
            'ALTER TABLE report_attachments ADD CONSTRAINT chk_report_attachments_byte_size '
            .'CHECK (byte_size > 0 AND byte_size <= 5242880)',
        );
        $this->addSql(
            'ALTER TABLE report_attachments ADD CONSTRAINT chk_report_attachments_content_hash '
            ."CHECK (content_hash ~ '^[a-f0-9]{64}$')",
        );
        $this->addSql(
            'ALTER TABLE report_attachments ADD CONSTRAINT chk_report_attachments_media_type '
            ."CHECK (media_type IN ('application/pdf', 'image/jpeg', 'image/png'))",
        );
        $this->addSql(
            'ALTER TABLE report_attachments ADD CONSTRAINT chk_report_attachments_storage_key '
            ."CHECK (storage_key ~ '^(quarantine|available)/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$')",
        );
        $this->addSql(
            'ALTER TABLE report_attachments ADD CONSTRAINT chk_report_attachments_status '
            ."CHECK (status IN ('quarantined', 'scanning', 'available', 'rejected', 'deletion_pending', 'deleted'))",
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql('ALTER TABLE report_attachments DROP CONSTRAINT fk_report_attachments_report');
        $this->addSql('DROP TABLE report_attachments');
        $this->addSql('ALTER TABLE reports DROP CONSTRAINT chk_reports_attachment_capacity');
        $this->addSql('ALTER TABLE reports DROP attachment_count');
        $this->addSql('ALTER TABLE reports DROP attachment_bytes');
    }
}
