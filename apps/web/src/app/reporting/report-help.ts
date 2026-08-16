import { DOCUMENT } from '@angular/common';
import {
  AfterViewInit,
  Component,
  ElementRef,
  HostListener,
  OnDestroy,
  inject,
  output,
  viewChild,
} from '@angular/core';

@Component({
  selector: 'app-report-help',
  standalone: true,
  templateUrl: './report-help.html',
  styleUrl: './report-help.scss',
})
export class ReportHelp implements AfterViewInit, OnDestroy {
  private readonly document = inject(DOCUMENT);
  private readonly dialog = viewChild.required<ElementRef<HTMLElement>>('dialog');
  private readonly previouslyFocusedElement =
    this.document.activeElement instanceof HTMLElement ? this.document.activeElement : null;

  readonly closed = output<void>();

  ngAfterViewInit(): void {
    this.dialog().nativeElement.focus();
  }

  ngOnDestroy(): void {
    this.previouslyFocusedElement?.focus();
  }

  /**
   * The backdrop is a div with role="button" rather than a real button,
   * because a button cannot legally contain the dialog's own interactive
   * content (its close button, in particular). Space would otherwise scroll
   * the page instead of activating it, the way it does for a native button.
   */
  protected onSpace(event: Event): void {
    event.preventDefault();
    this.closed.emit();
  }

  @HostListener('document:keydown', ['$event'])
  protected handleKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
      event.preventDefault();
      this.closed.emit();
      return;
    }

    if (event.key !== 'Tab') {
      return;
    }

    const focusableElements = Array.from(
      this.dialog().nativeElement.querySelectorAll<HTMLElement>(
        'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
      ),
    ).filter((element) => !element.hasAttribute('hidden'));

    if (focusableElements.length === 0) {
      event.preventDefault();
      this.dialog().nativeElement.focus();
      return;
    }

    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];
    const activeElement = this.document.activeElement;

    if (event.shiftKey && activeElement === firstElement) {
      event.preventDefault();
      lastElement.focus();
    } else if (!event.shiftKey && activeElement === lastElement) {
      event.preventDefault();
      firstElement.focus();
    } else if (!this.dialog().nativeElement.contains(activeElement)) {
      event.preventDefault();
      firstElement.focus();
    }
  }
}
