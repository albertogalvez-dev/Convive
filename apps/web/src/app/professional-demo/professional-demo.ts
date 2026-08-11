import { Component, inject, OnDestroy, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';

import { PublicSeoService } from '../public-seo.service';

const VIEWS = ['Bandeja', 'Revisión', 'Caso'] as const;

@Component({
  selector: 'app-professional-demo',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './professional-demo.html',
  styleUrl: './professional-demo.scss',
})
export class ProfessionalDemo implements OnInit, OnDestroy {
  private readonly seo = inject(PublicSeoService);
  private timer: ReturnType<typeof setInterval> | undefined;
  protected readonly views = VIEWS;
  protected activeView = 0;
  protected guiding = false;
  protected guideFinished = false;

  ngOnInit(): void {
    this.seo.update({
      title: 'Demostración profesional ficticia',
      description:
        'Explora con datos ficticios cómo un profesional autorizado revisa una comunicación y un caso.',
      path: '/demostracion/profesional/',
    });
    if (!globalThis.matchMedia?.('(prefers-reduced-motion: reduce)').matches) this.restart();
  }

  ngOnDestroy(): void {
    this.stop();
  }
  protected choose(index: number): void {
    this.activeView = index;
    this.guideFinished = true;
    this.stop();
  }
  protected pause(): void {
    this.stop();
  }
  protected restart(): void {
    this.activeView = 0;
    this.guideFinished = false;
    this.stop();
    this.guiding = true;
    this.timer = setInterval(() => {
      if (this.activeView === VIEWS.length - 1) this.stop();
      else this.activeView += 1;
    }, 2800);
  }
  protected skip(): void {
    this.activeView = VIEWS.length - 1;
    this.guideFinished = true;
    this.stop();
  }
  private stop(): void {
    if (this.timer !== undefined) clearInterval(this.timer);
    this.timer = undefined;
    this.guiding = false;
  }
}
