import { Component, inject } from '@angular/core';
import { ActivatedRoute } from '@angular/router';

/**
 * SaaS 2.0 — account screens (issue #511, expectations T-1 / T-12 / T-9).
 * Sign-up, the account before any centre, and account settings. Each view is
 * its own route. Fictional data.
 */

export type OnboardingView = 'signup' | 'empty' | 'settings';

@Component({
  selector: 'app-saas-onboarding',
  standalone: true,
  templateUrl: './saas-onboarding.html',
  styleUrl: './saas-onboarding.scss',
})
export class SaasOnboarding {
  protected readonly view: OnboardingView =
    (inject(ActivatedRoute).snapshot.data['view'] as OnboardingView) ?? 'signup';
}
