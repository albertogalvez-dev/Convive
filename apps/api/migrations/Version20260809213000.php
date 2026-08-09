<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260809213000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add initial professional report review state and inbox index (#31)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql('ALTER TABLE reports ADD review_reason TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE reports ADD reviewed_by_professional_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE reports ADD reviewed_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE reports ADD version INT DEFAULT 1 NOT NULL');
        $this->addSql(
            'CREATE INDEX idx_reports_professional_inbox '
            .'ON reports (organisation_id, status, created_at, id)',
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql('DROP INDEX idx_reports_professional_inbox');
        $this->addSql('ALTER TABLE reports DROP review_reason');
        $this->addSql('ALTER TABLE reports DROP reviewed_by_professional_id');
        $this->addSql('ALTER TABLE reports DROP reviewed_at');
        $this->addSql('ALTER TABLE reports DROP version');
    }
}
