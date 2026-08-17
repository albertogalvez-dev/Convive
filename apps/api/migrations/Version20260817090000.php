<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260817090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Castilla y León as a fifth territorial protocol profile (#262)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        // Source verified directly against the Boletín Oficial de Castilla y
        // León, not a secondary aggregator: fetched and read the full
        // 12-page text of ORDEN EDU/1071/2017, de 1 de diciembre, por la que
        // se establece el "Protocolo específico de actuación en supuestos de
        // posible acoso en centros docentes, sostenidos con fondos públicos
        // que impartan enseñanzas no universitarias de la Comunidad de
        // Castilla y León". Published BOCYL núm. 238 (14 December 2017).
        // http://bocyl.jcyl.es/boletines/2017/12/14/pdf/BOCYL-D-14122017-3.pdf
        //
        // A prior, unverified research pass claimed "no explicit hour/day
        // deadline was found in the source text" for this territory. That is
        // wrong for the protocol's first phase, confirmed verbatim on full
        // read of the Anexo:
        // - PRIMERA FASE (conocer, identificar, parar la situación): "Las
        //   actuaciones incluidas en esta primera fase se llevarán a cabo en
        //   un plazo máximo de 48 horas."
        // - Within that, point 2.2: the school director convenes an
        //   assessment meeting "en un plazo máximo de 24 horas", attended by
        //   the affected students' tutor(es), the orientador/a and the
        //   coordinador/a de convivencia.
        // - Point 2.3: if bullying is confirmed, a 4-person "Comisión
        //   específica de acoso escolar" is constituted (director,
        //   orientador/a, coordinador/a de convivencia, and a teacher related
        //   to the affected pupils).
        // - Point 2.5: the director gives immediate ("traslado inmediato")
        //   notice of the meeting record and all relevant information to
        //   Inspección educativa, and informs the family, preserving
        //   confidentiality and the provisional nature of conclusions.
        // The prior claim of "no deadline" only holds for the later phases:
        // SEGUNDA FASE and TERCERA FASE both state explicitly that their
        // timeframe is "el mínimo necesario para garantizar su adecuado
        // diseño e implementación" -- no fixed number, honestly not modelled
        // as one here either.
        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_source_versions
    (id, code, version, title, uri, territory, authority, published_on, reviewed_on)
VALUES
    (
        '019c4c21-4fd4-7f6d-a0d1-000000000001',
        'ES-CL-ORDER-2017-12-01-ANEXO',
        'BOCYL-238-2017',
        'Castilla y León school bullying protocol (Orden EDU/1071/2017)',
        'http://bocyl.jcyl.es/boletines/2017/12/14/pdf/BOCYL-D-14122017-3.pdf',
        'ES-CL',
        'binding',
        '2017-12-14',
        '2026-08-17'
    )
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_task_templates (id, source_version_id, stage, kind, title, approved)
VALUES
    (
        '019ffe74-0000-7000-8000-000000000001',
        '019c4c21-4fd4-7f6d-a0d1-000000000001',
        'immediate_actions',
        'internal_action',
        'Track the fictional 48-hour phase-one (know/identify/stop) deadline.',
        true
    ),
    (
        '019ffe74-0000-7000-8000-000000000002',
        '019c4c21-4fd4-7f6d-a0d1-000000000001',
        'urgent_protection',
        'internal_action',
        'Confirm the fictional director-convened assessment meeting within 24 hours.',
        true
    ),
    (
        '019ffe74-0000-7000-8000-000000000003',
        '019c4c21-4fd4-7f6d-a0d1-000000000001',
        'professional_coordination',
        'internal_action',
        'Record the fictional constitution of the 4-person Comisión específica de acoso escolar.',
        true
    ),
    (
        '019ffe74-0000-7000-8000-000000000004',
        '019c4c21-4fd4-7f6d-a0d1-000000000001',
        'inspection_communication',
        'external_communication',
        'Confirm the fictional immediate notification to Inspección educativa.',
        true
    ),
    (
        '019ffe74-0000-7000-8000-000000000005',
        '019c4c21-4fd4-7f6d-a0d1-000000000001',
        'family_communication',
        'external_communication',
        'Confirm the fictional family notification alongside the Inspección notification.',
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

        $this->addSql("DELETE FROM case_workflow_task_templates WHERE source_version_id = '019c4c21-4fd4-7f6d-a0d1-000000000001'");
        $this->addSql("DELETE FROM case_workflow_source_versions WHERE id = '019c4c21-4fd4-7f6d-a0d1-000000000001'");
    }
}
