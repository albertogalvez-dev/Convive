<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260817100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Cantabria as a sixth territorial protocol profile (#263)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        // Source verified directly against the Consejería de Educación y
        // Formación Profesional's own site (educantabria.es), not a
        // secondary aggregator: fetched and read the full 31-page text of
        // "Protocolo de actuación para los centros educativos ante una
        // posible situación de acoso escolar", issued by the Dirección
        // General de Innovación e Inspección Educativa. Multiple
        // independent sources (cantabria.es press coverage, third-party
        // child-protection archives) agree it was presented in September
        // 2016; none give the exact day, so published_on below records the
        // month with day 01 as a placeholder -- the day itself is not
        // confirmed. Unlike Aragón, Valencia, Castilla-La Mancha and
        // Castilla y León, this document carries no BOC citation, order or
        // resolution number: it is Consejería-issued procedural guidance
        // distributed directly from its own site, not a gazette-published
        // binding instrument, so authority is 'recommended' here rather
        // than 'binding'.
        // https://www.educantabria.es/documents/39930/385471/Protocolo+de+actuaci%C3%B3n+ante+una+posible+situaci%C3%B3n+de+acoso+escolar_.pdf/a4613219-c95e-db41-953d-4f3af21dcd44?t=1683636946944
        //
        // The prior research pass's claim of "no explicit deadline found in
        // the source text" is confirmed correct on full verification. Every
        // timing reference in the document is qualitative, never numeric:
        // "Constitución inmediata del Equipo de Valoración" (2.2),
        // "Comunicación inmediata ... al Servicio de Inspección Educativa y
        // a la Unidad de Convivencia" (2.3), and "en el plazo más breve
        // posible de tiempo" for the verification phase (3.2). No hour or
        // day figure appears anywhere in the 31 pages. Nothing numeric is
        // invented here either.
        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_source_versions
    (id, code, version, title, uri, territory, authority, published_on, reviewed_on)
VALUES
    (
        '019c4c22-4fd4-7f6d-a0d1-000000000001',
        'ES-CB-PROTOCOL-2016-09-EDUCANTABRIA',
        'EDUCANTABRIA-2016-09',
        'Cantabria school bullying protocol (Protocolo de actuación, September 2016)',
        'https://www.educantabria.es/documents/39930/385471/Protocolo+de+actuaci%C3%B3n+ante+una+posible+situaci%C3%B3n+de+acoso+escolar_.pdf/a4613219-c95e-db41-953d-4f3af21dcd44?t=1683636946944',
        'ES-CB',
        'recommended',
        '2016-09-01',
        '2026-08-17'
    )
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_task_templates (id, source_version_id, stage, kind, title, approved)
VALUES
    (
        '019ffe75-0000-7000-8000-000000000001',
        '019c4c22-4fd4-7f6d-a0d1-000000000001',
        'immediate_actions',
        'internal_action',
        'Confirm the fictional immediate constitution of the Equipo de Valoración.',
        true
    ),
    (
        '019ffe75-0000-7000-8000-000000000002',
        '019c4c22-4fd4-7f6d-a0d1-000000000001',
        'urgent_protection',
        'internal_action',
        'Confirm the fictional preventive protection and surveillance measures for the presumed victim.',
        true
    ),
    (
        '019ffe75-0000-7000-8000-000000000003',
        '019c4c22-4fd4-7f6d-a0d1-000000000001',
        'professional_coordination',
        'internal_action',
        'Record the fictional Equipo de Valoración composition (director, orientador/a, tutor/a and, where relevant, a Unidad de Convivencia representative).',
        true
    ),
    (
        '019ffe75-0000-7000-8000-000000000004',
        '019c4c22-4fd4-7f6d-a0d1-000000000001',
        'inspection_communication',
        'external_communication',
        'Confirm the fictional immediate notification to the Servicio de Inspección Educativa and the Unidad de Convivencia.',
        true
    ),
    (
        '019ffe75-0000-7000-8000-000000000005',
        '019c4c22-4fd4-7f6d-a0d1-000000000001',
        'family_communication',
        'external_communication',
        'Confirm the fictional notification to families that the protocol has been opened.',
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

        $this->addSql("DELETE FROM case_workflow_task_templates WHERE source_version_id = '019c4c22-4fd4-7f6d-a0d1-000000000001'");
        $this->addSql("DELETE FROM case_workflow_source_versions WHERE id = '019c4c22-4fd4-7f6d-a0d1-000000000001'");
    }
}
