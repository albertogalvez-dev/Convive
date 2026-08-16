<?php

declare(strict_types=1);

namespace App\Cases\Domain;

/**
 * The approved set of controlled case documents. Each renders only data the
 * requesting professional can already see in the workspace, and each carries a
 * template version so a generated document can be traced to the exact shape
 * that produced it.
 *
 * None of these is an official form of any administration, and every generated
 * document says so on its face.
 */
enum CaseDocumentTemplate: string
{
    case ActionRecord = 'action_record';
    case FollowUpPlan = 'follow_up_plan';
    case CoordinationNote = 'coordination_note';
    case FamilyCommunication = 'family_communication';
    case ProtocolReviewChecklist = 'protocol_review_checklist';
    case ClosureReport = 'closure_report';

    public function title(): string
    {
        return match ($this) {
            self::ActionRecord => 'Registro de actuación',
            self::FollowUpPlan => 'Plan de seguimiento',
            self::CoordinationNote => 'Nota de coordinación',
            self::FamilyCommunication => 'Comunicación a familia',
            self::ProtocolReviewChecklist => 'Checklist de revisión de protocolo',
            self::ClosureReport => 'Informe de cierre ficticio',
        };
    }

    /**
     * Bumped whenever the fields a template renders change, so an older
     * document remains attributable to the shape it was generated from.
     */
    public function version(): string
    {
        return '1.0';
    }

    public function purpose(): string
    {
        return match ($this) {
            self::ActionRecord => 'Recoge las actuaciones registradas en el caso y su estado.',
            self::FollowUpPlan => 'Recoge las tareas pendientes y sus fechas objetivo.',
            self::CoordinationNote => 'Recoge las comunicaciones registradas para coordinación interna.',
            self::FamilyCommunication => 'Recoge las comunicaciones dirigidas a la familia.',
            self::ProtocolReviewChecklist => 'Recoge cada etapa del protocolo y el estado de sus tareas.',
            self::ClosureReport => 'Recoge el cierre registrado del caso y su justificación.',
        };
    }
}
