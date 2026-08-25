import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';

import { App } from './app';
import { routes } from './app.routes';
import { i18nTestingModule } from './i18n/testing/provide-i18n-testing';

describe('App', () => {
  it('creates the routing shell without a technical health request', async () => {
    await TestBed.configureTestingModule({
      imports: [App, i18nTestingModule()],
      providers: [provideRouter(routes)],
    }).compileComponents();

    const fixture = TestBed.createComponent(App);
    fixture.detectChanges();

    expect(fixture.componentInstance).toBeTruthy();
    expect(fixture.nativeElement.querySelector('router-outlet')).toBeTruthy();
  });

  it('sets the document language for the whole application lifetime', async () => {
    await TestBed.configureTestingModule({
      imports: [App, i18nTestingModule()],
      providers: [provideRouter(routes)],
    }).compileComponents();

    const fixture = TestBed.createComponent(App);
    fixture.detectChanges();
    TestBed.tick();

    expect(document.documentElement.lang).toBe('es');
    expect(document.documentElement.dir).toBe('ltr');
  });

  it('gives the application hostname root a safe professional entry', () => {
    const applicationRoute = routes.find((route) => route.path === '' && route.children);
    const applicationRoot = applicationRoute?.children?.find((route) => route.path === '');

    expect(applicationRoot).toMatchObject({
      pathMatch: 'full',
      redirectTo: 'profesionales/acceso',
    });
  });
});
