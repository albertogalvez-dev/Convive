import { describe, expect, it } from 'vitest';

import { communicationChannelLabel, communicationRecipientLabel } from './case-labels';

describe('professional case labels', () => {
  it('maps communication enums to published translation keys', () => {
    expect(communicationRecipientLabel('family')).toBe(
      'professional-case.comms.recipientOption.family',
    );
    expect(communicationRecipientLabel('education_inspectorate')).toBe(
      'professional-case.comms.recipientOption.inspection',
    );
    expect(communicationChannelLabel('telephone')).toBe(
      'professional-case.comms.channelOption.phone',
    );
    expect(communicationChannelLabel('secure_portal')).toBe(
      'professional-case.comms.channelOption.secure_channel',
    );
  });
});
