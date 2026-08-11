export interface BlogArticle {
  readonly slug: string;
  readonly title: string;
  readonly description: string;
  readonly publishedAt: string;
  readonly updatedAt: string;
  readonly updatedLabel: string;
  readonly readingMinutes: number;
  readonly category: string;
  readonly introduction: string;
  readonly sections: ReadonlyArray<{
    readonly heading: string;
    readonly paragraphs: ReadonlyArray<string>;
  }>;
  readonly sources: ReadonlyArray<{ readonly label: string; readonly href: string }>;
}

export const BLOG_ARTICLES: ReadonlyArray<BlogArticle> = [
  {
    slug: 'escuchar-y-ordenar-comunicaciones',
    title: 'Escuchar y ordenar comunicaciones sin prometer más de lo posible',
    description:
      'Una explicación general sobre cómo un canal digital puede apoyar la escucha responsable en un centro educativo.',
    publishedAt: '2026-08-11',
    updatedAt: '2026-08-11',
    updatedLabel: '11 de agosto de 2026',
    readingMinutes: 4,
    category: 'Producto y convivencia',
    introduction:
      'Un canal digital no sustituye la conversación, el protocolo ni la atención urgente. Puede ayudar a que una comunicación llegue al centro, se ordene y reciba una respuesta trazable.',
    sections: [
      {
        heading: 'Un canal no decide por el centro',
        paragraphs: [
          'Cada situación necesita una valoración humana y contextual. Una herramienta puede reducir fricción al recibir información, pero no clasifica riesgos ni determina medidas por sí sola.',
          'Por eso Convive separa la comunicación inicial del trabajo profesional posterior y evita convertir una categoría o un formulario en un diagnóstico.',
        ],
      },
      {
        heading: 'Explicar los límites genera confianza',
        paragraphs: [
          'La persona que comunica necesita saber qué puede esperar: que el centro recibirá su mensaje, que podrá volver a consultarlo con sus credenciales y que una urgencia requiere los canales de emergencia apropiados.',
          'También necesita saber qué no se promete. Un canal sin cuenta no elimina todas las obligaciones del centro ni reemplaza la intervención presencial cuando esta es necesaria.',
        ],
      },
      {
        heading: 'La tecnología debe dejar espacio a la revisión',
        paragraphs: [
          'Una respuesta responsable exige profesionales autorizados, contexto y un registro proporcionado. Los equipos necesitan ver solo lo necesario para actuar y conservar evidencias de las decisiones significativas.',
          'Este artículo es información general sobre el enfoque de producto. No es asesoramiento jurídico, clínico ni una guía para responder ante una emergencia.',
        ],
      },
    ],
    sources: [
      {
        label: 'UNESCO: Behind the numbers: ending school violence and bullying',
        href: 'https://unesdoc.unesco.org/ark:/48223/pf0000366483',
      },
      {
        label: 'Ministerio de Educación: convivencia escolar',
        href: 'https://www.educacionfpydeportes.gob.es/mc/sgctie/convivencia-escolar.html',
      },
    ],
  },
];

export function articleForSlug(slug: string): BlogArticle | undefined {
  return BLOG_ARTICLES.find((article) => article.slug === slug);
}
