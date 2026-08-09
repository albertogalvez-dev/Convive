<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260809182000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add professional credentials, revocation state and shared sessions (#30)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql("ALTER TABLE professionals ADD password_hash VARCHAR(255) DEFAULT '!unavailable' NOT NULL");
        $this->addSql('ALTER TABLE professionals ADD active BOOLEAN DEFAULT TRUE NOT NULL');
        $this->addSql('ALTER TABLE professionals ADD security_revision INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE professionals ALTER password_hash DROP DEFAULT');
        $this->addSql('ALTER TABLE professionals ALTER active DROP DEFAULT');
        $this->addSql('ALTER TABLE professionals ALTER security_revision DROP DEFAULT');

        $this->addSql(
            'CREATE TABLE professional_sessions (
                sess_id VARCHAR(128) NOT NULL,
                sess_data BYTEA NOT NULL,
                sess_lifetime INT NOT NULL,
                sess_time INT NOT NULL,
                PRIMARY KEY (sess_id)
            )',
        );
        $this->addSql(
            'CREATE INDEX sess_lifetime_idx
             ON professional_sessions (sess_lifetime)',
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql('DROP TABLE professional_sessions');
        $this->addSql('ALTER TABLE professionals DROP password_hash');
        $this->addSql('ALTER TABLE professionals DROP active');
        $this->addSql('ALTER TABLE professionals DROP security_revision');
    }
}
