import { TestBed } from '@angular/core/testing';
import { vi } from 'vitest';
import { TokenRefreshService } from './token-refresh.service';
import { AuthService } from './auth.service';
import { of } from 'rxjs';

describe('TokenRefreshService', () => {
  let service: TokenRefreshService;
  let authService: AuthService;

  beforeEach(() => {
    const authServiceMock = {
      hasValidToken: vi.fn(),
      getTimeUntilExpiry: vi.fn(),
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

  describe('checkAndRefreshToken', () => {
    it('should refresh token when less than 10 minutes remain', (done) => {
      // Setup: token has 5 minutes remaining (less than 10 minute threshold)
      vi.mocked(authService.hasValidToken).mockReturnValue(true);
      vi.mocked(authService.getTimeUntilExpiry).mockReturnValue(5 * 60 * 1000); // 5 minutes
      vi.mocked(authService.refreshToken).mockReturnValue(
        of({
          token: 'new-token',
          type: 'Bearer',
          expires_at: new Date(Date.now() + 30 * 60 * 1000).toISOString(),
        }),
      );

      service.initializeMonitoring();
      service.triggerRefreshCheck();

      // Wait for debounce and async operations
      setTimeout(() => {
        expect(authService.refreshToken).toHaveBeenCalled();
        service.stopMonitoring();
        done();
      }, 6000); // Wait longer than debounce time (5 seconds)
    });

    it('should not refresh token when more than 10 minutes remain', (done) => {
      // Setup: token has 15 minutes remaining (more than 10 minute threshold)
      vi.mocked(authService.hasValidToken).mockReturnValue(true);
      vi.mocked(authService.getTimeUntilExpiry).mockReturnValue(15 * 60 * 1000); // 15 minutes
      vi.mocked(authService.refreshToken).mockReturnValue(
        of({
          token: 'new-token',
          type: 'Bearer',
          expires_at: new Date(Date.now() + 30 * 60 * 1000).toISOString(),
        }),
      );

      service.initializeMonitoring();
      service.triggerRefreshCheck();

      // Wait for debounce
      setTimeout(() => {
        expect(authService.refreshToken).not.toHaveBeenCalled();
        service.stopMonitoring();
        done();
      }, 6000);
    });

    it('should not refresh if token is invalid', (done) => {
      vi.mocked(authService.hasValidToken).mockReturnValue(false);
      vi.mocked(authService.refreshToken).mockReturnValue(
        of({
          token: 'new-token',
          type: 'Bearer',
          expires_at: new Date(Date.now() + 30 * 60 * 1000).toISOString(),
        }),
      );

      service.initializeMonitoring();
      service.triggerRefreshCheck();

      setTimeout(() => {
        expect(authService.refreshToken).not.toHaveBeenCalled();
        service.stopMonitoring();
        done();
      }, 6000);
    });
  });
});
