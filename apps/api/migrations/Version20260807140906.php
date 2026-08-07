<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260807140906 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reporter-authored follow-up entries for a report (#25)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection
                    ->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql(
            'CREATE TABLE report_follow_up_entries (
                id UUID NOT NULL,
                report_id UUID NOT NULL,
                author_type VARCHAR(20) NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )',
        );
        $this->addSql(
            'CREATE INDEX IDX_FFA580924BD2A4C0
             ON report_follow_up_entries (report_id)',
        );
        $this->addSql(
            'ALTER TABLE report_follow_up_entries
             ADD CONSTRAINT FK_FFA580924BD2A4C0
             FOREIGN KEY (report_id) REFERENCES reports (id) NOT DEFERRABLE',
        );
        $this->addSql(
            'ALTER TABLE report_follow_up_entries
             ADD CONSTRAINT chk_report_follow_up_entries_author_type
             CHECK (author_type IN (\'reporter\', \'professional\'))',
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
            'ALTER TABLE report_follow_up_entries
             DROP CONSTRAINT FK_FFA580924BD2A4C0',
        );
        $this->addSql('DROP TABLE report_follow_up_entries');
    }
}
