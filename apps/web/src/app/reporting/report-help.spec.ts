import { ComponentFixture, TestBed } from '@angular/core/testing';
import { vi } from 'vitest';

import { ReportHelp } from './report-help';

describe('ReportHelp', () => {
  let fixture: ComponentFixture<ReportHelp>;
  let page: HTMLElement;
  let closed: ReturnType<typeof vi.spyOn>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({ imports: [ReportHelp] }).compileComponents();
    fixture = TestBed.createComponent(ReportHelp);
    page = fixture.nativeElement as HTMLElement;
    closed = vi.spyOn(fixture.componentInstance.closed, 'emit');
    fixture.detectChanges();
  });

  const backdrop = (): HTMLElement => page.querySelector('.backdrop') as HTMLElement;

  it('exposes the backdrop as a keyboard-reachable control, not a bare div', () => {
    const element = backdrop();

    expect(element.getAttribute('role')).toBe('button');
    expect(element.getAttribute('tabindex')).toBe('0');
    expect(element.hasAttribute('aria-label')).toBe(true);
  });

  it('closes on Enter, the same as a native button would', () => {
    backdrop().dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }));

    expect(closed).toHaveBeenCalled();
  });

  it('closes on Space and prevents the page from scrolling', () => {
    const event = new KeyboardEvent('keydown', { key: ' ', bubbles: true, cancelable: true });
    backdrop().dispatchEvent(event);

    expect(closed).toHaveBeenCalled();
    expect(event.defaultPrevented).toBe(true);
  });

  it('closes on click, unchanged from before', () => {
    backdrop().click();

    expect(closed).toHaveBeenCalled();
  });

  it('does not close when a click on the dialog content bubbles, since it stops propagation', () => {
    page.querySelector('.dialog')?.dispatchEvent(new MouseEvent('click', { bubbles: true }));

    expect(closed).not.toHaveBeenCalled();
  });

  it('still closes via its own explicit close button', () => {
    const closeButton = Array.from(page.querySelectorAll('button')).find(
      (button) => button.className === 'close',
    );

    closeButton?.click();

    expect(closed).toHaveBeenCalled();
  });
});
