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
  template: `
    <div class="page-header">
      <h1>Dashboards</h1>
      <button mat-raised-button color="primary" (click)="navigateToNew()">
        <mat-icon>add</mat-icon>
        New Dashboard
      </button>
    </div>

    <mat-card>
      <mat-card-content>
        <div *ngIf="loading" class="loading-spinner">
          <mat-spinner diameter="50"></mat-spinner>
        </div>

        <div *ngIf="!loading && dashboards.length === 0" class="empty-state">
          <mat-icon>dashboard</mat-icon>
          <p>No dashboards found. Create one to get started!</p>
        </div>

        <div *ngIf="!loading && dashboards.length > 0" class="table-container">
          <table mat-table [dataSource]="dashboards" class="dashboards-table">
            <!-- Title Column -->
            <ng-container matColumnDef="title">
              <th mat-header-cell *matHeaderCellDef>Title</th>
              <td mat-cell *matCellDef="let dashboard">{{ dashboard.title }}</td>
            </ng-container>

            <!-- Description Column -->
            <ng-container matColumnDef="description">
              <th mat-header-cell *matHeaderCellDef>Description</th>
              <td mat-cell *matCellDef="let dashboard">{{ dashboard.description }}</td>
            </ng-container>

            <!-- Icon Column -->
            <ng-container matColumnDef="icon">
              <th mat-header-cell *matHeaderCellDef>Icon</th>
              <td mat-cell *matCellDef="let dashboard">
                <mat-icon *ngIf="dashboard.icon">{{ dashboard.icon }}</mat-icon>
                <span *ngIf="!dashboard.icon" class="no-icon">-</span>
              </td>
            </ng-container>

            <!-- Actions Column -->
            <ng-container matColumnDef="actions">
              <th mat-header-cell *matHeaderCellDef>Actions</th>
              <td mat-cell *matCellDef="let dashboard">
                <button
                  mat-icon-button
                  matTooltip="Edit"
                  (click)="navigateToEdit(dashboard.dashboard_id)"
                  color="primary"
                >
                  <mat-icon>edit</mat-icon>
                </button>
                <button
                  mat-icon-button
                  matTooltip="Overview"
                  (click)="navigateToOverview(dashboard.dashboard_id)"
                  color="primary"
                >
                  <mat-icon>dashboard</mat-icon>
                </button>
                <button
                  mat-icon-button
                  matTooltip="Delete"
                  (click)="delete(dashboard)"
                  color="warn"
                >
                  <mat-icon>delete</mat-icon>
                </button>
                <button
                  mat-icon-button
                  matTooltip="View"
                  (click)="navigateToView(dashboard.dashboard_id)"
                  color="accent"
                >
                  <mat-icon>visibility</mat-icon>
                </button>
              </td>
            </ng-container>

            <tr mat-header-row *matHeaderRowDef="displayedColumns"></tr>
            <tr mat-row *matRowDef="let row; columns: displayedColumns"></tr>
          </table>
        </div>
      </mat-card-content>
    </mat-card>
  `,
  styles: [
    `
      .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
      }

      h1 {
        margin: 0;
      }

      button mat-icon {
        margin-right: 8px;
      }

      .loading-spinner {
        display: flex;
        justify-content: center;
        padding: 40px;
      }

      .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 60px 40px;
        text-align: center;
        color: rgba(0, 0, 0, 0.54);
      }

      .empty-state mat-icon {
        font-size: 64px;
        width: 64px;
        height: 64px;
        margin-bottom: 16px;
        color: rgba(0, 0, 0, 0.26);
      }

      .table-container {
        overflow-x: auto;
      }

      .dashboards-table {
        width: 100%;
        border-collapse: collapse;
      }

      .dashboards-table th {
        font-weight: 600;
        background-color: rgba(0, 0, 0, 0.04);
      }

      .dashboards-table td {
        padding: 16px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.12);
      }

      .no-icon {
        color: rgba(0, 0, 0, 0.38);
      }

      .dashboards-table td mat-icon {
        font-size: 24px;
        width: 24px;
        height: 24px;
        vertical-align: middle;
      }

      button[mat-icon-button] {
        margin: 0 4px;
      }
    `,
  ],
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
