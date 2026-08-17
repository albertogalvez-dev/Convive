<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260817070000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Valencia as a third territorial protocol profile (#272)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        // Source verified directly against the Diari Oficial de la Generalitat
        // Valenciana, not a secondary aggregator: DECRETO 193/2025, de 12 de
        // diciembre, del Consell, de la convivencia en el sistema educativo
        // de la Comunitat Valenciana, published DOGV nº 10263 (17 December
        // 2025), in force since 7 January 2026, repeals Decreto 195/2022.
        // https://dogv.gva.es/datos/2025/12/17/pdf/2025_50344_es.pdf
        //
        // A prior, unverified research pass cited "Decreto 96/2026" as the
        // source. That decree exists but is unrelated: it modifies the
        // Educación Primaria curriculum, not school coexistence or bullying.
        // The real, currently governing decree is Decreto 193/2025, above.
        //
        // Unlike Aragón (#254), this decree is a general coexistence and
        // school-discipline framework, not a dedicated bullying-response
        // protocol -- "acoso y ciberacoso" is one of several conducts
        // classified as a "falta grave" (art. 25.b), handled through the
        // decree's general disciplinary-file (expediente disciplinario)
        // procedure. Its own transitional provision (disposición transitoria
        // primera) keeps the older Orden 62/2014's dedicated violence-response
        // protocols in force until further implementing regulation is issued;
        // that order was not separately verified and is not modelled here.
        //
        // Real, verified deadlines from Decreto 193/2025 itself (not inferred
        // from the national framework): opening the disciplinary file within
        // 5 school days of the facts becoming known (art. 28.1.3), and a
        // 2-month cap on the whole disciplinary procedure, notification
        // included (art. 28.4).
        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_source_versions
    (id, code, version, title, uri, territory, authority, published_on, reviewed_on)
VALUES
    (
        '019c4c1f-4fd4-7f6d-a0d1-000000000001',
        'ES-VC-DECREE-2025-12-12-CONVIVENCIA',
        'DOGV-10263-2025',
        'Valencian school coexistence decree (Decreto 193/2025)',
        'https://dogv.gva.es/datos/2025/12/17/pdf/2025_50344_es.pdf',
        'ES-VC',
        'binding',
        '2025-12-17',
        '2026-08-17'
    )
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_task_templates (id, source_version_id, stage, kind, title, approved)
VALUES
    (
        '019ffe72-0000-7000-8000-000000000001',
        '019c4c1f-4fd4-7f6d-a0d1-000000000001',
        'immediate_actions',
        'internal_action',
        'Review the fictional disciplinary file opened within 5 school days.',
        true
    ),
    (
        '019ffe72-0000-7000-8000-000000000002',
        '019c4c1f-4fd4-7f6d-a0d1-000000000001',
        'family_communication',
        'external_communication',
        'Confirm the fictional notification to legal representatives.',
        true
    ),
    (
        '019ffe72-0000-7000-8000-000000000003',
        '019c4c1f-4fd4-7f6d-a0d1-000000000001',
        'professional_coordination',
        'internal_action',
        'Record the fictional coordinación de bienestar y protección involvement.',
        true
    ),
    (
        '019ffe72-0000-7000-8000-000000000004',
        '019c4c1f-4fd4-7f6d-a0d1-000000000001',
        'action_plan',
        'internal_action',
        'Track the fictional 2-month disciplinary-file resolution deadline.',
        true
    )
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql("DELETE FROM case_workflow_task_templates WHERE source_version_id = '019c4c1f-4fd4-7f6d-a0d1-000000000001'");
        $this->addSql("DELETE FROM case_workflow_source_versions WHERE id = '019c4c1f-4fd4-7f6d-a0d1-000000000001'");
    }
}
