import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, throwError } from 'rxjs';
import { map, tap, catchError } from 'rxjs/operators';
import { environment } from '../../../environments/environment';
import { ApiResponse } from '../models';
import { StorageService } from './storage.service';

export interface TokenResponse {
  token: string;
  type: string;
  expires_at: string; // ISO 8601 date string
}

export interface LoginRequest {
  email: string;
  password: string;
  totp_code?: string;
  remember_me?: boolean;
}

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly storage = inject(StorageService);
  private readonly API_BASE = environment.apiBaseUrl;
  private readonly TOKEN_KEY = 'auth_token';
  private readonly EXPIRES_AT_KEY = 'token_expires_at';

  private readonly isAuthenticatedSubject = new BehaviorSubject<boolean>(this.hasValidToken());
  public isAuthenticated$ = this.isAuthenticatedSubject.asObservable();

  private readonly currentTokenSubject = new BehaviorSubject<string | null>(this.getStoredToken());
  public currentToken$ = this.currentTokenSubject.asObservable();

  constructor() {
    // Check if stored token is still valid on service initialization
    if (!this.hasValidToken()) {
      this.clearToken();
    }
  }

  /**
   * Authenticate user with email and password
   */
  login(request: LoginRequest): Observable<TokenResponse> {
    return this.http.post<ApiResponse<TokenResponse>>(`${this.API_BASE}/auth/login`, request).pipe(
      map((response) => response.data!),
      tap((response) => this.storeToken(response)),
      tap(() => this.isAuthenticatedSubject.next(true)),
      catchError((error) => {
        return throwError(() => error);
      }),
    );
  }

  /**
   * Refresh the current authentication token
   */
  refreshToken(): Observable<TokenResponse> {
    const token = this.getStoredToken();
    if (!token) {
      return throwError(() => new Error('No token to refresh'));
    }

    return this.http
      .post<ApiResponse<TokenResponse>>(`${this.API_BASE}/auth/token-refresh`, {})
      .pipe(
        map((response) => response.data!),
        tap((response) => this.storeToken(response)),
        catchError((error) => {
          // On refresh failure, clear token and mark as unauthenticated
          this.logout();
          return throwError(() => error);
        }),
      );
  }

  /**
   * Logout user and clear stored token
   */
  logout(): void {
    this.clearToken();
    this.isAuthenticatedSubject.next(false);
  }

  /**
   * Get the current stored token
   */
  getToken(): string | null {
    return this.getStoredToken();
  }

  /**
   * Check if token exists and is not expired
   */
  hasValidToken(): boolean {
    const token = this.getStoredToken();
    if (!token) {
      return false;
    }

    const expiresAt = this.getTokenExpiresAt();
    if (!expiresAt) {
      return false;
    }

    // Check if token expires within the next 30 seconds
    const bufferMs = 30 * 1000;
    return Date.now() + bufferMs < expiresAt;
  }

  /**
   * Get the token expiration time in milliseconds
   */
  getTokenExpiresAt(): number | null {
    const expiresAt = this.storage.getItem(this.EXPIRES_AT_KEY);
    if (!expiresAt) {
      return null;
    }
    return parseInt(expiresAt, 10);
  }

  /**
   * Get time until token expires in milliseconds
   */
  getTimeUntilExpiry(): number {
    const expiresAt = this.getTokenExpiresAt();
    if (!expiresAt) {
      return 0;
    }
    return Math.max(0, expiresAt - Date.now());
  }

  private getStoredToken(): string | null {
    return this.storage.getItem(this.TOKEN_KEY);
  }

  private storeToken(response: TokenResponse): void {
    const expiresAtMs = new Date(response.expires_at).getTime();
    this.storage.setItem(this.TOKEN_KEY, response.token);
    this.storage.setItem(this.EXPIRES_AT_KEY, expiresAtMs.toString());
    this.currentTokenSubject.next(response.token);
  }

  private clearToken(): void {
    this.storage.removeItem(this.TOKEN_KEY);
    this.storage.removeItem(this.EXPIRES_AT_KEY);
    this.currentTokenSubject.next(null);
  }
}
