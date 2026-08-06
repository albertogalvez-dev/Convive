<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260806101810 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add report access grants for anonymous capability-based access (#23)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection
                    ->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql(
            'CREATE TABLE report_access_grants (
                id UUID NOT NULL,
                report_id UUID NOT NULL,
                capability_hash VARCHAR(64) NOT NULL,
                issued_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                last_used_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                absolute_expires_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                revoked_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )',
        );
        $this->addSql(
            'CREATE UNIQUE INDEX UNIQ_1FFB1394B24D9A2D
             ON report_access_grants (capability_hash)',
        );
        $this->addSql(
            'CREATE INDEX IDX_1FFB13944BD2A4C0
             ON report_access_grants (report_id)',
        );
        $this->addSql(
            'ALTER TABLE report_access_grants
             ADD CONSTRAINT FK_1FFB13944BD2A4C0
             FOREIGN KEY (report_id) REFERENCES reports (id) NOT DEFERRABLE',
        );
        $this->addSql(
            'ALTER TABLE report_access_grants
             ADD CONSTRAINT chk_report_access_grants_capability_hash_format
             CHECK (capability_hash SIMILAR TO \'[0-9a-f]{64}\')',
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection
                    ->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql(
            'ALTER TABLE report_access_grants
             DROP CONSTRAINT FK_1FFB13944BD2A4C0',
        );
        $this->addSql('DROP TABLE report_access_grants');
    }
}
