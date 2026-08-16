import { PUBLIC_EMERGENCY_RESOURCES } from '../public-emergency-resources';
import {
  PUBLIC_GENERAL_EMAIL,
  PUBLIC_OPERATOR_NAME,
  PUBLIC_PRIVACY_EMAIL,
} from '../public-identity';
import type { PublicInformationContent, PublicInformationReview } from './public-information';

/**
 * The published public-information set for the fictional demonstration.
 *
 * It is deliberately limited to the five documents approved for publication: the
 * fictional-demonstration and non-emergency notice, the sandbox privacy notice, the
 * cookie notice, the sandbox terms and the accessibility notice. No real-service
 * terms, centre contract or commercial-email policy is published, because none has
 * been decided and inventing one would misrepresent the project.
 */
const REVIEW: PublicInformationReview = {
  owner: PUBLIC_OPERATOR_NAME,
  reviewedOn: '16 de agosto de 2026',
  trigger:
    'Se revisa cada seis meses y, además, siempre que cambien el alcance de la demostración, la identidad de quien la publica o el protocolo de referencia.',
};

const emergencyResourceItems = PUBLIC_EMERGENCY_RESOURCES.map(
  (resource) => `${resource.number} · ${resource.name}`,
);

export const PUBLIC_DEMONSTRATION_NOTICE: PublicInformationContent = {
  path: '/aviso-demostracion/',
  eyebrow: 'AVISO DE DEMOSTRACIÓN',
  seoDescription:
    'Convive es una demostración con información ficticia. No es un canal de emergencia ni un servicio oficial de la Junta de Andalucía.',
  title: 'Convive es una demostración con información ficticia.',
  description:
    'Todo lo que ves funciona sobre un centro educativo inventado. Nada de lo que escribas aquí llega a un colegio, a un instituto ni a ninguna administración.',
  notice:
    'Si alguien está en peligro, este sitio no es la vía. Abajo tienes los teléfonos públicos de ayuda.',
  sections: [
    {
      heading: 'Qué no es Convive',
      paragraphs: [
        'Conviene decirlo sin rodeos, porque una demostración que se parece a un servicio real puede confundir a quien llega buscando ayuda.',
      ],
      items: [
        'No es un canal de emergencia ni de atención urgente.',
        'No es un servicio oficial de la Junta de Andalucía ni de ninguna otra administración, y no está asociado a ninguna.',
        'No sustituye al protocolo de convivencia de ningún centro educativo.',
        'No ofrece asesoramiento jurídico, clínico ni psicológico.',
      ],
    },
    {
      heading: 'Teléfonos públicos de ayuda',
      paragraphs: [
        'Estos son recursos públicos oficiales. Convive los enumera tal cual, sin añadir ninguna indicación sobre cuál corresponde a cada situación.',
      ],
      items: emergencyResourceItems,
    },
    {
      heading: 'Qué sí puedes hacer aquí',
      paragraphs: [
        'Recorrer el producto entero sobre un centro ficticio: enviar una comunicación de ejemplo, seguirla y ver cómo la trabajaría un equipo profesional.',
        'Para eso hace falta que la información que escribas sea inventada. En los términos del entorno de pruebas está explicado con detalle.',
      ],
    },
    {
      heading: 'De dónde sale este proyecto',
      paragraphs: [
        'Convive es un proyecto personal de código abierto, publicado con licencia MIT, desarrollado gracias a la beca Aircury Summer of Code 2026 de Aircury SL.',
        'Aircury SL financia el trabajo mediante esa beca. No opera esta demostración, no participa en su contenido y nada de lo aquí publicado supone un respaldo suyo.',
      ],
    },
  ],
  review: REVIEW,
};

export const PUBLIC_PRIVACY_NOTICE: PublicInformationContent = {
  path: '/privacidad/',
  eyebrow: 'PRIVACIDAD',
  seoDescription:
    'La demostración pública de Convive no recoge datos personales de quien la visita. El contenido creado en el entorno de pruebas se borra en 24 horas.',
  title: 'La demostración no recoge datos personales.',
  description:
    'Esta demostración funciona con información ficticia y no pide, guarda ni analiza datos personales de quien la visita.',
  notice: 'Si tienes cualquier duda sobre privacidad, escribe a ' + PUBLIC_PRIVACY_EMAIL + '.',
  sections: [
    {
      heading: 'Quién publica este sitio',
      paragraphs: [
        `${PUBLIC_OPERATOR_NAME} publica y mantiene esta demostración. Es un proyecto personal, no una entidad ni un servicio contratado por ningún centro.`,
        `La vía para asuntos de privacidad es ${PUBLIC_PRIVACY_EMAIL}. Para cualquier otra cuestión, ${PUBLIC_GENERAL_EMAIL}.`,
      ],
    },
    {
      heading: 'Qué ocurre con lo que escribes en el entorno de pruebas',
      paragraphs: [
        'El entorno de pruebas acepta contenido para que puedas recorrer el producto, y ese contenido tiene una vida corta y deliberada.',
      ],
      items: [
        'Se borra de forma automática e irreversible en un plazo máximo de 24 horas.',
        'No se puede exportar ni se conserva ninguna copia de seguridad de él.',
        'Los casos y perfiles ficticios que mantiene el propio proyecto sí permanecen: son el material de demostración.',
        'Se conserva un registro técnico no identificativo de aceptación de seguridad durante 30 días.',
      ],
    },
    {
      heading: 'Los límites del anonimato',
      paragraphs: [
        'Convive no pide una cuenta para enviar una comunicación, y eso reduce la información que se conoce de quien escribe. No la elimina.',
        'El contenido que escribas puede identificarte por lo que cuentas, aunque no des tu nombre. Existen además registros técnicos propios de cualquier servicio en Internet.',
        'Por eso el producto no promete anonimato absoluto en ningún momento: sería una tranquilidad falsa, y en un asunto de convivencia escolar una tranquilidad falsa hace daño.',
      ],
    },
    {
      heading: 'Qué no se hace',
      paragraphs: ['Lo que no ocurre importa tanto como lo que ocurre.'],
      items: [
        'No hay analítica ni medición de audiencia de ningún tipo.',
        'No hay cookies no esenciales ni etiquetas de terceros.',
        'No se elabora ningún perfil de quien visita el sitio.',
        'No se admite información personal real, ni de quien visita ni de terceras personas.',
      ],
    },
    {
      heading: 'Tus derechos',
      paragraphs: [
        'Como esta demostración no trata datos personales de quien la visita, normalmente no hay ningún dato sobre el que ejercer un derecho de acceso, rectificación o supresión.',
        `Aun así la vía existe y está atendida: escribe a ${PUBLIC_PRIVACY_EMAIL} y recibirás respuesta.`,
      ],
    },
  ],
  review: REVIEW,
};

export const PUBLIC_COOKIE_NOTICE: PublicInformationContent = {
  path: '/cookies/',
  eyebrow: 'COOKIES',
  seoDescription:
    'Convive no usa cookies no esenciales, analítica ni etiquetas de terceros en su demostración pública.',
  title: 'No usamos cookies no esenciales.',
  description:
    'Este sitio no instala cookies publicitarias, de analítica ni de terceros, así que no hay nada que aceptar ni que rechazar.',
  notice: 'Por eso no verás ningún aviso pidiéndote consentimiento para instalar cookies.',
  sections: [
    {
      heading: 'Qué se guarda en tu navegador',
      paragraphs: [
        'En el sitio público no se guarda nada que permita reconocerte.',
        'Al entrar en la aplicación de demostración, el servidor mantiene una sesión técnica imprescindible para que el recorrido funcione: sin ella no sería posible enviar una comunicación de ejemplo ni volver a consultarla. Esa sesión no se usa para medir, seguir ni perfilar a nadie.',
      ],
    },
    {
      heading: 'Qué no se guarda',
      paragraphs: [],
      items: [
        'Cookies de analítica o de medición de audiencia.',
        'Cookies publicitarias o de personalización.',
        'Etiquetas, píxeles o scripts de terceros.',
      ],
    },
  ],
  review: REVIEW,
};

export const PUBLIC_SANDBOX_TERMS: PublicInformationContent = {
  path: '/terminos/',
  eyebrow: 'TÉRMINOS DEL ENTORNO DE PRUEBAS',
  seoDescription:
    'Condiciones de uso del entorno de pruebas de Convive: información inventada obligatoria, prohibido usarlo como canal de ayuda real.',
  title: 'Usa información inventada. Siempre.',
  description:
    'Estas condiciones son cortas porque solo hay dos cosas importantes: lo que escribas debe ser inventado, y esto no es un canal de ayuda.',
  notice:
    'Si necesitas contar algo real, hazlo por el cauce de tu centro educativo o por los teléfonos públicos de ayuda.',
  sections: [
    {
      heading: 'Información inventada, obligatoriamente',
      paragraphs: [
        'El entorno de pruebas está pensado para que escribas una situación imaginaria sobre un centro que no existe.',
      ],
      items: [
        'No escribas nombres, apellidos, teléfonos, direcciones ni ningún otro dato de una persona real, ni tuyos ni de nadie.',
        'No describas un hecho real ocurrido en un centro concreto.',
        'No subas documentos, imágenes ni grabaciones reales.',
      ],
    },
    {
      heading: 'Esto no es un canal de ayuda',
      paragraphs: [
        'Nadie lee lo que escribes aquí como si fuera una comunicación real. No hay un equipo profesional al otro lado, no se abre ningún expediente y no se avisa a ningún centro.',
        'Usar la demostración para pedir ayuda ante una situación real no solo no sirve: retrasa el momento en que esa situación llega a alguien que sí puede actuar.',
      ],
    },
    {
      heading: 'Qué puedes esperar del servicio',
      paragraphs: [
        'La demostración se ofrece tal cual, sin compromiso de disponibilidad. Puede interrumpirse, reiniciarse o cambiar sin aviso previo.',
        'El contenido que crees se borra automáticamente en un plazo máximo de 24 horas, y también puede retirarse antes si incumple estas condiciones.',
      ],
    },
  ],
  review: REVIEW,
};

export const PUBLIC_ACCESSIBILITY_NOTICE: PublicInformationContent = {
  path: '/accesibilidad/',
  eyebrow: 'ACCESIBILIDAD',
  seoDescription:
    'Compromiso de accesibilidad de Convive, estado real de la revisión y vía de contacto para comunicar una barrera.',
  title: 'Queremos que esto se pueda usar con cualquier medio.',
  description:
    'Convive se desarrolla con el objetivo de cumplir el nivel AA de las WCAG 2.2: manejo completo con teclado, foco visible, contraste suficiente y compatibilidad con productos de apoyo.',
  notice:
    'Si encuentras una barrera, escríbenos a ' +
    PUBLIC_GENERAL_EMAIL +
    ' y cuéntanos qué página y qué te ha impedido continuar.',
  sections: [
    {
      heading: 'Estado de la revisión',
      paragraphs: [
        'Las comprobaciones automáticas de accesibilidad forman parte de la integración continua del proyecto y se ejecutan en cada cambio.',
        'El 16 de agosto de 2026 se ejecutó una auditoría manual de los recorridos críticos, que encontró y corrigió dos fallos reales: un contraste insuficiente y tres estados de error que no se anunciaban a un lector de pantalla. Falta todavía la comprobación con productos de apoyo reales. Mientras no se complete, este sitio no declara conformidad con las WCAG 2.2 AA: afirmarlo sin haberlo comprobado sería faltar a la verdad.',
      ],
    },
    {
      heading: 'Qué hacemos con lo que nos cuentas',
      paragraphs: [
        'Cada barrera comunicada se registra públicamente como una incidencia en el repositorio del proyecto y se atiende antes que el trabajo de producto pendiente.',
        'No hace falta que sepas describirla en términos técnicos. Con saber qué querías hacer y dónde te quedaste atascado es suficiente.',
      ],
    },
  ],
  review: REVIEW,
};

export const PUBLIC_CONTACT_NOTICE: PublicInformationContent = {
  path: '/contacto/',
  eyebrow: 'CONTACTO',
  seoDescription:
    'Vías de contacto del proyecto Convive para dudas generales y para asuntos de privacidad.',
  title: 'Escríbenos por correo.',
  description:
    'No hay formulario de contacto: pedir datos personales para responder una duda sería incoherente con una demostración que no trata datos personales.',
  notice:
    'No envíes por esta vía información personal ni una situación real de convivencia escolar.',
  sections: [
    {
      heading: 'Dos direcciones',
      paragraphs: ['Según de qué quieras hablar.'],
      items: [
        `${PUBLIC_GENERAL_EMAIL} · dudas sobre el proyecto, la demostración y problemas de accesibilidad.`,
        `${PUBLIC_PRIVACY_EMAIL} · privacidad y ejercicio de derechos.`,
      ],
    },
  ],
  review: REVIEW,
};

export const PUBLIC_INFORMATION_PAGES: readonly PublicInformationContent[] = [
  PUBLIC_DEMONSTRATION_NOTICE,
  PUBLIC_PRIVACY_NOTICE,
  PUBLIC_COOKIE_NOTICE,
  PUBLIC_SANDBOX_TERMS,
  PUBLIC_ACCESSIBILITY_NOTICE,
  PUBLIC_CONTACT_NOTICE,
];
