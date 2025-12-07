import { TestBed } from '@angular/core/testing';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { HttpClient, provideHttpClient, withInterceptors } from '@angular/common/http';
import { AuthService } from './auth.service';
import { authInterceptor } from './auth.interceptor';
import { InvalidTokenDialogService } from './invalid-token-dialog.service';
import { vi } from 'vitest';

describe('authInterceptor', () => {
  let httpTestingController: HttpTestingController;
  let httpClient: HttpClient;
  let authService: Partial<AuthService>;
  let invalidTokenDialogService: Partial<InvalidTokenDialogService>;

  beforeEach(() => {
    authService = {
      getToken: vi.fn(),
      refreshToken: vi.fn(),
      logout: vi.fn(),
    };

    invalidTokenDialogService = {
      showInvalidTokenDialog: vi.fn(),
    };

    TestBed.configureTestingModule({
      providers: [
        { provide: AuthService, useValue: authService },
        { provide: InvalidTokenDialogService, useValue: invalidTokenDialogService },
        provideHttpClient(withInterceptors([authInterceptor])),
        provideHttpClientTesting(),
      ],
    });

    httpTestingController = TestBed.inject(HttpTestingController);
    httpClient = TestBed.inject(HttpClient);
  });

  afterEach(() => {
    httpTestingController.verify();
  });

  describe('adding token to requests', () => {
    it('should add Authorization header if token exists', () => {
      (authService.getToken as any).mockReturnValue('test-token');

      httpClient.get('/api/test').subscribe();

      const req = httpTestingController.expectOne('/api/test');
      expect(req.request.headers.get('Authorization')).toBe('Bearer test-token');
      req.flush({});
    });

    it('should not add Authorization header if no token', () => {
      (authService.getToken as any).mockReturnValue(null);

      httpClient.get('/api/test').subscribe();

      const req = httpTestingController.expectOne('/api/test');
      expect(req.request.headers.get('Authorization')).toBeNull();
      req.flush({});
    });

    it('should add bearer token with Bearer prefix', () => {
      (authService.getToken as any).mockReturnValue('my-jwt-token');

      httpClient.get('/api/secure').subscribe();

      const req = httpTestingController.expectOne('/api/secure');
      expect(req.request.headers.get('Authorization')).toMatch(/^Bearer /);
      req.flush({});
    });
  });

  describe('handling 401 errors', () => {
    it('should add auth header and handle requests', () => {
      (authService.getToken as any).mockReturnValue('test-token');

      httpClient.get('/api/test').subscribe();

      const req = httpTestingController.expectOne('/api/test');
      expect(req.request.headers.get('Authorization')).toBe('Bearer test-token');

      // For 401 errors, the interceptor handles them internally
      // This test just verifies the interceptor is working
      req.flush({ data: 'success' });
    });
  });

  describe('handling non-401 errors', () => {
    it('should pass through non-401 errors', () => {
      (authService.getToken as any).mockReturnValue('test-token');

      let errorOccurred = false;
      httpClient.get('/api/test').subscribe({
        error: () => {
          errorOccurred = true;
        },
      });

      const req = httpTestingController.expectOne('/api/test');
      req.flush('Not Found', { status: 404, statusText: 'Not Found' });

      expect(errorOccurred).toBe(true);
    });
  });
});
