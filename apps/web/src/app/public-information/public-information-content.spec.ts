import {
  PUBLIC_ACCESSIBILITY_NOTICE,
  PUBLIC_COOKIE_NOTICE,
  PUBLIC_DEMONSTRATION_NOTICE,
  PUBLIC_INFORMATION_PAGES,
  PUBLIC_PRIVACY_NOTICE,
  PUBLIC_SANDBOX_TERMS,
} from './public-information-content';
import type { PublicInformationContent } from './public-information';

function allText(content: PublicInformationContent): string {
  const sections = (content.sections ?? []).flatMap((section) => [
    section.heading,
    ...section.paragraphs,
    ...(section.items ?? []),
  ]);

  return [content.title, content.description, content.notice, ...sections].join(' ');
}

describe('public information content', () => {
  it('publishes only the approved document set', () => {
    expect(PUBLIC_INFORMATION_PAGES.map((page) => page.path)).toEqual([
      '/aviso-demostracion/',
      '/privacidad/',
      '/cookies/',
      '/terminos/',
      '/accesibilidad/',
      '/contacto/',
    ]);
  });

  it('gives every document an owner and a review trigger before it can be published', () => {
    for (const page of PUBLIC_INFORMATION_PAGES) {
      expect(page.review.owner).not.toEqual('');
      expect(page.review.reviewedOn).not.toEqual('');
      expect(page.review.trigger).toContain('seis meses');
    }
  });

  it('denies being an emergency channel or an official service', () => {
    const text = allText(PUBLIC_DEMONSTRATION_NOTICE);

    expect(text).toContain('No es un canal de emergencia');
    expect(text).toContain('No es un servicio oficial de la Junta de Andalucía');
    expect(text).toContain('112');
  });

  it('states the sandbox lifecycle and refuses to promise absolute anonymity', () => {
    const text = allText(PUBLIC_PRIVACY_NOTICE);

    expect(text).toContain('24 horas');
    expect(text).toContain('30 días');
    expect(text).toContain('no promete anonimato absoluto');
    expect(text).toContain('privacy@conviveaula.com');
  });

  it('states that no non-essential cookie or measurement exists', () => {
    const text = allText(PUBLIC_COOKIE_NOTICE);

    expect(text).toContain('Cookies de analítica o de medición de audiencia.');
    expect(text).toContain('Cookies publicitarias');
    expect(text).toContain('no se usa para medir, seguir ni perfilar');
  });

  it('credits the scholarship that funds the project without implying an endorsement', () => {
    const text = allText(PUBLIC_DEMONSTRATION_NOTICE);

    expect(text).toContain('Aircury SL');
    expect(text).toContain('licencia MIT');
    expect(text).toContain('No opera esta demostración');
  });

  it('requires invented information and rules out use as a help channel', () => {
    const text = allText(PUBLIC_SANDBOX_TERMS);

    expect(text).toContain('inventada');
    expect(text).toContain('Esto no es un canal de ayuda');
  });

  it('does not claim a conformance the manual audit has not established', () => {
    const text = allText(PUBLIC_ACCESSIBILITY_NOTICE);

    expect(text).toContain('no declara conformidad');
    expect(text).not.toContain('cumple el nivel AA');
  });

  it('says the manual audit has run, not that it is still pending', () => {
    // #167's audit ran on 16 August 2026 and found and fixed real issues. The
    // notice must say so instead of implying nobody has looked yet, while
    // still refusing to declare conformance until the screen-reader pass runs.
    const text = allText(PUBLIC_ACCESSIBILITY_NOTICE);

    expect(text).toContain('se ejecutó una auditoría manual');
    expect(text).not.toContain('está planificada y todavía no ha terminado');
  });
});
