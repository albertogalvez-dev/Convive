<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260809214500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add private professional authorship metadata to visible report responses (#33)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql(
            'ALTER TABLE report_follow_up_entries ADD professional_author_id UUID DEFAULT NULL',
        );
        $this->addSql(
            'ALTER TABLE report_follow_up_entries '
            .'ADD CONSTRAINT chk_report_follow_up_entries_professional_author '
            ."CHECK ((author_type = 'reporter' AND professional_author_id IS NULL) "
            ."OR (author_type = 'professional' AND professional_author_id IS NOT NULL))",
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql(
            'ALTER TABLE report_follow_up_entries '
            .'DROP CONSTRAINT chk_report_follow_up_entries_professional_author',
        );
        $this->addSql('ALTER TABLE report_follow_up_entries DROP professional_author_id');
    }
}
