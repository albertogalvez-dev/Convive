<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add La Rioja as a tenth territorial protocol profile (#267)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        // Source verified directly against the Boletín Oficial de La Rioja,
        // not a secondary aggregator: fetched and read the full 76-page text
        // of Resolución 126/2023, de 26 de junio, de la Consejería de
        // Educación, Cultura, Deporte y Juventud, por la que se actualiza el
        // Protocolo de actuación en casos de acoso escolar en los centros
        // educativos sostenidos con fondos públicos de la Comunidad Autónoma
        // de La Rioja. Published BOR núm. 131 (28 June 2023), page 13022,
        // reference III.2456, signed in Logroño on 26 June 2023 by Consejero
        // Pedro María Uruñuela Nájera. Effective the day after publication.
        // Authority is 'binding': "Estas medidas serán de aplicación y
        // obligado cumplimiento en todos los centros docentes públicos y
        // privados sostenidos con fondos públicos" (Resuelve, Segundo).
        // https://www.larioja.org/edu-aten-diversidad/es/protocolos/protocolo-acoso-escolar.ficheros/1493318-Acoso%20escolar%20actualizado.pdf
        //
        // The issue asked whether this had since been aligned to the April
        // 2026 national framework protocol. Verified: as of 18 August 2026,
        // the version the Consejería publishes on its own protocol page is
        // still this 2023 resolution. No later alignment instrument is
        // published there, so this remains the governing text.
        //
        // The prior research pass gave no deadline information for this
        // territory. The protocol does set them, confirmed verbatim --
        // - Constitute the Comisión de Valoración Urgente de la Convivencia
        //   "en un plazo no superior a 24 horas hábiles desde la recepción de
        //   la notificación" (Fase 1, punto 2). The notification is given a
        //   registro de entrada with date and time, plus acuse de recibo.
        // - Anexo I "Cronograma de actuación" places, in school days
        //   ("días lectivos"): the whole of Fase 1 on the 1st day, the
        //   information-gathering of Fase 2 on days 3 to 10, and the analysis
        //   plus the Plan de Actuación of Fase 3 on days 11 to 15. Follow-up
        //   (Fase 5) is left as "tiempo a determinar tras el proceso".
        //
        // One honesty note carried into the template wording: the cronograma
        // itself states "*Las fechas establecidas son aproximadas y figuran
        // como máximo. Puede haber procesos que se desarrollen en menos
        // tiempo." They are ceilings, not schedules, and are recorded here as
        // reference text only.
        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_source_versions
    (id, code, version, title, uri, territory, authority, published_on, reviewed_on)
VALUES
    (
        '019c4c26-4fd4-7f6d-a0d1-000000000001',
        'ES-RI-RESOLUTION-2023-06-26',
        'BOR-131-2023',
        'La Rioja school bullying protocol (Resolución 126/2023)',
        'https://www.larioja.org/edu-aten-diversidad/es/protocolos/protocolo-acoso-escolar.ficheros/1493318-Acoso%20escolar%20actualizado.pdf',
        'ES-RI',
        'binding',
        '2023-06-28',
        '2026-08-18'
    )
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_task_templates (id, source_version_id, stage, kind, title, approved)
VALUES
    (
        '019ffe79-0000-7000-8000-000000000001',
        '019c4c26-4fd4-7f6d-a0d1-000000000001',
        'immediate_actions',
        'internal_action',
        'Track the fictional 24-working-hour deadline to constitute the Comisión de Valoración Urgente de la Convivencia.',
        true
    ),
    (
        '019ffe79-0000-7000-8000-000000000002',
        '019c4c26-4fd4-7f6d-a0d1-000000000001',
        'urgent_protection',
        'internal_action',
        'Confirm the fictional urgent protection measures recorded for the affected student.',
        true
    ),
    (
        '019ffe79-0000-7000-8000-000000000003',
        '019c4c26-4fd4-7f6d-a0d1-000000000001',
        'professional_coordination',
        'internal_action',
        'Record the fictional Comisión de Valoración Urgente composition, including the coordinación de convivencia, bienestar y protección a la infancia.',
        true
    ),
    (
        '019ffe79-0000-7000-8000-000000000004',
        '019c4c26-4fd4-7f6d-a0d1-000000000001',
        'inspection_communication',
        'external_communication',
        'Confirm the fictional first-day notification to Inspección Educativa and the Comisión de Convivencia.',
        true
    ),
    (
        '019ffe79-0000-7000-8000-000000000005',
        '019c4c26-4fd4-7f6d-a0d1-000000000001',
        'family_communication',
        'external_communication',
        'Confirm the fictional urgent summons to the families of the parties involved.',
        true
    ),
    (
        '019ffe79-0000-7000-8000-000000000006',
        '019c4c26-4fd4-7f6d-a0d1-000000000001',
        'information_collection',
        'internal_action',
        'Track the fictional day 3 to day 10 school-day window for gathering information.',
        true
    ),
    (
        '019ffe79-0000-7000-8000-000000000007',
        '019c4c26-4fd4-7f6d-a0d1-000000000001',
        'action_plan',
        'internal_action',
        'Track the fictional day 11 to day 15 school-day window for the assessment and the Plan de Actuación.',
        true
    ),
    (
        '019ffe79-0000-7000-8000-000000000008',
        '019c4c26-4fd4-7f6d-a0d1-000000000001',
        'inspection_follow_up',
        'internal_action',
        'Record the fictional follow-up, which the source leaves without a fixed deadline.',
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

        $this->addSql("DELETE FROM case_workflow_task_templates WHERE source_version_id = '019c4c26-4fd4-7f6d-a0d1-000000000001'");
        $this->addSql("DELETE FROM case_workflow_source_versions WHERE id = '019c4c26-4fd4-7f6d-a0d1-000000000001'");
    }
}
