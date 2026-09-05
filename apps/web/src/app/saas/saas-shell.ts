import { Component, input, output, signal } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';

export interface DemoRoleOption {
  key: string;
  label: string;
  person: string;
}

/**
 * Shared shell for the SaaS 2.0 professional-side review screens (#508, #513,
 * #526, #527). Real design system; fictional content. The demo role picker is a
 * review aid and emits (roleChange); it is not part of the real product.
 */
@Component({
  selector: 'app-saas-shell',
  standalone: true,
  imports: [RouterLink, RouterLinkActive],
  templateUrl: './saas-shell.html',
  styleUrl: './saas-shell.scss',
})
export class SaasShell {
  readonly ribbon = input.required<string>();
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
