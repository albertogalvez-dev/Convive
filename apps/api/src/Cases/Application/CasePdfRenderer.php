<?php

declare(strict_types=1);

namespace App\Cases\Application;

use App\Cases\Domain\CaseAssignmentRole;
use App\Cases\Domain\CaseAuditAction;
use App\Cases\Domain\CaseAuditEvent;
use App\Cases\Domain\CaseAuditTarget;
use App\Cases\Domain\CaseModality;
use App\Cases\Domain\CaseProtocolStage;
use App\Cases\Domain\CaseStatus;
use App\Cases\Domain\CaseTaskKind;
use App\Cases\Domain\CaseTaskStatus;
use Dompdf\Dompdf;
use Dompdf\Options;

final class CasePdfRenderer
{
    /** @param list<CaseAuditEvent> $events */
    public function caseRecord(CaseWorkspaceDetail $detail, array $events): string
    {
        $case = $detail->managedCase;
        $tasks = array_map(
            fn ($task): string => sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                $this->escape($this->stageLabel($task->stage())),
                $this->escape($this->taskKindLabel($task->kind())),
                $this->escape($this->taskStatusLabel($task->status())),
                $this->escape($this->formatDate($task->dueAt())),
            ),
            $detail->tasks,
        );
        $audit = array_map(
            fn (CaseAuditEvent $event): string => sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
                $this->escape($this->formatDate($event->occurredAt())),
                $this->escape($this->auditActionLabel($event->action())),
                $this->escape($this->auditTargetLabel($event->target())),
            ),
            $events,
        );

        return $this->render(
            'Expediente operativo',
            'Expediente operativo',
            'Registro controlado. No incluye relato, personas vinculadas ni evidencias.',
            sprintf(
                '<section class="summary"><div><span>Caso</span><strong>%s</strong></div><div><span>Organización</span><strong>%s</strong></div><div><span>Estado</span><strong>%s</strong></div><div><span>Ámbito</span><strong>%s</strong></div><div><span>Acceso actual</span><strong>%s</strong></div><div><span>Creado</span><strong>%s</strong></div></section>'
                .'<h2>Tareas</h2>%s'
                .'<h2>Registro de actividad</h2>%s',
                $this->escape($case->id()->toRfc4122()),
                $this->escape($case->organisation()->name()),
                $this->escape($this->caseStatusLabel($case->status())),
                $this->escape($this->modalityLabel($case->modality())),
                $this->escape($this->assignmentRoleLabel($detail->currentAssignment->role())),
                $this->escape($this->formatDate($case->createdAt())),
                $this->table(
                    ['Etapa', 'Tipo', 'Estado', 'Fecha objetivo'],
                    $tasks,
                    'No hay tareas operativas registradas.',
                ),
                $this->table(
                    ['Fecha', 'Acción', 'Elemento'],
                    $audit,
                    'No hay actividad auditable registrada.',
                ),
            ),
        );
    }

    /** @param array{assigned: int, overdue: int, upcoming: int} $counts */
    public function operationalOverview(array $counts): string
    {
        return $this->render(
            'Resumen operativo',
            'Resumen operativo',
            'Solo incluye recuentos de los casos asignados a tu perfil.',
            sprintf(
                '<table class="metric-grid"><tr><td><span>Asignados</span><strong>%d</strong></td><td class="attention"><span>Fuera de plazo</span><strong>%d</strong></td><td><span>Próximos</span><strong>%d</strong></td></tr></table>',
                $counts['assigned'],
                $counts['overdue'],
                $counts['upcoming'],
            ),
        );
    }

    private function render(string $title, string $heading, string $description, string $body): string
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $pdf = new Dompdf($options);
        $pdf->setPaper('A4');
        $pdf->loadHtml(sprintf(<<<'HTML'
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>%s</title>
  <style>
    @page { margin: 42px 46px; }
    body { color: #102b60; font: 11px/1.45 "DejaVu Sans", sans-serif; }
    .brand { color: #159bd2; font-size: 10px; font-weight: bold; letter-spacing: 1.6px; text-transform: uppercase; }
    h1 { margin: 8px 0 4px; color: #102b60; font-size: 24px; letter-spacing: -0.5px; }
    h2 { margin: 28px 0 9px; color: #102b60; font-size: 14px; }
    .description { margin: 0; color: #526b91; }
    .summary { width: 100%%; margin-top: 24px; border-collapse: separate; border-spacing: 8px; }
    .summary div { display: inline-block; box-sizing: border-box; width: 30.9%%; min-height: 62px; margin: 0 1.1%% 10px 0; border: 1px solid #d4dfed; border-radius: 7px; padding: 10px 11px; background: #f8fbfd; vertical-align: top; }
    .summary span, .metric-grid span { display: block; color: #637493; font-size: 9px; font-weight: bold; text-transform: uppercase; }
    .summary strong { display: block; margin-top: 5px; color: #102b60; font-size: 10px; word-wrap: break-word; }
    .metric-grid { width: 100%%; margin-top: 24px; border-collapse: separate; border-spacing: 8px; }
    .metric-grid td { width: 33.33%%; min-height: 88px; border: 1px solid #d4dfed; border-radius: 7px; padding: 14px; background: #f8fbfd; }
    .metric-grid strong { display: block; margin-top: 7px; color: #102b60; font-size: 29px; line-height: 1; }
    .metric-grid .attention { border-color: #f0cf8a; background: #fff9eb; }
    table { width: 100%%; border-collapse: collapse; }
    th { border-bottom: 2px solid #b9cbe1; color: #40587d; font-size: 9px; text-align: left; text-transform: uppercase; }
    td { border-bottom: 1px solid #dce5f0; color: #17366d; }
    th, td { padding: 8px 6px; vertical-align: top; }
    .empty { margin: 0; border: 1px dashed #c7d4e5; border-radius: 7px; padding: 12px; color: #637493; }
  </style>
</head>
<body>
  <div class="brand">Convive · registro protegido</div>
  <h1>%s</h1>
  <p class="description">%s</p>
  %s
</body>
</html>
HTML, $this->escape($title), $this->escape($heading), $this->escape($description), $body));
        $pdf->render();

        return $pdf->output();
    }

    /**
     * @param list<string> $headers
     * @param list<string> $rows
     */
    private function table(array $headers, array $rows, string $emptyMessage): string
    {
        if ($rows === []) {
            return '<p class="empty">'.$this->escape($emptyMessage).'</p>';
        }

        return '<table><thead><tr><th>'.implode('</th><th>', array_map($this->escape(...), $headers))
            .'</th></tr></thead><tbody>'.implode('', $rows).'</tbody></table>';
    }

    private function formatDate(\DateTimeImmutable $date): string
    {
        return $date->format('d/m/Y H:i T');
    }

    private function caseStatusLabel(CaseStatus $status): string
    {
        return match ($status) {
            CaseStatus::Assessment => 'En valoración',
            CaseStatus::Active => 'En curso',
            CaseStatus::Closed => 'Cerrado',
        };
    }

    private function modalityLabel(CaseModality $modality): string
    {
        return match ($modality) {
            CaseModality::InPerson => 'En el centro',
            CaseModality::Digital => 'Entorno digital',
            CaseModality::Mixed => 'Mixto',
            CaseModality::Unknown => 'Sin concretar',
        };
    }

    private function assignmentRoleLabel(CaseAssignmentRole $role): string
    {
        return match ($role) {
            CaseAssignmentRole::Lead => 'Responsable',
            CaseAssignmentRole::Contributor => 'Colaborador',
            CaseAssignmentRole::Observer => 'Observador',
        };
    }

    private function stageLabel(CaseProtocolStage $stage): string
    {
        return match ($stage) {
            CaseProtocolStage::Identification => 'Identificación',
            CaseProtocolStage::ImmediateActions => 'Actuaciones inmediatas',
            CaseProtocolStage::UrgentProtection => 'Protección urgente',
            CaseProtocolStage::FamilyCommunication => 'Comunicación con la familia',
            CaseProtocolStage::ProfessionalCoordination => 'Coordinación profesional',
            CaseProtocolStage::InformationCollection => 'Recogida de información',
            CaseProtocolStage::EducationalMeasures => 'Medidas educativas',
            CaseProtocolStage::InspectionCommunication => 'Comunicación a Inspección',
            CaseProtocolStage::Assessment => 'Valoración',
            CaseProtocolStage::ActionPlan => 'Plan de actuación',
            CaseProtocolStage::FamilyMeasures => 'Medidas con la familia',
            CaseProtocolStage::InspectionFollowUp => 'Seguimiento de Inspección',
        };
    }

    private function taskKindLabel(CaseTaskKind $kind): string
    {
        return match ($kind) {
            CaseTaskKind::InternalAction => 'Actuación interna',
            CaseTaskKind::ExternalCommunication => 'Comunicación externa',
        };
    }

    private function taskStatusLabel(CaseTaskStatus $status): string
    {
        return match ($status) {
            CaseTaskStatus::Pending => 'Pendiente',
            CaseTaskStatus::Completed => 'Completada',
            CaseTaskStatus::NotApplicable => 'No aplicable',
        };
    }

    private function auditActionLabel(CaseAuditAction $action): string
    {
        return match ($action) {
            CaseAuditAction::CaseCreated => 'Caso creado',
            CaseAuditAction::ReportLinked => 'Comunicación vinculada',
            CaseAuditAction::AssignmentCreated => 'Asignación creada',
            CaseAuditAction::AssignmentChanged => 'Asignación modificada',
            CaseAuditAction::AssignmentRevoked => 'Asignación retirada',
            CaseAuditAction::TaskCreated => 'Tarea creada',
            CaseAuditAction::TaskCompleted => 'Tarea completada',
            CaseAuditAction::TaskMarkedNotApplicable => 'Tarea no aplicable',
            CaseAuditAction::EvidenceDownloadAuthorised => 'Descarga de evidencia autorizada',
            CaseAuditAction::AuditExported => 'Registro exportado',
            CaseAuditAction::CaseRecordExported => 'Expediente exportado',
            CaseAuditAction::PersonAdded => 'Persona vinculada',
            CaseAuditAction::PersonCorrected => 'Vinculación corregida',
            CaseAuditAction::PersonRemoved => 'Vinculación retirada',
            CaseAuditAction::StatusChanged => 'Estado del caso actualizado',
        };
    }

    private function auditTargetLabel(CaseAuditTarget $target): string
    {
        return match ($target) {
            CaseAuditTarget::Case => 'Caso',
            CaseAuditTarget::TriageDecision => 'Decisión de triaje',
            CaseAuditTarget::Assignment => 'Asignación',
            CaseAuditTarget::Task => 'Tarea',
            CaseAuditTarget::Attachment => 'Evidencia',
            CaseAuditTarget::AuditTrail => 'Registro de actividad',
            CaseAuditTarget::CaseRecord => 'Expediente',
            CaseAuditTarget::Person => 'Persona vinculada',
        };
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
