import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';

import publicSiteFooterEs from '../../i18n/public-site-footer/es.json';
import { i18nTestingModule } from '../i18n/testing/provide-i18n-testing';
import { PublicDemo } from './public-demo';

describe('PublicDemo', () => {
  it('keeps the fictional journey local and controllable', async () => {
    await TestBed.configureTestingModule({
      imports: [PublicDemo, i18nTestingModule({ 'public-site-footer': publicSiteFooterEs })],
      providers: [provideRouter([])],
    }).compileComponents();

    const fixture = TestBed.createComponent(PublicDemo);
    fixture.detectChanges();
    const page = fixture.nativeElement as HTMLElement;

    expect(page.textContent).toContain('DEMOSTRACIÓN FICTICIA');
    expect(page.querySelector('textarea')?.hasAttribute('readonly')).toBe(true);
    const pauseGuide = Array.from(page.querySelectorAll('button')).find((button) =>
      button.textContent?.includes('Pausar guía'),
    ) as HTMLButtonElement;
    expect(pauseGuide).toBeTruthy();

    pauseGuide.click();
    fixture.detectChanges();
    expect(page.textContent).toContain('Reiniciar guía');
    expect(page.querySelector('[href*="/r/"]')).toBeNull();
  });
});
