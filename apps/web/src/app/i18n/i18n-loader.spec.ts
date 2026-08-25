import { HttpClient } from '@angular/common/http';
import { TestBed } from '@angular/core/testing';
import { firstValueFrom, of } from 'rxjs';
import { vi } from 'vitest';

import { HttpTranslocoLoader } from './i18n-loader';

describe('HttpTranslocoLoader', () => {
  it('fetches a published locale from its scoped path', async () => {
    const get = vi.fn().mockReturnValue(of({ hello: 'hola' }));
    TestBed.configureTestingModule({
      providers: [{ provide: HttpClient, useValue: { get } }],
    });

    const loader = TestBed.inject(HttpTranslocoLoader);
    const translation = await firstValueFrom(loader.getTranslation('public-site-footer/es'));

    expect(get).toHaveBeenCalledWith('/i18n/public-site-footer/es.json');
    expect(translation).toEqual({ hello: 'hola' });
  });

  it('keeps a scopeless locale request empty without requesting an absent asset', async () => {
    const get = vi.fn().mockReturnValue(of({}));
    TestBed.configureTestingModule({
      providers: [{ provide: HttpClient, useValue: { get } }],
    });

    const loader = TestBed.inject(HttpTranslocoLoader);
    const translation = await firstValueFrom(loader.getTranslation('es'));

    expect(translation).toEqual({});
    expect(get).not.toHaveBeenCalled();
  });

  it('refuses to request a locale that is not published, without making a network call', async () => {
    const get = vi.fn().mockReturnValue(of({}));
    TestBed.configureTestingModule({
      providers: [{ provide: HttpClient, useValue: { get } }],
    });

    const loader = TestBed.inject(HttpTranslocoLoader);

    await expect(
      firstValueFrom(loader.getTranslation('public-site-footer/oc-aranes')),
    ).rejects.toThrow(/not published/);
    expect(get).not.toHaveBeenCalled();
  });
});
