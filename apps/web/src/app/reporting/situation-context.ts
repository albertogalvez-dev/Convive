export type SituationContext = 'in_person' | 'digital' | 'mixed' | 'unknown';

/**
 * The reporter-facing wording for a situation context. Shared by the
 * submission and follow-up journeys so both describe the same value
 * identically.
 */
export function describeSituationContext(situationContext: SituationContext): string {
  switch (situationContext) {
    case 'in_person':
      return 'En persona';
    case 'digital':
      return 'Online';
    case 'mixed':
      return 'En persona y online';
    case 'unknown':
      return 'Prefiero no decirlo';
  }
}
