import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatIconModule } from '@angular/material/icon';
import { ApiService } from '../../core/services/api.service';
import { Dashboard } from '../../core/models';
import { Observable } from 'rxjs';

@Component({
  selector: 'app-dashboard-list',
  standalone: true,
  imports: [
    CommonModule,
    RouterModule,
    MatCardModule,
    MatButtonModule,
    MatToolbarModule,
    MatIconModule,
  ],
  template: `
    <mat-toolbar color="primary" class="mat-elevation-z4">
      <mat-icon class="logo-icon">bookmark</mat-icon>
      <span class="app-title">Bookmark Bureau</span>
      <span class="spacer"></span>
      <button mat-button routerLink="/admin">
        <mat-icon>settings</mat-icon>
        <span>Admin</span>
      </button>
    </mat-toolbar>

    <div class="container">
      <div class="page-header">
        <h1>My Dashboards</h1>
        <p class="subtitle">Organize your bookmarks into customizable dashboards</p>
      </div>

      <div class="dashboard-grid">
        <mat-card
          class="dashboard-card"
          *ngFor="let dashboard of dashboards$ | async"
          [routerLink]="['/dashboard', dashboard.id]"
        >
          <mat-card-header>
            <mat-card-title>{{ dashboard.title }}</mat-card-title>
          </mat-card-header>
          <mat-card-content>
            <p *ngIf="dashboard.description">{{ dashboard.description }}</p>
            <p *ngIf="!dashboard.description" class="no-description">No description provided</p>
          </mat-card-content>
          <mat-card-actions>
            <button mat-button color="primary" [routerLink]="['/dashboard', dashboard.id]">
              <mat-icon>arrow_forward</mat-icon>
              View
            </button>
          </mat-card-actions>
        </mat-card>
      </div>

      <div class="empty-state" *ngIf="!(dashboards$ | async)?.length">
        <mat-icon>inbox</mat-icon>
        <h2>No dashboards yet</h2>
        <p>Create your first dashboard in the admin panel to get started.</p>
        <button mat-raised-button color="primary" routerLink="/admin">
          <mat-icon>settings</mat-icon>
          Go to Admin
        </button>
      </div>
    </div>
  `,
  styles: [
    `
      .logo-icon {
        margin-right: 8px;
      }

      .app-title {
        font-size: 20px;
        font-weight: 500;
      }

      .spacer {
        flex: 1 1 auto;
      }

      button mat-icon {
        margin-right: 4px;
      }

      .container {
        padding: 24px;
        max-width: 1200px;
        margin: 0 auto;
      }

      .page-header {
        margin-bottom: 32px;
      }

      .page-header h1 {
        margin: 0 0 8px 0;
        font-size: 32px;
        font-weight: 400;
      }

      .subtitle {
        margin: 0;
        color: rgba(0, 0, 0, 0.6);
        font-size: 16px;
      }

      .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 24px;
      }

      .dashboard-card {
        transition:
          transform 0.2s,
          box-shadow 0.2s;
        cursor: pointer;
      }

      .dashboard-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
      }

      .no-description {
        color: rgba(0, 0, 0, 0.4);
        font-style: italic;
      }

      .empty-state {
        text-align: center;
        padding: 64px 24px;
      }

      .empty-state mat-icon {
        font-size: 64px;
        width: 64px;
        height: 64px;
        color: rgba(0, 0, 0, 0.2);
        margin-bottom: 16px;
      }

      .empty-state h2 {
        margin: 16px 0 8px 0;
        font-size: 24px;
        font-weight: 400;
        color: rgba(0, 0, 0, 0.6);
      }

      .empty-state p {
        margin: 0 0 24px 0;
        color: rgba(0, 0, 0, 0.5);
      }
    `,
  ],
})
export class DashboardListComponent implements OnInit {
  private apiService = inject(ApiService);

  dashboards$!: Observable<Dashboard[]>;

  ngOnInit(): void {
    this.dashboards$ = this.apiService.listDashboards();
  }
}
