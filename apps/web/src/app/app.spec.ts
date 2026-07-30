import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';

import { App } from './app';

describe('App', () => {
  let httpTesting: HttpTestingController;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [App],
      providers: [provideHttpClient(), provideHttpClientTesting()],
    }).compileComponents();

    httpTesting = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpTesting.verify();
  });

  it('should create the app', () => {
    const fixture = TestBed.createComponent(App);
    const request = httpTesting.expectOne('/api/v1/health');

    request.flush({ status: 'ok' });

    expect(fixture.componentInstance).toBeTruthy();
  });

  it('should render the application name', () => {
    const fixture = TestBed.createComponent(App);
    const request = httpTesting.expectOne('/api/v1/health');

    request.flush({ status: 'ok' });
    fixture.detectChanges();

    const compiled = fixture.nativeElement as HTMLElement;
    expect(compiled.querySelector('h1')?.textContent).toBe('Convive');
  });

  it('should request and render the API health status', () => {
    const fixture = TestBed.createComponent(App);
    const request = httpTesting.expectOne('/api/v1/health');

    expect(request.request.method).toBe('GET');

    request.flush({ status: 'ok' });
    fixture.detectChanges();

    const compiled = fixture.nativeElement as HTMLElement;
    expect(compiled.querySelector('.status')?.textContent).toContain('API status: ok');
  });
});
