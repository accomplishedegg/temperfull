import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth';
import { map, catchError, of } from 'rxjs';

export const authGuard: CanActivateFn = (route, state) => {
    const authService = inject(AuthService);
    const router = inject(Router);

    console.log('AuthGuard: Starting check...');

    return authService.checkSessions().pipe(
        map(res => {
            const activeSessions = res.sessions || [];
            console.log('AuthGuard CheckSessions Response:', activeSessions.length);

            if (activeSessions.length > 2) {
                console.warn('AuthGuard Blocking: Too many sessions');
                return router.createUrlTree(['/auth']);
            }
            return true;
        }),
        catchError((err) => {
            console.error('AuthGuard Error (Likely 401/Not Logged In):', err);
            return of(router.createUrlTree(['/auth']));
        })
    );
};
