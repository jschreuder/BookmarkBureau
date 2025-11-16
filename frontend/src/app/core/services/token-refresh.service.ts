import { Injectable, inject, NgZone } from '@angular/core';
import { AuthService } from './auth.service';
import { Subject } from 'rxjs';
import { debounceTime, takeUntil } from 'rxjs/operators';

/**
 * TokenRefreshService manages activity-based token refresh.
 *
 * Strategy:
 * - Monitors user activity (mouse, keyboard, touch events)
 * - On activity detected, checks if token should be refreshed
 * - Refreshes token if it's within the final 10% of its lifetime
 * - Avoids excessive refreshes by debouncing activity (5 second window)
 * - Automatically stops monitoring when user logs out
 */
@Injectable({
  providedIn: 'root',
})
export class TokenRefreshService {
  private auth = inject(AuthService);
  private ngZone = inject(NgZone);

  private destroy$ = new Subject<void>();
  private activity$ = new Subject<void>();
  private isMonitoring = false;
  private activityDebounceMs = 5000; // Debounce activity for 5 seconds

  /**
   * Initialize activity monitoring and watch for logout
   */
  public initializeMonitoring(): void {
    if (this.isMonitoring) {
      return;
    }

    this.isMonitoring = true;

    // Set up activity monitoring
    this.setupActivityListeners();

    // Monitor activity with debounce
    this.activity$
      .pipe(debounceTime(this.activityDebounceMs), takeUntil(this.destroy$))
      .subscribe(() => {
        this.checkAndRefreshToken();
      });

    // Stop monitoring when user logs out
    this.auth.isAuthenticated$.pipe(takeUntil(this.destroy$)).subscribe((isAuthenticated) => {
      if (!isAuthenticated) {
        this.stopMonitoring();
      }
    });
  }

  /**
   * Stop activity monitoring
   */
  public stopMonitoring(): void {
    if (!this.isMonitoring) {
      return;
    }

    this.isMonitoring = false;
    this.destroy$.next();
    this.destroy$.complete();
    this.removeActivityListeners();
  }

  /**
   * Manually trigger a refresh check (used for explicit refresh needs)
   */
  public triggerRefreshCheck(): void {
    if (this.isMonitoring) {
      this.activity$.next();
    }
  }

  private setupActivityListeners(): void {
    // Run outside Angular zone to avoid triggering change detection on every event
    this.ngZone.runOutsideAngular(() => {
      // User activity events
      const events = ['mousedown', 'keydown', 'touchstart', 'click'];
      events.forEach((event) => {
        document.addEventListener(event, () => this.onActivity());
      });
    });
  }

  private removeActivityListeners(): void {
    const events = ['mousedown', 'keydown', 'touchstart', 'click'];
    events.forEach((event) => {
      document.removeEventListener(event, () => this.onActivity());
    });
  }

  private onActivity(): void {
    if (this.isMonitoring && this.auth.hasValidToken()) {
      this.activity$.next();
    }
  }

  private checkAndRefreshToken(): void {
    if (!this.auth.hasValidToken()) {
      return;
    }

    const expiresAt = this.auth.getTokenExpiresAt();
    if (!expiresAt) {
      return;
    }

    // Get time until expiry
    const timeUntilExpiry = this.auth.getTimeUntilExpiry();

    // Refresh if token is in final 10% of its lifetime
    // Example: if token lasts 1 hour, refresh in last 6 minutes
    const refreshThresholdMs = (expiresAt - (Date.now() - timeUntilExpiry)) * 0.1;

    if (timeUntilExpiry < refreshThresholdMs) {
      this.performRefresh();
    }
  }

  private performRefresh(): void {
    // Run refresh in Angular zone so that any side effects are detected
    this.ngZone.run(() => {
      this.auth.refreshToken().subscribe({
        next: () => {
          // Token refresh succeeded, auth service handles state
        },
        error: (error) => {
          // Auth service already logs out on refresh failure
        },
      });
    });
  }
}
