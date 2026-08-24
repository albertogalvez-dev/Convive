/**
 * Official public emergency and helpline numbers signposted from the public website.
 *
 * These are listed as verifiable public resources under their official service names.
 * Convive adds no interpretation of when a person should use one rather than another:
 * doing so would present the project as reviewed official guidance, which it is not.
 */
export interface PublicEmergencyResource {
  readonly translationKey: string;
  readonly number: string;
  readonly dial: string;
}

export const PUBLIC_EMERGENCY_RESOURCES: readonly PublicEmergencyResource[] = [
  { translationKey: 'emergency', number: '112', dial: '112' },
  { translationKey: 'violenceAgainstWomen', number: '016', dial: '016' },
  {
    translationKey: 'anar',
    number: '900 20 20 10',
    dial: '900202010',
  },
];
