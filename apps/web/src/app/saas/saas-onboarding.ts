import { Component, signal } from '@angular/core';

/**
 * SaaS 2.0 — account onboarding (issue #511, expectations T-1 / T-12 / T-9).
 * Sign-up, the pre-centre empty state, and account settings. Real screen for
 * owner review; fictional data. The view switcher is a review aid.
 */

type OnboardingView = 'signup' | 'empty' | 'settings';

@Component({
  selector: 'app-saas-onboarding',
  standalone: true,
  templateUrl: './saas-onboarding.html',
  styleUrl: './saas-onboarding.scss',
})
export class SaasOnboarding {
  protected readonly view = signal<OnboardingView>('signup');
  protected readonly views: readonly { key: OnboardingView; label: string }[] = [
    { key: 'signup', label: 'Registro' },
    { key: 'empty', label: 'Cuenta sin centro' },
    { key: 'settings', label: 'Ajustes de cuenta' },
  ];

  protected setView(key: OnboardingView): void {
    this.view.set(key);
  }
}
