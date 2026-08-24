import { Component } from '@angular/core';
import { provideTranslocoScope, TranslocoPipe } from '@jsverse/transloco';

import { PUBLIC_EMERGENCY_RESOURCES } from '../public-emergency-resources';
import { PUBLIC_SPONSOR_CREDIT_PREFIX, PUBLIC_SPONSOR_NAME } from '../public-identity';

/**
 * The footer shared by every page of the public website.
 *
 * It carries the three statements that must never depend on a visitor reaching a
 * particular page: that the demonstration is fictional, that it is not an emergency
 * channel nor an official service of any education authority, and where to turn instead.
 *
 * Its static copy is the first surface extracted into the i18n layer built in
 * #255 — every page includes this component, so proving the foundation here
 * proves it everywhere it is reused.
 */
@Component({
  selector: 'app-public-site-footer',
  standalone: true,
  imports: [TranslocoPipe],
  providers: [provideTranslocoScope('public-site-footer')],
  templateUrl: './public-site-footer.html',
  styleUrl: './public-site-footer.scss',
})
export class PublicSiteFooter {
  readonly emergencyResources = PUBLIC_EMERGENCY_RESOURCES;
  readonly sponsorCreditPrefix = PUBLIC_SPONSOR_CREDIT_PREFIX;
  readonly sponsorName = PUBLIC_SPONSOR_NAME;
}
