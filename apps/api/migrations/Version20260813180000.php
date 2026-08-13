<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260813180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add version-one neutral reporter and professional triage taxonomy (#177)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');

        $this->addSql("ALTER TABLE reports ADD reporter_recurrence VARCHAR(24) DEFAULT 'unknown' NOT NULL");
        $this->addSql("ALTER TABLE reports ADD reporter_attention_cue VARCHAR(32) DEFAULT 'unknown' NOT NULL");
        $this->addSql("ALTER TABLE reports ADD taxonomy_version VARCHAR(32) DEFAULT 'andalucia-v1' NOT NULL");
        $this->addSql('ALTER TABLE reports ADD professional_concern_category VARCHAR(40) DEFAULT NULL');
        $this->addSql('ALTER TABLE reports ADD professional_recurrence VARCHAR(24) DEFAULT NULL');
        $this->addSql('ALTER TABLE reports ADD professional_attention_cue VARCHAR(32) DEFAULT NULL');
        $this->addSql("ALTER TABLE reports ADD CONSTRAINT chk_reports_reporter_recurrence CHECK (reporter_recurrence IN ('single', 'repeated', 'ongoing', 'unknown'))");
        $this->addSql("ALTER TABLE reports ADD CONSTRAINT chk_reports_reporter_attention_cue CHECK (reporter_attention_cue IN ('needs_prompt_attention', 'no_prompt_attention_indicated', 'unknown'))");
        $this->addSql("ALTER TABLE reports ADD CONSTRAINT chk_reports_professional_concern_category CHECK (professional_concern_category IS NULL OR professional_concern_category IN ('peer_interaction', 'digital_interaction', 'exclusion_or_isolation', 'harmful_language_or_conduct', 'safety_or_wellbeing_concern', 'other', 'unknown'))");
        $this->addSql("ALTER TABLE reports ADD CONSTRAINT chk_reports_professional_recurrence CHECK (professional_recurrence IS NULL OR professional_recurrence IN ('single', 'repeated', 'ongoing', 'unknown'))");
        $this->addSql("ALTER TABLE reports ADD CONSTRAINT chk_reports_professional_attention_cue CHECK (professional_attention_cue IS NULL OR professional_attention_cue IN ('needs_prompt_attention', 'no_prompt_attention_indicated', 'unknown'))");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');

        $this->addSql('ALTER TABLE reports DROP CONSTRAINT chk_reports_professional_attention_cue');
        $this->addSql('ALTER TABLE reports DROP CONSTRAINT chk_reports_professional_recurrence');
        $this->addSql('ALTER TABLE reports DROP CONSTRAINT chk_reports_professional_concern_category');
        $this->addSql('ALTER TABLE reports DROP CONSTRAINT chk_reports_reporter_attention_cue');
        $this->addSql('ALTER TABLE reports DROP CONSTRAINT chk_reports_reporter_recurrence');
        $this->addSql('ALTER TABLE reports DROP professional_attention_cue');
        $this->addSql('ALTER TABLE reports DROP professional_recurrence');
        $this->addSql('ALTER TABLE reports DROP professional_concern_category');
        $this->addSql('ALTER TABLE reports DROP reporter_attention_cue');
        $this->addSql('ALTER TABLE reports DROP reporter_recurrence');
        $this->addSql('ALTER TABLE reports DROP taxonomy_version');
    }
}
