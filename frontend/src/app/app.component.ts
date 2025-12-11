import { Component, OnInit, inject, signal } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { AuthService } from './core/services/auth.service';
import { TokenRefreshService } from './core/services/token-refresh.service';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet],
  templateUrl: './app.component.html',
})
export class AppComponent implements OnInit {
  protected readonly title = signal('frontend');
  private readonly auth = inject(AuthService);
  private readonly tokenRefresh = inject(TokenRefreshService);

  ngOnInit(): void {
    // Initialize activity-based token refresh if user is already authenticated
    if (this.auth.hasValidToken()) {
      this.tokenRefresh.initializeMonitoring();
    }

    // Start/stop monitoring based on auth state changes
    this.auth.isAuthenticated$.subscribe((isAuthenticated) => {
      if (isAuthenticated) {
        this.tokenRefresh.initializeMonitoring();
      } else {
        this.tokenRefresh.stopMonitoring();
      }
    });
  }
}
