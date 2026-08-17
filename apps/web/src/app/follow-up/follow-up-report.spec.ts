import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FollowUpReport } from './follow-up-report';
import { ReporterProgressStage, ReportFollowUpState } from './follow-up.service';

describe('FollowUpReport', () => {
  let fixture: ComponentFixture<FollowUpReport>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({ imports: [FollowUpReport] }).compileComponents();

    fixture = TestBed.createComponent(FollowUpReport);
  });

  it('shows the honest received stage when nothing has happened yet', () => {
    render(reportState('received'));

    const progress = section();
    expect(progress?.textContent).toContain('Recibida');
    expect(progress?.textContent).toContain('Todavía no la ha revisado nadie');
  });

  it('shows that a professional is looking at it once it is under review', () => {
    render(reportState('under_review'));

    expect(section()?.textContent).toContain('En revisión');
  });

  it('shows that work is under way once the report led to a case', () => {
    render(reportState('action_taken'));

    expect(section()?.textContent).toContain('El centro está actuando');
  });

  it('shows a closed report as finished without saying how it finished', () => {
    render(reportState('closed'));

    const text = section()?.textContent ?? '';
    expect(text).toContain('Cerrada');
    expect(text).toContain('puedes escribir de nuevo');
  });

  it('never names a professional, a decision, or a promised date in any stage', () => {
    const forbidden = [
      // A decision or its reasoning.
      'descart',
      'desestim',
      'derivad',
      'rechaz',
      'motivo',
      'razón',
      'decidi',
      'expediente',
      // A promise of when something will happen.
      'en breve',
      'pronto',
      'plazo',
      'días',
      'horas',
      'responderemos',
      'te contestaremos',
    ];

    const stages: ReporterProgressStage[] = ['received', 'under_review', 'action_taken', 'closed'];

    for (const stage of stages) {
      render(reportState(stage));
      const text = (section()?.textContent ?? '').toLowerCase();

      for (const term of forbidden) {
        expect(text).not.toContain(term);
      }
    }
  });

  it('keeps the conversation usable when the API sends no stage at all', () => {
    const state = reportState('received');
    delete state.progressStage;

    render(state);

    expect(section()).toBeNull();
    // The parts of the page the reporter actually depends on are still there.
    expect(fixture.nativeElement.textContent).toContain('CNV-2026-0001');
    expect(fixture.nativeElement.textContent).toContain('Una situación ocurrió durante el recreo.');
  });

  it('keeps the conversation usable when the API sends a stage it does not know', () => {
    const state = reportState('received');
    // A future API adding a stage this build predates must degrade, not crash.
    state.progressStage = 'escalated_to_inspectorate' as ReporterProgressStage;

    render(state);

    expect(section()).toBeNull();
    expect(fixture.nativeElement.textContent).toContain('CNV-2026-0001');
  });

  function render(state: ReportFollowUpState): void {
    fixture.componentRef.setInput('report', state);
    fixture.detectChanges();
  }

  function section(): HTMLElement | null {
    return fixture.nativeElement.querySelector('.report-progress');
  }

  function reportState(progressStage: ReporterProgressStage): ReportFollowUpState {
    return {
      publicReference: 'CNV-2026-0001',
      situationDescription: 'Una situación ocurrió durante el recreo.',
      situationContext: 'in_person',
      status: 'received',
      progressStage,
      createdAt: '2026-08-09T12:00:00.000+00:00',
      followUpEntries: [],
    };
  }
});
