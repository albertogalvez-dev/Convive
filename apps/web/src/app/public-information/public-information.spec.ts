import { TestBed } from '@angular/core/testing';
import { ActivatedRoute } from '@angular/router';

import { PublicInformation, PublicInformationContent } from './public-information';

const content: PublicInformationContent = {
  path: '/aviso-demostracion/',
  eyebrow: 'AVISO DE DEMOSTRACIÓN',
  title: 'Convive es una demostración con información ficticia.',
  seoDescription: 'Convive es una demostración con información ficticia.',
  description: 'Nada de lo que escribas aquí llega a un centro educativo.',
  notice: 'Si alguien está en peligro, este sitio no es la vía.',
  sections: [
    {
      heading: 'Qué no es Convive',
      paragraphs: ['Conviene decirlo sin rodeos.'],
      items: ['No es un canal de emergencia ni de atención urgente.'],
    },
  ],
  review: {
    owner: 'Alberto Gálvez — Proyecto Convive',
    reviewedOn: '16 de agosto de 2026',
    trigger: 'Se revisa cada seis meses.',
  },
};

async function renderWith(pageContent: PublicInformationContent): Promise<HTMLElement> {
  await TestBed.configureTestingModule({
    imports: [PublicInformation],
    providers: [
      { provide: ActivatedRoute, useValue: { snapshot: { data: { content: pageContent } } } },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(PublicInformation);
  fixture.detectChanges();

  return fixture.nativeElement as HTMLElement;
}

describe('PublicInformation', () => {
  it('publishes the document body without exposing an operational journey', async () => {
    const page = await renderWith(content);

    expect(page.querySelector('h1')?.textContent).toContain(
      'demostración con información ficticia',
    );
    expect(page.querySelector('h2')?.textContent).toContain('Qué no es Convive');
    expect(page.querySelector('li')?.textContent).toContain('No es un canal de emergencia');
    expect(
      Array.from(page.querySelectorAll('a[href="/"]')).some((link) =>
        link.textContent?.includes('Volver al inicio'),
      ),
    ).toBe(true);
    expect(page.querySelector('[href*="/r/"]')).toBeNull();
  });

  it('states who reviewed the page and what makes it due again', async () => {
    const page = await renderWith(content);

    const review = page.querySelector('.review')?.textContent ?? '';
    expect(review).toContain('16 de agosto de 2026');
    expect(review).toContain('Alberto Gálvez — Proyecto Convive');
    expect(review).toContain('Se revisa cada seis meses.');
  });

  it('carries the safety footer on every document, not only the demonstration notice', async () => {
    const page = await renderWith({ ...content, sections: undefined });

    const footer = page.querySelector('footer')?.textContent ?? '';
    expect(footer).toContain('datos ficticios');
    expect(footer).toContain('112');
  });
});
