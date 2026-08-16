import { Component, inject, OnInit } from '@angular/core';

import { PublicSeoService } from '../public-seo.service';
import { PublicSiteFooter } from '../public-site-footer/public-site-footer';
import { professionalAccessUrlFor } from '../site-hosts';

@Component({
  selector: 'app-public-home',
  standalone: true,
  imports: [PublicSiteFooter],
  templateUrl: './public-home.html',
  styleUrl: './public-home.scss',
})
export class PublicHome implements OnInit {
  private readonly seo = inject(PublicSeoService);
  readonly professionalAccessUrl = professionalAccessUrlFor(globalThis.location.hostname);

  ngOnInit(): void {
    this.seo.update({
      title: 'Convive',
      description:
        'Un canal seguro para que los centros educativos reciban, ordenen y respondan comunicaciones.',
      path: '/',
    });
  }
}
