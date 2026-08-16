import { Component } from '@angular/core';
import { provideTranslocoScope, TranslocoPipe } from '@jsverse/transloco';

import { PUBLIC_EMERGENCY_RESOURCES } from '../public-emergency-resources';
import {
  PUBLIC_GENERAL_EMAIL,
  PUBLIC_OPERATOR_NAME,
  PUBLIC_SPONSOR_CREDIT,
} from '../public-identity';

/**
 * The footer shared by every page of the public website.
 *
 * It carries the three statements that must never depend on a visitor reaching a
 * particular page: that the demonstration is fictional, that it is not an emergency
 * channel nor an official Junta de Andalucía service, and where to turn instead.
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
  readonly operatorName = PUBLIC_OPERATOR_NAME;
  readonly generalEmail = PUBLIC_GENERAL_EMAIL;
  readonly sponsorCredit = PUBLIC_SPONSOR_CREDIT;
}
