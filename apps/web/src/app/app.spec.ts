import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';

import { App } from './app';
import { routes } from './app.routes';

describe('App', () => {
  let httpTesting: HttpTestingController;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [App],
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter(routes)],
    }).compileComponents();

    httpTesting = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpTesting.verify();
    window.history.replaceState({}, '', '/');
  });

  it('should create the app', () => {
    const fixture = TestBed.createComponent(App);

    fixture.detectChanges();

    const request = httpTesting.expectOne('/api/v1/health');

    request.flush({ status: 'ok' });

    expect(fixture.componentInstance).toBeTruthy();
  });

  it('should render the application name', () => {
    const fixture = TestBed.createComponent(App);

    fixture.detectChanges();

    const request = httpTesting.expectOne('/api/v1/health');

    request.flush({ status: 'ok' });
    fixture.detectChanges();

    const compiled = fixture.nativeElement as HTMLElement;
    expect(compiled.querySelector('h1')?.textContent).toBe('Convive');
  });

  it('should request and render the API health status', () => {
    const fixture = TestBed.createComponent(App);

    fixture.detectChanges();

    const request = httpTesting.expectOne('/api/v1/health');

    expect(request.request.method).toBe('GET');

    request.flush({ status: 'ok' });
    fixture.detectChanges();

    const compiled = fixture.nativeElement as HTMLElement;
    expect(compiled.querySelector('.status')?.textContent).toContain('API status: ok');
  });

  it('should not request the API health status on a direct reporting route', () => {
    window.history.replaceState({}, '', '/r/ORG_TEST');

    const fixture = TestBed.createComponent(App);

    fixture.detectChanges();

    httpTesting.expectNone('/api/v1/health');

    const compiled = fixture.nativeElement as HTMLElement;
    expect(compiled.querySelector('.status')).toBeNull();
  });
});
