import { HttpErrorResponse } from '@angular/common/http';
import { Component, OnInit, inject, output, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';

import { FollowUpService, ReporterEmailNotificationStatus } from './follow-up.service';

@Component({
  selector: 'app-reporter-email-notifications',
  standalone: true,
  imports: [ReactiveFormsModule],
  templateUrl: './reporter-email-notifications.html',
  styleUrl: './reporter-email-notifications.scss',
})
export class ReporterEmailNotifications implements OnInit {
  private readonly formBuilder = inject(FormBuilder);
  private readonly followUp = inject(FollowUpService);

  readonly accessRejected = output<void>();
  protected readonly state = signal<ReporterEmailNotificationStatus | null>(null);
  protected readonly loading = signal(true);
  protected readonly saving = signal(false);
  protected readonly removing = signal(false);
  protected readonly message = signal<string | null>(null);
  protected readonly error = signal<string | null>(null);

  protected readonly form = this.formBuilder.nonNullable.group({
    email: ['', [Validators.required, Validators.email, Validators.maxLength(254)]],
    consent: [false, Validators.requiredTrue],
  });

  ngOnInit(): void {
    this.load();
  }

  protected configure(): void {
    this.form.markAllAsTouched();

    if (this.form.invalid || this.saving()) {
      return;
    }

    this.saving.set(true);
    this.error.set(null);
    this.message.set(null);
    this.followUp.configureEmailNotifications(this.form.controls.email.value.trim()).subscribe({
      next: (state) => {
        this.state.set(state);
        this.saving.set(false);
        this.form.reset();
        this.message.set('Revisa tu correo y confirma el aviso en las próximas 24 horas.');
      },
      error: (error: unknown) => this.handleError(error),
    });
  }

  protected remove(): void {
    if (this.removing()) {
      return;
    }

    this.removing.set(true);
    this.error.set(null);
    this.followUp.removeEmailNotifications().subscribe({
      next: () => {
        this.state.set({ enabled: true, status: 'none' });
        this.removing.set(false);
        this.message.set('Ya no recibirás avisos por correo.');
      },
      error: (error: unknown) => this.handleError(error),
    });
  }

  private load(): void {
    this.followUp.getEmailNotificationStatus().subscribe({
      next: (state) => {
        this.state.set(state);
        this.loading.set(false);
      },
      error: (error: unknown) => this.handleError(error),
    });
  }

  private handleError(error: unknown): void {
    this.loading.set(false);
    this.saving.set(false);
    this.removing.set(false);

    if (error instanceof HttpErrorResponse && error.status === 401) {
      this.accessRejected.emit();
      return;
    }

    this.error.set('No hemos podido cambiar los avisos ahora. Inténtalo de nuevo.');
  }
}
