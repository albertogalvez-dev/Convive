import { Component, input, signal } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';

/**
 * Shared professional-area shell for the SaaS 2.0 screens (#508, #513, #526,
 * #527). Mirrors the delivered professional portal shell: sticky collapsible
 * sidebar on desktop, header navigation on mobile.
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

  protected toggle(): void {
    this.collapsed.update((value) => !value);
  }
}
