<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Madrid as an eleventh territorial protocol profile (#269)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        // Source verified directly against the Consejería's own publication,
        // not a secondary aggregator: fetched and read the full text of
        // "Protocolo de actuación ante una situación de posible riesgo,
        // sospecha o evidencia de acoso escolar en los alumnos de los centros
        // educativos no universitarios", issued by the Consejería de
        // Educación, Ciencia y Universidades, Viceconsejería de Política y
        // Organización Educativa, Unidad de convivencia y contra el acoso
        // escolar. Dated February 2026 and, on its own cover, "Actualizado el
        // 30 de abril de 2026" -- the most recent territorial source in this
        // sequence.
        // https://www.educa2.madrid.org/web/convivencia/acoso-escolar
        //
        // Authority is 'recommended' on the document's own framing: it "tiene
        // como objetivo facilitar ideas y estrategias", and points to a
        // separate "Plan de prevención" as the piece that is "de obligado
        // cumplimiento". The binding frame it implements is article 35.3 of
        // Ley 4/2023, de 22 de marzo, de Derechos, Garantías y Protección
        // Integral de la Infancia y la Adolescencia de la Comunidad de
        // Madrid, with Decreto 32/2019, de 9 de abril, governing the
        // disciplinary procedure that runs alongside it.
        //
        // Real, verified deadlines -- and one distinction that matters:
        // - Opening the protocol triggers the "apertura inmediata" of a Plan
        //   individualizado de protección, and immediate communication to the
        //   Servicio de Inspección Educativa (SIE) and the Unidad de
        //   convivencia, both through the Raíces platform.
        // - The director "de manera inmediata designará a un único docente"
        //   who has a "plazo máximo de quince días lectivos" to gather the
        //   information needed to analyse the situation. School days.
        // - Where the evidence is inconclusive, a further observation period
        //   runs for a "plazo máximo de quince días" -- stated without
        //   "lectivos", so it is deliberately recorded here as calendar days
        //   rather than silently harmonised with the fifteen school days
        //   above.
        //
        // On the question this issue raised about the "Unidad de Convivencia":
        // it is a Consejería-level body (Unidad de convivencia y contra el
        // acoso escolar) that receives notifications alongside the SIE, not a
        // school role. It does not map onto the coordinador de bienestar y
        // protección, which the same protocol names separately as a school
        // figure. Both are modelled as distinct below.
        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_source_versions
    (id, code, version, title, uri, territory, authority, published_on, reviewed_on)
VALUES
    (
        '019c4c27-4fd4-7f6d-a0d1-000000000001',
        'ES-MD-PROTOCOL-2026-04-30',
        'MADRID-2026-04-30',
        'Madrid school bullying protocol (Consejería de Educación, updated 30 April 2026)',
        'https://www.educa2.madrid.org/web/convivencia/acoso-escolar',
        'ES-MD',
        'recommended',
        '2026-04-30',
        '2026-08-18'
    )
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_task_templates (id, source_version_id, stage, kind, title, approved)
VALUES
    (
        '019ffe7a-0000-7000-8000-000000000001',
        '019c4c27-4fd4-7f6d-a0d1-000000000001',
        'immediate_actions',
        'internal_action',
        'Confirm the fictional immediate opening of the Plan individualizado de protección for the student.',
        true
    ),
    (
        '019ffe7a-0000-7000-8000-000000000002',
        '019c4c27-4fd4-7f6d-a0d1-000000000001',
        'urgent_protection',
        'internal_action',
        'Record the fictional obligatory protection measures selected for the student.',
        true
    ),
    (
        '019ffe7a-0000-7000-8000-000000000003',
        '019c4c27-4fd4-7f6d-a0d1-000000000001',
        'inspection_communication',
        'external_communication',
        'Confirm the fictional notification to the Servicio de Inspección Educativa and the Unidad de convivencia y contra el acoso escolar.',
        true
    ),
    (
        '019ffe7a-0000-7000-8000-000000000004',
        '019c4c27-4fd4-7f6d-a0d1-000000000001',
        'professional_coordination',
        'internal_action',
        'Record the fictional designation of the single teacher tasked with gathering the information.',
        true
    ),
    (
        '019ffe7a-0000-7000-8000-000000000005',
        '019c4c27-4fd4-7f6d-a0d1-000000000001',
        'information_collection',
        'internal_action',
        'Track the fictional 15-school-day maximum for gathering information to analyse the situation.',
        true
    ),
    (
        '019ffe7a-0000-7000-8000-000000000006',
        '019c4c27-4fd4-7f6d-a0d1-000000000001',
        'family_communication',
        'external_communication',
        'Confirm the fictional in-person communication to the family, noting the article 32.7 exception where a family member may be the aggressor.',
        true
    ),
    (
        '019ffe7a-0000-7000-8000-000000000007',
        '019c4c27-4fd4-7f6d-a0d1-000000000001',
        'assessment',
        'internal_action',
        'Track the fictional 15-calendar-day maximum for further observation where the evidence is inconclusive.',
        true
    ),
    (
        '019ffe7a-0000-7000-8000-000000000008',
        '019c4c27-4fd4-7f6d-a0d1-000000000001',
        'action_plan',
        'internal_action',
        'Record the fictional Plan individualizado de intervención agreed for the students involved.',
        true
    ),
    (
        '019ffe7a-0000-7000-8000-000000000009',
        '019c4c27-4fd4-7f6d-a0d1-000000000001',
        'inspection_follow_up',
        'external_communication',
        'Confirm the fictional closure record sent to the Servicio de Inspección Educativa and the Unidad de convivencia.',
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

        $this->addSql("DELETE FROM case_workflow_task_templates WHERE source_version_id = '019c4c27-4fd4-7f6d-a0d1-000000000001'");
        $this->addSql("DELETE FROM case_workflow_source_versions WHERE id = '019c4c27-4fd4-7f6d-a0d1-000000000001'");
    }
}
