import {
  AfterViewInit,
  Component,
  ElementRef,
  inject,
  OnDestroy,
  OnInit,
  signal,
  ViewChild,
} from '@angular/core';
import { provideTranslocoScope, TranslocoPipe } from '@jsverse/transloco';

import { PublicSeoService } from '../public-seo.service';
import { PublicSiteFooter } from '../public-site-footer/public-site-footer';
import { LanguageSwitcher } from '../language-switcher/language-switcher';
import { professionalAccessUrlFor } from '../site-hosts';

@Component({
  selector: 'app-public-home',
  standalone: true,
  imports: [LanguageSwitcher, PublicSiteFooter, TranslocoPipe],
  providers: [provideTranslocoScope('public-home')],
  templateUrl: './public-home.html',
  styleUrl: './public-home.scss',
})
export class PublicHome implements AfterViewInit, OnDestroy, OnInit {
  @ViewChild('navigationToggle') private navigationToggle?: ElementRef<HTMLButtonElement>;
  @ViewChild('heroVideo') private heroVideo?: ElementRef<HTMLVideoElement>;

  private readonly seo = inject(PublicSeoService);
  private readonly reducedMotionQuery = globalThis.matchMedia?.('(prefers-reduced-motion: reduce)');
  readonly professionalAccessUrl = professionalAccessUrlFor(globalThis.location.hostname);
  protected readonly mobileNavigationOpen = signal(false);
  protected readonly reducedMotion = signal(this.reducedMotionQuery?.matches ?? false);
  protected readonly heroVideoUnavailable = signal(false);

  private readonly onReducedMotionChange = (event: MediaQueryListEvent): void => {
    this.reducedMotion.set(event.matches);

    if (event.matches) {
      this.heroVideo?.nativeElement.pause();
      return;
    }

    this.startHeroVideo();
  };

  private readonly onVisibilityChange = (): void => {
    if (!document.hidden) {
      this.startHeroVideo();
    }
  };

  ngOnInit(): void {
    this.seo.update({
      title: 'Convive',
      description:
        'Un canal seguro para que los centros educativos reciban, ordenen y respondan comunicaciones.',
      path: '/',
    });
    this.reducedMotionQuery?.addEventListener('change', this.onReducedMotionChange);
    document.addEventListener('visibilitychange', this.onVisibilityChange);
  }

  ngAfterViewInit(): void {
    this.startHeroVideo();
  }

  ngOnDestroy(): void {
    this.reducedMotionQuery?.removeEventListener('change', this.onReducedMotionChange);
    document.removeEventListener('visibilitychange', this.onVisibilityChange);
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

  protected showHeroFallback(): void {
    this.heroVideoUnavailable.set(true);
  }

  protected restartHeroVideo(): void {
    const video = this.heroVideo?.nativeElement;
    if (video === undefined || this.reducedMotion()) {
      return;
    }

    video.currentTime = 0;
    this.playHeroVideo(video);
  }

  protected resumeHeroVideo(): void {
    this.startHeroVideo();
  }

  private startHeroVideo(): void {
    const video = this.heroVideo?.nativeElement;
    if (video === undefined || this.reducedMotion()) {
      return;
    }

    this.playHeroVideo(video);
  }

  private playHeroVideo(video: HTMLVideoElement): void {
    video.defaultMuted = true;
    video.muted = true;
    video.loop = true;

    try {
      void video.play()?.catch(() => undefined);
    } catch {
      // The static poster remains available if the browser refuses playback.
    }
  }
}
