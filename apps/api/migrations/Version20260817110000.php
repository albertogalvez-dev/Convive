<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260817110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Extremadura as a seventh territorial protocol profile (#264)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        // Source verified directly against the Consejería de Educación y
        // Empleo's own site (educarex.es), not a secondary aggregator:
        // fetched and read the full 88-page text of "Orientaciones
        // educativas para el plan de actuación en relación con las
        // alteraciones de la convivencia por acoso escolar en los centros
        // educativos. Los protocolos de intervención", produced by the
        // Servicio de Inspección General de Educación y Evaluación for the
        // Observatorio para la Convivencia Escolar de Extremadura.
        // https://www.educarex.es/pub/cont/com/0033/documentos/procolo_acoso.pdf
        //
        // Two corrections to the prior research pass, both confirmed on the
        // document's own text:
        // - It is dated **October 2016**, not December 2016 (page 2:
        //   "Octubre, 2016").
        // - It carries no DOE citation. It is an Inspección-produced
        //   guidance document, not a gazette-published decree or order, so
        //   authority is 'recommended' rather than 'binding'. (The binding
        //   instrument it defers to for disciplinary measures is Decreto
        //   50/2007, de 20 de marzo, DOE of 27 March 2007 -- a general
        //   rights-and-duties decree, not a bullying protocol, and not
        //   modelled here.)
        //
        // The issue also flagged that an update was reportedly in progress
        // to address cyberbullying and shorten intervention times. Checked:
        // as of August 2026 the Consejería is still revising the framework;
        // no superseding text has been published, so the October 2016
        // document remains the current one on educarex.es.
        //
        // The prior claim that no explicit deadline exists is confirmed
        // correct. The protocol's three phases (Detección, Observación y
        // análisis, Intervención) use only qualitative timing: "Se actuará
        // de manera inmediata, evitando dilaciones innecesarias" for the
        // observation phase, and "en el plazo más breve posible de tiempo"
        // for the Equipo de Valoración's analysis (B.5). No hour or day
        // figure appears anywhere, and none is invented here.
        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_source_versions
    (id, code, version, title, uri, territory, authority, published_on, reviewed_on)
VALUES
    (
        '019c4c23-4fd4-7f6d-a0d1-000000000001',
        'ES-EX-PROTOCOL-2016-10-EDUCAREX',
        'EDUCAREX-2016-10',
        'Extremadura school bullying intervention protocols (Orientaciones educativas, October 2016)',
        'https://www.educarex.es/pub/cont/com/0033/documentos/procolo_acoso.pdf',
        'ES-EX',
        'recommended',
        '2016-10-01',
        '2026-08-17'
    )
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_task_templates (id, source_version_id, stage, kind, title, approved)
VALUES
    (
        '019ffe76-0000-7000-8000-000000000001',
        '019c4c23-4fd4-7f6d-a0d1-000000000001',
        'immediate_actions',
        'internal_action',
        'Record the fictional equipo directivo decision on whether the protocol proceeds.',
        true
    ),
    (
        '019ffe76-0000-7000-8000-000000000002',
        '019c4c23-4fd4-7f6d-a0d1-000000000001',
        'urgent_protection',
        'internal_action',
        'Confirm the fictional preventive measures protecting the presumed victim.',
        true
    ),
    (
        '019ffe76-0000-7000-8000-000000000003',
        '019c4c23-4fd4-7f6d-a0d1-000000000001',
        'professional_coordination',
        'internal_action',
        'Record the fictional Equipo de Valoración constitution (equipo directivo, DO/EOEP, educador social and a teacher).',
        true
    ),
    (
        '019ffe76-0000-7000-8000-000000000004',
        '019c4c23-4fd4-7f6d-a0d1-000000000001',
        'inspection_communication',
        'external_communication',
        'Confirm the fictional initial notification to the Servicio de Inspección Educativa.',
        true
    ),
    (
        '019ffe76-0000-7000-8000-000000000005',
        '019c4c23-4fd4-7f6d-a0d1-000000000001',
        'family_communication',
        'external_communication',
        'Confirm the fictional first meeting informing both families of the situation.',
        true
    ),
    (
        '019ffe76-0000-7000-8000-000000000006',
        '019c4c23-4fd4-7f6d-a0d1-000000000001',
        'assessment',
        'internal_action',
        'Review the fictional Equipo de Valoración report concluding whether bullying is confirmed.',
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

        $this->addSql("DELETE FROM case_workflow_task_templates WHERE source_version_id = '019c4c23-4fd4-7f6d-a0d1-000000000001'");
        $this->addSql("DELETE FROM case_workflow_source_versions WHERE id = '019c4c23-4fd4-7f6d-a0d1-000000000001'");
    }
}
