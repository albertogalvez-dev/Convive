<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811062000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add append-only report triage decisions and minimal managed cases (#43)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql(<<<'SQL'
CREATE TABLE managed_cases (
    id UUID NOT NULL,
    organisation_id UUID NOT NULL,
    created_by_professional_id UUID NOT NULL,
    created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    PRIMARY KEY(id),
    CONSTRAINT fk_managed_case_organisation FOREIGN KEY (organisation_id) REFERENCES organisations (id),
    CONSTRAINT fk_managed_case_creator FOREIGN KEY (created_by_professional_id) REFERENCES professionals (id)
)
SQL);
        $this->addSql('CREATE INDEX idx_31ca6a459e6b1585 ON managed_cases (organisation_id)');
        $this->addSql(<<<'SQL'
CREATE TABLE report_triage_decisions (
    id UUID NOT NULL,
    report_id UUID NOT NULL,
    organisation_id UUID NOT NULL,
    decided_by_professional_id UUID NOT NULL,
    outcome VARCHAR(20) NOT NULL,
    reason TEXT NOT NULL,
    decided_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    terminal_report_id UUID DEFAULT NULL,
    case_id UUID DEFAULT NULL,
    PRIMARY KEY(id),
    CONSTRAINT fk_report_triage_report FOREIGN KEY (report_id) REFERENCES reports (id),
    CONSTRAINT fk_report_triage_organisation FOREIGN KEY (organisation_id) REFERENCES organisations (id),
    CONSTRAINT fk_report_triage_actor FOREIGN KEY (decided_by_professional_id) REFERENCES professionals (id),
    CONSTRAINT fk_report_triage_terminal_report FOREIGN KEY (terminal_report_id) REFERENCES reports (id),
    CONSTRAINT fk_report_triage_case FOREIGN KEY (case_id) REFERENCES managed_cases (id),
    CONSTRAINT uniq_4dfbc8311e0579a0 UNIQUE (terminal_report_id),
    CONSTRAINT uniq_4dfbc831cf10d4f5 UNIQUE (case_id),
    CONSTRAINT chk_report_triage_outcome CHECK (outcome IN ('keep', 'redirect', 'dismiss', 'link_to_case')),
    CONSTRAINT chk_report_triage_terminal_shape CHECK (
        (outcome = 'keep' AND terminal_report_id IS NULL AND case_id IS NULL)
        OR (outcome IN ('redirect', 'dismiss') AND terminal_report_id = report_id AND case_id IS NULL)
        OR (outcome = 'link_to_case' AND terminal_report_id = report_id AND case_id IS NOT NULL)
    ),
    CONSTRAINT chk_report_triage_reason_length CHECK (char_length(btrim(reason)) BETWEEN 10 AND 1000)
)
SQL);
        $this->addSql('CREATE INDEX idx_report_triage_history ON report_triage_decisions (report_id, decided_at, id)');
        $this->addSql('CREATE INDEX idx_4dfbc8319e6b1585 ON report_triage_decisions (organisation_id)');
        $this->addSql('CREATE INDEX idx_4dfbc831703f4374 ON report_triage_decisions (decided_by_professional_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql('DROP TABLE report_triage_decisions');
        $this->addSql('DROP TABLE managed_cases');
    }
}
