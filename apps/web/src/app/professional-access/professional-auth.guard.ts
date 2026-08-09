import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { catchError, map, of } from 'rxjs';

import { ProfessionalSessionService } from './professional-session.service';

export const professionalAuthGuard: CanActivateFn = () => {
  const router = inject(Router);

  return inject(ProfessionalSessionService)
    .restore()
    .pipe(
      map(() => true),
      catchError(() => of(router.createUrlTree(['/profesionales/acceso']))),
    );
};
