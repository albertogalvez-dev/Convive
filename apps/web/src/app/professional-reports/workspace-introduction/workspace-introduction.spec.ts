import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ProfessionalSessionService } from '../../professional-access/professional-session.service';
import { WORKSPACE_INTRODUCTION_STEPS } from './workspace-introduction-steps';
import { WorkspaceIntroduction } from './workspace-introduction';

describe('WorkspaceIntroduction', () => {
  let fixture: ComponentFixture<WorkspaceIntroduction>;
  let page: HTMLElement;
  let sessions: ProfessionalSessionService;

  const professionalId = 'professional-1';
  const storageKey = `convive.workspace-introduction.${professionalId}`;

  const render = async (): Promise<void> => {
    await TestBed.configureTestingModule({
      imports: [WorkspaceIntroduction],
      providers: [provideHttpClient(), provideHttpClientTesting()],
    }).compileComponents();
    sessions = TestBed.inject(ProfessionalSessionService);
    fixture = TestBed.createComponent(WorkspaceIntroduction);
    page = fixture.nativeElement as HTMLElement;
    sessions.professional.set({ id: professionalId, name: 'Laura Martín', email: 'l@e.invalid' });
    fixture.detectChanges();
  };

  const buttonLabelled = (label: string): HTMLButtonElement | undefined =>
    Array.from(page.querySelectorAll('button')).find((button) =>
      button.textContent?.trim().startsWith(label),
    );

  beforeEach(() => localStorage.removeItem(storageKey));
  afterEach(() => localStorage.removeItem(storageKey));

  it('introduces a first-time professional and states the position in the sequence', async () => {
    await render();

    expect(page.querySelector('[role="dialog"]')).not.toBeNull();
    expect(page.querySelector('.position')?.textContent).toContain(
      `Paso 1 de ${WORKSPACE_INTRODUCTION_STEPS.length}`,
    );
    // The position is announced, not merely displayed.
    expect(page.querySelector('.position')?.getAttribute('role')).toBe('status');
    expect(page.querySelector('[role="dialog"]')?.getAttribute('aria-modal')).toBe('true');
  });

  it('does not introduce a professional who has already been introduced', async () => {
    localStorage.setItem(storageKey, 'seen');

    await render();

    expect(page.querySelector('[role="dialog"]')).toBeNull();
  });

  it('can be skipped from the very first step and never comes back', async () => {
    await render();

    buttonLabelled('Saltar')?.click();
    fixture.detectChanges();

    expect(page.querySelector('[role="dialog"]')).toBeNull();
    expect(localStorage.getItem(storageKey)).toBe('seen');
  });

  it('exits on Escape from any step, not only the first', async () => {
    await render();

    buttonLabelled('Siguiente')?.click();
    fixture.detectChanges();
    expect(page.querySelector('.position')?.textContent).toContain('Paso 2');

    page
      .querySelector('.introduction-backdrop')
      ?.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
    fixture.detectChanges();

    expect(page.querySelector('[role="dialog"]')).toBeNull();
    expect(localStorage.getItem(storageKey)).toBe('seen');
  });

  it('walks the whole sequence and closes at the end', async () => {
    await render();

    for (let step = 1; step < WORKSPACE_INTRODUCTION_STEPS.length; step += 1) {
      buttonLabelled('Siguiente')?.click();
      fixture.detectChanges();
    }
    expect(page.querySelector('.position')?.textContent).toContain(
      `Paso ${WORKSPACE_INTRODUCTION_STEPS.length}`,
    );

    buttonLabelled('Empezar')?.click();
    fixture.detectChanges();

    expect(page.querySelector('[role="dialog"]')).toBeNull();
  });

  it('keeps Tab inside the dialog so the workspace behind it is never reached', async () => {
    await render();

    const buttons = Array.from(page.querySelectorAll('button'));
    const last = buttons[buttons.length - 1];
    last.focus();
    const event = new KeyboardEvent('keydown', { key: 'Tab', bubbles: true, cancelable: true });
    page.querySelector('.introduction-backdrop')?.dispatchEvent(event);
    fixture.detectChanges();

    expect(event.defaultPrevented).toBe(true);
    expect(document.activeElement).toBe(buttons[0]);
  });

  it('is fully operable by keyboard, including the way out', async () => {
    await render();

    // Every control is a real button, so it is reachable and activatable with a
    // keyboard without any extra handling.
    const controls = Array.from(page.querySelectorAll('.controls *'));
    expect(
      controls.every((element) => element.tagName !== 'DIV' || element.className === 'advance'),
    ).toBe(true);
    expect(buttonLabelled('Saltar')).toBeDefined();
  });

  it('says nothing about what the product decides, and shows no case content', () => {
    const text = WORKSPACE_INTRODUCTION_STEPS.flatMap((step) => [step.title, ...step.body]).join(
      ' ',
    );

    // The case interface states that Convive neither decides an obligation nor
    // calculates a deadline. The introduction must not contradict it.
    expect(text).toContain('no decide qué hay que hacer');
    expect(text).toContain('no deciden una obligación ni calculan un plazo');
    expect(text).toContain('Pertenecer al centro no te muestra ningún caso');
    expect(text).not.toMatch(/caso [A-Z0-9]{6,}/);
  });
});
