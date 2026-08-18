<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Ceuta and Melilla as territorial protocol profiles (#276)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        // Source verified against the signed ministerial original, read in
        // full: "Resolución de la Dirección General de Planificación y
        // Gestión Educativa por la que se establece un protocolo específico
        // de actuación en supuestos de posible acoso escolar y/o ciberacoso
        // en centros docentes sostenidos con fondos públicos que impartan
        // enseñanzas no universitarias en las ciudades de Ceuta y Melilla".
        // Ministerio de Educación, Formación Profesional y Deportes, signed
        // electronically by María del Ángel Muñoz Muñoz on 8 August 2024
        // (CSV GEN-76ed-00bd-b4f3-f1ba-3344-36c0-af46-7972). It "surtirá
        // efectos el día siguiente de su firma", hence 9 August 2024.
        // https://www.educacionfpydeportes.gob.es/dam/jcr:42b6494e-3822-46e1-9c80-bda5374f8ecb/resolucion-acoso-escolar.pdf
        //
        // A union-hosted copy of this protocol circulates online and was the
        // first hit; it was not used. The text modelled here is the signed
        // ministerial PDF with a verifiable CSV.
        //
        // Authority is 'binding' for publicly funded centres. The resolution
        // itself notes that for private centres it "podrá ser de aplicación
        // supletoria" where they have no procedure of their own, which is a
        // limit on reach rather than on force, so it is not modelled as a
        // weaker authority.
        //
        // Why two source rows for one document: Ceuta and Melilla are
        // separate territories under a single instrument. A source version
        // carries exactly one territory, so the same resolution is recorded
        // once per city rather than inventing a shared pseudo-territory.
        // Both rows cite the identical URI and date.
        //
        // Real, verified deadlines, all in school days:
        // - Opening the protocol and constituting the equipo de valoración e
        //   intervención: "en el plazo máximo de dos días lectivos a partir
        //   del día siguiente de la notificación recibida a través del anexo
        //   I".
        // - First communication to the families: "en un plazo máximo de dos
        //   días lectivos desde la constitución del equipo".
        // - Gathering information: "un periodo máximo de cinco días
        //   lectivos".
        // - The decision meeting: "tres días lectivos desde la devolución del
        //   informe de los docentes observadores".
        // - Follow-up meetings run "al menos con periodicidad mensual" and
        //   the case may stay open into the following school year if the
        //   situation is not resolved.
        //
        // Two safeguards in this source worth preserving because they are
        // unusual and both concern the people involved rather than paperwork:
        // - The team assigns an expediente code (centre code plus a running
        //   number) so that "en la sucesiva documentación generada no
        //   aparezcan los nombres del alumnado implicado".
        // - Staff who gathered the information "no podrá ser designado como
        //   instructor o instructora del expediente disciplinario".
        //
        // Also recorded because it decides a real question: a family's
        // refusal of consent does not stop the protocol -- the source says
        // the superior interest of the minors prevails and the process
        // continues.
        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_source_versions
    (id, code, version, title, uri, territory, authority, published_on, reviewed_on)
VALUES
    (
        '019c4c2a-4fd4-7f6d-a0d1-000000000001',
        'ES-CE-MEFPD-RESOLUTION-2024-08-08',
        'CEUTA-2024-08-08',
        'Ceuta and Melilla specific bullying and cyberbullying protocol (MEFPD resolution, 8 August 2024)',
        'https://www.educacionfpydeportes.gob.es/dam/jcr:42b6494e-3822-46e1-9c80-bda5374f8ecb/resolucion-acoso-escolar.pdf',
        'ES-CE',
        'binding',
        '2024-08-08',
        '2026-08-18'
    ),
    (
        '019c4c2a-4fd4-7f6d-a0d1-000000000002',
        'ES-ML-MEFPD-RESOLUTION-2024-08-08',
        'MELILLA-2024-08-08',
        'Ceuta and Melilla specific bullying and cyberbullying protocol (MEFPD resolution, 8 August 2024)',
        'https://www.educacionfpydeportes.gob.es/dam/jcr:42b6494e-3822-46e1-9c80-bda5374f8ecb/resolucion-acoso-escolar.pdf',
        'ES-ML',
        'binding',
        '2024-08-08',
        '2026-08-18'
    )
SQL);

        foreach ([
            ['019ffe7d', '019c4c2a-4fd4-7f6d-a0d1-000000000001'],
            ['019ffe7e', '019c4c2a-4fd4-7f6d-a0d1-000000000002'],
        ] as [$prefix, $sourceId]) {
            $this->addSql(<<<SQL
INSERT INTO case_workflow_task_templates (id, source_version_id, stage, kind, title, approved)
VALUES
    (
        '{$prefix}-0000-7000-8000-000000000001',
        '{$sourceId}',
        'immediate_actions',
        'internal_action',
        'Confirm the fictional observation measures put in place before the equipo de valoración e intervención is constituted.',
        true
    ),
    (
        '{$prefix}-0000-7000-8000-000000000002',
        '{$sourceId}',
        'professional_coordination',
        'internal_action',
        'Record the fictional constitution of the equipo de valoración e intervención, within two school days of the Anexo I notice.',
        true
    ),
    (
        '{$prefix}-0000-7000-8000-000000000003',
        '{$sourceId}',
        'urgent_protection',
        'internal_action',
        'Confirm the fictional initial safety measures for the affected student and any precautionary measures.',
        true
    ),
    (
        '{$prefix}-0000-7000-8000-000000000004',
        '{$sourceId}',
        'inspection_communication',
        'external_communication',
        'Confirm the fictional submission of Anexo I and Anexo II to the Dirección Provincial, which forwards them to Inspección and the Unidad de Convivencia.',
        true
    ),
    (
        '{$prefix}-0000-7000-8000-000000000005',
        '{$sourceId}',
        'family_communication',
        'external_communication',
        'Record the fictional first family interview, within two school days of the team being constituted.',
        true
    ),
    (
        '{$prefix}-0000-7000-8000-000000000006',
        '{$sourceId}',
        'information_collection',
        'internal_action',
        'Track the fictional five-school-day maximum for the designated teachers to gather information.',
        true
    ),
    (
        '{$prefix}-0000-7000-8000-000000000007',
        '{$sourceId}',
        'assessment',
        'internal_action',
        'Record the fictional decision meeting, within three school days of the observers returning their report.',
        true
    ),
    (
        '{$prefix}-0000-7000-8000-000000000008',
        '{$sourceId}',
        'inspection_follow_up',
        'internal_action',
        'Track the fictional intervention plan follow-up, which the source requires at least monthly.',
        true
    )
SQL);
        }
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql("DELETE FROM case_workflow_task_templates WHERE source_version_id IN ('019c4c2a-4fd4-7f6d-a0d1-000000000001', '019c4c2a-4fd4-7f6d-a0d1-000000000002')");
        $this->addSql("DELETE FROM case_workflow_source_versions WHERE id IN ('019c4c2a-4fd4-7f6d-a0d1-000000000001', '019c4c2a-4fd4-7f6d-a0d1-000000000002')");
    }
}
