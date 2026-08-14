<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814070000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add immutable minimised managed-case communication records (#179)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');
        $this->addSql(<<<'SQL'
CREATE TABLE case_communications (
    id UUID NOT NULL, case_id UUID NOT NULL, responsible_professional_id UUID NOT NULL,
    recipient VARCHAR(30) NOT NULL, channel VARCHAR(30) NOT NULL, status VARCHAR(20) NOT NULL,
    occurred_at TIMESTAMPTZ NOT NULL, note VARCHAR(500) NOT NULL, created_by_professional_id UUID NOT NULL,
    created_at TIMESTAMPTZ NOT NULL, supersedes_communication_id UUID DEFAULT NULL, PRIMARY KEY(id),
    CONSTRAINT fk_case_communication_case FOREIGN KEY (case_id) REFERENCES managed_cases (id),
    CONSTRAINT fk_case_communication_responsible FOREIGN KEY (responsible_professional_id) REFERENCES professionals (id),
    CONSTRAINT fk_case_communication_creator FOREIGN KEY (created_by_professional_id) REFERENCES professionals (id),
    CONSTRAINT fk_case_communication_supersedes FOREIGN KEY (supersedes_communication_id) REFERENCES case_communications (id),
    CONSTRAINT chk_case_communication_recipient CHECK (recipient IN ('family','external_service','education_inspectorate','other')),
    CONSTRAINT chk_case_communication_channel CHECK (channel IN ('in_person','telephone','secure_portal','written_record','other')),
    CONSTRAINT chk_case_communication_status CHECK (status IN ('planned','recorded','not_applicable')),
    CONSTRAINT chk_case_communication_note CHECK (char_length(btrim(note)) BETWEEN 1 AND 500)
)
SQL);
        $this->addSql('CREATE INDEX idx_case_communication_case_occurred ON case_communications (case_id, occurred_at, id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');
        $this->addSql('DROP TABLE case_communications');
    }
}
