import { Component } from '@angular/core';

import { professionalAccessUrlFor } from '../site-hosts';

@Component({
  selector: 'app-public-home',
  standalone: true,
  templateUrl: './public-home.html',
  styleUrl: './public-home.scss',
})
export class PublicHome {
  readonly professionalAccessUrl = professionalAccessUrlFor(globalThis.location.hostname);
}
