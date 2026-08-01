<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260731194013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create organisations and reports tables for anonymous report intake';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE organisations (id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE reports (id UUID NOT NULL, situation_description TEXT NOT NULL, situation_context VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, public_reference VARCHAR(32) NOT NULL, access_secret_hash VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, organisation_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F11FA74583849292 ON reports (public_reference)');
        $this->addSql('CREATE INDEX IDX_F11FA7459E6B1585 ON reports (organisation_id)');
        $this->addSql('ALTER TABLE reports ADD CONSTRAINT FK_F11FA7459E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisations (id) NOT DEFERRABLE');
        $this->addSql("ALTER TABLE reports ADD CONSTRAINT reports_situation_context_check CHECK (situation_context IN ('in_person', 'digital', 'mixed', 'unknown'))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reports DROP CONSTRAINT FK_F11FA7459E6B1585');
        $this->addSql('DROP TABLE organisations');
        $this->addSql('DROP TABLE reports');
    }
}
