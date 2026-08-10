import { isApplicationHostname, isPublicWebsiteHostname } from './host-boundary.guard';

describe('host boundaries', () => {
  it.each(['localhost', '127.0.0.1', '::1', 'app.localhost'])(
    'keeps local development routes available on %s',
    (hostname) => {
      expect(isApplicationHostname(hostname)).toBe(true);
      expect(isPublicWebsiteHostname(hostname)).toBe(true);
    },
  );

  it('keeps the production public website and application hosts separate', () => {
    expect(isPublicWebsiteHostname('conviveaula.com')).toBe(true);
    expect(isApplicationHostname('conviveaula.com')).toBe(false);
    expect(isPublicWebsiteHostname('app.conviveaula.com')).toBe(false);
    expect(isApplicationHostname('app.conviveaula.com')).toBe(true);
  });

  it('does not serve either area on an unconfigured production hostname', () => {
    expect(isPublicWebsiteHostname('preview.conviveaula.com')).toBe(false);
    expect(isApplicationHostname('preview.conviveaula.com')).toBe(false);
  });
});
