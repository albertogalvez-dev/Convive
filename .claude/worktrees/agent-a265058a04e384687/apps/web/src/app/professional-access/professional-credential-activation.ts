import { Component, inject, signal } from '@angular/core';
import { FormControl, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';

import { ProfessionalAccountsService } from '../professional-reports/professional-accounts.service';

@Component({
  selector: 'app-professional-credential-activation',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './professional-credential-activation.html',
  styleUrl: './professional-credential-activation.scss',
})
export class ProfessionalCredentialActivation {
  private readonly accountsApi = inject(ProfessionalAccountsService);
  protected readonly completed = signal(false);
  protected readonly submitting = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly form = new FormGroup({
    secret: new FormControl('', [Validators.required, Validators.pattern(/^[a-f0-9]{64}$/)]),
    password: new FormControl('', [
      Validators.required,
      Validators.minLength(15),
      Validators.maxLength(255),
    ]),
  });

  protected submit(): void {
    if (this.form.invalid || this.submitting()) {
      this.form.markAllAsTouched();
      return;
    }
    const { secret, password } = this.form.getRawValue();
    if (!secret || !password) return;
    this.submitting.set(true);
    this.error.set(null);
    this.accountsApi.acceptCredential(secret, password).subscribe({
      next: () => {
        this.form.reset();
        this.completed.set(true);
        this.submitting.set(false);
      },
      error: () => {
        this.error.set(
          'El código no está disponible. Solicita uno nuevo a la administración del centro.',
        );
        this.submitting.set(false);
      },
    });
  }
}
