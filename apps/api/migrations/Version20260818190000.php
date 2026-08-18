<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Navarra as a twentieth territorial protocol profile (#268)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        // Source: Orden Foral 204/2010, de 16 de diciembre, del Consejero de
        // Educacion, "por la que se regula la convivencia en los centros
        // educativos no universitarios publicos y privados concertados de la
        // Comunidad Foral de Navarra", published in the BON on 20 January
        // 2011. Read from the Government of Navarra's own legal database.
        // http://www.lexnavarra.navarra.es/detalle.asp?r=9755
        //
        // It develops Decreto Foral 47/2010, de 23 de agosto (modified by
        // Decreto Foral 57/2014), whose article 11 obliges centres to
        // intervene in acoso. Authority is 'binding': an Orden Foral
        // published in the gazette and binding on every publicly funded
        // centre.
        //
        // WHY THIS ISSUE WAS HELD, AND WHY IT NO LONGER IS.
        // This profile was deliberately not modelled in the first pass
        // because a replacement Decreto Foral de Convivencia had been
        // presented and looked imminent. Status verified on 18 August 2026:
        // the Consejo Escolar de Navarra's own site records that on 20-21 May
        // 2026 the decree was PRESENTED and "aun debe pasar por el Consejo
        // Escolar de Navarra y ser aprobado por el Gobierno foral". No BON
        // publication could be found three months later, neither in the
        // gazette nor in the Gobierno Abierto record of the rule-making.
        // Press headlines say Navarra "estrena" the decree; the body of the
        // same article says it is not approved. Decreto Foral 47/2010 and
        // this Orden Foral therefore remain the governing texts and are what
        // this profile cites. If the new decree is published, this profile
        // needs a NEW source version, not an edit to this one.
        //
        // THE ONE NUMBER IN THIS PROTOCOL IS NOT A PROTOCOL DEADLINE.
        // Article 21.8 states that "una mediacion formal considerada como
        // circunstancia atenuante debe estar concluida en un plazo maximo de
        // 8 dias lectivos". That is a condition for mediation to count as a
        // mitigating circumstance in the disciplinary reckoning, not a
        // deadline for responding to acoso. Presenting it as "you have eight
        // school days" would invent an obligation and, far worse, imply the
        // response itself may wait that long. The template says what it
        // actually is.
        //
        // Beyond that, article 15 sets no numeric deadlines at all. Navarra
        // is the second territory in this sequence, after Galicia, whose
        // protocol states none of its own, and the templates below quote
        // none.
        //
        // Structure recorded from articles 15, 19 and 21: any member of the
        // community must notify the direccion; the direccion is responsible
        // and coordinates, and may delegate the enquiry to the tutor with
        // support from orientacion; the Comision de Convivencia is notified;
        // the direccion informs the Inspeccion educativa of every case; and
        // the Asesoria de Convivencia may intervene where the centre asks in
        // writing, which is a request rather than an automatic escalation.
        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_source_versions
    (id, code, version, title, uri, territory, authority, published_on, reviewed_on)
VALUES
    (
        '019c4c2e-4fd4-7f6d-a0d1-000000000001',
        'ES-NC-ORDEN-FORAL-204-2010',
        'NAVARRA-2010-12-16',
        'Navarra school coexistence regulation (Orden Foral 204/2010, de 16 de diciembre)',
        'http://www.lexnavarra.navarra.es/detalle.asp?r=9755',
        'ES-NC',
        'binding',
        '2010-12-16',
        '2026-08-18'
    )
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_task_templates (id, source_version_id, stage, kind, title, title_key, approved)
VALUES
    (
        '019ffe82-0000-7000-8000-000000000001',
        '019c4c2e-4fd4-7f6d-a0d1-000000000001',
        'immediate_actions',
        'internal_action',
        'Registra la comunicación ficticia a la dirección del centro, a quien corresponde coordinar la actuación.',
        'caseWorkflow.template.es_nc.immediate_actions',
        true
    ),
    (
        '019ffe82-0000-7000-8000-000000000002',
        '019c4c2e-4fd4-7f6d-a0d1-000000000001',
        'urgent_protection',
        'internal_action',
        'Confirma las medidas ficticias de protección del alumnado afectado mientras se investiga.',
        'caseWorkflow.template.es_nc.urgent_protection',
        true
    ),
    (
        '019ffe82-0000-7000-8000-000000000003',
        '019c4c2e-4fd4-7f6d-a0d1-000000000001',
        'professional_coordination',
        'internal_action',
        'Registra si la dirección delega la indagación ficticia en la tutoría con apoyo del departamento de orientación.',
        'caseWorkflow.template.es_nc.professional_coordination',
        true
    ),
    (
        '019ffe82-0000-7000-8000-000000000004',
        '019c4c2e-4fd4-7f6d-a0d1-000000000001',
        'information_collection',
        'internal_action',
        'Registra la investigación ficticia del episodio, que la fuente exige siempre que se sospeche acoso.',
        'caseWorkflow.template.es_nc.information_collection',
        true
    ),
    (
        '019ffe82-0000-7000-8000-000000000005',
        '019c4c2e-4fd4-7f6d-a0d1-000000000001',
        'assessment',
        'internal_action',
        'Registra la valoración ficticia del caso conforme a la definición de acoso del artículo 15.',
        'caseWorkflow.template.es_nc.assessment',
        true
    ),
    (
        '019ffe82-0000-7000-8000-000000000006',
        '019c4c2e-4fd4-7f6d-a0d1-000000000001',
        'family_communication',
        'external_communication',
        'Registra la comunicación ficticia a las familias del alumnado implicado.',
        'caseWorkflow.template.es_nc.family_communication',
        true
    ),
    (
        '019ffe82-0000-7000-8000-000000000007',
        '019c4c2e-4fd4-7f6d-a0d1-000000000001',
        'family_measures',
        'internal_action',
        'Confirma la puesta en conocimiento ficticia de la Comisión de Convivencia del centro.',
        'caseWorkflow.template.es_nc.family_measures',
        true
    ),
    (
        '019ffe82-0000-7000-8000-000000000008',
        '019c4c2e-4fd4-7f6d-a0d1-000000000001',
        'inspection_communication',
        'external_communication',
        'Confirma que la dirección informa a la Inspección educativa, que la fuente exige en todos los casos.',
        'caseWorkflow.template.es_nc.inspection_communication',
        true
    ),
    (
        '019ffe82-0000-7000-8000-000000000009',
        '019c4c2e-4fd4-7f6d-a0d1-000000000001',
        'educational_measures',
        'internal_action',
        'Registra las medidas educativas ficticias previstas en el plan de convivencia del centro.',
        'caseWorkflow.template.es_nc.educational_measures',
        true
    ),
    (
        '019ffe82-0000-7000-8000-000000000010',
        '019c4c2e-4fd4-7f6d-a0d1-000000000001',
        'action_plan',
        'internal_action',
        'Si se abre mediación ficticia, recuerda que solo cuenta como atenuante si concluye dentro del plazo que fija la fuente; no es un plazo de respuesta al acoso.',
        'caseWorkflow.template.es_nc.action_plan',
        true
    ),
    (
        '019ffe82-0000-7000-8000-000000000011',
        '019c4c2e-4fd4-7f6d-a0d1-000000000001',
        'inspection_follow_up',
        'internal_action',
        'Registra el seguimiento ficticio y, si el centro lo pide por escrito, la intervención de la Asesoría de Convivencia.',
        'caseWorkflow.template.es_nc.inspection_follow_up',
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

        $this->addSql("DELETE FROM case_workflow_task_templates WHERE source_version_id = '019c4c2e-4fd4-7f6d-a0d1-000000000001'");
        $this->addSql("DELETE FROM case_workflow_source_versions WHERE id = '019c4c2e-4fd4-7f6d-a0d1-000000000001'");
    }
}
