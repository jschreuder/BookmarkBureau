import { Injectable, inject } from '@angular/core';
import {
  HttpRequest,
  HttpHandler,
  HttpEvent,
  HttpInterceptor,
  HttpErrorResponse,
} from '@angular/common/http';
import { Observable, throwError, Subject } from 'rxjs';
import { catchError, filter, take, switchMap } from 'rxjs/operators';
import { AuthService } from './auth.service';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  private auth = inject(AuthService);
  private refreshTokenSubject = new Subject<string>();
  private isRefreshing = false;

  intercept(request: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    // Add token to request if available
    if (this.auth.getToken()) {
      request = this.addTokenToRequest(request);
    }

    return next.handle(request).pipe(catchError((error) => this.handleError(error, request, next)));
  }

  private addTokenToRequest(request: HttpRequest<any>): HttpRequest<any> {
    const token = this.auth.getToken();
    if (!token) {
      return request;
    }

    return request.clone({
      setHeaders: {
        Authorization: `Bearer ${token}`,
      },
    });
  }

  private handleError(
    error: HttpErrorResponse,
    request: HttpRequest<any>,
    next: HttpHandler,
  ): Observable<HttpEvent<any>> {
    // Handle 401 Unauthorized responses
    if (error.status === 401) {
      return this.handle401Error(request, next);
    }

    return throwError(() => error);
  }

  private handle401Error(request: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    // If refresh is already in progress, queue the request
    if (this.isRefreshing) {
      return new Observable<HttpEvent<any>>((observer) => {
        this.refreshTokenSubject
          .pipe(
            take(1),
            switchMap((token) => {
              const retryRequest = this.addTokenToRequest(request);
              return next.handle(retryRequest);
            }),
            catchError((err) => {
              observer.error(err);
              return throwError(() => err);
            }),
          )
          .subscribe({
            next: (event) => observer.next(event),
            error: (err) => observer.error(err),
            complete: () => observer.complete(),
          });
      });
    }

    // Start refresh process
    this.isRefreshing = true;

    return new Observable<HttpEvent<any>>((observer) => {
      this.auth
        .refreshToken()
        .pipe(
          switchMap((response) => {
            this.isRefreshing = false;
            this.refreshTokenSubject.next(response.token);
            const retryRequest = this.addTokenToRequest(request);
            return next.handle(retryRequest);
          }),
          catchError((err) => {
            this.isRefreshing = false;
            this.auth.logout();
            return throwError(() => err);
          }),
        )
        .subscribe({
          next: (event) => observer.next(event),
          error: (err) => observer.error(err),
          complete: () => observer.complete(),
        });
    });
  }
}
