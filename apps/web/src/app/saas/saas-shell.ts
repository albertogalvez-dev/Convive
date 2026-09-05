import { Component, DestroyRef, computed, inject, input, signal } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';

const DATE_FMT = new Intl.DateTimeFormat('es-ES', {
  weekday: 'long',
  day: 'numeric',
  month: 'long',
  year: 'numeric',
});
const TIME_FMT = new Intl.DateTimeFormat('es-ES', { hour: '2-digit', minute: '2-digit' });

/**
 * Shared professional-area shell for the SaaS 2.0 screens. Mirrors the
 * delivered professional portal: sticky collapsible sidebar on desktop,
 * header navigation on mobile, and a live date-and-time in the top bar.
 */
@Component({
  selector: 'app-saas-shell',
  standalone: true,
  imports: [RouterLink, RouterLinkActive],
  templateUrl: './saas-shell.html',
  styleUrl: './saas-shell.scss',
})
export class SaasShell {
  readonly personName = input.required<string>();
  readonly centreName = input.required<string>();
  readonly unread = input<number>(0);

  protected readonly collapsed = signal(false);

  private readonly now = signal(new Date());
  protected readonly clockDate = computed(() => {
    const formatted = DATE_FMT.format(this.now());
    return formatted.charAt(0).toUpperCase() + formatted.slice(1);
  });
  protected readonly clockTime = computed(() => TIME_FMT.format(this.now()));

  constructor() {
    const ticker = setInterval(() => this.now.set(new Date()), 15_000);
    inject(DestroyRef).onDestroy(() => clearInterval(ticker));
  }

  protected toggle(): void {
    this.collapsed.update((value) => !value);
  }
}
