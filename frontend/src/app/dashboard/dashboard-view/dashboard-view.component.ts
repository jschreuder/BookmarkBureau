import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatIconModule } from '@angular/material/icon';
import { MatChipsModule } from '@angular/material/chips';

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
    MatChipsModule
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

    <div class="container">
      <div class="dashboard-header">
        <div class="header-content">
          <mat-icon class="dashboard-icon">dashboard</mat-icon>
          <div>
            <h1>Dashboard Title</h1>
            <p class="description">Dashboard description will appear here. This is a great place to organize your favorite links.</p>
          </div>
        </div>
      </div>

      <section class="favorites-section">
        <div class="section-header">
          <h2><mat-icon>star</mat-icon> Favorites</h2>
        </div>
        <div class="link-grid">
          <mat-card class="link-card">
            <mat-card-content>
              <mat-icon color="accent">link</mat-icon>
              <div class="link-info">
                <h3>Example Link</h3>
                <p class="link-url">https://example.com</p>
                <p class="link-description">Favorite bookmarks will be displayed here as cards.</p>
              </div>
            </mat-card-content>
          </mat-card>
        </div>
      </section>

      <section class="categories-section">
        <div class="section-header">
          <h2><mat-icon>folder</mat-icon> Categories</h2>
        </div>

        <mat-card class="category-card">
          <mat-card-header>
            <mat-card-title>Example Category</mat-card-title>
          </mat-card-header>
          <mat-card-content>
            <div class="link-list">
              <div class="link-item">
                <mat-icon>link</mat-icon>
                <div class="link-info">
                  <h4>Link Title</h4>
                  <p class="link-url">https://example.com</p>
                  <mat-chip-set>
                    <mat-chip>tag1</mat-chip>
                    <mat-chip>tag2</mat-chip>
                  </mat-chip-set>
                </div>
              </div>
            </div>
          </mat-card-content>
        </mat-card>
      </section>
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
      transition: transform 0.2s, box-shadow 0.2s;
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
  `]
})
export class DashboardViewComponent {}
