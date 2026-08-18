<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the Basque Country as an eighteenth territorial protocol profile (#274)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        // Source verified by reading the signed original end to end, all 53
        // pages: "Resolución de la Viceconsejera de Educación sobre las
        // instrucciones que regulan la aplicación del Protocolo de actuación
        // ante situaciones de acoso escolar en los centros docentes no
        // universitarios de la CAPV y del Protocolo de intervención y
        // posvención de la conducta suicida en el ámbito educativo".
        // Departamento de Educación / Hezkuntza Saila, signed digitally by
        // María Begoña Pedrosa Lobato on 12 March 2024.
        // https://www.euskadi.eus/contenidos/informacion/hezkuntza_ikuskaritza_dok_lh/es_def/adjuntos/Resolucion-protocolos-de-acoso-y-conducta-suicida.pdf
        //
        // Authority is 'binding' on the resolution's own words: it applies to
        // "todos los centros docentes de la CAPV sostenidos con fondos
        // públicos" and "tiene un carácter básico y preceptivo". Legal frame:
        // Ley 17/2023, de 21 de diciembre, de Educación de la CAPV (arts. 3
        // and 31), Decreto 77/2023 (arts. 6 and 7) and Decreto 201/2008 on
        // students' rights and duties, which supplies the corrective
        // procedure.
        //
        // WHAT THIS PROFILE COVERS, AND WHAT IT DOES NOT.
        // Per the decision recorded on the issue, only the incident-response
        // chapter is modelled: the seven steps of the Protocolo de actuación
        // ante situaciones de acoso escolar.
        //
        // Left as unmodelled reference, deliberately:
        // - Bizikasi, the permanent programme the Departamento launched in
        //   2017-2018. It is standing positive-coexistence work with its own
        //   compulsory staff training, not steps in responding to a case.
        // - The Protocolo de intervención y posvención de la conducta
        //   suicida, established by the same resolution. It is a separate
        //   protocol with its own team, its own annexes and its own 22
        //   school-day deadline, and folding it into a bullying profile would
        //   misrepresent both.
        //
        // Real, verified deadlines, all in school days and all counted from a
        // named document:
        // - Paso 2: "un periodo, como máximo, de 5 días lectivos" from the
        //   Anexo 0 to the teaching-team meeting.
        // - Paso 3: "un nuevo periodo máximo de 15 días lectivos" to gather
        //   evidence and complete Informe A.
        // - Paso 5: "un máximo de 22 días lectivos desde que se emitió el
        //   Informe A" to send Informe B.
        //
        // A rule no other territory in this sequence states, and the reason
        // the deadlines above are modelled as hard rather than indicative:
        // "Los plazos indicados en cada paso deben ser respetados. Cuando la
        // directora o el director estime que no pueden cumplirse los plazos
        // por razones debidamente justificadas, deberá solicitar el visto
        // bueno de la Inspección de Educación." A deadline here slips only
        // with the inspectorate's approval.
        //
        // Two safeguards preserved because they concern a child rather than
        // paperwork:
        // - Confidentiality between families: "la dirección únicamente
        //   proporcionará a las familias información sobre sus hijas e hijos,
        //   nunca sobre el resto de alumnas y alumnos implicados", and the
        //   annexes identify students by initials only.
        // - Protection follows the child: if the affected student moves school
        //   before the case is closed, the Coordinadora de Bienestar y
        //   Protección transmits Informe B to the receiving school, so a
        //   transfer cannot quietly end the protection.
        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_source_versions
    (id, code, version, title, uri, territory, authority, published_on, reviewed_on)
VALUES
    (
        '019c4c2c-4fd4-7f6d-a0d1-000000000001',
        'ES-PV-RESOLUTION-2024-03-12',
        'EUSKADI-2024-03-12',
        'Basque Country school bullying protocol (Resolución de 12 de marzo de 2024)',
        'https://www.euskadi.eus/contenidos/informacion/hezkuntza_ikuskaritza_dok_lh/es_def/adjuntos/Resolucion-protocolos-de-acoso-y-conducta-suicida.pdf',
        'ES-PV',
        'binding',
        '2024-03-12',
        '2026-08-18'
    )
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_task_templates (id, source_version_id, stage, kind, title, title_key, approved)
VALUES
    (
        '019ffe80-0000-7000-8000-000000000001',
        '019c4c2c-4fd4-7f6d-a0d1-000000000001',
        'immediate_actions',
        'internal_action',
        'Registra la demanda de intervención ficticia en el Anexo 0 y las medidas de urgencia adoptadas.',
        'caseWorkflow.template.es_pv.immediate_actions',
        true
    ),
    (
        '019ffe80-0000-7000-8000-000000000002',
        '019c4c2c-4fd4-7f6d-a0d1-000000000001',
        'urgent_protection',
        'internal_action',
        'Confirma las medidas ficticias de protección del alumnado presuntamente acosado antes de recabar evidencias.',
        'caseWorkflow.template.es_pv.urgent_protection',
        true
    ),
    (
        '019ffe80-0000-7000-8000-000000000003',
        '019c4c2c-4fd4-7f6d-a0d1-000000000001',
        'professional_coordination',
        'internal_action',
        'Sigue el máximo ficticio de 5 días lectivos para la reunión del equipo docente con el Equipo BAT.',
        'caseWorkflow.template.es_pv.professional_coordination',
        true
    ),
    (
        '019ffe80-0000-7000-8000-000000000004',
        '019c4c2c-4fd4-7f6d-a0d1-000000000001',
        'information_collection',
        'internal_action',
        'Sigue el máximo ficticio de 15 días lectivos para recabar evidencias y completar el Informe A.',
        'caseWorkflow.template.es_pv.information_collection',
        true
    ),
    (
        '019ffe80-0000-7000-8000-000000000005',
        '019c4c2c-4fd4-7f6d-a0d1-000000000001',
        'family_communication',
        'external_communication',
        'Registra la comunicación ficticia a las familias, por separado y sin datos de otro alumnado.',
        'caseWorkflow.template.es_pv.family_communication',
        true
    ),
    (
        '019ffe80-0000-7000-8000-000000000006',
        '019c4c2c-4fd4-7f6d-a0d1-000000000001',
        'inspection_communication',
        'external_communication',
        'Confirma el envío ficticio del Informe A a la Inspección de Educación y a los Servicios de Apoyo.',
        'caseWorkflow.template.es_pv.inspection_communication',
        true
    ),
    (
        '019ffe80-0000-7000-8000-000000000007',
        '019c4c2c-4fd4-7f6d-a0d1-000000000001',
        'assessment',
        'internal_action',
        'Registra la estimación ficticia sobre si existe acoso, argumentando los cuatro criterios del Informe A.',
        'caseWorkflow.template.es_pv.assessment',
        true
    ),
    (
        '019ffe80-0000-7000-8000-000000000008',
        '019c4c2c-4fd4-7f6d-a0d1-000000000001',
        'educational_measures',
        'internal_action',
        'Registra las medidas educativas ficticias cuando el Informe A no aprecia acoso.',
        'caseWorkflow.template.es_pv.educational_measures',
        true
    ),
    (
        '019ffe80-0000-7000-8000-000000000009',
        '019c4c2c-4fd4-7f6d-a0d1-000000000001',
        'action_plan',
        'internal_action',
        'Sigue el máximo ficticio de 22 días lectivos desde el Informe A para enviar el Informe B.',
        'caseWorkflow.template.es_pv.action_plan',
        true
    ),
    (
        '019ffe80-0000-7000-8000-000000000010',
        '019c4c2c-4fd4-7f6d-a0d1-000000000001',
        'family_measures',
        'internal_action',
        'Confirma el traslado ficticio del Informe B al nuevo centro si el alumnado cambia antes del cierre.',
        'caseWorkflow.template.es_pv.family_measures',
        true
    ),
    (
        '019ffe80-0000-7000-8000-000000000011',
        '019c4c2c-4fd4-7f6d-a0d1-000000000001',
        'inspection_follow_up',
        'internal_action',
        'Registra el acta ficticia de finalización, que solo procede si la situación está reconducida.',
        'caseWorkflow.template.es_pv.inspection_follow_up',
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

        $this->addSql("DELETE FROM case_workflow_task_templates WHERE source_version_id = '019c4c2c-4fd4-7f6d-a0d1-000000000001'");
        $this->addSql("DELETE FROM case_workflow_source_versions WHERE id = '019c4c2c-4fd4-7f6d-a0d1-000000000001'");
    }
}
