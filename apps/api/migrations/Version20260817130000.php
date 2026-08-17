<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260817130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Asturias as a ninth territorial protocol profile (#266)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        // Source verified by reading the full 18-page text of the Circular
        // "por la que se regula la aplicación del protocolo de actuación
        // ante posibles situaciones de acoso y ciberacoso escolar en los
        // centros docentes no universitarios del Principado de Asturias",
        // signed in Oviedo on 30 January 2024 by the Consejera de
        // Educación, Lydia Espina López.
        //
        // Two corrections to the prior research pass, both confirmed on the
        // document's own text:
        // - The September 2022 version it cited is **superseded**. This
        //   circular's disposición duodécima states it "sustituye y deja sin
        //   efecto todas las anteriores relativas a la aplicación del
        //   protocolo", and its disposición undécima names the Circular de
        //   28 de septiembre de 2022 explicitly as the one that only
        //   survives for cases already open.
        // - The claim that no explicit deadline exists is **wrong**. The
        //   circular carries a full "Cronograma de actuaciones" annex of
        //   numeric deadlines, all in school days ("días lectivos"),
        //   confirmed verbatim --
        //   * 1 día lectivo from learning of the facts: notify Inspección
        //     Educativa that the protocol has been opened (1.1).
        //   * 2 días lectivos: convene the presumed victim's family and
        //     minute the meeting, and separately constitute the Equipo de
        //     seguimiento (1.2.1, 1.2.2).
        //   * 5 días lectivos: the information-gathering procedure among
        //     teaching staff, where the Equipo lacks direct knowledge
        //     (1.2.3).
        //   * 10 días lectivos: overall cap on Paso 1, ending with the
        //     estimatoria/desestimatoria decision sent to Inspección
        //     (1.2.8).
        //   * 22 días lectivos: cap on Paso 2 when the conclusion is that
        //     there is no bullying (2.6).
        //   * 27 días lectivos: cap on Paso 3 when bullying is confirmed,
        //     ending with the Informe SÍ Acoso and the plan de actuación
        //     (3.6).
        //   * 50 días lectivos: cap on Paso 5, the follow-up and evaluation
        //     report on the plan de actuación (5.4).
        //
        // It is a Consejería circular, not a BOPA-published decree, so
        // authority is 'recommended'. (Its disciplinary measures defer to
        // Decreto 249/2007, de 26 de septiembre, modified by Decreto 7/2019
        // -- a general rights-and-duties decree, not a bullying protocol,
        // and not modelled here.)
        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_source_versions
    (id, code, version, title, uri, territory, authority, published_on, reviewed_on)
VALUES
    (
        '019c4c25-4fd4-7f6d-a0d1-000000000001',
        'ES-AS-CIRCULAR-2024-01-30',
        'EDUCASTUR-2024-01',
        'Asturias school bullying and cyberbullying protocol circular (30 January 2024)',
        'https://www.educastur.es/-/instrucciones-de-aplicacion-del-protocolo-de-actuacion-ante-situaciones-de-posible-acoso-escolar',
        'ES-AS',
        'recommended',
        '2024-01-30',
        '2026-08-17'
    )
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_task_templates (id, source_version_id, stage, kind, title, approved)
VALUES
    (
        '019ffe78-0000-7000-8000-000000000001',
        '019c4c25-4fd4-7f6d-a0d1-000000000001',
        'inspection_communication',
        'external_communication',
        'Track the fictional 1-school-day deadline to notify Inspección Educativa that the protocol is open.',
        true
    ),
    (
        '019ffe78-0000-7000-8000-000000000002',
        '019c4c25-4fd4-7f6d-a0d1-000000000001',
        'family_communication',
        'external_communication',
        'Track the fictional 2-school-day deadline to convene the affected student''s family.',
        true
    ),
    (
        '019ffe78-0000-7000-8000-000000000003',
        '019c4c25-4fd4-7f6d-a0d1-000000000001',
        'urgent_protection',
        'internal_action',
        'Confirm the fictional urgent protection measures recorded in the acta inicial.',
        true
    ),
    (
        '019ffe78-0000-7000-8000-000000000004',
        '019c4c25-4fd4-7f6d-a0d1-000000000001',
        'professional_coordination',
        'internal_action',
        'Track the fictional 2-school-day deadline to constitute the Equipo de seguimiento.',
        true
    ),
    (
        '019ffe78-0000-7000-8000-000000000005',
        '019c4c25-4fd4-7f6d-a0d1-000000000001',
        'information_collection',
        'internal_action',
        'Track the fictional 5-school-day teaching-staff information-gathering deadline.',
        true
    ),
    (
        '019ffe78-0000-7000-8000-000000000006',
        '019c4c25-4fd4-7f6d-a0d1-000000000001',
        'assessment',
        'internal_action',
        'Track the fictional 10-school-day deadline for the indicios valoración sent to Inspección.',
        true
    ),
    (
        '019ffe78-0000-7000-8000-000000000007',
        '019c4c25-4fd4-7f6d-a0d1-000000000001',
        'action_plan',
        'internal_action',
        'Track the fictional 27-school-day deadline for the plan de actuación when bullying is confirmed.',
        true
    ),
    (
        '019ffe78-0000-7000-8000-000000000008',
        '019c4c25-4fd4-7f6d-a0d1-000000000001',
        'inspection_follow_up',
        'external_communication',
        'Track the fictional 50-school-day deadline for the plan de actuación follow-up report.',
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

        $this->addSql("DELETE FROM case_workflow_task_templates WHERE source_version_id = '019c4c25-4fd4-7f6d-a0d1-000000000001'");
        $this->addSql("DELETE FROM case_workflow_source_versions WHERE id = '019c4c25-4fd4-7f6d-a0d1-000000000001'");
    }
}
