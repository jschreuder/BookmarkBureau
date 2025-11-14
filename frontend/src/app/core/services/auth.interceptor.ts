import { inject } from '@angular/core';
import { HttpInterceptorFn, HttpErrorResponse } from '@angular/common/http';
import { throwError, Subject } from 'rxjs';
import { catchError, switchMap, take } from 'rxjs/operators';
import { AuthService } from './auth.service';

// Shared state for managing token refresh
let isRefreshing = false;
const refreshTokenSubject = new Subject<string>();

export const authInterceptor: HttpInterceptorFn = (request, next) => {
  const auth = inject(AuthService);
  const token = auth.getToken();

  console.log('[authInterceptor] Intercepting request:', request.url, 'Token present:', !!token);

  // Add token to request if available
  if (token) {
    request = request.clone({
      setHeaders: {
        Authorization: `Bearer ${token}`,
      },
    });
    console.log('[authInterceptor] Token added to request');
  } else {
    console.log('[authInterceptor] No token available');
  }

  return next(request).pipe(
    catchError((error: HttpErrorResponse) => {
      // Handle 401 Unauthorized responses
      if (error.status === 401) {
        return handle401Error(request, next, auth);
      }
      return throwError(() => error);
    }),
  );
};

function handle401Error(request: any, next: any, auth: AuthService) {
  // If refresh is already in progress, queue the request
  if (isRefreshing) {
    return new Promise<any>((resolve, reject) => {
      refreshTokenSubject
        .pipe(
          take(1),
          switchMap((token) => {
            const retryRequest = request.clone({
              setHeaders: {
                Authorization: `Bearer ${token}`,
              },
            });
            return next(retryRequest);
          }),
          catchError((err) => {
            reject(err);
            return throwError(() => err);
          }),
        )
        .subscribe({
          next: (event) => resolve(event),
          error: (err) => reject(err),
        });
    });
  }

  // Start refresh process
  isRefreshing = true;

  return new Promise<any>((resolve, reject) => {
    auth
      .refreshToken()
      .pipe(
        switchMap((response) => {
          isRefreshing = false;
          refreshTokenSubject.next(response.token);
          const retryRequest = request.clone({
            setHeaders: {
              Authorization: `Bearer ${response.token}`,
            },
          });
          return next(retryRequest);
        }),
        catchError((err) => {
          isRefreshing = false;
          auth.logout();
          reject(err);
          return throwError(() => err);
        }),
      )
      .subscribe({
        next: (event) => resolve(event),
        error: (err) => reject(err),
      });
  });
}
