import { Injectable, inject } from '@angular/core';
import { Router, CanActivateFn } from '@angular/router';
import { AuthService } from '../services/auth.service';

/**
 * Guard that checks if user is authenticated before accessing a route
 * If not authenticated, redirects to login page
 */
@Injectable({
  providedIn: 'root',
})
export class AuthGuard {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  canActivate(): boolean {
    if (this.auth.hasValidToken()) {
      return true;
    }

    // Navigate to login and return false to prevent navigation to route
    this.router.navigate(['/login']);
    return false;
  }
}

/**
 * Function-based guard for protecting routes
 * Usage: { path: 'admin', component: AdminComponent, canActivate: [authGuard] }
 */
export const authGuard: CanActivateFn = () => {
  const auth = inject(AuthService);
  const router = inject(Router);

  const hasToken = auth.hasValidToken();

  if (hasToken) {
    return true;
  }

  router.navigate(['/login']);
  return false;
};
