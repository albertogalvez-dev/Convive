<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Catalonia as a seventeenth territorial protocol profile (#273)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        // Source verified by reading the Departament's own published document
        // end to end, all 88 pages: "Protocol d'actuació davant de qualsevol
        // tipus de violència en l'àmbit educatiu", Departament d'Educació,
        // juliol 2024. The publication page records a last update of
        // 30.09.2024.
        // https://educacio.gencat.cat/ca/departament/publicacions/protocols/actuacio-davant-violencia-ambit-educatiu/
        //
        // Authority is 'binding' on the document's own words: "tots els
        // centres públics i també els privats sostinguts amb fons públics
        // (concertats) que conformen el servei d'educació de Catalunya han
        // d'aplicar aquest Protocol".
        //
        // It expressly replaces and subsumes the four earlier protocols,
        // including the "Protocol de prevenció, detecció i intervenció davant
        // l'assetjament i el ciberassetjament entre iguals" that the research
        // pass behind this issue had pointed at. Legal frame: Llei 12/2009
        // (LEC) arts. 33.1, 37.1, 142.9, 150.2; LOPIVI 8/2021 arts. 15, 16,
        // 30, 34; Decret 102/2010 arts. 19.1.e and 25.4; Decret 279/2006.
        //
        // WHAT THIS PROFILE COVERS, AND WHAT IT DOES NOT.
        // Per the decision recorded on the issue, only chapter 6, "Circuit
        // d'actuació", is modelled: detecció, valoració, comunicació and the
        // phases of intervenció educativa. Chapter 5, "Prevenció" -- the
        // projecte de convivència, provenció, sensibilització and continuous
        // preventive observation -- is left as unmodelled reference. It is
        // permanent programme work rather than steps in responding to a case,
        // and turning it into task templates would misrepresent what it is.
        //
        // Three scope limits the source itself sets, kept rather than
        // smoothed over:
        // - Violence exercised by staff or any adult goes to the maltractament
        //   infantil protocol, not this one.
        // - Violence in the family or any non-educational context likewise.
        // - Sexual violence between students follows chapter 7, which
        //   explicitly disapplies the equip de valoració, the fase diagnòstica
        //   and the fase de tipificació, and instead runs creure, protegir,
        //   derivar (to a Barnahus unit) and fer seguiment. Flattening that
        //   into the general circuit would push a child through an
        //   exploration the source forbids, so it is modelled as its own
        //   template rather than folded in.
        //
        // Deadlines, quoted exactly, and note how few are hard:
        // - A possible seriously harmful conduct must be put "immediatament en
        //   coneixement de la direcció del centre".
        // - The informe de cas: "Es RECOMANA que en un termini de 48 hores des
        //   de la detecció de la situació de violència, o de 72 hores en cas
        //   que s'hagin d'aplicar altres eines diagnòstiques a part de les
        //   entrevistes". This is a recommendation in the source, not an
        //   obligation, and the template says so. Convive cites what a
        //   protocol says; it must not promote a recommendation into a duty.
        // - Convening the families: "Convé no deixar passar més d'una setmana
        //   des de l'inici de la fase d'intervenció educativa".
        // - Follow-up with the student who exercised violence is suggested
        //   weekly in the first month and fortnightly in the second and third;
        //   with the student who suffered it, three times in the first week,
        //   twice in the second, once in the third, across three months.
        //
        // Structures: an equip de valoració of at most five or six
        // professionals, including as a minimum the tutor/a, the cap
        // d'estudis, the orientació or pedagogia terapèutica professional and
        // the coordinador/a de coeducació, convivència i benestar de
        // l'alumnat. Registration and the automated notification to Inspecció
        // both run through REVA.
        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_source_versions
    (id, code, version, title, uri, territory, authority, published_on, reviewed_on)
VALUES
    (
        '019c4c2b-4fd4-7f6d-a0d1-000000000001',
        'ES-CT-PROTOCOL-VIOLENCIA-2024-07',
        'CATALUNYA-2024-07',
        'Catalonia protocol for any type of violence in the educational sphere (July 2024)',
        'https://educacio.gencat.cat/ca/departament/publicacions/protocols/actuacio-davant-violencia-ambit-educatiu/',
        'ES-CT',
        'binding',
        '2024-07-01',
        '2026-08-18'
    )
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_task_templates (id, source_version_id, stage, kind, title, title_key, approved)
VALUES
    (
        '019ffe7f-0000-7000-8000-000000000001',
        '019c4c2b-4fd4-7f6d-a0d1-000000000001',
        'immediate_actions',
        'internal_action',
        'Confirma el registre ficticio de la situación en el REVA al activarse la alerta.',
        'caseWorkflow.template.es_ct.immediate_actions',
        true
    ),
    (
        '019ffe7f-0000-7000-8000-000000000002',
        '019c4c2b-4fd4-7f6d-a0d1-000000000001',
        'professional_coordination',
        'internal_action',
        'Registra la composición ficticia del equip de valoració, de cinco o seis profesionales como máximo.',
        'caseWorkflow.template.es_ct.professional_coordination',
        true
    ),
    (
        '019ffe7f-0000-7000-8000-000000000003',
        '019c4c2b-4fd4-7f6d-a0d1-000000000001',
        'information_collection',
        'internal_action',
        'Registra la fase diagnóstica ficticia: personas implicadas, tipo de violencia, temporalidad y entornos.',
        'caseWorkflow.template.es_ct.information_collection',
        true
    ),
    (
        '019ffe7f-0000-7000-8000-000000000004',
        '019c4c2b-4fd4-7f6d-a0d1-000000000001',
        'assessment',
        'internal_action',
        'Registra el informe de caso ficticio, que la fuente recomienda (no exige) en 48 horas, o 72 con otras herramientas.',
        'caseWorkflow.template.es_ct.assessment',
        true
    ),
    (
        '019ffe7f-0000-7000-8000-000000000005',
        '019c4c2b-4fd4-7f6d-a0d1-000000000001',
        'inspection_communication',
        'external_communication',
        'Confirma la comunicación ficticia a la Inspecció d''Educació, que el REVA genera de forma automatizada.',
        'caseWorkflow.template.es_ct.inspection_communication',
        true
    ),
    (
        '019ffe7f-0000-7000-8000-000000000006',
        '019c4c2b-4fd4-7f6d-a0d1-000000000001',
        'urgent_protection',
        'internal_action',
        'Registra el mapa de seguridad ficticio de urgencia y, después, el mapa de protección.',
        'caseWorkflow.template.es_ct.urgent_protection',
        true
    ),
    (
        '019ffe7f-0000-7000-8000-000000000007',
        '019c4c2b-4fd4-7f6d-a0d1-000000000001',
        'family_communication',
        'external_communication',
        'Registra la entrevista ficticia con las familias, que conviene no demorar más de una semana.',
        'caseWorkflow.template.es_ct.family_communication',
        true
    ),
    (
        '019ffe7f-0000-7000-8000-000000000008',
        '019c4c2b-4fd4-7f6d-a0d1-000000000001',
        'educational_measures',
        'internal_action',
        'Registra el plan de trabajo ficticio, distinto para cada parte implicada y para el grupo clase.',
        'caseWorkflow.template.es_ct.educational_measures',
        true
    ),
    (
        '019ffe7f-0000-7000-8000-000000000009',
        '019c4c2b-4fd4-7f6d-a0d1-000000000001',
        'action_plan',
        'internal_action',
        'Registra el reconocimiento ficticio del daño y, si procede, la práctica restaurativa, siempre voluntaria.',
        'caseWorkflow.template.es_ct.action_plan',
        true
    ),
    (
        '019ffe7f-0000-7000-8000-000000000010',
        '019c4c2b-4fd4-7f6d-a0d1-000000000001',
        'family_measures',
        'internal_action',
        'Confirma la derivación ficticia a Barnahus si hay violencia sexual: sin equip de valoració ni exploración.',
        'caseWorkflow.template.es_ct.family_measures',
        true
    ),
    (
        '019ffe7f-0000-7000-8000-000000000011',
        '019c4c2b-4fd4-7f6d-a0d1-000000000001',
        'inspection_follow_up',
        'internal_action',
        'Registra el seguimiento ficticio y el cierre, que la fuente condiciona a que el alumnado implicado lo manifieste.',
        'caseWorkflow.template.es_ct.inspection_follow_up',
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

        $this->addSql("DELETE FROM case_workflow_task_templates WHERE source_version_id = '019c4c2b-4fd4-7f6d-a0d1-000000000001'");
        $this->addSql("DELETE FROM case_workflow_source_versions WHERE id = '019c4c2b-4fd4-7f6d-a0d1-000000000001'");
    }
}
