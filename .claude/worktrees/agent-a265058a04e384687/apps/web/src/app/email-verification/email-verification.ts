import { HttpClient } from '@angular/common/http';
import { Component, OnInit, inject, signal } from '@angular/core';

@Component({
  selector: 'app-email-verification',
  standalone: true,
  templateUrl: './email-verification.html',
  styleUrl: './email-verification.scss',
})
export class EmailVerification implements OnInit {
  private readonly http = inject(HttpClient);
  protected readonly state = signal<'verifying' | 'verified' | 'invalid'>('verifying');

  ngOnInit(): void {
    const token = new URLSearchParams(window.location.hash.slice(1)).get('token') ?? '';
    history.replaceState(null, '', window.location.pathname);

    this.http
      .post<{ verified: boolean }>('/api/v1/public/reporter-email-verifications', { token })
      .subscribe({
        next: () => this.state.set('verified'),
        error: () => this.state.set('invalid'),
      });
  }
}
