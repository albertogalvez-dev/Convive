import blogAr from '../../i18n/blog/ar.json';
import blogCaValencia from '../../i18n/blog/ca-valencia.json';
import blogCa from '../../i18n/blog/ca.json';
import blogEs from '../../i18n/blog/es.json';
import blogEu from '../../i18n/blog/eu.json';
import blogGl from '../../i18n/blog/gl.json';
import { BlogTranslation, publishedArticles, publishedArticleMetadata } from './blog-content';

const translations = [blogEs, blogCa, blogCaValencia, blogEu, blogGl, blogAr] as const;
const completeDraftSlugs = [
  'explicar-limites-antes-de-abrir-un-canal',
  'confidencialidad-no-es-anonimato-absoluto',
  'una-respuesta-inicial-que-abre-el-siguiente-paso',
  'acoso-conflicto-y-malestar-no-significan-lo-mismo',
  'la-escucha-empieza-antes-del-formulario',
  'que-significa-un-enfoque-de-centro-completo',
  'disenar-un-aviso-de-privacidad-que-se-pueda-entender',
  'ciberconvivencia-prevenir-no-es-vigilar-a-toda-la-comunidad',
  'hablar-de-seguridad-sin-convertir-el-miedo-en-el-mensaje',
  'cuando-un-canal-digital-no-es-la-via-adecuada',
  'la-primera-conversacion-despues-de-un-aviso',
  'un-recurso-accesible-es-un-recurso-que-llega',
  'datos-minimos-contexto-suficiente',
  'una-cultura-de-cuidado-no-cabe-en-un-boton',
  'como-explicar-a-las-familias-el-recorrido-de-una-comunicacion',
  'el-papel-de-la-reparacion-cuando-hay-dano',
  'cerrar-el-trimestre-con-una-comunicacion-clara',
  'la-informacion-sensible-no-debe-viajar-por-cualquier-canal',
  'empezar-el-curso-con-normas-que-se-puedan-practicar',
  'participacion-del-alumnado-escuchar-no-es-delegar-la-responsabilidad',
  'como-evitar-que-un-formulario-sustituya-la-valoracion-humana',
  'cuidar-la-confidencialidad-en-conversaciones-de-equipo',
  'senales-contexto-y-limites-de-la-deteccion-temprana',
  'un-lenguaje-inclusivo-para-hablar-de-convivencia',
  'que-aporta-una-trazabilidad-proporcionada',
  'ciberacoso-acompanar-documentar-y-derivar-sin-improvisar',
  'la-coordinacion-no-consiste-en-compartirlo-todo',
  'cuando-la-respuesta-necesita-a-mas-de-una-persona',
  'evitar-etiquetas-que-danan-antes-de-comprender',
  'el-valor-de-una-pregunta-abierta-y-concreta',
  'como-redactar-mensajes-que-no-culpabilicen',
  'el-entorno-digital-tambien-forma-parte-del-centro',
  'proteccion-de-datos-quien-necesita-saber-que',
  'preparar-una-reunion-con-cuidado-y-proposito',
  'la-convivencia-se-sostiene-tambien-fuera-del-aula',
  'prevencion-observar-el-clima-antes-de-que-aparezca-una-crisis',
  'compartir-informacion-con-familias-sin-exponer-a-otras-personas',
  'cuando-la-tecnologia-ayuda-a-ordenar-no-a-decidir',
  'disenar-procesos-que-admitan-dudas-y-rectificaciones',
  'la-vuelta-de-una-comunicacion-dar-continuidad-sin-prometer-plazos',
  'fin-de-curso-conservar-lo-necesario-y-revisar-lo-aprendido',
  'el-bienestar-del-equipo-tambien-afecta-a-la-respuesta',
  'un-recurso-accesible-es-un-recurso-mas-util',
  'las-palabras-importan-cuando-hay-una-situacion-sensible',
  'privacidad-desde-el-diseno-en-herramientas-educativas',
  'preparar-el-proximo-curso-a-partir-de-lo-que-funciono',
  'convivencia-digital-durante-el-verano-pautas-sin-alarmismo',
  'volver-a-explicar-los-limites-antes-de-reiniciar-un-canal',
  'una-guia-breve-para-leer-comunicaciones-con-responsabilidad',
  'que-revisar-antes-de-abrir-un-nuevo-curso',
  'un-ano-de-escucha-preguntas-para-mejorar-sin-simplificar',
] as const;

describe('blog content', () => {
  it('publishes the reviewed article in every supported locale', () => {
    for (const translation of translations) {
      const articles = publishedArticles(translation as BlogTranslation, new Date('2026-08-24'));
      const article = articles.find(
        (candidate) => candidate.slug === 'escuchar-y-ordenar-comunicaciones',
      );

      expect(article?.title).toBeTruthy();
      expect(article?.coverAlt).toBeTruthy();
      expect(article?.sections).toHaveLength(3);
      expect(article?.sources).toHaveLength(2);
    }
  });

  it('keeps editorial drafts out of publication until an explicit approval', () => {
    const drafts = publishedArticleMetadata(new Date('2026-09-30')).map(({ slug }) => slug);

    expect(drafts).not.toContain('explicar-limites-antes-de-abrir-un-canal');
    expect(drafts).not.toContain('confidencialidad-no-es-anonimato-absoluto');
    expect(drafts).not.toContain('una-respuesta-inicial-que-abre-el-siguiente-paso');
  });

  it('requires complete editorial copy in every public locale before publication', () => {
    for (const translation of translations) {
      const content = translation as BlogTranslation;

      for (const article of publishedArticleMetadata(new Date('2026-10-01'))) {
        const copy = content.articles[article.slug];
        expect(copy?.title).toBeTruthy();
        expect(copy?.description).toBeTruthy();
        expect(copy?.coverAlt).toBeTruthy();
        expect(copy?.sections).toHaveLength(3);
        expect(copy?.sources).toHaveLength(2);
      }
    }
  });

  it('keeps the reviewed editorial drafts complete in every public locale', () => {
    for (const translation of translations) {
      const content = translation as BlogTranslation;

      for (const slug of completeDraftSlugs) {
        expect(content.articles[slug]?.title).toBeTruthy();
        expect(content.articles[slug]?.sections).toHaveLength(3);
        expect(content.articles[slug]?.sources).toHaveLength(2);
      }
    }
  });
});
