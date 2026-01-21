import { Routes } from '@angular/router';
import { AuthComponent } from './auth/auth';
import { HomeComponent } from './home/home';
import { entryGuard } from './auth/entry.guard';
import { authGuard } from './auth/auth.guard';
import { roleGuard } from './auth/role.guard';
import { ManageTemperComponent } from './manage-temper/manage-temper';
import { ManageUsersComponent } from './manage-users/manage-users';

export const routes: Routes = [
    { path: 'home', component: HomeComponent, canActivate: [authGuard] },
    { path: 'manage-temper', component: ManageTemperComponent, canActivate: [authGuard, roleGuard] },
    { path: 'manage-users', component: ManageUsersComponent, canActivate: [authGuard, roleGuard] },
    { path: 'manage-leads', loadComponent: () => import('./manage-leads/manage-leads').then(m => m.ManageLeadsComponent), canActivate: [authGuard, roleGuard] },
    { path: 'manage-subscription-types', loadComponent: () => import('./manage-subscription-types/manage-subscription-types').then(m => m.ManageSubscriptionTypesComponent), canActivate: [authGuard, roleGuard] },
    { path: 'search', loadComponent: () => import('./search/search').then(m => m.SearchComponent), canActivate: [authGuard] },
    { path: 'my-subscriptions', loadComponent: () => import('./user-subscriptions/user-subscriptions.component').then(m => m.UserSubscriptionsComponent), canActivate: [authGuard] },
    { path: 'auth', component: AuthComponent },
    { path: '', canActivate: [entryGuard], component: AuthComponent, pathMatch: 'full' },
    { path: '**', redirectTo: '/auth' }
];
