<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Galicia as a nineteenth territorial protocol profile (#275)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        // Source verified by reading the Consellería's own published document
        // end to end, all 80 pages: "Protocolo educativo para a prevención, a
        // detección e o tratamento do acoso e do ciberacoso escolar", Xunta de
        // Galicia, published on the educonvives.gal portal.
        // https://www.edu.xunta.gal/portal/sites/web/files/protocolo_educativo_para_a_prevencion_deteccion_e_tratamento_do_acoso_e_ciberacoso_escolarpdf.pdf
        //
        // ABOUT published_on: the Consellería publishes this document
        // UNDATED. There is no date on the cover, in the colophon or in the
        // PDF metadata. The date recorded here is therefore a FLOOR, not a
        // publication date: the protocol cites the Orde do 23 de agosto de
        // 2023 and the Estratexia Galega de Convivencia Escolar 2025, so it
        // cannot predate 23 August 2023. reviewed_on is the date Convive
        // actually verified the text, which is the only date here that can be
        // vouched for.
        //
        // AUTHORITY: recorded as 'recommended', and this is a deliberate
        // distinction rather than caution. Galicia's binding obligations live
        // in Lei 4/2011, do 30 de xuño (art. 7, the right to integral
        // protection; art. 28, the legal definition of acoso; art. 30.2.c,
        // the person responsible for attending to the victim) and in Decreto
        // 8/2015, do 8 de xaneiro (art. 12, every plan de convivencia must
        // facilitate prevention and elimination of violence; art. 39, the
        // corrective measures; art. 46.1, the corrective procedure). This
        // document is the Consellería's protocol implementing them, but --
        // unlike Murcia, Catalonia or the Basque Country -- its own text
        // never declares itself preceptivo or de obrigado cumprimento. It is
        // cited as what it is.
        //
        // THE FINDING THAT MATTERS MOST FOR CONVIVE: this protocol sets NO
        // numeric deadlines of its own. Every other territory modelled so far
        // states its own maximum in days or hours. Galicia says "o antes
        // posible" and then defers: "No caso de que a conduta que orixina
        // esta intervención sexa considerada gravemente prexudicial para a
        // convivencia, os prazos axustaranse aos que a normativa impón para
        // instruír un procedemento corrector". The templates below therefore
        // quote no day counts, because inventing one would be Convive
        // deciding a deadline the source deliberately leaves to the
        // corrective procedure.
        //
        // The single numeric temporalización in the whole document is the
        // follow-up, and the source calls it "a temporalización proposta":
        // fortnightly in the first month, monthly from the second. It is
        // recorded as proposed, not as a duty.
        //
        // PUNTO LARANXA -- OUT OF SCOPE, per the decision recorded on the
        // issue. The source defines it as an "espazo físico e virtual onde
        // calquera persoa coñecedora dunha situación de posible acoso poderá
        // comunicalo", and lists it alongside a caixa de socorro, a taboleiro
        // de denuncias, help mailboxes and the school website as channels a
        // centre may create. It is a school-side reporting channel, not a
        // step in responding to a case. Convive neither represents it nor
        // implements it, and this migration does not touch the public
        // reporting entry point. The one place it legitimately touches the
        // response is the equipo ACAE's duty of "revisión diaria e filtraxe
        // das posibles denuncias", which is why the first template mentions
        // reviewing whatever channel the school uses without naming Convive
        // as that channel.
        //
        // Two protective rules kept verbatim in substance because they decide
        // what happens to a child:
        // - Separation falls on the aggressor, not the victim: "Nun principio,
        //   o cambio debe afectar a este último, a non ser que a vítima pida
        //   expresamente o contrario", and the protocol lists "a solución é
        //   cambiar a vítima de centro educativo" among its falsos mitos.
        // - Families are not informed during the investigation phase, but
        //   from the apertura onwards communication and citation are
        //   obligatory for implicated minors. Getting that order backwards
        //   would either breach the source or leave families uninformed once
        //   the protocol is open.
        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_source_versions
    (id, code, version, title, uri, territory, authority, published_on, reviewed_on)
VALUES
    (
        '019c4c2d-4fd4-7f6d-a0d1-000000000001',
        'ES-GA-EDUCONVIVES-ACAE',
        'GALICIA-EDUCONVIVES-ACAE',
        'Galicia bullying and cyberbullying protocol (Consellería de Educación, educonvives.gal)',
        'https://www.edu.xunta.gal/portal/sites/web/files/protocolo_educativo_para_a_prevencion_deteccion_e_tratamento_do_acoso_e_ciberacoso_escolarpdf.pdf',
        'ES-GA',
        'recommended',
        '2023-08-23',
        '2026-08-18'
    )
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_task_templates (id, source_version_id, stage, kind, title, title_key, approved)
VALUES
    (
        '019ffe81-0000-7000-8000-000000000001',
        '019c4c2d-4fd4-7f6d-a0d1-000000000001',
        'immediate_actions',
        'internal_action',
        'Registra la comunicación ficticia de los hechos en el Anexo 1, firmada por la dirección del centro.',
        'caseWorkflow.template.es_ga.immediate_actions',
        true
    ),
    (
        '019ffe81-0000-7000-8000-000000000002',
        '019c4c2d-4fd4-7f6d-a0d1-000000000001',
        'professional_coordination',
        'internal_action',
        'Registra la constitución ficticia del equipo ACAE para este caso, con un mínimo de tres personas del claustro.',
        'caseWorkflow.template.es_ga.professional_coordination',
        true
    ),
    (
        '019ffe81-0000-7000-8000-000000000003',
        '019c4c2d-4fd4-7f6d-a0d1-000000000001',
        'assessment',
        'internal_action',
        'Registra la primera valoración ficticia del Anexo 3: si hay indicios, procede abrir investigación.',
        'caseWorkflow.template.es_ga.assessment',
        true
    ),
    (
        '019ffe81-0000-7000-8000-000000000004',
        '019c4c2d-4fd4-7f6d-a0d1-000000000001',
        'information_collection',
        'internal_action',
        'Registra la investigación ficticia: observación en zonas de riesgo y entrevistas a todas las partes.',
        'caseWorkflow.template.es_ga.information_collection',
        true
    ),
    (
        '019ffe81-0000-7000-8000-000000000005',
        '019c4c2d-4fd4-7f6d-a0d1-000000000001',
        'urgent_protection',
        'internal_action',
        'Confirma las medidas inmediatas ficticias: al separar, el cambio recae en quien acosa, salvo petición expresa de la víctima.',
        'caseWorkflow.template.es_ga.urgent_protection',
        true
    ),
    (
        '019ffe81-0000-7000-8000-000000000006',
        '019c4c2d-4fd4-7f6d-a0d1-000000000001',
        'educational_measures',
        'internal_action',
        'Registra las medidas educativas ficticias de las NOFC cuando el dictamen descarta el acoso.',
        'caseWorkflow.template.es_ga.educational_measures',
        true
    ),
    (
        '019ffe81-0000-7000-8000-000000000007',
        '019c4c2d-4fd4-7f6d-a0d1-000000000001',
        'action_plan',
        'internal_action',
        'Registra la apertura ficticia del protocolo y el nombramiento de la persona responsable de atender a la víctima.',
        'caseWorkflow.template.es_ga.action_plan',
        true
    ),
    (
        '019ffe81-0000-7000-8000-000000000008',
        '019c4c2d-4fd4-7f6d-a0d1-000000000001',
        'family_communication',
        'external_communication',
        'Registra la comunicación ficticia de apertura a las familias, obligatoria a partir de esta fase.',
        'caseWorkflow.template.es_ga.family_communication',
        true
    ),
    (
        '019ffe81-0000-7000-8000-000000000009',
        '019c4c2d-4fd4-7f6d-a0d1-000000000001',
        'inspection_communication',
        'external_communication',
        'Confirma el envío ficticio a la inspección educativa de la copia de apertura y de la de cierre.',
        'caseWorkflow.template.es_ga.inspection_communication',
        true
    ),
    (
        '019ffe81-0000-7000-8000-000000000010',
        '019c4c2d-4fd4-7f6d-a0d1-000000000001',
        'family_measures',
        'internal_action',
        'Registra la reunión ficticia con las personas implicadas en presencia de la familia o representantes legales.',
        'caseWorkflow.template.es_ga.family_measures',
        true
    ),
    (
        '019ffe81-0000-7000-8000-000000000011',
        '019c4c2d-4fd4-7f6d-a0d1-000000000001',
        'inspection_follow_up',
        'internal_action',
        'Sigue el seguimiento ficticio propuesto por la fuente: quincenal el primer mes y mensual después, y avisa al centro receptor si hay traslado.',
        'caseWorkflow.template.es_ga.inspection_follow_up',
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

        $this->addSql("DELETE FROM case_workflow_task_templates WHERE source_version_id = '019c4c2d-4fd4-7f6d-a0d1-000000000001'");
        $this->addSql("DELETE FROM case_workflow_source_versions WHERE id = '019c4c2d-4fd4-7f6d-a0d1-000000000001'");
    }
}
