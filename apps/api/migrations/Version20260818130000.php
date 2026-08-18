<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Canarias as a thirteenth territorial protocol profile (#271)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        // Source verified by reading the Consejería's own published document
        // end to end: "Guía para el profesorado. Marco general de actuación
        // ante un posible caso de acoso escolar", colección Guías y
        // Orientaciones, edited by the Dirección General de Ordenación,
        // Innovación y Promoción Educativa and written by its Equipo técnico
        // de gestión de la convivencia escolar. Canarias, 2015.
        // https://www.gobiernodecanarias.org/educacion/web/servicios/inspeccion_educativa/normativa_clasificada/orientaciones_programas_protocolos/protocolos/
        //
        // Authority is 'recommended', and the document is explicit about why:
        // it is published in a "Guías y orientaciones" series and states that
        // "Cada centro, desde su autonomía, ya dispone de su propio
        // protocolo". It is the territory-wide reference framework, not an
        // instrument that replaces the school's own protocol. The binding
        // frame it works within is Decreto 114/2011, whose article 3.2
        // supplies the principles of oportunidad, intervención mínima,
        // graduación and proporcionalidad.
        //
        // Scope limit stated by the source itself and respected here: the
        // framework is "exclusivo para atender supuestos de bullying (acoso
        // entre iguales)" and is expressly NOT valid for staff-to-student,
        // student-to-staff or adult-to-adult cases.
        //
        // Real, verified deadlines:
        // - Whoever receives the information passes it to the director "de
        //   forma inmediata"; the director designates a referente del caso
        //   from the equipo de gestión de la convivencia and calls the family
        //   for a first interview "en un plazo no superior a dos días".
        // - Where a precautionary distancing measure is needed above primary
        //   education, attendance may be suspended "por un máximo de cuatro
        //   días", with the family called back "en uno o dos días".
        // - Where the case may be criminal and the student is over 14, the
        //   inspector is informed "de manera inmediata".
        // - Follow-up runs "al menos durante tres meses" before the case can
        //   be closed, explicitly not counting holiday periods even across a
        //   change of school year; weekly in the first month, fortnightly in
        //   the remaining two.
        //
        // Two facts recorded rather than smoothed over:
        // - Secondary press reporting from November 2025 described a maximum
        //   of five days for the first interview. The primary text says two.
        //   The primary text is what is modelled.
        // - Press reporting from 2026 says the Consejería has presented a
        //   "boceto" of a new anti-bullying protocol. A draft is not a
        //   published instrument, so the 2015 framework still governs. Worth
        //   rechecking before launch.
        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_source_versions
    (id, code, version, title, uri, territory, authority, published_on, reviewed_on)
VALUES
    (
        '019c4c29-4fd4-7f6d-a0d1-000000000001',
        'ES-CN-DGOIPE-MARCO-2015',
        'CANARIAS-2015',
        'Canarias general framework for a possible school bullying case (DGOIPE, 2015)',
        'https://www.gobiernodecanarias.org/educacion/web/servicios/inspeccion_educativa/normativa_clasificada/orientaciones_programas_protocolos/protocolos/',
        'ES-CN',
        'recommended',
        '2015-01-01',
        '2026-08-18'
    )
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_task_templates (id, source_version_id, stage, kind, title, approved)
VALUES
    (
        '019ffe7c-0000-7000-8000-000000000001',
        '019c4c29-4fd4-7f6d-a0d1-000000000001',
        'immediate_actions',
        'internal_action',
        'Confirm the fictional designation of a referente del caso from the equipo de gestión de la convivencia.',
        true
    ),
    (
        '019ffe7c-0000-7000-8000-000000000002',
        '019c4c29-4fd4-7f6d-a0d1-000000000001',
        'family_communication',
        'external_communication',
        'Record the fictional first interview with whoever reported, held within the two days the source allows.',
        true
    ),
    (
        '019ffe7c-0000-7000-8000-000000000003',
        '019c4c29-4fd4-7f6d-a0d1-000000000001',
        'information_collection',
        'internal_action',
        'Record the fictional interview with the affected student, kept with the same referente throughout.',
        true
    ),
    (
        '019ffe7c-0000-7000-8000-000000000004',
        '019c4c29-4fd4-7f6d-a0d1-000000000001',
        'assessment',
        'internal_action',
        'Record the fictional pre-intervention analysis against the three diagnostic criteria the source sets.',
        true
    ),
    (
        '019ffe7c-0000-7000-8000-000000000005',
        '019c4c29-4fd4-7f6d-a0d1-000000000001',
        'urgent_protection',
        'internal_action',
        'Confirm the fictional urgent protection measures, including any four-day precautionary distancing.',
        true
    ),
    (
        '019ffe7c-0000-7000-8000-000000000006',
        '019c4c29-4fd4-7f6d-a0d1-000000000001',
        'professional_coordination',
        'internal_action',
        'Record the fictional educational session with the observing students, a group of three to six.',
        true
    ),
    (
        '019ffe7c-0000-7000-8000-000000000007',
        '019c4c29-4fd4-7f6d-a0d1-000000000001',
        'inspection_communication',
        'external_communication',
        'Confirm the fictional notification to the inspector, immediate where the case may be criminal.',
        true
    ),
    (
        '019ffe7c-0000-7000-8000-000000000008',
        '019c4c29-4fd4-7f6d-a0d1-000000000001',
        'inspection_follow_up',
        'internal_action',
        'Track the fictional three-month accompaniment plan, which the source counts excluding holidays.',
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

        $this->addSql("DELETE FROM case_workflow_task_templates WHERE source_version_id = '019c4c29-4fd4-7f6d-a0d1-000000000001'");
        $this->addSql("DELETE FROM case_workflow_source_versions WHERE id = '019c4c29-4fd4-7f6d-a0d1-000000000001'");
    }
}
