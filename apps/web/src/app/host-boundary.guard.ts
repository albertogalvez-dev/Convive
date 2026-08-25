import { isPlatformBrowser } from '@angular/common';
import { inject, PLATFORM_ID } from '@angular/core';
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

export const applicationHostGuard: CanMatchFn = () => {
  const platformId = inject(PLATFORM_ID);

  return isPlatformBrowser(platformId) && isApplicationHostname(globalThis.location.hostname);
};

export const publicWebsiteHostGuard: CanMatchFn = () => {
  const platformId = inject(PLATFORM_ID);

  return !isPlatformBrowser(platformId) || isPublicWebsiteHostname(globalThis.location.hostname);
};
