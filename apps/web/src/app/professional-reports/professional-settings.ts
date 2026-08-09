import { Component, inject, signal } from '@angular/core';
import { Router } from '@angular/router';

import { ProfessionalSessionService } from '../professional-access/professional-session.service';

@Component({
  selector: 'app-professional-settings',
  standalone: true,
  templateUrl: './professional-settings.html',
  styleUrl: './professional-settings.scss',
})
export class ProfessionalSettings {
  private readonly sessions = inject(ProfessionalSessionService);
  private readonly router = inject(Router);
  protected readonly professional = this.sessions.professional;
  protected readonly loggingOut = signal(false);

  protected logout(): void {
    if (this.loggingOut()) return;
    this.loggingOut.set(true);
    this.sessions.logout().subscribe({
      next: () => void this.router.navigate(['/profesionales/acceso']),
      error: () => this.loggingOut.set(false),
    });
  }
}
