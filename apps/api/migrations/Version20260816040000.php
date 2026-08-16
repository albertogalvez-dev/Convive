<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260816040000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional reporter timing and reported people to reports (#194).';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');

        $this->addSql("ALTER TABLE reports ADD reporter_timing VARCHAR(24) DEFAULT 'unknown' NOT NULL");
        $this->addSql("ALTER TABLE reports ADD CONSTRAINT chk_report_reporter_timing CHECK (reporter_timing IN ('within_days', 'within_weeks', 'longer_ago', 'unknown'))");
        // Nullable because naming nobody is a complete report, not a missing
        // answer: absence and an empty string must not be distinguishable.
        $this->addSql('ALTER TABLE reports ADD reported_people VARCHAR(200) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');

        $this->addSql('ALTER TABLE reports DROP CONSTRAINT chk_report_reporter_timing');
        $this->addSql('ALTER TABLE reports DROP reporter_timing');
        $this->addSql('ALTER TABLE reports DROP reported_people');
    }
}
