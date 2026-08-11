<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Model case status, modality, involved people and professional assignments (#44)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql("ALTER TABLE managed_cases ADD status VARCHAR(20) DEFAULT 'assessment' NOT NULL");
        $this->addSql("ALTER TABLE managed_cases ADD modality VARCHAR(20) DEFAULT 'unknown' NOT NULL");
        $this->addSql(<<<'SQL'
UPDATE managed_cases AS managed_case
SET modality = report.situation_context
FROM report_triage_decisions AS decision
JOIN reports AS report ON report.id = decision.report_id
WHERE decision.case_id = managed_case.id
SQL);
        $this->addSql('ALTER TABLE managed_cases ALTER status DROP DEFAULT');
        $this->addSql('ALTER TABLE managed_cases ALTER modality DROP DEFAULT');
        $this->addSql("ALTER TABLE managed_cases ADD CONSTRAINT chk_managed_case_status CHECK (status IN ('assessment', 'active', 'closed'))");
        $this->addSql("ALTER TABLE managed_cases ADD CONSTRAINT chk_managed_case_modality CHECK (modality IN ('in_person', 'digital', 'mixed', 'unknown'))");

        $this->addSql(<<<'SQL'
CREATE TABLE case_assignments (
    id UUID NOT NULL,
    case_id UUID NOT NULL,
    professional_id UUID NOT NULL,
    role VARCHAR(20) NOT NULL,
    assigned_by_professional_id UUID NOT NULL,
    assigned_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    revoked_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
    PRIMARY KEY(id),
    CONSTRAINT fk_case_assignment_case FOREIGN KEY (case_id) REFERENCES managed_cases (id),
    CONSTRAINT fk_case_assignment_professional FOREIGN KEY (professional_id) REFERENCES professionals (id),
    CONSTRAINT fk_case_assignment_actor FOREIGN KEY (assigned_by_professional_id) REFERENCES professionals (id),
    CONSTRAINT uniq_case_assignment_professional UNIQUE (case_id, professional_id),
    CONSTRAINT chk_case_assignment_role CHECK (role IN ('lead', 'contributor', 'observer')),
    CONSTRAINT chk_case_assignment_revocation CHECK (revoked_at IS NULL OR revoked_at >= assigned_at)
)
SQL);
        $this->addSql('CREATE INDEX idx_case_assignment_professional_active ON case_assignments (professional_id, revoked_at)');

        $this->addSql(<<<'SQL'
INSERT INTO case_assignments (
    id, case_id, professional_id, role, assigned_by_professional_id, assigned_at, revoked_at
)
SELECT managed_case.id, managed_case.id, managed_case.created_by_professional_id, 'lead',
       managed_case.created_by_professional_id, managed_case.created_at, NULL
FROM managed_cases AS managed_case
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE case_involved_people (
    id UUID NOT NULL,
    case_id UUID NOT NULL,
    name VARCHAR(120) NOT NULL,
    role VARCHAR(20) NOT NULL,
    added_by_professional_id UUID NOT NULL,
    added_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    PRIMARY KEY(id),
    CONSTRAINT fk_case_involved_person_case FOREIGN KEY (case_id) REFERENCES managed_cases (id),
    CONSTRAINT fk_case_involved_person_actor FOREIGN KEY (added_by_professional_id) REFERENCES professionals (id),
    CONSTRAINT chk_case_involved_person_name CHECK (char_length(btrim(name)) BETWEEN 1 AND 120),
    CONSTRAINT chk_case_involved_person_role CHECK (role IN ('affected', 'alleged_actor', 'witness', 'guardian', 'other'))
)
SQL);
        $this->addSql('CREATE INDEX idx_case_involved_people_case ON case_involved_people (case_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql('DROP TABLE case_involved_people');
        $this->addSql('DROP TABLE case_assignments');
        $this->addSql('ALTER TABLE managed_cases DROP CONSTRAINT chk_managed_case_modality');
        $this->addSql('ALTER TABLE managed_cases DROP CONSTRAINT chk_managed_case_status');
        $this->addSql('ALTER TABLE managed_cases DROP modality');
        $this->addSql('ALTER TABLE managed_cases DROP status');
    }
}
