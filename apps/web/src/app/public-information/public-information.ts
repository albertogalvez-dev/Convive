import { Component, inject } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';

import { professionalAccessUrlFor } from '../site-hosts';

export interface PublicInformationContent {
  readonly eyebrow: string;
  readonly title: string;
  readonly description: string;
  readonly notice: string;
}

@Component({
  selector: 'app-public-information',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './public-information.html',
  styleUrl: './public-information.scss',
})
export class PublicInformation {
  private readonly route = inject(ActivatedRoute);
  readonly content = this.route.snapshot.data['content'] as PublicInformationContent;
  readonly professionalAccessUrl = professionalAccessUrlFor(globalThis.location.hostname);
}
