import { TestBed } from '@angular/core/testing';
import { signal } from '@angular/core';
import { TranslocoService } from '@jsverse/transloco';

import { I18nDocumentSync } from './i18n-document-sync.service';

describe('I18nDocumentSync', () => {
  it('sets lang and a left-to-right direction for a standard locale', () => {
    const activeLang = signal('es');
    TestBed.configureTestingModule({
      providers: [{ provide: TranslocoService, useValue: { activeLang } }],
    });

    TestBed.inject(I18nDocumentSync);
    TestBed.tick();

    expect(document.documentElement.lang).toBe('es');
    expect(document.documentElement.dir).toBe('ltr');
  });

  it('switches to right-to-left when the active locale is Arabic', () => {
    const activeLang = signal('ar');
    TestBed.configureTestingModule({
      providers: [{ provide: TranslocoService, useValue: { activeLang } }],
    });

    TestBed.inject(I18nDocumentSync);
    TestBed.tick();

    expect(document.documentElement.lang).toBe('ar');
    expect(document.documentElement.dir).toBe('rtl');
  });

  it('follows the active locale when it changes at runtime', () => {
    const activeLang = signal('es');
    TestBed.configureTestingModule({
      providers: [{ provide: TranslocoService, useValue: { activeLang } }],
    });

    TestBed.inject(I18nDocumentSync);
    TestBed.tick();
    expect(document.documentElement.dir).toBe('ltr');

    activeLang.set('ar');
    TestBed.tick();

    expect(document.documentElement.lang).toBe('ar');
    expect(document.documentElement.dir).toBe('rtl');
  });
});
