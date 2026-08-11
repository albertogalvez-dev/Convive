<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Model source-aware case tasks and deterministic lifecycle states (#45)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql(<<<'SQL'
CREATE TABLE case_workflow_source_versions (
    id UUID NOT NULL,
    code VARCHAR(80) NOT NULL,
    version VARCHAR(40) NOT NULL,
    title VARCHAR(180) NOT NULL,
    uri VARCHAR(500) DEFAULT NULL,
    territory VARCHAR(20) NOT NULL,
    authority VARCHAR(20) NOT NULL,
    published_on DATE NOT NULL,
    reviewed_on DATE NOT NULL,
    PRIMARY KEY(id),
    CONSTRAINT uniq_case_workflow_source_version UNIQUE (code, version),
    CONSTRAINT chk_case_workflow_source_authority CHECK (authority IN ('binding', 'recommended', 'internal')),
    CONSTRAINT chk_case_workflow_source_text CHECK (
        char_length(btrim(code)) BETWEEN 1 AND 80
        AND char_length(btrim(version)) BETWEEN 1 AND 40
        AND char_length(btrim(title)) BETWEEN 1 AND 180
        AND char_length(btrim(territory)) BETWEEN 1 AND 20
        AND (uri IS NULL OR char_length(btrim(uri)) BETWEEN 1 AND 500)
    ),
    CONSTRAINT chk_case_workflow_source_dates CHECK (reviewed_on >= published_on),
    CONSTRAINT chk_case_workflow_official_uri CHECK (authority = 'internal' OR uri IS NOT NULL)
)
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_source_versions
    (id, code, version, title, uri, territory, authority, published_on, reviewed_on)
VALUES
    (
        '019c4c1d-4fd4-7f6d-a0d1-000000000001',
        'ES-AN-ORDER-2011-06-20-ANNEX-I',
        'BOJA-132-2011',
        'Andalusian school bullying protocol',
        'https://www.juntadeandalucia.es/boja/2011/132/1',
        'ES-AN',
        'binding',
        '2011-07-07',
        '2026-08-11'
    ),
    (
        '019c4c1d-4fd4-7f6d-a0d1-000000000002',
        'ES-AN-INSTRUCTIONS-2017-01-11-CYBERBULLYING',
        'SIGNED-2017-01-11',
        'Andalusian cyberbullying instructions',
        'https://www.juntadeandalucia.es/educacion/portals/abaco-portlet/content/fb2e79b3-4146-4d03-8001-9650eefc0f02',
        'ES-AN',
        'binding',
        '2017-01-11',
        '2026-08-11'
    ),
    (
        '019c4c1d-4fd4-7f6d-a0d1-000000000003',
        'ES-MEFPD-FRAMEWORK-2026-04-15',
        'PRESENTED-2026-04-15',
        'National bullying and cyberbullying reference framework',
        'https://www.educacionfpydeportes.gob.es/prensa/actualidad/2026/04/20260415-protocoloacoso.html',
        'ES',
        'recommended',
        '2026-04-15',
        '2026-08-11'
    ),
    (
        '019c4c1d-4fd4-7f6d-a0d1-000000000004',
        'CONVIVE-INTERNAL-ANDALUSIA-DEMO',
        '2026-08-11',
        'Convive fictional Andalusian demonstration target',
        NULL,
        'ES-AN-GR',
        'internal',
        '2026-08-11',
        '2026-08-11'
    )
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE case_tasks (
    id UUID NOT NULL,
    case_id UUID NOT NULL,
    owner_professional_id UUID NOT NULL,
    source_version_id UUID NOT NULL,
    stage VARCHAR(40) NOT NULL,
    kind VARCHAR(30) NOT NULL,
    title VARCHAR(160) NOT NULL,
    due_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    status VARCHAR(20) NOT NULL,
    created_by_professional_id UUID NOT NULL,
    created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    resolved_by_professional_id UUID DEFAULT NULL,
    resolved_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
    not_applicable_reason VARCHAR(500) DEFAULT NULL,
    PRIMARY KEY(id),
    CONSTRAINT fk_case_task_case FOREIGN KEY (case_id) REFERENCES managed_cases (id),
    CONSTRAINT fk_case_task_owner FOREIGN KEY (owner_professional_id) REFERENCES professionals (id),
    CONSTRAINT fk_case_task_source FOREIGN KEY (source_version_id) REFERENCES case_workflow_source_versions (id),
    CONSTRAINT fk_case_task_creator FOREIGN KEY (created_by_professional_id) REFERENCES professionals (id),
    CONSTRAINT fk_case_task_resolver FOREIGN KEY (resolved_by_professional_id) REFERENCES professionals (id),
    CONSTRAINT chk_case_task_stage CHECK (stage IN (
        'identification', 'immediate_actions', 'urgent_protection', 'family_communication',
        'professional_coordination', 'information_collection', 'educational_measures',
        'inspection_communication', 'assessment', 'action_plan', 'family_measures',
        'inspection_follow_up'
    )),
    CONSTRAINT chk_case_task_kind CHECK (kind IN ('internal_action', 'external_communication')),
    CONSTRAINT chk_case_task_status CHECK (status IN ('pending', 'completed', 'not_applicable')),
    CONSTRAINT chk_case_task_title CHECK (char_length(btrim(title)) BETWEEN 1 AND 160),
    CONSTRAINT chk_case_task_due CHECK (due_at >= created_at),
    CONSTRAINT chk_case_task_resolution CHECK (
        (status = 'pending' AND resolved_by_professional_id IS NULL AND resolved_at IS NULL AND not_applicable_reason IS NULL)
        OR (status = 'completed' AND resolved_by_professional_id IS NOT NULL AND resolved_at >= created_at AND not_applicable_reason IS NULL)
        OR (status = 'not_applicable' AND resolved_by_professional_id IS NOT NULL AND resolved_at >= created_at
            AND char_length(btrim(not_applicable_reason)) BETWEEN 1 AND 500)
    )
)
SQL);
        $this->addSql('CREATE INDEX idx_case_task_case_status_due ON case_tasks (case_id, status, due_at)');
        $this->addSql('CREATE INDEX idx_case_task_owner_status_due ON case_tasks (owner_professional_id, status, due_at)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql('DROP TABLE case_tasks');
        $this->addSql('DROP TABLE case_workflow_source_versions');
    }
}
