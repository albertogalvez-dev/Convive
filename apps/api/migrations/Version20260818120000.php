<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Murcia as a twelfth territorial protocol profile (#270)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        // Source verified directly against the Consejería's own published
        // instrument, not a secondary aggregator: fetched and read the full
        // 27-page text of the Resolución de 13 de noviembre de 2017, de la
        // Dirección General de Atención a la Diversidad y Calidad Educativa,
        // por la que se dictan instrucciones para la mejora de la convivencia
        // escolar en los centros educativos no universitarios sostenidos con
        // fondos públicos de la Comunidad Autónoma de la Región de Murcia.
        // Signed electronically by Mª Esperanza Moreno Reventós.
        // https://www.carm.es/web/pagina?IDCONTENIDO=4105&IDTIPO=100
        //
        // Authority is 'binding': the resolution dictates instructions to
        // centres, states its own "entrada en vigor" the day after
        // publication, and expressly repeals the Resolución de 4 de abril de
        // 2006. It develops Decreto 16/2016, de 9 de marzo, which supplies
        // the disciplinary frame (arts. 18.1, 19, 35 and 45).
        //
        // Real, verified deadlines:
        // - Anyone in the school community who learns of a case "tiene la
        //   obligación de ponerla inmediatamente en conocimiento del equipo
        //   directivo". The resolution adds, uniquely among the territories
        //   modelled so far, that failing to do so "podría ser constitutivo
        //   de ilícito penal".
        // - The director acts "de forma inmediata" to open the diligencias
        //   and designate an equipo de intervención; the jefe de estudios
        //   holds a first coordination meeting "con carácter inmediato"; and
        //   Anexo I goes "de forma inmediata" to Inspección de Educación and
        //   the Servicio de Ordenación Académica.
        // - The whole investigation and its report are capped: "no pudiendo
        //   exceder los 20 días lectivos como máximo", counted from the date
        //   Anexo I was communicated.
        // - Case documentation is kept "al menos durante dos cursos
        //   académicos".
        //
        // Note recorded deliberately rather than acted on: press reporting
        // from May 2026 says the Consejería is "ultimando el texto" of a new
        // Decreto de Convivencia Escolar that would toughen the sanctioning
        // regime. It is not approved or published, so the 2017 resolution
        // remains the governing text and is what this profile cites. This
        // needs rechecking before launch.
        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_source_versions
    (id, code, version, title, uri, territory, authority, published_on, reviewed_on)
VALUES
    (
        '019c4c28-4fd4-7f6d-a0d1-000000000001',
        'ES-MC-RESOLUTION-2017-11-13',
        'MURCIA-2017-11-13',
        'Murcia school coexistence instructions (Resolución de 13 de noviembre de 2017)',
        'https://www.carm.es/web/pagina?IDCONTENIDO=4105&IDTIPO=100',
        'ES-MC',
        'binding',
        '2017-11-13',
        '2026-08-18'
    )
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_task_templates (id, source_version_id, stage, kind, title, approved)
VALUES
    (
        '019ffe7b-0000-7000-8000-000000000001',
        '019c4c28-4fd4-7f6d-a0d1-000000000001',
        'immediate_actions',
        'internal_action',
        'Confirm the fictional immediate opening of the diligencias and designation of the equipo de intervención.',
        true
    ),
    (
        '019ffe7b-0000-7000-8000-000000000002',
        '019c4c28-4fd4-7f6d-a0d1-000000000001',
        'professional_coordination',
        'internal_action',
        'Record the fictional first coordination meeting held by the jefatura de estudios with the equipo de intervención.',
        true
    ),
    (
        '019ffe7b-0000-7000-8000-000000000003',
        '019c4c28-4fd4-7f6d-a0d1-000000000001',
        'urgent_protection',
        'internal_action',
        'Confirm the fictional urgent protection measures adopted for the affected student.',
        true
    ),
    (
        '019ffe7b-0000-7000-8000-000000000004',
        '019c4c28-4fd4-7f6d-a0d1-000000000001',
        'inspection_communication',
        'external_communication',
        'Confirm the fictional immediate submission of Anexo I to Inspección de Educación and the Servicio de Ordenación Académica.',
        true
    ),
    (
        '019ffe7b-0000-7000-8000-000000000005',
        '019c4c28-4fd4-7f6d-a0d1-000000000001',
        'information_collection',
        'internal_action',
        'Record the fictional interviews with the affected student, non-participating observers and both families.',
        true
    ),
    (
        '019ffe7b-0000-7000-8000-000000000006',
        '019c4c28-4fd4-7f6d-a0d1-000000000001',
        'assessment',
        'internal_action',
        'Track the fictional 20-school-day maximum for the investigation and its report, counted from the Anexo I communication.',
        true
    ),
    (
        '019ffe7b-0000-7000-8000-000000000007',
        '019c4c28-4fd4-7f6d-a0d1-000000000001',
        'family_communication',
        'external_communication',
        'Confirm the fictional in-person communication of the outcome to the families, with a copy of Anexo V.',
        true
    ),
    (
        '019ffe7b-0000-7000-8000-000000000008',
        '019c4c28-4fd4-7f6d-a0d1-000000000001',
        'inspection_follow_up',
        'internal_action',
        'Record the fictional Plan de Seguimiento Sistemático, which the source requires whatever the outcome.',
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

        $this->addSql("DELETE FROM case_workflow_task_templates WHERE source_version_id = '019c4c28-4fd4-7f6d-a0d1-000000000001'");
        $this->addSql("DELETE FROM case_workflow_source_versions WHERE id = '019c4c28-4fd4-7f6d-a0d1-000000000001'");
    }
}
