import { TestBed } from '@angular/core/testing';
import { Router } from '@angular/router';
import { vi } from 'vitest';
import { AuthGuard, authGuard } from './auth.guard';
import { AuthService } from '../services/auth.service';

describe('AuthGuard', () => {
  let guard: AuthGuard;
  let authService: any;
  let router: any;

  beforeEach(() => {
    authService = {
      hasValidToken: vi.fn(),
    };

    router = {
      navigate: vi.fn(),
    };

    TestBed.configureTestingModule({
      providers: [
        AuthGuard,
        { provide: AuthService, useValue: authService },
        { provide: Router, useValue: router },
      ],
    });

    guard = TestBed.inject(AuthGuard);
  });

  describe('canActivate', () => {
    it('should allow access when token is valid', () => {
      authService.hasValidToken.mockReturnValue(true);

      const result = guard.canActivate();

      expect(result).toBe(true);
      expect(router.navigate).not.toHaveBeenCalled();
    });

    it('should deny access and redirect to login when token is invalid', () => {
      authService.hasValidToken.mockReturnValue(false);

      const result = guard.canActivate();

      expect(result).toBe(false);
      expect(router.navigate).toHaveBeenCalledWith(['/login']);
    });

    it('should deny access and redirect to login when token does not exist', () => {
      authService.hasValidToken.mockReturnValue(false);

      const result = guard.canActivate();

      expect(result).toBe(false);
      expect(router.navigate).toHaveBeenCalledWith(['/login']);
    });
  });
});

describe('authGuard (functional guard)', () => {
  let authService: any;
  let router: any;

  beforeEach(() => {
    authService = {
      hasValidToken: vi.fn(),
    };

    router = {
      navigate: vi.fn(),
    };

    TestBed.configureTestingModule({
      providers: [
        { provide: AuthService, useValue: authService },
        { provide: Router, useValue: router },
      ],
    });

    authService = TestBed.inject(AuthService);
    router = TestBed.inject(Router);
  });

  it('should allow access when token is valid', () => {
    authService.hasValidToken.mockReturnValue(true);

    const result = TestBed.runInInjectionContext(() => authGuard({} as any, {} as any));

    expect(result).toBe(true);
    expect(router.navigate).not.toHaveBeenCalled();
  });

  it('should deny access and redirect to login when token is invalid', () => {
    authService.hasValidToken.mockReturnValue(false);

    const result = TestBed.runInInjectionContext(() => authGuard({} as any, {} as any));

    expect(result).toBe(false);
    expect(router.navigate).toHaveBeenCalledWith(['/login']);
  });

  it('should deny access and redirect to login when token does not exist', () => {
    authService.hasValidToken.mockReturnValue(false);

    const result = TestBed.runInInjectionContext(() => authGuard({} as any, {} as any));

    expect(result).toBe(false);
    expect(router.navigate).toHaveBeenCalledWith(['/login']);
  });

  it('should handle multiple consecutive calls correctly', () => {
    // First call - valid token
    authService.hasValidToken.mockReturnValue(true);
    let result = TestBed.runInInjectionContext(() => authGuard({} as any, {} as any));
    expect(result).toBe(true);

    // Second call - invalid token
    authService.hasValidToken.mockReturnValue(false);
    result = TestBed.runInInjectionContext(() => authGuard({} as any, {} as any));
    expect(result).toBe(false);
    expect(router.navigate).toHaveBeenCalledWith(['/login']);

    // Third call - valid token again
    authService.hasValidToken.mockReturnValue(true);
    result = TestBed.runInInjectionContext(() => authGuard({} as any, {} as any));
    expect(result).toBe(true);
  });
});
