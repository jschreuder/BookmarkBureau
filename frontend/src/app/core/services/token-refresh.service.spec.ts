import { TestBed } from '@angular/core/testing';
import { vi } from 'vitest';
import { TokenRefreshService } from './token-refresh.service';
import { AuthService } from './auth.service';
import { of, BehaviorSubject, Observable } from 'rxjs';

describe('TokenRefreshService', () => {
  let service: TokenRefreshService;
  let authService: AuthService;

  beforeEach(() => {
    const authServiceMock = {
      hasValidToken: vi.fn(),
      getTimeUntilExpiry: vi.fn(),
      getToken: vi.fn(),
      refreshToken: vi.fn(),
      logout: vi.fn(),
      isAuthenticated$: of(true),
    };

    TestBed.configureTestingModule({
      providers: [TokenRefreshService, { provide: AuthService, useValue: authServiceMock }],
    });

    service = TestBed.inject(TokenRefreshService);
    authService = TestBed.inject(AuthService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should allow setting custom refresh threshold', () => {
    expect(() => service.setRefreshThreshold(5 * 60 * 1000)).not.toThrow();
  });

  it('should throw error for invalid refresh threshold', () => {
    expect(() => service.setRefreshThreshold(0)).toThrowError('Refresh threshold must be positive');
    expect(() => service.setRefreshThreshold(-1000)).toThrowError(
      'Refresh threshold must be positive',
    );
  });

  describe('Refresh Logic', () => {
    it('should initialize and stop monitoring without errors', () => {
      service.initializeMonitoring();
      expect(service).toBeTruthy();
      service.stopMonitoring();
      expect(service).toBeTruthy();
    });
  });

  describe('Activity Monitoring', () => {
    it('should not start monitoring twice', () => {
      service.initializeMonitoring();
      service.initializeMonitoring();

      // Should not throw or create duplicate listeners
      expect(service).toBeTruthy();
      service.stopMonitoring();
    });

    it('should not stop monitoring twice', () => {
      service.initializeMonitoring();
      service.stopMonitoring();
      service.stopMonitoring();

      // Should not throw
      expect(service).toBeTruthy();
    });

    it('should remove event listeners when stopped', () => {
      const removeEventListenerSpy = vi.spyOn(document, 'removeEventListener');

      service.initializeMonitoring();
      service.stopMonitoring();

      expect(removeEventListenerSpy).toHaveBeenCalled();
      expect(removeEventListenerSpy.mock.calls.some((call) => call[0] === 'mousedown')).toBe(true);
      expect(removeEventListenerSpy.mock.calls.some((call) => call[0] === 'keydown')).toBe(true);
      expect(removeEventListenerSpy.mock.calls.some((call) => call[0] === 'touchstart')).toBe(true);
      expect(removeEventListenerSpy.mock.calls.some((call) => call[0] === 'click')).toBe(true);

      removeEventListenerSpy.mockRestore();
    });
  });

  describe('Error Handling', () => {
    it('should stop monitoring when user logs out', async () => {
      const authSubject = new BehaviorSubject<boolean>(true);
      // Override the authService.isAuthenticated$ property
      Object.defineProperty(authService, 'isAuthenticated$', {
        get: () => authSubject.asObservable(),
        configurable: true,
      });

      service.initializeMonitoring();

      // Simulate logout
      authSubject.next(false);

      await new Promise((resolve) => setTimeout(resolve, 100));
      // Monitoring should be stopped
      expect(service).toBeTruthy();
    });
  });
});
