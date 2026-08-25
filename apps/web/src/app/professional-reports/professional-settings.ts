import { HttpErrorResponse } from '@angular/common/http';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';

import { ProfessionalSessionService } from '../professional-access/professional-session.service';
import {
  ProfessionalAbsence,
  ProfessionalMembership,
  ProfessionalProfileService,
} from './professional-profile.service';

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
  protected readonly isDemonstration = computed(() => this.sessions.demonstrationRole() !== null);
  protected readonly loggingOut = signal(false);
  protected readonly memberships = signal<ProfessionalMembership[]>([]);
  protected readonly name = signal('');
  protected readonly email = signal('');
  protected readonly saving = signal(false);
  protected readonly savedMessage = signal<string | null>(null);
  protected readonly errorMessage = signal<string | null>(null);
  protected readonly confirmingEmail = signal(false);
  protected readonly absences = signal<ProfessionalAbsence[]>([]);
  protected readonly absenceForm = signal({ startsOn: '', endsOn: '', note: '' });
  protected readonly absenceError = signal<string | null>(null);

  /** The address the profile was loaded with, used to detect a real change. */
  private currentEmail = '';

  ngOnInit(): void {
    this.profiles.get().subscribe({
      next: (profile) => {
        this.name.set(profile.name);
        this.email.set(profile.email);
        this.currentEmail = profile.email;
        this.memberships.set(profile.memberships);
      },
      error: () => this.errorMessage.set('No hemos podido cargar tus datos.'),
    });
    if (!this.isDemonstration()) {
      this.loadAbsences();
    }
  }

  protected recordAbsence(): void {
    const form = this.absenceForm();
    this.absenceError.set(null);
    this.profiles
      .recordAbsence({ startsOn: form.startsOn, endsOn: form.endsOn, note: form.note || undefined })
      .subscribe({
        next: () => {
          this.absenceForm.set({ startsOn: '', endsOn: '', note: '' });
          this.loadAbsences();
        },
        error: () =>
          this.absenceError.set('Revisa las fechas: la vuelta no puede ser anterior a la salida.'),
      });
  }

  protected cancelAbsence(absence: ProfessionalAbsence): void {
    this.profiles.cancelAbsence(absence.id).subscribe({
      next: () => this.loadAbsences(),
      error: () => this.absenceError.set('No hemos podido anular esta ausencia.'),
    });
  }

  private loadAbsences(): void {
    this.profiles.absences().subscribe({
      next: ({ items }) => this.absences.set(items),
      error: () => this.absenceError.set('No hemos podido cargar tus ausencias.'),
    });
  }

  protected save(): void {
    if (this.saving()) {
      return;
    }

    // Replacing the login identifier can lock the professional out, so it is
    // confirmed explicitly rather than saved with the rest of the form.
    if (this.email().trim().toLowerCase() !== this.currentEmail.toLowerCase()) {
      this.confirmingEmail.set(true);
      return;
    }

    this.persist();
  }

  protected cancelEmailChange(): void {
    this.confirmingEmail.set(false);
    this.email.set(this.currentEmail);
  }

  protected confirmEmailChange(): void {
    this.confirmingEmail.set(false);
    this.persist();
  }

  private persist(): void {
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

        this.currentEmail = profile.email;
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
