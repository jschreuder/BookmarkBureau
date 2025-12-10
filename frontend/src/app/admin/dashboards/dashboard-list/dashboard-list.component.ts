import {
  Component,
  OnInit,
  inject,
  ChangeDetectionStrategy,
  ChangeDetectorRef,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Router } from '@angular/router';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTableModule } from '@angular/material/table';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSnackBarModule, MatSnackBar } from '@angular/material/snack-bar';
import { MatDialogModule, MatDialog } from '@angular/material/dialog';
import { MatTooltipModule } from '@angular/material/tooltip';
import { Dashboard } from '../../../core/models';
import { ApiService } from '../../../core/services/api.service';
import { ConfirmDialogComponent } from '../../../shared/components/confirm-dialog/confirm-dialog.component';

@Component({
  selector: 'app-admin-dashboard-list',
  standalone: true,
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    CommonModule,
    RouterModule,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatTableModule,
    MatProgressSpinnerModule,
    MatSnackBarModule,
    MatDialogModule,
    MatTooltipModule,
  ],
  templateUrl: './dashboard-list.component.html',
  styleUrl: './dashboard-list.component.scss',
})
export class AdminDashboardListComponent implements OnInit {
  private readonly apiService = inject(ApiService);
  private readonly router = inject(Router);
  private readonly snackBar = inject(MatSnackBar);
  private readonly dialog = inject(MatDialog);
  private readonly cdr = inject(ChangeDetectorRef);

  dashboards: Dashboard[] = [];
  loading = true;
  displayedColumns = ['title', 'description', 'icon', 'actions'];

  ngOnInit() {
    this.loadDashboards();
  }

  loadDashboards() {
    this.loading = true;
    this.apiService.listDashboards().subscribe({
      next: (dashboards) => {
        this.dashboards = dashboards;
        this.loading = false;
        this.cdr.markForCheck();
      },
      error: () => {
        this.snackBar.open('Failed to load dashboards', 'Close', { duration: 5000 });
        this.loading = false;
        this.cdr.markForCheck();
      },
    });
  }

  navigateToNew() {
    this.router.navigate(['/admin/dashboards/new']);
  }

  navigateToView(dashboardId: string) {
    window.open(`/dashboard/${dashboardId}`, '_blank');
  }

  navigateToOverview(dashboardId: string) {
    this.router.navigate(['/admin/dashboards', dashboardId, 'overview']);
  }

  navigateToEdit(dashboardId: string) {
    this.router.navigate(['/admin/dashboards', dashboardId, 'edit']);
  }

  delete(dashboard: Dashboard) {
    const dialogRef = this.dialog.open(ConfirmDialogComponent, {
      width: '400px',
      data: {
        title: 'Delete Dashboard',
        message: `Are you sure you want to delete "${dashboard.title}"? This action cannot be undone.`,
      },
    });

    dialogRef.afterClosed().subscribe((result) => {
      if (result) {
        this.apiService.deleteDashboard(dashboard.dashboard_id).subscribe({
          next: () => {
            this.snackBar.open('Dashboard deleted successfully', 'Close', { duration: 3000 });
            this.loadDashboards();
          },
          error: () => {
            this.snackBar.open('Failed to delete dashboard', 'Close', { duration: 5000 });
            this.cdr.markForCheck();
          },
        });
      }
    });
  }
}
