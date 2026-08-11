<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260810214500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add bounded reporter-supplied attachment descriptions (#38)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql('ALTER TABLE report_attachments ADD description VARCHAR(500) DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE report_attachments ADD CONSTRAINT chk_report_attachments_description '
            .'CHECK (description IS NULL OR char_length(description) BETWEEN 1 AND 500)',
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql('ALTER TABLE report_attachments DROP CONSTRAINT chk_report_attachments_description');
        $this->addSql('ALTER TABLE report_attachments DROP description');
    }
}
