import { HttpErrorResponse } from '@angular/common/http';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';

import { ProfessionalSessionService } from '../professional-access/professional-session.service';
import { ProfessionalMembership, ProfessionalProfileService } from './professional-profile.service';

@Component({
  selector: 'app-professional-settings',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './professional-settings.html',
  styleUrl: './professional-settings.scss',
})
export class ProfessionalSettings implements OnInit {
  private readonly sessions = inject(ProfessionalSessionService);
  private readonly profiles = inject(ProfessionalProfileService);
  private readonly router = inject(Router);

  protected readonly professional = this.sessions.professional;
  protected readonly loggingOut = signal(false);
  protected readonly memberships = signal<ProfessionalMembership[]>([]);
  protected readonly name = signal('');
  protected readonly email = signal('');
  protected readonly saving = signal(false);
  protected readonly savedMessage = signal<string | null>(null);
  protected readonly errorMessage = signal<string | null>(null);

  ngOnInit(): void {
    this.profiles.get().subscribe({
      next: (profile) => {
        this.name.set(profile.name);
        this.email.set(profile.email);
        this.memberships.set(profile.memberships);
      },
      error: () => this.errorMessage.set('No hemos podido cargar tus datos.'),
    });
  }

  protected save(): void {
    if (this.saving()) {
      return;
    }

    this.saving.set(true);
    this.savedMessage.set(null);
    this.errorMessage.set(null);
    this.profiles.update({ name: this.name().trim(), email: this.email().trim() }).subscribe({
      next: (profile) => {
        this.saving.set(false);
        this.memberships.set(profile.memberships);
        if (profile.sessionEnded) {
          // The email is the login identifier, so the current session no longer
          // matches the account and the professional has to sign in again.
          this.sessions.professional.set(null);
          void this.router.navigate(['/profesionales/acceso']);
          return;
        }

        this.savedMessage.set('Hemos guardado tus datos.');
      },
      error: (error: unknown) => {
        this.saving.set(false);
        if (error instanceof HttpErrorResponse && error.status === 409) {
          this.errorMessage.set('Ese correo ya pertenece a otra cuenta.');
          return;
        }

        this.errorMessage.set('No hemos podido guardar los cambios. Revisa los datos.');
      },
    });
  }

  protected roleLabel(role: ProfessionalMembership['role']): string {
    return role === 'administrator' ? 'Administración' : 'Bienestar y protección';
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
