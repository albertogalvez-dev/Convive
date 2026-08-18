<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Gives every workflow task template a stable translation key, and restores
 * Spanish as the language its title is written in (#310).
 *
 * Why this migration exists: template titles are rendered straight to the
 * professional's screen. They were written in English because they began as
 * developer-facing seed text, and nobody decided to show English to a Spanish
 * school. This migration fixes that, and adds the key that makes any other
 * language possible at all.
 *
 * The key is derived rather than hand-assigned: (territory, stage) is unique
 * across every template, which was verified against the database before
 * choosing this shape. That means a new territorial migration gets its keys
 * for free and cannot forget one.
 */
final class Version20260818150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add translation keys to workflow task templates and restore Spanish titles (#310)';
    }

    /**
     * Spanish source titles, keyed by territory and protocol stage.
     *
     * These are translations of the existing text, not rewrites: the facts
     * each title states -- the body named, the deadline quoted, whether it is
     * school days or calendar days -- are exactly those verified against each
     * territory's primary source. A translation pass must not become a
     * chance to restate an obligation.
     *
     * @return array<string, array<string, string>>
     */
    private function spanishTitles(): array
    {
        return [
            'ES-AN' => [
                'immediate_actions' => 'Revisa el plan de protección inmediata ficticio.',
                'information_collection' => 'Registra el paso ficticio de recogida de información.',
            ],
            'ES-AN-GR' => [
                'family_communication' => 'Decide si procede un contacto ficticio con la familia.',
            ],
            'ES-AR' => [
                'family_communication' => 'Confirma la comunicación inmediata ficticia a la familia.',
                'immediate_actions' => 'Revisa la activación inmediata ficticia del protocolo.',
                'information_collection' => 'Registra la investigación ficticia, rigurosa y objetiva.',
                'urgent_protection' => 'Confirma que las medidas ficticias de protección a la víctima están en marcha.',
            ],
            'ES-AS' => [
                'action_plan' => 'Sigue el plazo ficticio de 27 días lectivos del plan de actuación cuando se confirma el acoso.',
                'assessment' => 'Sigue el plazo ficticio de 10 días lectivos para la valoración de indicios enviada a Inspección.',
                'family_communication' => 'Sigue el plazo ficticio de 2 días lectivos para convocar a la familia del alumnado afectado.',
                'information_collection' => 'Sigue el plazo ficticio de 5 días lectivos de recogida de información del profesorado.',
                'inspection_communication' => 'Sigue el plazo ficticio de 1 día lectivo para notificar a Inspección Educativa la apertura del protocolo.',
                'inspection_follow_up' => 'Sigue el plazo ficticio de 50 días lectivos del informe de seguimiento del plan de actuación.',
                'professional_coordination' => 'Sigue el plazo ficticio de 2 días lectivos para constituir el Equipo de seguimiento.',
                'urgent_protection' => 'Confirma las medidas ficticias de protección urgente recogidas en el acta inicial.',
            ],
            'ES-CB' => [
                'family_communication' => 'Confirma la notificación ficticia a las familias de que se ha abierto el protocolo.',
                'immediate_actions' => 'Confirma la constitución inmediata ficticia del Equipo de Valoración.',
                'inspection_communication' => 'Confirma la notificación inmediata ficticia al Servicio de Inspección Educativa y a la Unidad de Convivencia.',
                'professional_coordination' => 'Registra la composición ficticia del Equipo de Valoración (dirección, orientación, tutoría y, si procede, la Unidad de Convivencia).',
                'urgent_protection' => 'Confirma las medidas ficticias de protección y vigilancia preventivas para la presunta víctima.',
            ],
            'ES-CE' => [
                'assessment' => 'Registra la reunión ficticia de toma de decisiones, dentro de los tres días lectivos siguientes al informe de las personas observadoras.',
                'family_communication' => 'Registra la primera entrevista ficticia con la familia, dentro de los dos días lectivos siguientes a la constitución del equipo.',
                'immediate_actions' => 'Confirma las medidas ficticias de observación previas a la constitución del equipo de valoración e intervención.',
                'information_collection' => 'Sigue el máximo ficticio de cinco días lectivos para que el profesorado designado recoja la información.',
                'inspection_communication' => 'Confirma el envío ficticio del Anexo I y el Anexo II a la Dirección Provincial, que los traslada a Inspección y a la Unidad de Convivencia.',
                'inspection_follow_up' => 'Sigue el seguimiento ficticio del plan de intervención, que la fuente exige al menos con periodicidad mensual.',
                'professional_coordination' => 'Registra la constitución ficticia del equipo de valoración e intervención, dentro de los dos días lectivos siguientes al Anexo I.',
                'urgent_protection' => 'Confirma las medidas ficticias iniciales de seguridad para el alumnado afectado y las cautelares que procedan.',
            ],
            'ES-CL' => [
                'family_communication' => 'Confirma la notificación ficticia a la familia junto con la notificación a Inspección.',
                'immediate_actions' => 'Sigue el plazo ficticio de 48 horas de la fase uno (conocer, identificar y detener).',
                'inspection_communication' => 'Confirma la notificación inmediata ficticia a Inspección educativa.',
                'professional_coordination' => 'Registra la constitución ficticia de la Comisión específica de acoso escolar, de 4 personas.',
                'urgent_protection' => 'Confirma la reunión ficticia de valoración convocada por la dirección en 24 horas.',
            ],
            'ES-CM' => [
                'action_plan' => 'Sigue el plazo ficticio de 30 días lectivos del plan de actuación para Inspección.',
                'family_communication' => 'Sigue el plazo ficticio de 24 horas para notificar a la familia.',
                'immediate_actions' => 'Sigue el plazo ficticio de 48 horas para constituir la comisión de acoso.',
                'inspection_communication' => 'Confirma la notificación inmediata ficticia a Inspección educativa.',
                'urgent_protection' => 'Confirma las medidas ficticias de protección inmediata para el alumnado afectado.',
            ],
            'ES-CN' => [
                'assessment' => 'Registra el análisis ficticio previo a la intervención frente a los tres criterios diagnósticos que fija la fuente.',
                'family_communication' => 'Registra la primera entrevista ficticia con quien informó, dentro de los dos días que permite la fuente.',
                'immediate_actions' => 'Confirma la designación ficticia de la persona referente del caso dentro del equipo de gestión de la convivencia.',
                'information_collection' => 'Registra la entrevista ficticia con el alumnado afectado, mantenida siempre con la misma persona referente.',
                'inspection_communication' => 'Confirma la notificación ficticia a la inspección, inmediata cuando el caso pueda ser delictivo.',
                'inspection_follow_up' => 'Sigue el plan ficticio de acompañamiento de tres meses, que la fuente cuenta sin contar los periodos de vacaciones.',
                'professional_coordination' => 'Registra la sesión educativa ficticia con el alumnado observador, en grupo de tres a seis.',
                'urgent_protection' => 'Confirma las medidas ficticias de protección urgente, incluido cualquier distanciamiento cautelar de cuatro días.',
            ],
            'ES-EX' => [
                'assessment' => 'Revisa el informe ficticio del Equipo de Valoración que concluye si se confirma el acoso.',
                'family_communication' => 'Confirma la primera reunión ficticia en la que se informa a ambas familias de la situación.',
                'immediate_actions' => 'Registra la decisión ficticia del equipo directivo sobre si el protocolo continúa.',
                'inspection_communication' => 'Confirma la notificación inicial ficticia al Servicio de Inspección Educativa.',
                'professional_coordination' => 'Registra la constitución ficticia del Equipo de Valoración (equipo directivo, DO/EOEP, educación social y una persona del profesorado).',
                'urgent_protection' => 'Confirma las medidas ficticias preventivas que protegen a la presunta víctima.',
            ],
            'ES-IB' => [
                'assessment' => 'Sigue el plazo ficticio de 5 días hábiles de la primera reunión de gestión del caso.',
                'educational_measures' => 'Sigue el plazo ficticio de 7 días hábiles de las entrevistas individuales del método Pikas.',
                'family_communication' => 'Confirma las entrevistas ficticias con las familias del alumnado implicado.',
                'immediate_actions' => 'Registra la designación ficticia, el mismo día, de la persona referente del caso.',
                'information_collection' => 'Sigue el plazo ficticio de 4 días hábiles de acollida i valoració.',
                'inspection_communication' => 'Confirma la notificación ficticia, el mismo día, al Departament d\'Inspecció Educativa.',
                'inspection_follow_up' => 'Confirma el informe ficticio de cierre enviado a la inspección del centro.',
                'urgent_protection' => 'Confirma las medidas ficticias de protección y observación valoradas para el alumnado afectado.',
            ],
            'ES-MC' => [
                'assessment' => 'Sigue el máximo ficticio de 20 días lectivos de la investigación y su informe, contados desde la comunicación del Anexo I.',
                'family_communication' => 'Confirma la comunicación presencial ficticia del resultado a las familias, con copia del Anexo V.',
                'immediate_actions' => 'Confirma la apertura inmediata ficticia de las diligencias y la designación del equipo de intervención.',
                'information_collection' => 'Registra las entrevistas ficticias con el alumnado afectado, el observador no participante y ambas familias.',
                'inspection_communication' => 'Confirma el envío inmediato ficticio del Anexo I a la Inspección de Educación y al Servicio de Ordenación Académica.',
                'inspection_follow_up' => 'Registra el Plan de Seguimiento Sistemático ficticio, que la fuente exige sea cual sea el resultado.',
                'professional_coordination' => 'Registra la primera reunión ficticia de coordinación de la jefatura de estudios con el equipo de intervención.',
                'urgent_protection' => 'Confirma las medidas ficticias de protección urgente adoptadas para el alumnado afectado.',
            ],
            'ES-MD' => [
                'action_plan' => 'Registra el Plan individualizado de intervención ficticio acordado para el alumnado implicado.',
                'assessment' => 'Sigue el máximo ficticio de 15 días naturales de observación añadida cuando las evidencias no son concluyentes.',
                'family_communication' => 'Confirma la comunicación presencial ficticia a la familia, teniendo en cuenta la excepción del artículo 32.7 cuando la agresión pueda venir de un familiar.',
                'immediate_actions' => 'Confirma la apertura inmediata ficticia del Plan individualizado de protección del alumnado.',
                'information_collection' => 'Sigue el máximo ficticio de 15 días lectivos para recabar la información con la que analizar la situación.',
                'inspection_communication' => 'Confirma la notificación ficticia al Servicio de Inspección Educativa y a la Unidad de convivencia y contra el acoso escolar.',
                'inspection_follow_up' => 'Confirma el acta ficticia de cierre enviada al Servicio de Inspección Educativa y a la Unidad de convivencia.',
                'professional_coordination' => 'Registra la designación ficticia del único docente encargado de recabar la información.',
                'urgent_protection' => 'Registra las medidas ficticias de protección obligatorias elegidas para el alumnado.',
            ],
            'ES-ML' => [
                'assessment' => 'Registra la reunión ficticia de toma de decisiones, dentro de los tres días lectivos siguientes al informe de las personas observadoras.',
                'family_communication' => 'Registra la primera entrevista ficticia con la familia, dentro de los dos días lectivos siguientes a la constitución del equipo.',
                'immediate_actions' => 'Confirma las medidas ficticias de observación previas a la constitución del equipo de valoración e intervención.',
                'information_collection' => 'Sigue el máximo ficticio de cinco días lectivos para que el profesorado designado recoja la información.',
                'inspection_communication' => 'Confirma el envío ficticio del Anexo I y el Anexo II a la Dirección Provincial, que los traslada a Inspección y a la Unidad de Convivencia.',
                'inspection_follow_up' => 'Sigue el seguimiento ficticio del plan de intervención, que la fuente exige al menos con periodicidad mensual.',
                'professional_coordination' => 'Registra la constitución ficticia del equipo de valoración e intervención, dentro de los dos días lectivos siguientes al Anexo I.',
                'urgent_protection' => 'Confirma las medidas ficticias iniciales de seguridad para el alumnado afectado y las cautelares que procedan.',
            ],
            'ES-RI' => [
                'action_plan' => 'Sigue la ventana ficticia del día 11 al día 15 lectivos para la valoración y el Plan de Actuación.',
                'family_communication' => 'Confirma la citación urgente ficticia a las familias de las partes implicadas.',
                'immediate_actions' => 'Sigue el plazo ficticio de 24 horas hábiles para constituir la Comisión de Valoración Urgente de la Convivencia.',
                'information_collection' => 'Sigue la ventana ficticia del día 3 al día 10 lectivos para recabar información.',
                'inspection_communication' => 'Confirma la notificación ficticia del primer día a la Inspección Educativa y a la Comisión de Convivencia.',
                'inspection_follow_up' => 'Registra el seguimiento ficticio, que la fuente deja sin plazo fijado.',
                'professional_coordination' => 'Registra la composición ficticia de la Comisión de Valoración Urgente, incluida la coordinación de convivencia, bienestar y protección a la infancia.',
                'urgent_protection' => 'Confirma las medidas ficticias de protección urgente recogidas para el alumnado afectado.',
            ],
            'ES-VC' => [
                'action_plan' => 'Sigue el plazo ficticio de 2 meses para resolver el expediente disciplinario.',
                'family_communication' => 'Confirma la notificación ficticia a los representantes legales.',
                'immediate_actions' => 'Revisa el expediente disciplinario ficticio abierto en 5 días lectivos.',
                'professional_coordination' => 'Registra la participación ficticia de la coordinación de bienestar y protección.',
            ],
        ];
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql('ALTER TABLE case_workflow_task_templates ADD title_key VARCHAR(160) DEFAULT NULL');

        // Derived, not hand-assigned. (territory, stage) is unique across
        // every template, so this cannot skip one or collide.
        $this->addSql(<<<'SQL'
UPDATE case_workflow_task_templates AS t
SET title_key = 'caseWorkflow.template.'
    || lower(replace(s.territory, '-', '_'))
    || '.'
    || t.stage
FROM case_workflow_source_versions AS s
WHERE s.id = t.source_version_id
SQL);

        $this->addSql('ALTER TABLE case_workflow_task_templates ALTER COLUMN title_key SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_case_workflow_task_template_title_key ON case_workflow_task_templates (title_key)');

        foreach ($this->spanishTitles() as $territory => $titles) {
            foreach ($titles as $stage => $title) {
                $this->addSql(
                    <<<'SQL'
UPDATE case_workflow_task_templates AS t
SET title = :title
FROM case_workflow_source_versions AS s
WHERE s.id = t.source_version_id AND s.territory = :territory AND t.stage = :stage
SQL,
                    ['title' => $title, 'territory' => $territory, 'stage' => $stage],
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        // The English titles are not restored: they were never intended to
        // reach a user, and reinstating them would reintroduce the defect
        // this migration exists to fix.
        $this->addSql('DROP INDEX uniq_case_workflow_task_template_title_key');
        $this->addSql('ALTER TABLE case_workflow_task_templates DROP title_key');
    }
}
