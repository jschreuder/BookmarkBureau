import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { AuthService, TokenResponse, LoginRequest } from './auth.service';
import { environment } from '../../../environments/environment';

describe('AuthService', () => {
  let service: AuthService;
  let httpMock: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [AuthService],
    });

    service = TestBed.inject(AuthService);
    httpMock = TestBed.inject(HttpTestingController);

    localStorage.clear();
  });

  afterEach(() => {
    httpMock.verify();
    localStorage.clear();
  });

  describe('login', () => {
    it('should send login request with credentials', () => {
      const request: LoginRequest = {
        email: 'test@example.com',
        password: 'password123',
        remember_me: false,
      };

      const mockResponse: TokenResponse = {
        token: 'test-token',
        type: 'Bearer',
        expires_at: new Date(Date.now() + 3600000).toISOString(),
      };

      service.login(request).subscribe((response) => {
        expect(response.token).toBe('test-token');
      });

      const req = httpMock.expectOne(`${environment.apiBaseUrl}/auth/login`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual(request);
      req.flush({ data: mockResponse });
    });

    it('should store token after successful login', () => {
      const request: LoginRequest = {
        email: 'test@example.com',
        password: 'password123',
      };

      const mockResponse: TokenResponse = {
        token: 'test-token',
        type: 'Bearer',
        expires_at: new Date(Date.now() + 3600000).toISOString(),
      };

      service.login(request).subscribe(() => {
        expect(service.getToken()).toBe('test-token');
        expect(localStorage.getItem('auth_token')).toBe('test-token');
      });

      const req = httpMock.expectOne(`${environment.apiBaseUrl}/auth/login`);
      req.flush({ data: mockResponse });
    });

    it('should update isAuthenticated$ after successful login', () => {
      const request: LoginRequest = {
        email: 'test@example.com',
        password: 'password123',
      };

      const mockResponse: TokenResponse = {
        token: 'test-token',
        type: 'Bearer',
        expires_at: new Date(Date.now() + 3600000).toISOString(),
      };

      return new Promise<void>((resolve) => {
        let emissionCount = 0;
        service.isAuthenticated$.subscribe((isAuth) => {
          emissionCount++;
          if (emissionCount === 2) {
            expect(isAuth).toBe(true);
            resolve();
          }
        });

        service.login(request).subscribe();
        const req = httpMock.expectOne(`${environment.apiBaseUrl}/auth/login`);
        req.flush({ data: mockResponse });
      });
    });

    it('should include TOTP code if provided', () => {
      const request: LoginRequest = {
        email: 'test@example.com',
        password: 'password123',
        totp_code: '123456',
      };

      const mockResponse: TokenResponse = {
        token: 'test-token',
        type: 'Bearer',
        expires_at: new Date(Date.now() + 3600000).toISOString(),
      };

      service.login(request).subscribe();

      const req = httpMock.expectOne(`${environment.apiBaseUrl}/auth/login`);
      expect(req.request.body.totp_code).toBe('123456');
      req.flush({ data: mockResponse });
    });
  });

  describe('refreshToken', () => {
    it('should send refresh token request', () => {
      localStorage.setItem('auth_token', 'old-token');
      localStorage.setItem('token_expires_at', (Date.now() + 3600000).toString());

      const mockResponse: TokenResponse = {
        token: 'new-token',
        type: 'Bearer',
        expires_at: new Date(Date.now() + 7200000).toISOString(),
      };

      service.refreshToken().subscribe((response) => {
        expect(response.token).toBe('new-token');
      });

      const req = httpMock.expectOne(`${environment.apiBaseUrl}/auth/token-refresh`);
      expect(req.request.method).toBe('POST');
      req.flush({ data: mockResponse });
    });

    it('should update token after successful refresh', () => {
      localStorage.setItem('auth_token', 'old-token');
      localStorage.setItem('token_expires_at', (Date.now() + 3600000).toString());

      const mockResponse: TokenResponse = {
        token: 'new-token',
        type: 'Bearer',
        expires_at: new Date(Date.now() + 7200000).toISOString(),
      };

      service.refreshToken().subscribe(() => {
        expect(service.getToken()).toBe('new-token');
      });

      const req = httpMock.expectOne(`${environment.apiBaseUrl}/auth/token-refresh`);
      req.flush({ data: mockResponse });
    });

    it('should logout on refresh failure', () => {
      localStorage.setItem('auth_token', 'old-token');
      localStorage.setItem('token_expires_at', (Date.now() + 3600000).toString());

      service.refreshToken().subscribe(
        () => {
          throw new Error('Should not succeed');
        },
        () => {
          expect(service.getToken()).toBeNull();
          expect(localStorage.getItem('auth_token')).toBeNull();
        },
      );

      const req = httpMock.expectOne(`${environment.apiBaseUrl}/auth/token-refresh`);
      req.error(new ErrorEvent('Unauthorized'));
    });

    it('should throw error if no token to refresh', () => {
      service.refreshToken().subscribe(
        () => {
          throw new Error('Should not succeed');
        },
        (error) => {
          expect(error.message).toBe('No token to refresh');
        },
      );
    });
  });

  describe('logout', () => {
    it('should clear stored token', () => {
      localStorage.setItem('auth_token', 'test-token');
      localStorage.setItem('token_expires_at', (Date.now() + 3600000).toString());

      service.logout();

      expect(service.getToken()).toBeNull();
      expect(localStorage.getItem('auth_token')).toBeNull();
    });

    it('should update isAuthenticated$ to false', () => {
      localStorage.setItem('auth_token', 'test-token');
      localStorage.setItem('token_expires_at', (Date.now() + 3600000).toString());

      return new Promise<void>((resolve) => {
        let callCount = 0;
        service.isAuthenticated$.subscribe((isAuth) => {
          callCount++;
          if (callCount === 2) {
            expect(isAuth).toBe(false);
            resolve();
          }
        });

        service.logout();
      });
    });
  });

  describe('hasValidToken', () => {
    it('should return true if token exists and is not expired', () => {
      const expiresAt = Date.now() + 3600000;
      localStorage.setItem('auth_token', 'test-token');
      localStorage.setItem('token_expires_at', expiresAt.toString());

      expect(service.hasValidToken()).toBe(true);
    });

    it('should return false if token is expired', () => {
      const expiresAt = Date.now() - 1000;
      localStorage.setItem('auth_token', 'test-token');
      localStorage.setItem('token_expires_at', expiresAt.toString());

      expect(service.hasValidToken()).toBe(false);
    });

    it('should return false if no token stored', () => {
      expect(service.hasValidToken()).toBe(false);
    });

    it('should consider buffer of 30 seconds for expiry check', () => {
      const expiresAt = Date.now() + 20000;
      localStorage.setItem('auth_token', 'test-token');
      localStorage.setItem('token_expires_at', expiresAt.toString());

      expect(service.hasValidToken()).toBe(false);
    });
  });

  describe('getTimeUntilExpiry', () => {
    it('should return time in milliseconds until token expires', () => {
      const expiresAt = Date.now() + 3600000;
      localStorage.setItem('token_expires_at', expiresAt.toString());

      const timeUntilExpiry = service.getTimeUntilExpiry();
      expect(timeUntilExpiry).toBeGreaterThan(0);
      expect(timeUntilExpiry).toBeLessThanOrEqual(3600000);
    });

    it('should return 0 if no expiration time stored', () => {
      expect(service.getTimeUntilExpiry()).toBe(0);
    });
  });

  describe('currentToken$', () => {
    it('should emit token changes', () => {
      const mockResponse: TokenResponse = {
        token: 'test-token',
        type: 'Bearer',
        expires_at: new Date(Date.now() + 3600000).toISOString(),
      };

      return new Promise<void>((resolve) => {
        const emissions: (string | null)[] = [];
        service.currentToken$.subscribe((token) => {
          emissions.push(token);
          if (emissions.length === 2) {
            expect(emissions[0]).toBeNull();
            expect(emissions[1]).toBe('test-token');
            resolve();
          }
        });

        service
          .login({
            email: 'test@example.com',
            password: 'password123',
          })
          .subscribe();

        const req = httpMock.expectOne(`${environment.apiBaseUrl}/auth/login`);
        req.flush({ data: mockResponse });
      });
    });
  });
});
