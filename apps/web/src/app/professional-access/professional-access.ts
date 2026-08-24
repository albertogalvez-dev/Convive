import { Component, inject, OnInit, signal } from '@angular/core';
import { Router } from '@angular/router';

import {
  DEMO_PROFESSIONAL_ROLES,
  demonstrationStartPath,
  DemoProfessionalRole,
} from './demo-professional-role';
import { ProfessionalSessionService } from './professional-session.service';

@Component({
  selector: 'app-professional-access',
  standalone: true,
  imports: [],
  templateUrl: './professional-access.html',
  styleUrl: './professional-access.scss',
})
export class ProfessionalAccess implements OnInit {
  private readonly sessions = inject(ProfessionalSessionService);
  private readonly router = inject(Router);

  protected readonly openingDemo = signal<DemoProfessionalRole | null>(null);
  protected readonly demoErrorMessage = signal<string | null>(null);
  protected readonly demoRoles = DEMO_PROFESSIONAL_ROLES;
  protected readonly selectedDemoRole = signal<DemoProfessionalRole>('triage');

  ngOnInit(): void {
    this.sessions.restore().subscribe({
      next: ({ demonstrationRole }) =>
        void this.router.navigateByUrl(
          demonstrationRole ? demonstrationStartPath(demonstrationRole) : '/profesionales',
        ),
      error: () => {
        // An absent or expired session is the normal anonymous entry state.
      },
    });
  }

  protected openDemonstration(role: DemoProfessionalRole): void {
    if (this.openingDemo() !== null) {
      return;
    }

    this.openingDemo.set(role);
    this.demoErrorMessage.set(null);
    this.sessions.startDemonstration(role).subscribe({
      next: () => {
        this.openingDemo.set(null);
        void this.router.navigateByUrl(demonstrationStartPath(role));
      },
      error: () => {
        this.openingDemo.set(null);
        this.demoErrorMessage.set(
          'No hemos podido abrir la demostración. Inténtalo de nuevo más tarde.',
        );
      },
    });
  }

  protected selectDemonstration(event: Event): void {
    const role = (event.target as HTMLSelectElement).value as DemoProfessionalRole;

    if (this.demoRoles.some((option) => option.id === role)) {
      this.selectedDemoRole.set(role);
    }
  }
}
