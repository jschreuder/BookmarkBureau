import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, ActivatedRoute } from '@angular/router';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatIconModule } from '@angular/material/icon';
import { MatChipsModule } from '@angular/material/chips';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { ApiService } from '../../core/services/api.service';
import { FullDashboard, CategoryWithLinks, Link } from '../../core/models';
import { Observable, catchError, of } from 'rxjs';

@Component({
  selector: 'app-dashboard-view',
  standalone: true,
  imports: [
    CommonModule,
    RouterModule,
    MatCardModule,
    MatButtonModule,
    MatToolbarModule,
    MatIconModule,
    MatChipsModule,
    MatProgressSpinnerModule,
  ],
  template: `
    <mat-toolbar color="primary" class="mat-elevation-z4">
      <button mat-icon-button routerLink="/dashboard" aria-label="Back to dashboards">
        <mat-icon>arrow_back</mat-icon>
      </button>
      <mat-icon class="logo-icon">bookmark</mat-icon>
      <span class="app-title">Bookmark Bureau</span>
      <span class="spacer"></span>
      <button mat-button routerLink="/admin">
        <mat-icon>settings</mat-icon>
        <span>Admin</span>
      </button>
    </mat-toolbar>

    <div class="container loading" *ngIf="(dashboard$ | async) === null">
      <mat-spinner></mat-spinner>
      <p>Loading dashboard...</p>
    </div>

    <div class="container" *ngIf="dashboard$ | async as data">
      <div class="dashboard-header">
        <div class="header-content">
          <mat-icon class="dashboard-icon" *ngIf="data.dashboard.icon">
            {{ data.dashboard.icon }}
          </mat-icon>
          <mat-icon class="dashboard-icon" *ngIf="!data.dashboard.icon"> dashboard </mat-icon>
          <div>
            <h1>{{ data.dashboard.title }}</h1>
            <p class="description">{{ data.dashboard.description }}</p>
          </div>
        </div>
      </div>

      <section class="favorites-section" *ngIf="data.favorites && data.favorites.length > 0">
        <div class="section-header">
          <h2><mat-icon>star</mat-icon> Favorites</h2>
        </div>
        <div class="link-grid">
          <mat-card class="link-card" *ngFor="let link of data.favorites">
            <mat-card-content>
              <mat-icon color="accent" *ngIf="link.icon">
                {{ link.icon }}
              </mat-icon>
              <mat-icon color="accent" *ngIf="!link.icon"> link </mat-icon>
              <div class="link-info">
                <h3>{{ link.title }}</h3>
                <p class="link-url">{{ link.url }}</p>
                <p class="link-description" *ngIf="link.description">
                  {{ link.description }}
                </p>
              </div>
            </mat-card-content>
          </mat-card>
        </div>
      </section>

      <section class="categories-section" *ngIf="data.categories && data.categories.length > 0">
        <div class="section-header">
          <h2><mat-icon>folder</mat-icon> Categories</h2>
        </div>

        <mat-card class="category-card" *ngFor="let category of data.categories">
          <mat-card-header>
            <mat-card-title>{{ category.title }}</mat-card-title>
          </mat-card-header>
          <mat-card-content>
            <div class="link-list">
              <div class="link-item" *ngFor="let link of category.links">
                <mat-icon *ngIf="link.icon">{{ link.icon }}</mat-icon>
                <mat-icon *ngIf="!link.icon">link</mat-icon>
                <div class="link-info">
                  <h4>{{ link.title }}</h4>
                  <p class="link-url">{{ link.url }}</p>
                  <mat-chip-set *ngIf="link.tags && link.tags.length > 0">
                    <mat-chip *ngFor="let tag of link.tags">{{ tag.tag_name }}</mat-chip>
                  </mat-chip-set>
                </div>
              </div>
            </div>
          </mat-card-content>
        </mat-card>
      </section>

      <div
        class="empty-state"
        *ngIf="
          (!data.favorites || data.favorites.length === 0) &&
          (!data.categories || data.categories.length === 0)
        "
      >
        <mat-icon>inbox</mat-icon>
        <h2>No content yet</h2>
        <p>Visit the admin panel to add links and categories to this dashboard.</p>
        <button mat-raised-button color="primary" routerLink="/admin">
          <mat-icon>settings</mat-icon>
          Go to Admin
        </button>
      </div>
    </div>

    <div class="container error-container" *ngIf="error$ | async as error">
      <mat-icon class="error-icon">error</mat-icon>
      <h2>Error loading dashboard</h2>
      <p>{{ error }}</p>
      <button mat-raised-button routerLink="/dashboard">Back to Dashboards</button>
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

      .container.loading {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 400px;
      }

      .container.loading mat-spinner {
        margin-bottom: 16px;
      }

      .dashboard-header {
        margin-bottom: 40px;
        padding: 24px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 8px;
        color: white;
      }

      .header-content {
        display: flex;
        align-items: start;
        gap: 16px;
      }

      .dashboard-icon {
        font-size: 48px;
        width: 48px;
        height: 48px;
      }

      .dashboard-header h1 {
        margin: 0 0 8px 0;
        font-size: 32px;
        font-weight: 400;
      }

      .description {
        margin: 0;
        opacity: 0.9;
        font-size: 16px;
      }

      .favorites-section,
      .categories-section {
        margin-bottom: 40px;
      }

      .section-header {
        margin-bottom: 16px;
      }

      .section-header h2 {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
        font-size: 24px;
        font-weight: 400;
      }

      .link-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
      }

      .link-card {
        transition:
          transform 0.2s,
          box-shadow 0.2s;
        cursor: pointer;
      }

      .link-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      }

      .link-card mat-card-content {
        display: flex;
        gap: 12px;
        align-items: start;
      }

      .link-card mat-icon {
        font-size: 32px;
        width: 32px;
        height: 32px;
      }

      .link-info h3 {
        margin: 0 0 4px 0;
        font-size: 16px;
        font-weight: 500;
      }

      .link-url {
        margin: 0 0 8px 0;
        font-size: 12px;
        color: rgba(0, 0, 0, 0.6);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .link-description {
        margin: 0;
        font-size: 14px;
        color: rgba(0, 0, 0, 0.7);
      }

      .category-card {
        margin-bottom: 16px;
      }

      .link-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
      }

      .link-item {
        display: flex;
        gap: 12px;
        padding: 12px;
        border-radius: 4px;
        transition: background-color 0.2s;
        cursor: pointer;
      }

      .link-item:hover {
        background-color: rgba(0, 0, 0, 0.04);
      }

      .link-item mat-icon {
        color: rgba(0, 0, 0, 0.6);
      }

      .link-item h4 {
        margin: 0 0 4px 0;
        font-size: 16px;
        font-weight: 500;
      }

      .link-item mat-chip-set {
        margin-top: 8px;
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

      .error-container {
        text-align: center;
        padding: 64px 24px;
      }

      .error-icon {
        font-size: 64px;
        width: 64px;
        height: 64px;
        color: #d32f2f;
        margin-bottom: 16px;
      }
    `,
  ],
})
export class DashboardViewComponent implements OnInit {
  private apiService = inject(ApiService);
  private route = inject(ActivatedRoute);

  dashboard$!: Observable<FullDashboard | null>;
  error$!: Observable<string | null>;

  ngOnInit(): void {
    const dashboardId = this.route.snapshot.paramMap.get('id');

    if (!dashboardId) {
      this.dashboard$ = of(null);
      this.error$ = of('Dashboard ID not found');
      return;
    }

    this.dashboard$ = this.apiService.getDashboard(dashboardId).pipe(
      catchError((error) => {
        console.error('Error loading dashboard:', error);
        this.error$ = of(error?.error?.error || 'Failed to load dashboard');
        return of(null);
      }),
    );
  }
}
