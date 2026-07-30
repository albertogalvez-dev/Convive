import { NgOptimizedImage } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { Component, inject, signal } from '@angular/core';
import { RouterOutlet } from '@angular/router';

interface HealthResponse {
  status: string;
}

@Component({
  selector: 'app-root',
  imports: [RouterOutlet, NgOptimizedImage],
  templateUrl: './app.html',
  styleUrl: './app.scss',
})
export class App {
  private readonly http = inject(HttpClient);

  protected readonly apiStatus = signal('Checking API…');

  constructor() {
    this.http.get<HealthResponse>('/api/v1/health').subscribe({
      next: (response) => this.apiStatus.set(`API status: ${response.status}`),
      error: () => this.apiStatus.set('API unavailable'),
    });
  }
}
