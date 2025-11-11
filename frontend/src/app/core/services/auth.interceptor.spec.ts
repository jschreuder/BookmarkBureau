import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { HttpClient } from '@angular/common/http';
import { AuthService } from './auth.service';
import { AuthInterceptor } from './auth.interceptor';
import { HTTP_INTERCEPTORS } from '@angular/common/http';
import { vi } from 'vitest';

describe('AuthInterceptor', () => {
  let httpClient: HttpClient;
  let httpMock: HttpTestingController;
  let authService: Partial<AuthService>;

  beforeEach(() => {
    authService = {
      getToken: vi.fn(),
    };

    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [
        { provide: AuthService, useValue: authService },
        { provide: HTTP_INTERCEPTORS, useClass: AuthInterceptor, multi: true },
      ],
    });

    httpClient = TestBed.inject(HttpClient);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  describe('adding token to requests', () => {
    it('should add Authorization header if token exists', () => {
      (authService.getToken as any).mockReturnValue('test-token');

      httpClient.get('/api/test').subscribe();

      const req = httpMock.expectOne('/api/test');
      expect(req.request.headers.get('Authorization')).toBe('Bearer test-token');
      req.flush({});
    });

    it('should not add Authorization header if no token', () => {
      (authService.getToken as any).mockReturnValue(null);

      httpClient.get('/api/test').subscribe();

      const req = httpMock.expectOne('/api/test');
      expect(req.request.headers.get('Authorization')).toBeNull();
      req.flush({});
    });

    it('should add bearer token with Bearer prefix', () => {
      (authService.getToken as any).mockReturnValue('my-jwt-token');

      httpClient.get('/api/secure').subscribe();

      const req = httpMock.expectOne('/api/secure');
      expect(req.request.headers.get('Authorization')).toMatch(/^Bearer /);
      req.flush({});
    });
  });
});
