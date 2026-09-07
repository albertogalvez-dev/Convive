import municipalities from './spanish-municipalities.json';

/**
 * The full list of Spanish municipality names (INE), sorted, for the
 * type-to-filter Municipio field. Fictional-data review screens only; this
 * asset is removed before the SaaS 2.0 surfaces ship for real.
 *
 * Source: INE municipal register, via the `doncicuto/es-municipalities`
 * dataset — 8,114 distinct names.
 */
export const SPANISH_MUNICIPALITIES: readonly string[] = municipalities;
