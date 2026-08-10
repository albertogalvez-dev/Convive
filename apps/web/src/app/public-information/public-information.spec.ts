import { TestBed } from '@angular/core/testing';
import { ActivatedRoute } from '@angular/router';

import { PublicInformation, PublicInformationContent } from './public-information';

const content: PublicInformationContent = {
  eyebrow: 'DEMOSTRACIÓN FICTICIA',
  title: 'La demostración se está preparando.',
  description: 'Muestra recorridos ficticios.',
  notice: 'No utilices esta página para comunicar una situación real o urgente.',
};

describe('PublicInformation', () => {
  it('keeps planned public destinations informative without exposing an operational journey', async () => {
    await TestBed.configureTestingModule({
      imports: [PublicInformation],
      providers: [{ provide: ActivatedRoute, useValue: { snapshot: { data: { content } } } }],
    }).compileComponents();

    const fixture = TestBed.createComponent(PublicInformation);
    fixture.detectChanges();

    const page = fixture.nativeElement as HTMLElement;
    expect(page.querySelector('h1')?.textContent).toContain('demostración se está preparando');
    expect(
      Array.from(page.querySelectorAll('a[href="/"]')).some((link) =>
        link.textContent?.includes('Volver al inicio'),
      ),
    ).toBe(true);
    expect(page.querySelector('[href*="/r/"]')).toBeNull();
  });
});
