import { Component, inject } from '@angular/core';
import { RouterOutlet } from '@angular/router';

import { I18nDocumentSync } from './i18n/i18n-document-sync.service';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet],
  templateUrl: './app.html',
})
export class App {
  // Injected for its side effect only: keeps <html lang>/dir in step with
  // the active locale for the whole application's lifetime.
  private readonly documentSync = inject(I18nDocumentSync);
}
