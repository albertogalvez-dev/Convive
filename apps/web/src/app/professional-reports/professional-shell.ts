import { Component, inject, OnInit, signal } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';

import { ProfessionalSessionService } from '../professional-access/professional-session.service';
import { ProfessionalPortalStore } from './professional-portal.store';

@Component({
  selector: 'app-professional-shell',
  standalone: true,
  imports: [RouterLink, RouterLinkActive, RouterOutlet],
  templateUrl: './professional-shell.html',
  styleUrl: './professional-shell.scss',
})
export class ProfessionalShell implements OnInit {
  private readonly sessions = inject(ProfessionalSessionService);
  private readonly router = inject(Router);
  private readonly portal = inject(ProfessionalPortalStore);

  protected readonly professional = this.sessions.professional;
  protected readonly loggingOut = signal(false);
  protected readonly sidebarCollapsed = signal(false);
  protected readonly newNotificationCount = this.portal.newNotificationCount;

  ngOnInit(): void {
    this.portal.load();
  }

  protected toggleSidebar(): void {
    this.sidebarCollapsed.update((collapsed) => !collapsed);
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
