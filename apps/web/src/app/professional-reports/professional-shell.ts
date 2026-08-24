import { Component, inject, OnInit, signal } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';

import { ProfessionalSessionService } from '../professional-access/professional-session.service';
import {
  demonstrationRoleLabel,
  isCaseDemonstrationRole,
} from '../professional-access/demo-professional-role';
import { ProfessionalNotificationsService } from './professional-notifications.service';
import { ProfessionalPortalStore } from './professional-portal.store';
import { WorkspaceIntroduction } from './workspace-introduction/workspace-introduction';

@Component({
  selector: 'app-professional-shell',
  standalone: true,
  imports: [RouterLink, RouterLinkActive, RouterOutlet, WorkspaceIntroduction],
  templateUrl: './professional-shell.html',
  styleUrl: './professional-shell.scss',
})
export class ProfessionalShell implements OnInit {
  private readonly sessions = inject(ProfessionalSessionService);
  private readonly router = inject(Router);
  private readonly portal = inject(ProfessionalPortalStore);
  private readonly notifications = inject(ProfessionalNotificationsService);

  protected readonly professional = this.sessions.professional;
  protected readonly demonstrationRole = this.sessions.demonstrationRole;
  protected readonly demonstrationRoleLabel = demonstrationRoleLabel;
  protected readonly loggingOut = signal(false);
  protected readonly sidebarCollapsed = signal(false);
  protected readonly newNotificationCount = signal<number | null>(null);

  ngOnInit(): void {
    if (
      this.demonstrationRole() === 'administrator' ||
      isCaseDemonstrationRole(this.demonstrationRole())
    ) {
      return;
    }
    this.portal.load();
    this.notifications
      .list()
      .subscribe({ next: ({ unreadCount }) => this.newNotificationCount.set(unreadCount) });
  }

  protected toggleSidebar(): void {
    this.sidebarCollapsed.update((collapsed) => !collapsed);
  }

  protected showCentreNavigation(): boolean {
    const role = this.demonstrationRole();

    return role === null || role === 'triage';
  }

  protected showCases(): boolean {
    return this.demonstrationRole() !== 'administrator';
  }

  protected showAccounts(): boolean {
    const role = this.demonstrationRole();

    return role === null || role === 'administrator';
  }

  protected logout(): void {
    if (this.loggingOut()) return;
    this.loggingOut.set(true);
    this.sessions.logout().subscribe({
      next: () => void this.router.navigate(['/profesionales/acceso']),
      error: () => this.loggingOut.set(false),
    });
  }
}
