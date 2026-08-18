<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Record whether a report is a first-person or a witness account (#259).';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');

        // Defaulting existing rows to 'experienced' is a statement of fact,
        // not an assumption: every report that exists when this runs was
        // submitted through an entry point framed as "what happened to you".
        // There is no 'unknown' value because the entry point always
        // determines this -- the reporter is never asked to declare it.
        $this->addSql("ALTER TABLE reports ADD reporter_perspective VARCHAR(16) DEFAULT 'experienced' NOT NULL");
        $this->addSql("ALTER TABLE reports ADD CONSTRAINT chk_report_reporter_perspective CHECK (reporter_perspective IN ('experienced', 'witnessed'))");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');

        $this->addSql('ALTER TABLE reports DROP CONSTRAINT chk_report_reporter_perspective');
        $this->addSql('ALTER TABLE reports DROP reporter_perspective');
    }
}
