import { Component, computed, inject, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';

/**
 * SaaS 2.0 — centre creation and centre identity (issue #512, C-1 / C-3).
 * Each view is its own route. Fictional data.
 *
 * The applicable-guidance reference is disclosed here, at centre creation, and
 * echoed on the identity view — it is never repeated on the working case
 * (charter §6.4 P-12, revised 2026-09-06).
 */

export type CentreView = 'create' | 'identity';

interface TerritorialGuide {
  /** Value of the matching <option>. */
  province: string;
  territory: string;
  source: string;
  reviewed: string;
}

const GUIDES: readonly TerritorialGuide[] = [
  {
    province: 'Andalucía — Sevilla',
    territory: 'Andalucía',
    source: 'Protocolo de Andalucía (BOJA 132/2011)',
    reviewed: '12/08/2026',
  },
  {
    province: 'Comunidad de Madrid — Madrid',
    territory: 'Comunidad de Madrid',
    source: 'Protocolo de la Comunidad de Madrid',
    reviewed: '04/07/2026',
  },
  {
    province: 'Illes Balears — Palma',
    territory: 'Illes Balears',
    source: 'Protocol de les Illes Balears',
    reviewed: '21/05/2026',
  },
  {
    province: 'Catalunya — Barcelona',
    territory: 'Catalunya',
    source: 'Protocol de Catalunya',
    reviewed: '30/06/2026',
  },
  {
    province: 'Euskadi — Bilbao',
    territory: 'Euskadi',
    source: 'Protokoloa — Euskadi',
    reviewed: '09/06/2026',
  },
  {
    province: 'Galicia — A Coruña',
    territory: 'Galicia',
    source: 'Protocolo de Galicia',
    reviewed: '15/07/2026',
  },
];

@Component({
  selector: 'app-saas-centre',
  standalone: true,
  templateUrl: './saas-centre.html',
  styleUrl: './saas-centre.scss',
})
export class SaasCentre {
  protected readonly view: CentreView =
    (inject(ActivatedRoute).snapshot.data['view'] as CentreView) ?? 'create';

  protected readonly guides = GUIDES;
  private readonly province = signal(GUIDES[0].province);
  protected readonly guide = computed(
    () => GUIDES.find((g) => g.province === this.province()) ?? GUIDES[0],
  );

  protected selectProvince(event: Event): void {
    this.province.set((event.target as HTMLSelectElement).value);
  }
}
