import { Injectable, inject } from '@angular/core';
import { Title } from '@angular/platform-browser';
import { RouterStateSnapshot, TitleStrategy } from '@angular/router';

/**
 * Gives every route a stable browser title without letting technical paths,
 * report references or other private route state leak into the tab title.
 */
@Injectable()
export class ConviveTitleStrategy extends TitleStrategy {
  private readonly title = inject(Title);

  override updateTitle(snapshot: RouterStateSnapshot): void {
    const section = this.buildTitle(snapshot);
    const pageTitle =
      section === undefined || section === 'Convive' ? 'Convive' : `${section} | Convive`;

    this.title.setTitle(pageTitle);
  }
}
