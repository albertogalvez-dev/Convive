import { PUBLIC_OPERATOR_NAME } from '../public-identity';

/**
 * The published public-information set for the fictional demonstration.
 *
 * It is deliberately limited to the five documents approved for publication: the
 * fictional-demonstration and non-emergency notice, the sandbox privacy notice, the
 * cookie notice, the sandbox terms and the accessibility notice. No real-service
 * terms, centre contract or commercial-email policy is published, because none has
 * been decided and inventing one would misrepresent the project.
 *
 * The prose itself -- eyebrow, title, description, section copy, the review
 * trigger -- lives in the `public-information` Transloco scope
 * (`apps/web/src/i18n/public-information/es.json`) rather than here, so that
 * translating a locale means editing that JSON, not this file. What stays
 * here is route-shaping metadata: which JSON entry a path resolves to, and
 * who reviewed it and when, which read like data rather than copy that needs
 * translating per locale (compare how the footer keeps its operator name and
 * emergency numbers as component-bound data instead of translated text).
 */
export interface PublicInformationPageMeta {
  /** The key into the `public-information` scope's translation tree. */
  readonly id: string;
  readonly path: string;
  readonly reviewedOn: string;
  readonly owner: string;
}

const REVIEWED_ON = '16 de agosto de 2026';

export const PUBLIC_DEMONSTRATION_NOTICE: PublicInformationPageMeta = {
  id: 'demonstrationNotice',
  path: '/aviso-demostracion/',
  reviewedOn: REVIEWED_ON,
  owner: PUBLIC_OPERATOR_NAME,
};

export const PUBLIC_PRIVACY_NOTICE: PublicInformationPageMeta = {
  id: 'privacyNotice',
  path: '/privacidad/',
  reviewedOn: REVIEWED_ON,
  owner: PUBLIC_OPERATOR_NAME,
};

export const PUBLIC_COOKIE_NOTICE: PublicInformationPageMeta = {
  id: 'cookieNotice',
  path: '/cookies/',
  reviewedOn: REVIEWED_ON,
  owner: PUBLIC_OPERATOR_NAME,
};

export const PUBLIC_SANDBOX_TERMS: PublicInformationPageMeta = {
  id: 'sandboxTerms',
  path: '/terminos/',
  reviewedOn: REVIEWED_ON,
  owner: PUBLIC_OPERATOR_NAME,
};

export const PUBLIC_ACCESSIBILITY_NOTICE: PublicInformationPageMeta = {
  id: 'accessibilityNotice',
  path: '/accesibilidad/',
  reviewedOn: REVIEWED_ON,
  owner: PUBLIC_OPERATOR_NAME,
};

export const PUBLIC_CONTACT_NOTICE: PublicInformationPageMeta = {
  id: 'contactNotice',
  path: '/contacto/',
  reviewedOn: REVIEWED_ON,
  owner: PUBLIC_OPERATOR_NAME,
};

export const PUBLIC_INFORMATION_PAGES: readonly PublicInformationPageMeta[] = [
  PUBLIC_DEMONSTRATION_NOTICE,
  PUBLIC_PRIVACY_NOTICE,
  PUBLIC_COOKIE_NOTICE,
  PUBLIC_SANDBOX_TERMS,
  PUBLIC_ACCESSIBILITY_NOTICE,
  PUBLIC_CONTACT_NOTICE,
];
