import {
  Component,
  OnInit,
  inject,
  ChangeDetectionStrategy,
  ChangeDetectorRef,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Router } from '@angular/router';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatSidenavModule } from '@angular/material/sidenav';
import { MatListModule } from '@angular/material/list';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatDividerModule } from '@angular/material/divider';
import { ApiService } from '../../core/services/api.service';
import { AuthService } from '../../core/services/auth.service';
import { Dashboard } from '../../core/models';

@Component({
  selector: 'app-admin-layout',
  standalone: true,
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    CommonModule,
    RouterModule,
    MatToolbarModule,
    MatSidenavModule,
    MatListModule,
    MatIconModule,
    MatButtonModule,
    MatDividerModule,
  ],
  templateUrl: './admin-layout.component.html',
  styleUrls: ['./admin-layout.component.scss'],
})
export class AdminLayoutComponent implements OnInit {
  private readonly apiService = inject(ApiService);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly cdr = inject(ChangeDetectorRef);

  dashboardsExpanded = true;
  topDashboards: Dashboard[] = [];

  ngOnInit(): void {
    this.loadTopDashboards();
  }

  toggleDashboards(): void {
    this.dashboardsExpanded = !this.dashboardsExpanded;
  }

  loadTopDashboards(): void {
    this.apiService.listDashboards().subscribe({
      next: (dashboards) => {
        // Sort by updatedAt descending and take top 10
        this.topDashboards = dashboards
          .sort((a, b) => {
            const dateA = new Date(a.updated_at).getTime();
            const dateB = new Date(b.updated_at).getTime();
            return dateB - dateA;
          })
          .slice(0, 10);
        this.cdr.markForCheck();
      },
      error: () => {
        // Handle error silently - dashboards will just be empty
        this.topDashboards = [];
        this.cdr.markForCheck();
      },
    });
  }

  logout(): void {
    this.auth.logout();
    this.router.navigate(['/login']);
  }

  navigateToDashboards(): void {
    this.router.navigate(['/dashboard']);
  }
}
