import { inject } from '@angular/core';
import { Router, CanActivateFn } from '@angular/router';
import { AuthService } from '../services/auth';

export const roleGuard: CanActivateFn = (route, state) => {
    const auth = inject(AuthService);
    const router = inject(Router);
    const user = auth.currentUser;

    if (user && (user.role === 'admin' || user.role === 'sales_manager')) {
        return true;
    }

    // Redirect to home or search if not authorized
    return router.createUrlTree(['/home']);
};
