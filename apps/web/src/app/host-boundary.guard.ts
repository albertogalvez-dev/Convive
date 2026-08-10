import { CanMatchFn } from '@angular/router';

import {
  APPLICATION_HOSTNAME,
  isLocalDevelopmentHost,
  PUBLIC_WEBSITE_HOSTNAME,
} from './site-hosts';

export function isApplicationHostname(hostname: string): boolean {
  return isLocalDevelopmentHost(hostname) || hostname === APPLICATION_HOSTNAME;
}

export function isPublicWebsiteHostname(hostname: string): boolean {
  return isLocalDevelopmentHost(hostname) || hostname === PUBLIC_WEBSITE_HOSTNAME;
}

export const applicationHostGuard: CanMatchFn = () =>
  isApplicationHostname(globalThis.location.hostname);

export const publicWebsiteHostGuard: CanMatchFn = () =>
  isPublicWebsiteHostname(globalThis.location.hostname);
