import { Component, inject, OnDestroy, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';

import { PublicSeoService } from '../public-seo.service';

const STEPS = ['Describe una situación', 'Indica el contexto', 'Revisa el ejemplo'] as const;

@Component({
  selector: 'app-public-demo',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './public-demo.html',
  styleUrl: './public-demo.scss',
})
export class PublicDemo implements OnInit, OnDestroy {
  private readonly seo = inject(PublicSeoService);
  private timer: ReturnType<typeof setInterval> | undefined;
  protected readonly steps = STEPS;
  protected currentStep = 0;
  protected guideRunning = false;
  protected guideSkipped = false;
  protected selectedContext = 'En el centro';

  ngOnInit(): void {
    this.seo.update({
      title: 'Demostración ficticia',
      description:
        'Explora un ejemplo ficticio del recorrido de comunicación de Convive sin enviar información.',
      path: '/demostracion/',
    });

    if (!globalThis.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
      this.restartGuide();
    }
  }

  ngOnDestroy(): void {
    this.stopGuide();
  }

  protected selectStep(step: number): void {
    this.currentStep = step;
    this.guideSkipped = true;
    this.stopGuide();
  }

  protected next(): void {
    this.currentStep = Math.min(this.currentStep + 1, this.steps.length - 1);
  }

  protected previous(): void {
    this.currentStep = Math.max(this.currentStep - 1, 0);
  }

  protected pauseGuide(): void {
    this.stopGuide();
  }

  protected restartGuide(): void {
    this.currentStep = 0;
    this.guideSkipped = false;
    this.stopGuide();
    this.guideRunning = true;
    this.timer = setInterval(() => {
      if (this.currentStep === this.steps.length - 1) {
        this.stopGuide();
        return;
      }
      this.currentStep += 1;
    }, 2600);
  }

  protected skipGuide(): void {
    this.currentStep = this.steps.length - 1;
    this.guideSkipped = true;
    this.stopGuide();
  }

  private stopGuide(): void {
    if (this.timer !== undefined) {
      clearInterval(this.timer);
      this.timer = undefined;
    }
    this.guideRunning = false;
  }
}
