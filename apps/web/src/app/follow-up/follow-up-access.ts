import { Component, inject, input, output, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';

/**
 * The canonical access-secret profile accepted by the backend: exactly 64
 * lowercase hexadecimal characters (ADR-0010). Alternative representations
 * are not normalised into it; they are rejected so the reporter can correct
 * the value before an attempt is spent against the verification limit.
 */
const ACCESS_SECRET_PATTERN = /^[0-9a-f]{64}$/;

@Component({
  selector: 'app-follow-up-access',
  standalone: true,
  imports: [ReactiveFormsModule],
  templateUrl: './follow-up-access.html',
  styleUrl: './follow-up-access.scss',
})
export class FollowUpAccess {
  private readonly formBuilder = inject(FormBuilder);

  readonly verifying = input(false);
  readonly errorMessage = input<string | null>(null);
  readonly notice = input<string | null>(null);
  readonly secretSubmitted = output<string>();

  protected readonly secretVisible = signal(false);

  /**
   * `reference` is never sent to the API: verification accepts only the
   * secret (ADR-0010). It exists so the browser credential manager can
   * label and autofill the pair it was allowed to store (ADR-0011).
   */
  protected readonly form = this.formBuilder.nonNullable.group({
    reference: [''],
    accessSecret: ['', [Validators.required, Validators.pattern(ACCESS_SECRET_PATTERN)]],
  });

  protected submit(): void {
    const control = this.form.controls.accessSecret;

    control.setValue(control.value.trim());
    control.markAsTouched();

    if (this.verifying() || control.invalid) {
      return;
    }

    this.secretSubmitted.emit(control.value);
  }

  protected toggleSecretVisibility(): void {
    this.secretVisible.update((visible) => !visible);
  }
}
