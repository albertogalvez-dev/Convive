import { Component, OnInit } from '@angular/core';

import { publicReportingUrlFor } from '../site-hosts';
import { DEMO_PUBLIC_REPORTING_IDENTIFIER } from './demo-public-reporting';

/**
 * Keeps the printable demonstration address short while directing it to the
 * same reserved fictional reporting route as the QR code.
 */
@Component({
  selector: 'app-public-demo-redirect',
  standalone: true,
  template: '<p>Abriendo la demostración…</p>',
})
export class PublicDemoRedirect implements OnInit {
  ngOnInit(): void {
    globalThis.location.replace(
      publicReportingUrlFor(globalThis.location.hostname, DEMO_PUBLIC_REPORTING_IDENTIFIER),
    );
  }
}
