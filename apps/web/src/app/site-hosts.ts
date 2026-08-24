export const PUBLIC_WEBSITE_HOSTNAME = 'conviveaula.com';
export const APPLICATION_HOSTNAME = `app.${PUBLIC_WEBSITE_HOSTNAME}`;

export function isLocalDevelopmentHost(hostname: string): boolean {
  return (
    hostname === 'localhost' ||
    hostname === '127.0.0.1' ||
    hostname === '::1' ||
    hostname.endsWith('.localhost')
  );
}

export function professionalAccessUrlFor(hostname: string): string {
  if (isLocalDevelopmentHost(hostname)) {
    return '/profesionales/acceso';
  }

  return `https://${APPLICATION_HOSTNAME}/profesionales/acceso`;
}

export function publicReportingUrlFor(hostname: string, publicIdentifier: string): string {
  const path = `/r/${encodeURIComponent(publicIdentifier)}`;

  if (isLocalDevelopmentHost(hostname)) {
    return path;
  }

  return `https://${APPLICATION_HOSTNAME}${path}`;
}
