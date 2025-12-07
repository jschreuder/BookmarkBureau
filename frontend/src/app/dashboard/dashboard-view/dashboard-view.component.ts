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
    <div class="container loading" *ngIf="(dashboard$ | async) === null">
      <mat-spinner></mat-spinner>
      <p>Loading dashboard...</p>
    </div>

    <div *ngIf="dashboard$ | async as data">
      <!-- Compact Top Bar -->
      <div class="dashboard-toolbar">
        <div class="toolbar-content">
          <div class="toolbar-header">
            <mat-icon class="toolbar-icon" *ngIf="data.dashboard.icon">
              {{ data.dashboard.icon }}
            </mat-icon>
            <mat-icon class="toolbar-icon" *ngIf="!data.dashboard.icon">dashboard</mat-icon>
            <span class="toolbar-title">{{ data.dashboard.title }}</span>
          </div>

          <!-- Favorites in Toolbar -->
          <div class="toolbar-favorites" *ngIf="data.favorites && data.favorites.length > 0">
            <mat-chip
              *ngFor="let link of data.favorites"
              (click)="openLink(link.url)"
              class="favorite-chip"
            >
              <mat-icon *ngIf="link.icon">{{ link.icon }}</mat-icon>
              {{ link.title }}
            </mat-chip>
          </div>
        </div>
      </div>

      <!-- Description as Quote -->
      <div class="description-quote" *ngIf="data.dashboard.description">
        <p>{{ data.dashboard.description }}</p>
      </div>

      <!-- Main Content Container -->
      <div class="container">
        <!-- Categories Section -->
        <section class="categories-section" *ngIf="data.categories && data.categories.length > 0">
          <div class="categories-grid">
            <mat-card class="category-card" *ngFor="let category of data.categories">
              <mat-card-header [style.background-color]="category.color || '#667eea'">
                <mat-icon
                  mat-card-avatar
                  class="category-icon"
                  [style.color]="getTextColor(category.color)"
                >
                  folder
                </mat-icon>
                <mat-card-title [style.color]="getTextColor(category.color)">{{
                  category.title
                }}</mat-card-title>
              </mat-card-header>
              <mat-card-content>
                <div class="link-list">
                  <div
                    class="link-item"
                    *ngFor="let link of category.links"
                    (click)="openLink(link.url)"
                  >
                    <mat-icon *ngIf="link.icon">{{ link.icon }}</mat-icon>
                    <mat-icon *ngIf="!link.icon">link</mat-icon>
                    <div class="link-info">
                      <h4>{{ link.title }}</h4>
                      <p class="link-description" *ngIf="link.description">
                        {{ link.description }}
                      </p>
                      <mat-chip-set *ngIf="link.tags && link.tags.length > 0">
                        <mat-chip *ngFor="let tag of link.tags">{{ tag.tag_name }}</mat-chip>
                      </mat-chip-set>
                    </div>
                  </div>
                </div>
              </mat-card-content>
            </mat-card>
          </div>
        </section>

        <!-- Empty State -->
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
      .container {
        padding: 20px;
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

      /* Compact Toolbar */
      .dashboard-toolbar {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 16px 0;
        color: white;
      }

      .toolbar-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
      }

      .toolbar-header {
        display: flex;
        align-items: center;
        gap: 12px;
      }

      .toolbar-icon {
        font-size: 32px;
        width: 32px;
        height: 32px;
      }

      .toolbar-title {
        font-size: 24px;
        font-weight: 500;
      }

      .toolbar-favorites {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
      }

      .favorite-chip {
        background-color: rgba(255, 255, 255, 0.2) !important;
        color: white !important;
        border: none !important;
        transition:
          transform 0.2s,
          background-color 0.2s;
      }

      .favorite-chip:hover {
        transform: scale(1.05);
        background-color: rgba(255, 255, 255, 0.3) !important;
      }

      .favorite-chip ::ng-deep .mdc-evolution-chip__action--primary {
        cursor: pointer !important;
      }

      .favorite-chip ::ng-deep .mat-mdc-chip-action-label {
        display: flex;
        align-items: center;
        gap: 4px;
        color: white !important;
      }

      .favorite-chip mat-icon {
        font-size: 18px;
        width: 18px;
        height: 18px;
        color: white !important;
        vertical-align: middle;
      }

      /* Description Quote */
      .description-quote {
        text-align: center;
        padding: 16px 24px 12px;
        max-width: 800px;
        margin: 0 auto;
      }

      .description-quote p {
        margin: 0;
        font-size: 14px;
        font-style: italic;
        color: rgba(0, 0, 0, 0.5);
        line-height: 1.5;
      }

      /* Categories Section */
      .categories-section {
        margin-top: 8px;
      }

      .categories-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
      }

      @media (max-width: 1200px) {
        .categories-grid {
          grid-template-columns: repeat(2, 1fr);
        }
      }

      @media (max-width: 768px) {
        .categories-grid {
          grid-template-columns: 1fr;
        }

        .toolbar-content {
          flex-direction: column;
          align-items: flex-start;
          gap: 12px;
        }

        .toolbar-favorites {
          width: 100%;
        }
      }

      .category-card {
        margin-bottom: 0;
        overflow: hidden;
      }

      .category-card ::ng-deep .mat-card-header {
        align-items: center;
        padding: 12px 20px !important;
        margin: 0 !important;
      }

      .category-card ::ng-deep .mat-card-title {
        font-size: 16px;
        font-weight: 600;
        margin: 0;
      }

      .category-icon {
        width: 24px;
        height: 24px;
        font-size: 24px;
        margin-right: 6px;
      }

      .category-card ::ng-deep mat-card-content {
        padding-top: 8px;
      }

      .link-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
      }

      .link-item {
        display: flex;
        gap: 12px;
        padding: 10px;
        border-radius: 4px;
        transition: background-color 0.2s;
        cursor: pointer;
        align-items: flex-start;
      }

      .link-item:hover {
        background-color: rgba(0, 0, 0, 0.04);
      }

      .link-item mat-icon {
        color: rgba(0, 0, 0, 0.6);
        flex-shrink: 0;
      }

      .link-info {
        flex: 1;
        min-width: 0;
      }

      .link-info h4 {
        margin: 0 0 4px 0;
        font-size: 15px;
        font-weight: 500;
      }

      .link-description {
        margin: 0;
        font-size: 13px;
        color: rgba(0, 0, 0, 0.6);
        line-height: 1.4;
        word-wrap: break-word;
        overflow-wrap: break-word;
      }

      .link-item mat-chip-set {
        margin-top: 8px;
      }

      /* Empty State */
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

      /* Error State */
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
  error$: Observable<string | null> = of(null);

  ngOnInit(): void {
    const dashboardId = this.route.snapshot.paramMap.get('id');

    if (!dashboardId) {
      this.dashboard$ = of(null);
      this.error$ = of('Dashboard ID not found');
      return;
    }

    this.dashboard$ = this.apiService.getDashboard(dashboardId).pipe(
      catchError((error) => {
        this.error$ = of(error?.error?.error || 'Failed to load dashboard');
        return of(null);
      }),
    );
  }

  openLink(url: string): void {
    window.open(url, '_blank');
  }

  getTextColor(hexColor: string | undefined): string {
    if (!hexColor) {
      return 'white';
    }

    // Convert hex to RGB
    const hex = hexColor.replace('#', '');
    const r = parseInt(hex.substring(0, 2), 16);
    const g = parseInt(hex.substring(2, 4), 16);
    const b = parseInt(hex.substring(4, 6), 16);

    // Calculate luminance using relative luminance formula
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

    // Return white text for dark backgrounds, black text for light backgrounds
    return luminance > 0.5 ? '#000000' : '#ffffff';
  }
}
