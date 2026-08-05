import { NgOptimizedImage } from '@angular/common';
import { DOCUMENT } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { Component, inject, signal } from '@angular/core';
import { NavigationEnd, Router, RouterOutlet } from '@angular/router';
import { filter } from 'rxjs';

interface HealthResponse {
  status: string;
}

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, NgOptimizedImage],
  templateUrl: './app.html',
  styleUrl: './app.scss',
})
export class App {
  private readonly http = inject(HttpClient);
  private readonly document = inject(DOCUMENT);
  private readonly router = inject(Router, { optional: true });
  private healthRequested = false;

  protected readonly apiStatus = signal('Checking API...');
  protected readonly showTechnicalFoundation = signal(true);

  constructor() {
    this.updateShellVisibility(this.document.location?.pathname ?? this.router?.url ?? '/');

    if (this.showTechnicalFoundation()) {
      this.requestHealthStatus();
    }

    this.router?.events
      .pipe(filter((event) => event instanceof NavigationEnd))
      .subscribe((event) => {
        this.updateShellVisibility(event.urlAfterRedirects);

        if (this.showTechnicalFoundation()) {
          this.requestHealthStatus();
        }
      });
  }

  private updateShellVisibility(url: string): void {
    this.showTechnicalFoundation.set(url === '/' || url === '');
  }

  private requestHealthStatus(): void {
    if (this.healthRequested) {
      return;
    }

    this.healthRequested = true;

    this.http.get<HealthResponse>('/api/v1/health').subscribe({
      next: (response) => this.apiStatus.set(`API status: ${response.status}`),
      error: () => this.apiStatus.set('API unavailable'),
    });
  }
}
