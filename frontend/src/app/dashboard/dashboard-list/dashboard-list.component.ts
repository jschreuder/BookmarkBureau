import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatIconModule } from '@angular/material/icon';

@Component({
  selector: 'app-dashboard-list',
  standalone: true,
  imports: [CommonModule, RouterModule, MatCardModule, MatButtonModule, MatToolbarModule, MatIconModule],
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
        <mat-card class="dashboard-card">
          <mat-card-header>
            <mat-card-title>Dashboard list will be displayed here</mat-card-title>
          </mat-card-header>
          <mat-card-content>
            <p>Click on a dashboard to view its bookmarks and categories.</p>
            <p>Use the Admin panel to create and manage your dashboards.</p>
          </mat-card-content>
          <mat-card-actions>
            <button mat-button color="primary">
              <mat-icon>add</mat-icon>
              Create Dashboard
            </button>
          </mat-card-actions>
        </mat-card>
      </div>
    </div>
  `,
  styles: [`
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
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .dashboard-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
      cursor: pointer;
    }
  `]
})
export class DashboardListComponent {}
