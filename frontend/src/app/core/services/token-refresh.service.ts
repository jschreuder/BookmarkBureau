import { Injectable, inject } from '@angular/core';
import { AuthService } from './auth.service';
import { Subject } from 'rxjs';
import { debounceTime, takeUntil } from 'rxjs/operators';

/**
 * TokenRefreshService manages activity-based token refresh.
 *
 * Strategy:
 * - Monitors user activity (mouse, keyboard, touch events)
 * - On activity detected, checks if token should be refreshed
 * - Refreshes token if less than 10 minutes remain until expiration
 * - Avoids excessive refreshes by debouncing activity (5 second window)
 * - Automatically stops monitoring when user logs out
 */
@Injectable({
  providedIn: 'root',
})
export class TokenRefreshService {
  private auth = inject(AuthService);

  private destroy$ = new Subject<void>();
  private activity$ = new Subject<void>();
  private isMonitoring = false;
  private activityDebounceMs = 5000; // Debounce activity for 5 seconds
  private refreshThresholdMs = 10 * 60 * 1000; // Refresh when less than 10 minutes remain
  private activityHandlers: Map<string, () => void> = new Map();

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

  /**
   * Set custom refresh threshold in milliseconds
   * @param thresholdMs Time in milliseconds before expiration to trigger refresh
   */
  public setRefreshThreshold(thresholdMs: number): void {
    if (thresholdMs <= 0) {
      throw new Error('Refresh threshold must be positive');
    }
    this.refreshThresholdMs = thresholdMs;
  }

  private setupActivityListeners(): void {
    // In zoneless mode, event listeners don't trigger change detection
    // User activity events
    const events = ['mousedown', 'keydown', 'touchstart', 'click'];
    events.forEach((event) => {
      const handler = () => this.onActivity();
      this.activityHandlers.set(event, handler);
      document.addEventListener(event, handler);
    });
  }

  private removeActivityListeners(): void {
    this.activityHandlers.forEach((handler, event) => {
      document.removeEventListener(event, handler);
    });
    this.activityHandlers.clear();
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

    // Get time until expiry
    const timeUntilExpiry = this.auth.getTimeUntilExpiry();

    // Refresh if less than threshold time remains (default: 10 minutes)
    if (timeUntilExpiry <= this.refreshThresholdMs) {
      this.performRefresh();
    }
  }

  private performRefresh(): void {
    // Verify token still exists before attempting refresh
    if (!this.auth.getToken()) {
      return;
    }

    // In zoneless mode, change detection is automatic with markForCheck()
    // Auth service will handle any necessary change detection notifications
    this.auth.refreshToken().subscribe({
      next: () => {
        // Token refresh succeeded, auth service handles state
      },
      error: (error) => {
        // Auth service already logs out on refresh failure
      },
    });
  }
}
