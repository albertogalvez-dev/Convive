import { Component, inject } from '@angular/core';
import { ActivatedRoute } from '@angular/router';

/**
 * SaaS 2.0 — centre creation and centre identity (issue #512, C-1 / C-3).
 * Each view is its own route. Fictional data.
 */

export type CentreView = 'create' | 'identity';

@Component({
  selector: 'app-saas-centre',
  standalone: true,
  templateUrl: './saas-centre.html',
  styleUrl: './saas-centre.scss',
})
export class SaasCentre {
  protected readonly view: CentreView =
    (inject(ActivatedRoute).snapshot.data['view'] as CentreView) ?? 'create';
}
