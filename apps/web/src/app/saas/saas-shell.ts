import { Component, input, output, signal } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';

export interface DemoRoleOption {
  key: string;
  label: string;
  person: string;
}

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
  readonly roles = input<readonly DemoRoleOption[]>([]);
  readonly activeRole = input<string>('');

  readonly roleChange = output<string>();

  protected readonly collapsed = signal(false);

  protected toggle(): void {
    this.collapsed.update((value) => !value);
  }

  protected onRolePick(event: Event): void {
    this.roleChange.emit((event.target as HTMLSelectElement).value);
  }
}
