<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260816050000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reporting-channel state for centre link lifecycle (#195).';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');

        $this->addSql("ALTER TABLE organisations ADD reporting_channel_status VARCHAR(16) DEFAULT 'active' NOT NULL");
        $this->addSql("ALTER TABLE organisations ADD CONSTRAINT chk_organisation_reporting_channel_status CHECK (reporting_channel_status IN ('active', 'paused', 'retired'))");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');

        $this->addSql('ALTER TABLE organisations DROP CONSTRAINT chk_organisation_reporting_channel_status');
        $this->addSql('ALTER TABLE organisations DROP reporting_channel_status');
    }
}
