import { DOCUMENT } from '@angular/common';
import {
  AfterViewInit,
  Component,
  effect,
  ElementRef,
  inject,
  OnDestroy,
  signal,
  viewChild,
} from '@angular/core';

import { ProfessionalSessionService } from '../../professional-access/professional-session.service';
import {
  WORKSPACE_INTRODUCTION_STEPS,
  WorkspaceIntroductionStep,
} from './workspace-introduction-steps';

const STORAGE_PREFIX = 'convive.workspace-introduction.';

/**
 * A short introduction to the professional workspace for a first-time visitor.
 *
 * It is a plain modal dialog rather than a spotlight overlay with cut-outs.
 * Spotlights are the pattern most often shipped with a broken focus order, and
 * nothing here needs one: the value is in what the steps say, not in dimming
 * part of the page.
 *
 * The dialog can be left from the first step, by the skip button, by the close
 * button or with Escape, and leaving by any of those routes means it never
 * appears again. Focus is confined while it is open and returns to whatever had
 * it before, so a keyboard user is never stranded.
 */
@Component({
  selector: 'app-workspace-introduction',
  standalone: true,
  templateUrl: './workspace-introduction.html',
  styleUrl: './workspace-introduction.scss',
})
export class WorkspaceIntroduction implements AfterViewInit, OnDestroy {
  private readonly document = inject(DOCUMENT);
  private readonly sessions = inject(ProfessionalSessionService);
  private readonly dialog = viewChild<ElementRef<HTMLElement>>('dialog');

  protected readonly steps: readonly WorkspaceIntroductionStep[] = WORKSPACE_INTRODUCTION_STEPS;
  protected readonly stepIndex = signal(0);
  protected readonly open = signal(false);

  private previouslyFocused: HTMLElement | null = null;
  private decided = false;

  constructor() {
    // The identity arrives from the session signal, which may still be loading
    // when this component is created. Deciding in the constructor would read a
    // null professional and silently swallow the introduction.
    effect(() => {
      const professional = this.sessions.professional();
      if (this.decided || professional === null) return;

      this.decided = true;
      if (this.hasSeenIntroduction()) return;

      this.previouslyFocused = this.document.activeElement as HTMLElement | null;
      this.open.set(true);
      this.focusDialog();
    });
  }

  ngAfterViewInit(): void {
    this.focusDialog();
  }

  ngOnDestroy(): void {
    this.restoreFocus();
  }

  protected get isLastStep(): boolean {
    return this.stepIndex() === this.steps.length - 1;
  }

  protected next(): void {
    if (this.isLastStep) {
      this.dismiss();

      return;
    }
    this.stepIndex.update((index) => index + 1);
    this.focusDialog();
  }

  protected previous(): void {
    if (this.stepIndex() === 0) return;
    this.stepIndex.update((index) => index - 1);
    this.focusDialog();
  }

  /**
   * Leaving by any route is final. A person who skipped the introduction on
   * their first visit did so on purpose, and showing it again would override
   * that decision rather than help.
   */
  protected dismiss(): void {
    if (!this.open()) return;
    this.rememberSeen();
    this.open.set(false);
    this.restoreFocus();
  }

  protected onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
      event.preventDefault();
      this.dismiss();

      return;
    }
    if (event.key === 'Tab') {
      this.confineFocus(event);
    }
  }

  /**
   * Keeps Tab and Shift+Tab inside the dialog by wrapping at its ends, so the
   * browser never moves focus to the workspace behind it.
   */
  private confineFocus(event: KeyboardEvent): void {
    const focusable = this.focusableElements();
    if (focusable.length === 0) return;

    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    const active = this.document.activeElement;

    if (event.shiftKey && (active === first || active === this.dialog()?.nativeElement)) {
      event.preventDefault();
      last.focus();

      return;
    }
    if (!event.shiftKey && active === last) {
      event.preventDefault();
      first.focus();
    }
  }

  private focusableElements(): HTMLElement[] {
    const container = this.dialog()?.nativeElement;
    if (container === undefined) return [];

    return Array.from(
      container.querySelectorAll<HTMLElement>('button:not([disabled]), [href], [tabindex="0"]'),
    );
  }

  private focusDialog(): void {
    if (!this.open()) return;
    queueMicrotask(() => this.dialog()?.nativeElement.focus());
  }

  private restoreFocus(): void {
    this.previouslyFocused?.focus?.();
    this.previouslyFocused = null;
  }

  /**
   * The flag is stored per professional rather than per browser. A centre
   * computer is shared, and a colleague signing in for the first time on it
   * deserves the introduction the criterion promises them.
   */
  private storageKey(): string | null {
    const professional = this.sessions.professional();

    return professional === null ? null : STORAGE_PREFIX + professional.id;
  }

  private hasSeenIntroduction(): boolean {
    const key = this.storageKey();
    if (key === null) return true;

    try {
      return globalThis.localStorage?.getItem(key) === 'seen';
    } catch {
      // A browser that refuses storage would otherwise show the introduction on
      // every single visit, which is worse than never showing it.
      return true;
    }
  }

  private rememberSeen(): void {
    const key = this.storageKey();
    if (key === null) return;

    try {
      globalThis.localStorage?.setItem(key, 'seen');
    } catch {
      // Nothing to do: the introduction stays dismissed for this visit.
    }
  }
}
