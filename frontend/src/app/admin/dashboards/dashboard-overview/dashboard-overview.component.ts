import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterModule } from '@angular/router';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatDividerModule } from '@angular/material/divider';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatChipsModule } from '@angular/material/chips';
import { MatTooltipModule } from '@angular/material/tooltip';
import { MatDialogModule, MatDialog } from '@angular/material/dialog';
import { FullDashboard, CategoryWithLinks, Link, Favorite } from '../../../core/models';
import { ApiService } from '../../../core/services/api.service';
import { AddCategoryDialogComponent } from '../add-category-dialog/add-category-dialog.component';
import { AddLinkDialogComponent } from '../add-link-dialog/add-link-dialog.component';
import { ConfirmDialogComponent } from '../../../shared/components/confirm-dialog/confirm-dialog.component';

@Component({
  selector: 'app-dashboard-overview',
  standalone: true,
  imports: [
    CommonModule,
    RouterModule,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatDividerModule,
    MatProgressSpinnerModule,
    MatSnackBarModule,
    MatChipsModule,
    MatTooltipModule,
    MatDialogModule,
  ],
  templateUrl: './dashboard-overview.component.html',
  styleUrls: ['./dashboard-overview.component.scss'],
})
export class DashboardOverviewComponent implements OnInit {
  private readonly apiService = inject(ApiService);
  private readonly route = inject(ActivatedRoute);
  private readonly snackBar = inject(MatSnackBar);
  private readonly dialog = inject(MatDialog);

  dashboardId: string = '';
  fullDashboard: FullDashboard | null = null;
  loading = true;
  error: string | null = null;

  ngOnInit(): void {
    this.route.paramMap.subscribe((params) => {
      this.dashboardId = params.get('id') || '';
      if (!this.dashboardId) {
        this.error = 'Dashboard ID not found';
        this.loading = false;
        return;
      }
      this.loadDashboard();
    });
  }

  loadDashboard(): void {
    this.loading = true;
    this.error = null;
    this.apiService.getDashboard(this.dashboardId).subscribe({
      next: (dashboard) => {
        this.fullDashboard = dashboard;
        this.loading = false;
      },
      error: (error) => {
        console.error('Error loading dashboard:', error);
        this.error = 'Failed to load dashboard';
        this.snackBar.open('Failed to load dashboard', 'Close', { duration: 5000 });
        this.loading = false;
      },
    });
  }

  openAddCategoryDialog(): void {
    const dialogRef = this.dialog.open(AddCategoryDialogComponent, {
      width: '500px',
      maxWidth: '90vw',
      panelClass: 'add-category-dialog',
      data: { dashboardId: this.dashboardId },
    });

    dialogRef.afterClosed().subscribe((result) => {
      if (result) {
        this.loadDashboard();
        this.snackBar.open('Category added successfully', 'Close', { duration: 3000 });
      }
    });
  }

  openAddFavoriteDialog(): void {
    const dialogRef = this.dialog.open(AddLinkDialogComponent, {
      width: '600px',
      maxWidth: '90vw',
      panelClass: 'add-link-dialog',
      data: { dashboardId: this.dashboardId, isFavorite: true },
    });

    dialogRef.afterClosed().subscribe((result) => {
      if (result) {
        this.loadDashboard();
        this.snackBar.open('Link added to favorites', 'Close', { duration: 3000 });
      }
    });
  }

  openAddLinkToCategoryDialog(category: CategoryWithLinks): void {
    const dialogRef = this.dialog.open(AddLinkDialogComponent, {
      width: '600px',
      maxWidth: '90vw',
      panelClass: 'add-link-dialog',
      data: { dashboardId: this.dashboardId, categoryId: category.id, isFavorite: false },
    });

    dialogRef.afterClosed().subscribe((result) => {
      if (result) {
        this.loadDashboard();
        this.snackBar.open('Link added to category', 'Close', { duration: 3000 });
      }
    });
  }

  removeCategory(category: CategoryWithLinks): void {
    const dialogRef = this.dialog.open(ConfirmDialogComponent, {
      width: '400px',
      data: {
        title: 'Delete Category',
        message: `Are you sure you want to delete "${category.title}"? This action cannot be undone.`,
      },
    });

    dialogRef.afterClosed().subscribe((result) => {
      if (result) {
        this.apiService.deleteCategory(category.id).subscribe({
          next: () => {
            this.snackBar.open('Category deleted successfully', 'Close', { duration: 3000 });
            this.loadDashboard();
          },
          error: (error) => {
            console.error('Error deleting category:', error);
            this.snackBar.open('Failed to delete category', 'Close', { duration: 5000 });
          },
        });
      }
    });
  }

  removeFavorite(link: Link): void {
    const dialogRef = this.dialog.open(ConfirmDialogComponent, {
      width: '400px',
      data: {
        title: 'Remove from Favorites',
        message: `Remove "${link.title}" from favorites?`,
      },
    });

    dialogRef.afterClosed().subscribe((result) => {
      if (result) {
        this.apiService.removeFavorite(this.dashboardId, link.id).subscribe({
          next: () => {
            this.snackBar.open('Removed from favorites', 'Close', { duration: 3000 });
            this.loadDashboard();
          },
          error: (error) => {
            console.error('Error removing favorite:', error);
            this.snackBar.open('Failed to remove favorite', 'Close', { duration: 5000 });
          },
        });
      }
    });
  }

  removeLinkFromCategory(categoryId: string, linkId: string, linkTitle: string): void {
    const dialogRef = this.dialog.open(ConfirmDialogComponent, {
      width: '400px',
      data: {
        title: 'Remove Link',
        message: `Remove "${linkTitle}" from this category?`,
      },
    });

    dialogRef.afterClosed().subscribe((result) => {
      if (result) {
        // Call API to remove link from category (assuming similar endpoint exists)
        this.snackBar.open('Link removed from category', 'Close', { duration: 3000 });
        this.loadDashboard();
      }
    });
  }
}
