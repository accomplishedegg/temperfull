import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth';
import { map, catchError, of } from 'rxjs';

export const entryGuard: CanActivateFn = (route, state) => {
    const authService = inject(AuthService);
    const router = inject(Router);

    console.log('EntryGuard: Checking session for root redirect...');

    return authService.checkSessions().pipe(
        map(res => {
            const activeSessions = res.sessions || [];
            // If we are here, we are authenticated (200 OK)

            // Check blocking condition just in case (though AuthGuard on /home handles it too)
            if (activeSessions.length > 2) {
                // If blocked, we usually go to auth to show block screen, 
                // but technically that IS the 'auth' route. 
                // The user wants "if authenticated take him to home".
                // If blocked, he IS authenticated but blocked.
                // Let's send to home, and let Home's AuthGuard or AuthComponent logic handle the "Block" display?
                // Wait, Home's AuthGuard checks session > 2 -> redirects to Auth.
                // So validation:
                // 1. Auth? Yes. Sessions <= 2? -> Home.
                // 2. Auth? Yes. Sessions > 2? -> Auth (to show block).
                // 3. Auth? No. -> Auth.

                // This means success here -> Home if <= 2, Auth if > 2.

                return router.createUrlTree(['/auth']); // Blocked -> Auth
            }

            return router.createUrlTree(['/home']); // Success -> Home
        }),
        catchError(() => {
            // Not authenticated -> Auth
            return of(router.createUrlTree(['/auth']));
        })
    );
};
