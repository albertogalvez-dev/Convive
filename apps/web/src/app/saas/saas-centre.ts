import { Component, signal } from '@angular/core';

/**
 * SaaS 2.0 — centre creation and identity (issue #512, expectations C-1 / C-3).
 * Real screen for owner review; fictional data. The view switcher is a review aid.
 */

type CentreView = 'create' | 'identity';

@Component({
  selector: 'app-saas-centre',
  standalone: true,
  templateUrl: './saas-centre.html',
  styleUrl: './saas-centre.scss',
})
export class SaasCentre {
  protected readonly view = signal<CentreView>('create');
  protected readonly views: readonly { key: CentreView; label: string }[] = [
    { key: 'create', label: 'Crear centro' },
    { key: 'identity', label: 'Identidad del centro' },
  ];

  protected setView(key: CentreView): void {
    this.view.set(key);
  }
}
