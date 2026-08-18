import { TestBed } from '@angular/core/testing';
import { TranslocoService } from '@jsverse/transloco';

import footerEs from '../../i18n/public-site-footer/es.json';
import { i18nTestingModule } from '../i18n/testing/provide-i18n-testing';
import { LanguageSwitcher } from './language-switcher';

describe('LanguageSwitcher', () => {
  afterEach(() => {
    localStorage.clear();
  });

  async function render(): Promise<{
    page: HTMLElement;
    transloco: TranslocoService;
    detectChanges: () => void;
  }> {
    await TestBed.configureTestingModule({
      imports: [LanguageSwitcher, i18nTestingModule({ 'public-site-footer': footerEs })],
    }).compileComponents();

    const fixture = TestBed.createComponent(LanguageSwitcher);
    fixture.detectChanges();

    return {
      page: fixture.nativeElement as HTMLElement,
      transloco: TestBed.inject(TranslocoService),
      detectChanges: () => fixture.detectChanges(),
    };
  }

  it('offers every signed-off locale by its own name, not a translation of its name', async () => {
    const { page } = await render();

    const options = Array.from(page.querySelectorAll<HTMLOptionElement>('option'));

    expect(options.map((option) => option.value)).toEqual(['es', 'ca', 'ca-valencia', 'gl', 'ar']);
    expect(options.map((option) => option.textContent?.trim())).toEqual([
      'Español',
      'Català',
      'Valencià',
      'Galego',
      'العربية',
    ]);
  });

  it('exposes the select through a real label, not only an aria attribute', async () => {
    const { page } = await render();

    const select = page.querySelector<HTMLSelectElement>('select');
    const label = page.querySelector<HTMLLabelElement>('label');

    expect(select).not.toBeNull();
    expect(label?.getAttribute('for')).toBe(select?.id);
  });

  it('switches the active locale and persists the explicit choice', async () => {
    const { page, transloco } = await render();

    const select = page.querySelector<HTMLSelectElement>('select');

    if (!select) {
      throw new Error('The language select was not found.');
    }

    select.value = 'ca';
    select.dispatchEvent(new Event('change'));

    expect(transloco.getActiveLang()).toBe('ca');
    expect(localStorage.getItem('convive-locale')).toBe('ca');
  });

  it('reflects the active locale as the selected option', async () => {
    const { page, transloco, detectChanges } = await render();

    transloco.setActiveLang('ca-valencia');
    detectChanges();

    const select = page.querySelector<HTMLSelectElement>('select');

    expect(select?.value).toBe('ca-valencia');
  });

  it('ignores a change event carrying a value that is not a signed-off locale', async () => {
    const { page, transloco } = await render();

    const select = page.querySelector<HTMLSelectElement>('select');

    if (!select) {
      throw new Error('The language select was not found.');
    }

    Object.defineProperty(select, 'value', { value: 'xx', configurable: true });
    select.dispatchEvent(new Event('change'));

    expect(transloco.getActiveLang()).toBe('es');
    expect(localStorage.getItem('convive-locale')).toBeNull();
  });
});
