import { HttpErrorResponse } from '@angular/common/http';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';

import { ProfessionalIdentity, ProfessionalSessionService } from './professional-session.service';

@Component({
  selector: 'app-professional-access',
  standalone: true,
  imports: [ReactiveFormsModule],
  templateUrl: './professional-access.html',
  styleUrl: './professional-access.scss',
})
export class ProfessionalAccess implements OnInit {
  private readonly formBuilder = inject(FormBuilder);
  private readonly sessions = inject(ProfessionalSessionService);

  protected readonly submitting = signal(false);
  protected readonly passwordVisible = signal(false);
  protected readonly errorMessage = signal<string | null>(null);
  protected readonly professional = signal<ProfessionalIdentity | null>(null);
  protected readonly loggingOut = signal(false);

  protected readonly form = this.formBuilder.nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', Validators.required],
  });

  ngOnInit(): void {
    this.sessions.current().subscribe({
      next: ({ professional }) => this.professional.set(professional),
      error: () => {
        // An absent or expired session is the normal anonymous entry state.
      },
    });
  }

  protected login(): void {
    this.form.markAllAsTouched();

    if (this.submitting() || this.form.invalid) {
      return;
    }

    this.submitting.set(true);
    this.errorMessage.set(null);

    const { email, password } = this.form.getRawValue();

    this.sessions.login(email.trim().toLowerCase(), password).subscribe({
      next: ({ professional }) => {
        this.submitting.set(false);
        this.form.reset();
        this.professional.set(professional);
      },
      error: (error: unknown) => {
        this.submitting.set(false);
        this.form.controls.password.reset();
        this.errorMessage.set(describeLoginError(error));
      },
    });
  }

  protected logout(): void {
    if (this.loggingOut()) {
      return;
    }

    this.loggingOut.set(true);
    this.errorMessage.set(null);

    this.sessions.logout().subscribe({
      next: () => {
        this.loggingOut.set(false);
        this.professional.set(null);
      },
      error: () => {
        this.loggingOut.set(false);
        this.errorMessage.set('No hemos podido cerrar la sesión. Inténtalo de nuevo.');
      },
    });
  }

  protected togglePasswordVisibility(): void {
    this.passwordVisible.update((visible) => !visible);
  }
}

function describeLoginError(error: unknown): string {
  if (error instanceof HttpErrorResponse) {
    if (error.status === 401) {
      return 'El correo o la contraseña no son correctos.';
    }

    if (error.status === 429) {
      return 'Demasiados intentos. Espera unos minutos antes de volver a probar.';
    }
  }

  return 'No hemos podido iniciar sesión. Inténtalo de nuevo más tarde.';
}
