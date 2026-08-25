import { PUBLIC_WEBSITE_HOSTNAME } from './site-hosts';

/**
 * Who publishes the public demonstration, and how a visitor reaches them.
 *
 * The project is published under the owner's name as a personal project rather than
 * an entity, with no tax identifier and no postal address: the public demonstration
 * processes no visitor personal data, so the heavier identification a real service
 * would need does not apply.
 */
export const PUBLIC_OPERATOR_NAME = 'Alberto Gálvez — Proyecto Convive';

/** Public professional profile of Convive's publisher. */
export const PUBLIC_OPERATOR_LINKEDIN_URL = 'https://www.linkedin.com/in/alberto-galvez-aguado/';

/** Route for privacy questions and for exercising rights. */
export const PUBLIC_PRIVACY_EMAIL = `privacy@${PUBLIC_WEBSITE_HOSTNAME}`;

/** General route for questions about the project and for accessibility problems. */
export const PUBLIC_GENERAL_EMAIL = `hola@${PUBLIC_WEBSITE_HOSTNAME}`;

/**
 * Credit to the organisation whose scholarship funds the project.
 *
 * Aircury Summer of Code 2026 requires the work to name and credit Aircury SL, so
 * this appears on every public page rather than on a single page a visitor might
 * never open. It states who funded the work and nothing more: Aircury SL does not
 * operate Convive, and no endorsement of its content is implied.
 */
export const PUBLIC_SPONSOR_NAME = 'Aircury SL';
export const PUBLIC_SPONSOR_CREDIT_PREFIX =
  'Proyecto desarrollado gracias a la beca Aircury Summer of Code 2026 de ';
export const PUBLIC_SPONSOR_CREDIT = `${PUBLIC_SPONSOR_CREDIT_PREFIX}${PUBLIC_SPONSOR_NAME}.`;
