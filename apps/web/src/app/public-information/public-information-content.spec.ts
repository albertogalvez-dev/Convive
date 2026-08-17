import publicInformationEs from '../../i18n/public-information/es.json';
import { PUBLIC_INFORMATION_PAGES } from './public-information-content';

interface TranslatedSection {
  readonly heading: string;
  readonly paragraphs: readonly string[];
  readonly items?: readonly string[];
}

interface TranslatedPage {
  readonly title: string;
  readonly description: string;
  readonly notice: string;
  readonly sections?: readonly TranslatedSection[];
  readonly reviewTrigger: string;
}

const pages = publicInformationEs as unknown as Record<string, TranslatedPage>;

function allText(page: TranslatedPage): string {
  const sections = (page.sections ?? []).flatMap((section) => [
    section.heading,
    ...section.paragraphs,
    ...(section.items ?? []),
  ]);

  return [page.title, page.description, page.notice, ...sections].join(' ');
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

  it('gives every document an owner, a review date and a review trigger before it can be published', () => {
    for (const page of PUBLIC_INFORMATION_PAGES) {
      expect(page.owner).not.toEqual('');
      expect(page.reviewedOn).not.toEqual('');
      expect(pages[page.id].reviewTrigger).toContain('seis meses');
    }
  });

  it('denies being an emergency channel or an official service', () => {
    const text = allText(pages['demonstrationNotice']);

    expect(text).toContain('No es un canal de emergencia');
    expect(text).toContain('No es un servicio oficial de ninguna administración educativa');
    expect(text).toContain('112');
  });

  it('states the sandbox lifecycle and refuses to promise absolute anonymity', () => {
    const text = allText(pages['privacyNotice']);

    expect(text).toContain('24 horas');
    expect(text).toContain('30 días');
    expect(text).toContain('no promete anonimato absoluto');
    expect(pages['privacyNotice'].notice).toContain('{{privacyEmail}}');
  });

  it('states that no non-essential cookie or measurement exists', () => {
    const text = allText(pages['cookieNotice']);

    expect(text).toContain('Cookies de analítica o de medición de audiencia.');
    expect(text).toContain('Cookies publicitarias');
    expect(text).toContain('no se usa para medir, seguir ni perfilar');
  });

  it('credits the scholarship that funds the project without implying an endorsement', () => {
    const text = allText(pages['demonstrationNotice']);

    expect(text).toContain('Aircury SL');
    expect(text).toContain('licencia MIT');
    expect(text).toContain('No opera esta demostración');
  });

  it('requires invented information and rules out use as a help channel', () => {
    const text = allText(pages['sandboxTerms']);

    expect(text).toContain('inventada');
    expect(text).toContain('Esto no es un canal de ayuda');
  });

  it('does not claim a conformance the manual audit has not established', () => {
    const text = allText(pages['accessibilityNotice']);

    expect(text).toContain('no declara conformidad');
    expect(text).not.toContain('cumple el nivel AA');
  });

  it('says the manual audit has run, not that it is still pending', () => {
    // #167's audit ran on 16 August 2026 and found and fixed real issues. The
    // notice must say so instead of implying nobody has looked yet, while
    // still refusing to declare conformance until the screen-reader pass runs.
    const text = allText(pages['accessibilityNotice']);

    expect(text).toContain('se ejecutó una auditoría manual');
    expect(text).not.toContain('está planificada y todavía no ha terminado');
  });
});
