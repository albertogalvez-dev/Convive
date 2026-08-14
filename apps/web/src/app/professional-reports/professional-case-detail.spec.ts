import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute, Router } from '@angular/router';
import { vi } from 'vitest';

import { ProfessionalCaseDetailPage } from './professional-case-detail';

describe('ProfessionalCaseDetailPage', () => {
  const endpoint = '/api/v1/professional/cases/case-1';
  let fixture: ComponentFixture<ProfessionalCaseDetailPage>;
  let page: HTMLElement;
  let http: HttpTestingController;
  let navigate: ReturnType<typeof vi.fn>;

  beforeEach(async () => {
    navigate = vi.fn().mockResolvedValue(true);
    await TestBed.configureTestingModule({
      imports: [ProfessionalCaseDetailPage],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: ActivatedRoute, useValue: { snapshot: { paramMap: { get: () => 'case-1' } } } },
        { provide: Router, useValue: { navigate } },
      ],
    }).compileComponents();
    fixture = TestBed.createComponent(ProfessionalCaseDetailPage);
    page = fixture.nativeElement as HTMLElement;
    http = TestBed.inject(HttpTestingController);
    fixture.detectChanges();
  });

  afterEach(() => http.verify());

  it('renders operational tasks, source authority, a private evidence route and the explicit audit trail', () => {
    http.expectOne(endpoint).flush(detail());
    http.expectOne(`${endpoint}/audit-events`).flush({
      items: [
        {
          id: 'audit-1',
          action: 'task_created',
          target: 'task',
          actorName: 'Fictional Professional',
          occurredAt: '2026-08-11T09:30:00+00:00',
        },
      ],
    });
    fixture.detectChanges();

    expect(page.textContent).toContain('Comunicación con Inspección');
    expect(page.textContent).toContain('Normativa aplicable');
    expect(page.textContent).toContain('Persona afectada');
    expect(page.textContent).toContain('Decisi\u00f3n registrada');
    expect(page.textContent).toContain('Abierto como caso');
    expect(page.querySelector<HTMLAnchorElement>('.evidence-list a')?.getAttribute('href')).toBe(
      '/api/v1/professional/cases/case-1/evidence/evidence-1/download',
    );
    expect(page.textContent).toContain('Auditoría');
    expect(page.textContent).toContain('Tarea creada');
    expect(page.querySelector<HTMLAnchorElement>('.audit-panel a')?.getAttribute('href')).toBe(
      '/api/v1/professional/cases/case-1/audit-events/export',
    );
    expect(page.querySelector<HTMLAnchorElement>('.export-panel a')?.getAttribute('href')).toBe(
      '/api/v1/professional/cases/case-1/export',
    );
  });

  it('uses the indistinguishable unavailable state and redirects expired sessions', () => {
    http.expectOne(endpoint).flush(null, { status: 404, statusText: 'Not Found' });
    fixture.detectChanges();
    expect(page.textContent).toContain('Caso no disponible');

    const second = TestBed.createComponent(ProfessionalCaseDetailPage);
    second.detectChanges();
    http.expectOne(endpoint).flush(null, { status: 401, statusText: 'Unauthorized' });
    expect(navigate).toHaveBeenCalledWith(['/profesionales/acceso']);
  });

  it('records a reasoned contributor or observer access change through the exact-case endpoint', () => {
    http.expectOne(endpoint).flush(detail());
    http.expectOne(`${endpoint}/audit-events`).flush({ items: [] });
    fixture.detectChanges();

    const component = fixture.componentInstance as unknown as {
      openChangeAssignmentRole(id: string, role: 'contributor' | 'observer'): void;
      changeAssignmentReason: string;
      updateAssignmentRole(item: ReturnType<typeof detail>): void;
    };
    component.openChangeAssignmentRole('assignment-2', 'observer');
    component.changeAssignmentReason = 'Fictional collaboration scope changed.';
    component.updateAssignmentRole(detail());

    const request = http.expectOne(`${endpoint}/assignments/assignment-2/role`);
    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual({
      role: 'contributor',
      reason: 'Fictional collaboration scope changed.',
    });
    request.flush({
      id: 'assignment-2',
      professional: { id: 'professional-2', name: 'Fictional collaborator' },
      role: 'contributor',
      assignedAt: '2026-08-11T09:00:00+00:00',
    });
    http.expectOne(endpoint).flush(detail());
    http.expectOne(`${endpoint}/audit-events`).flush({ items: [] });
    fixture.detectChanges();

    expect(page.textContent).toContain(
      'El nivel de acceso se ha actualizado con un motivo registrado.',
    );
  });

  function detail() {
    return {
      id: 'case-1',
      status: 'assessment',
      modality: 'mixed',
      createdAt: '2026-08-11T09:00:00+00:00',
      organisationName: 'Fictional School',
      assignmentRole: 'lead',
      pendingTasks: 1,
      overdueTasks: 0,
      nextDueAt: '2026-08-12T09:00:00+00:00',
      permissions: { manage: true, manageAssignments: true, export: true, viewAudit: true },
      assignableProfessionals: [
        { id: 'professional-1', name: 'Fictional Professional' },
        { id: 'professional-2', name: 'Fictional collaborator' },
      ],
      people: [{ id: 'person-1', name: 'Fictional person', role: 'affected' }],
      assignments: [
        {
          id: 'assignment-1',
          professional: { id: 'professional-1', name: 'Fictional Professional' },
          role: 'lead',
          assignedAt: '2026-08-11T09:00:00+00:00',
        },
        {
          id: 'assignment-2',
          professional: { id: 'professional-2', name: 'Fictional collaborator' },
          role: 'observer',
          assignedAt: '2026-08-11T09:00:00+00:00',
        },
      ],
      tasks: [
        {
          id: 'task-1',
          title: 'Record fictional inspection communication',
          stage: 'inspection_communication',
          kind: 'external_communication',
          status: 'pending',
          dueAt: '2026-08-12T09:00:00+00:00',
          overdue: false,
          owner: { id: 'professional-1', name: 'Fictional Professional' },
          source: {
            id: 'source-1',
            title: 'Fictional protocol source',
            version: '2026.1',
            authority: 'binding',
            territory: 'Andalucia',
            uri: null,
          },
          resolvedAt: null,
          resolvedBy: null,
          notApplicableReason: null,
        },
      ],
      sourceReport: {
        id: 'report-1',
        publicReference: 'ABCDEF0123456789',
        decision: {
          outcome: 'link_to_case',
          reason: 'La comunicaci\u00f3n ficticia requiere seguimiento como caso.',
          decidedAt: '2026-08-11T09:00:00+00:00',
        },
      },
      evidence: [
        {
          id: 'evidence-1',
          description: 'Fictional available evidence',
          mediaType: 'application/pdf',
          byteSize: 512,
          createdAt: '2026-08-11T09:00:00+00:00',
        },
      ],
      timeline: [
        { type: 'case_created', occurredAt: '2026-08-11T09:00:00+00:00', label: 'Case created' },
      ],
    };
  }
});
