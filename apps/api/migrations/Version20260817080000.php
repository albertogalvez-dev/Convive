<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260817080000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Castilla-La Mancha as a fourth territorial protocol profile (#261)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        // Source verified directly against the Diario Oficial de Castilla-La
        // Mancha, not a secondary aggregator: fetched and read the full
        // 19-page text of Resolución de 18/01/2017, de la Consejería de
        // Educación, Cultura y Deportes, por la que se acuerda dar
        // publicidad al protocolo de actuación ante situaciones de acoso
        // escolar en los centros docentes públicos no universitarios de
        // Castilla-La Mancha. Published DOCM año XXXVI, nº 14 (20 January
        // 2017), reference 2017/632.
        // http://docm.jccm.es/portaldocm/descargarArchivo.do?ruta=2017/01/20/pdf/2017_632.pdf&tipo=rutaDocm
        //
        // Unlike Aragón and Valencia, the prior research pass's citation for
        // this territory checked out on full verification: the protocol
        // really does set explicit numeric deadlines, confirmed verbatim --
        // - Constitute the Comisión de Acoso Escolar (3 members: a member of
        //   the equipo directivo, the orientador/a, and a teacher) "lo antes
        //   posible, nunca más tarde de 48 horas" from a reasonable
        //   indication of bullying (Tercero.1-2).
        // - Notify the affected students' families within 24 hours of the
        //   commission's constitution (Cuarto.3).
        // - Immediately notify Inspección educativa, by phone and in
        //   writing, of the facts, the commission's constitution and the
        //   immediate measures taken (Cuarto.2).
        // - Submit the Plan de Actuación to Inspección educativa within a
        //   maximum of 30 días lectivos (school days) from the commission's
        //   constitution (Quinto).
        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_source_versions
    (id, code, version, title, uri, territory, authority, published_on, reviewed_on)
VALUES
    (
        '019c4c20-4fd4-7f6d-a0d1-000000000001',
        'ES-CM-RESOLUTION-2017-01-18-ANEXO-I',
        'DOCM-14-2017',
        'Castilla-La Mancha school bullying protocol (Resolución de 18/01/2017)',
        'http://docm.jccm.es/portaldocm/descargarArchivo.do?ruta=2017/01/20/pdf/2017_632.pdf&tipo=rutaDocm',
        'ES-CM',
        'binding',
        '2017-01-20',
        '2026-08-17'
    )
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_task_templates (id, source_version_id, stage, kind, title, approved)
VALUES
    (
        '019ffe73-0000-7000-8000-000000000001',
        '019c4c20-4fd4-7f6d-a0d1-000000000001',
        'immediate_actions',
        'internal_action',
        'Track the fictional 48-hour bullying-commission constitution deadline.',
        true
    ),
    (
        '019ffe73-0000-7000-8000-000000000002',
        '019c4c20-4fd4-7f6d-a0d1-000000000001',
        'urgent_protection',
        'internal_action',
        'Confirm the fictional immediate protective measures for the affected student.',
        true
    ),
    (
        '019ffe73-0000-7000-8000-000000000003',
        '019c4c20-4fd4-7f6d-a0d1-000000000001',
        'inspection_communication',
        'external_communication',
        'Confirm the fictional immediate notification to Inspección educativa.',
        true
    ),
    (
        '019ffe73-0000-7000-8000-000000000004',
        '019c4c20-4fd4-7f6d-a0d1-000000000001',
        'family_communication',
        'external_communication',
        'Track the fictional 24-hour family-notification deadline.',
        true
    ),
    (
        '019ffe73-0000-7000-8000-000000000005',
        '019c4c20-4fd4-7f6d-a0d1-000000000001',
        'action_plan',
        'internal_action',
        'Track the fictional 30-school-day action-plan deadline for Inspección.',
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

        $this->addSql("DELETE FROM case_workflow_task_templates WHERE source_version_id = '019c4c20-4fd4-7f6d-a0d1-000000000001'");
        $this->addSql("DELETE FROM case_workflow_source_versions WHERE id = '019c4c20-4fd4-7f6d-a0d1-000000000001'");
    }
}
