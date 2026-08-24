import { Component, ElementRef, inject, OnInit, signal, ViewChild } from '@angular/core';
import { provideTranslocoScope, TranslocoPipe } from '@jsverse/transloco';

import { PublicSeoService } from '../public-seo.service';
import { PublicSiteFooter } from '../public-site-footer/public-site-footer';
import { professionalAccessUrlFor } from '../site-hosts';

@Component({
  selector: 'app-public-home',
  standalone: true,
  imports: [PublicSiteFooter, TranslocoPipe],
  providers: [provideTranslocoScope('public-home')],
  templateUrl: './public-home.html',
  styleUrl: './public-home.scss',
})
export class PublicHome implements OnInit {
  @ViewChild('navigationToggle') private navigationToggle?: ElementRef<HTMLButtonElement>;

  private readonly seo = inject(PublicSeoService);
  readonly professionalAccessUrl = professionalAccessUrlFor(globalThis.location.hostname);
  protected readonly mobileNavigationOpen = signal(false);

  ngOnInit(): void {
    this.seo.update({
      title: 'Convive',
      description:
        'Un canal seguro para que los centros educativos reciban, ordenen y respondan comunicaciones.',
      path: '/',
    });
  }

  protected toggleMobileNavigation(): void {
    this.mobileNavigationOpen.update((open) => !open);
  }

  protected closeMobileNavigation(returnFocus = false): void {
    this.mobileNavigationOpen.set(false);

    if (returnFocus) {
      queueMicrotask(() => this.navigationToggle?.nativeElement.focus());
    }
  }
}
