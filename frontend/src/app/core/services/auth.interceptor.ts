import { inject } from '@angular/core';
import { HttpInterceptorFn, HttpErrorResponse } from '@angular/common/http';
import { throwError, NEVER } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { AuthService } from './auth.service';
import { InvalidTokenDialogService } from './invalid-token-dialog.service';

export const authInterceptor: HttpInterceptorFn = (request, next) => {
  const auth = inject(AuthService);
  const invalidTokenDialog = inject(InvalidTokenDialogService);
  const token = auth.getToken();

  // Add token to request if available
  if (token) {
    request = request.clone({
      setHeaders: {
        Authorization: `Bearer ${token}`,
      },
    });
  }

  return next(request).pipe(
    catchError((error: HttpErrorResponse) => {
      // Handle 401 Unauthorized responses
      if (error.status === 401) {
        invalidTokenDialog.showInvalidTokenDialog();
        // Return NEVER to prevent error propagation to components
        return NEVER;
      }
      return throwError(() => error);
    }),
  );
};
