<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805214028 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Constrain report access-secret hashes for indexed lookup';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection
                    ->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql(
            'ALTER TABLE reports
             ALTER access_secret_hash TYPE VARCHAR(64)',
        );
        $this->addSql(
            'CREATE UNIQUE INDEX UNIQ_F11FA745290525A3
             ON reports (access_secret_hash)',
        );
        $this->addSql(
            'ALTER TABLE reports
             ADD CONSTRAINT chk_reports_access_secret_hash_format
             CHECK (access_secret_hash SIMILAR TO \'[0-9a-f]{64}\')',
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
            'DROP INDEX UNIQ_F11FA745290525A3',
        );
        $this->addSql(
            'ALTER TABLE reports
             DROP CONSTRAINT chk_reports_access_secret_hash_format',
        );
        $this->addSql(
            'ALTER TABLE reports
             ALTER access_secret_hash TYPE VARCHAR(255)',
        );
    }
}
