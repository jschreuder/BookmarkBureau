import { Component, OnInit, inject } from '@angular/core';
import { CommonModule, ViewportScroller } from '@angular/common';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatDividerModule } from '@angular/material/divider';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatChipsModule } from '@angular/material/chips';
import { MatTooltipModule } from '@angular/material/tooltip';
import { MatDialogModule, MatDialog } from '@angular/material/dialog';
import { DragDropModule, CdkDragDrop } from '@angular/cdk/drag-drop';
import { FullDashboard, CategoryWithLinks, Link, Favorite } from '../../../core/models';
import { ApiService } from '../../../core/services/api.service';
import { AddCategoryDialogComponent } from '../add-category-dialog/add-category-dialog.component';
import { AddLinkDialogComponent } from '../add-link-dialog/add-link-dialog.component';
import { ConfirmDialogComponent } from '../../../shared/components/confirm-dialog/confirm-dialog.component';
import { EditLinkDialogComponent } from '../edit-link-dialog/edit-link-dialog.component';
import { EditCategoryDialogComponent } from '../edit-category-dialog/edit-category-dialog.component';

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
    DragDropModule,
  ],
  templateUrl: './dashboard-overview.component.html',
  styleUrls: ['./dashboard-overview.component.scss'],
})
export class DashboardOverviewComponent implements OnInit {
  private readonly apiService = inject(ApiService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly snackBar = inject(MatSnackBar);
  private readonly dialog = inject(MatDialog);
  private readonly viewportScroller = inject(ViewportScroller);

  dashboardId: string = '';
  fullDashboard: FullDashboard | null = null;
  loading = true;
  error: string | null = null;
  favoritesReorderMode = false;
  categoriesReorderMode = false;
  private savedScrollPosition: [number, number] | null = null;

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

  loadDashboard(preserveScroll = false): void {
    // Save current scroll position if requested
    if (preserveScroll) {
      this.savedScrollPosition = this.viewportScroller.getScrollPosition();
    }

    this.loading = true;
    this.error = null;
    this.apiService.getDashboard(this.dashboardId).subscribe({
      next: (dashboard) => {
        this.fullDashboard = dashboard;
        this.loading = false;

        // Restore scroll position after Angular updates the DOM
        if (preserveScroll && this.savedScrollPosition) {
          // Use requestAnimationFrame to ensure DOM is fully rendered
          requestAnimationFrame(() => {
            this.viewportScroller.scrollToPosition(this.savedScrollPosition!);
            this.savedScrollPosition = null;
          });
        }
      },
      error: (error) => {
        this.error = 'Failed to load dashboard';
        this.snackBar.open('Failed to load dashboard', 'Close', { duration: 5000 });
        this.loading = false;
        this.savedScrollPosition = null;
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
        this.loadDashboard(true);
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
        this.loadDashboard(true);
        this.snackBar.open('Link added to favorites', 'Close', { duration: 3000 });
      }
    });
  }

  openAddLinkToCategoryDialog(category: CategoryWithLinks): void {
    const dialogRef = this.dialog.open(AddLinkDialogComponent, {
      width: '600px',
      maxWidth: '90vw',
      panelClass: 'add-link-dialog',
      data: { dashboardId: this.dashboardId, categoryId: category.category_id, isFavorite: false },
    });

    dialogRef.afterClosed().subscribe((result) => {
      if (result) {
        this.loadDashboard(true);
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
        this.apiService.deleteCategory(category.category_id).subscribe({
          next: () => {
            this.snackBar.open('Category deleted successfully', 'Close', { duration: 3000 });
            this.loadDashboard(true);
          },
          error: (error) => {
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
        this.apiService.removeFavorite(this.dashboardId, link.link_id).subscribe({
          next: () => {
            this.snackBar.open('Removed from favorites', 'Close', { duration: 3000 });
            this.loadDashboard(true);
          },
          error: (error) => {
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
        title: 'Delete Link',
        message: `Delete "${linkTitle}" from this category?`,
      },
    });

    dialogRef.afterClosed().subscribe((result) => {
      if (result) {
        this.apiService.deleteLink(linkId).subscribe({
          next: () => {
            this.snackBar.open('Link deleted successfully', 'Close', { duration: 3000 });
            this.loadDashboard(true);
          },
          error: (_error) => {
            this.snackBar.open('Failed to delete link', 'Close', { duration: 5000 });
          },
        });
      }
    });
  }

  editDashboard(): void {
    this.router.navigate(['/admin/dashboards', this.dashboardId, 'edit']);
  }

  viewDashboard(): void {
    window.open(`/dashboard/${this.dashboardId}`, '_blank');
  }

  editFavorite(link: Link): void {
    const dialogRef = this.dialog.open(EditLinkDialogComponent, {
      width: '600px',
      maxWidth: '90vw',
      panelClass: 'edit-link-dialog',
      data: { link },
    });

    dialogRef.afterClosed().subscribe((result) => {
      if (result) {
        this.loadDashboard(true);
        this.snackBar.open('Favorite updated successfully', 'Close', { duration: 3000 });
      }
    });
  }

  editCategory(category: CategoryWithLinks): void {
    const dialogRef = this.dialog.open(EditCategoryDialogComponent, {
      width: '400px',
      maxWidth: '90vw',
      panelClass: 'edit-category-dialog',
      data: { category },
    });

    dialogRef.afterClosed().subscribe((result) => {
      if (result) {
        this.loadDashboard(true);
        this.snackBar.open('Category updated successfully', 'Close', { duration: 3000 });
      }
    });
  }

  editLinkInCategory(link: Link): void {
    const dialogRef = this.dialog.open(EditLinkDialogComponent, {
      width: '600px',
      maxWidth: '90vw',
      panelClass: 'edit-link-dialog',
      data: { link },
    });

    dialogRef.afterClosed().subscribe((result) => {
      if (result) {
        this.loadDashboard(true);
        this.snackBar.open('Link updated successfully', 'Close', { duration: 3000 });
      }
    });
  }

  onFavoritesDropped(event: CdkDragDrop<Link[]>): void {
    if (event.previousIndex === event.currentIndex || !this.fullDashboard) {
      return;
    }

    // Reorder the local favorites array
    const favorites = this.fullDashboard.favorites;
    const [draggedItem] = favorites.splice(event.previousIndex, 1);
    favorites.splice(event.currentIndex, 0, draggedItem);

    // Build the reorder payload matching FavoriteReorderAction format
    // Note: sort_order must be positive (1-indexed, not 0-indexed)
    const links = favorites.map((link, index) => ({
      link_id: link.link_id,
      sort_order: index + 1,
    }));

    this.apiService.reorderFavorites(this.dashboardId, links).subscribe({
      next: () => {
        this.snackBar.open('Favorites reordered successfully', 'Close', { duration: 3000 });
      },
      error: (error) => {
        this.snackBar.open('Failed to reorder favorites', 'Close', { duration: 5000 });
        // Reload dashboard to restore original order
        this.loadDashboard();
      },
    });
  }

  onCategoryLinksDropped(event: CdkDragDrop<Link[]>, category: CategoryWithLinks): void {
    if (event.previousIndex === event.currentIndex || !this.fullDashboard) {
      return;
    }

    // Reorder the local category links array
    const links = category.links;
    const [draggedItem] = links.splice(event.previousIndex, 1);
    links.splice(event.currentIndex, 0, draggedItem);

    // Build the reorder payload matching CategoryLinkReorderAction format
    // Note: sort_order must be positive (1-indexed, not 0-indexed)
    const reorderLinks = links.map((link, index) => ({
      link_id: link.link_id,
      sort_order: index + 1,
    }));

    this.apiService.reorderCategoryLinks(category.category_id, reorderLinks).subscribe({
      next: () => {
        this.snackBar.open('Category links reordered successfully', 'Close', { duration: 3000 });
      },
      error: (error) => {
        this.snackBar.open('Failed to reorder category links', 'Close', { duration: 5000 });
        // Reload dashboard to restore original order
        this.loadDashboard();
      },
    });
  }

  onCategoriesDropped(event: CdkDragDrop<CategoryWithLinks[]>): void {
    if (event.previousIndex === event.currentIndex || !this.fullDashboard) {
      return;
    }

    // Reorder the local categories array
    const categories = this.fullDashboard.categories;
    const [draggedItem] = categories.splice(event.previousIndex, 1);
    categories.splice(event.currentIndex, 0, draggedItem);

    // Build the reorder payload matching CategoryReorderAction format
    // Note: sort_order must be positive (1-indexed, not 0-indexed)
    const reorderCategories = categories.map((category, index) => ({
      category_id: category.category_id,
      sort_order: index + 1,
    }));

    this.apiService.reorderCategories(this.dashboardId, reorderCategories).subscribe({
      next: () => {
        this.snackBar.open('Categories reordered successfully', 'Close', { duration: 3000 });
      },
      error: (error) => {
        this.snackBar.open('Failed to reorder categories', 'Close', { duration: 5000 });
        // Reload dashboard to restore original order
        this.loadDashboard();
      },
    });
  }
}
