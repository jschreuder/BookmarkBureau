import {
  Component,
  OnInit,
  inject,
  HostListener,
  ChangeDetectionStrategy,
  ChangeDetectorRef,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, ActivatedRoute } from '@angular/router';
import { Title } from '@angular/platform-browser';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatIconModule } from '@angular/material/icon';
import { MatChipsModule } from '@angular/material/chips';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { ApiService } from '../../core/services/api.service';
import { FullDashboard } from '../../core/models';
import { Observable, catchError, of } from 'rxjs';
import { LinkSearchDialogComponent, SearchResult } from './link-search-dialog.component';
import { getTextColor } from '../../shared/utils/color.util';

@Component({
  selector: 'app-dashboard-view',
  standalone: true,
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    CommonModule,
    RouterModule,
    MatCardModule,
    MatButtonModule,
    MatToolbarModule,
    MatIconModule,
    MatChipsModule,
    MatProgressSpinnerModule,
    MatDialogModule,
    MatSnackBarModule,
  ],
  templateUrl: './dashboard-view.component.html',
  styleUrl: './dashboard-view.component.scss',
})
export class DashboardViewComponent implements OnInit {
  private readonly apiService = inject(ApiService);
  private readonly route = inject(ActivatedRoute);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly cdr = inject(ChangeDetectorRef);
  private readonly titleService = inject(Title);

  dashboard$!: Observable<FullDashboard | null>;
  error$: Observable<string | null> = of(null);
  private currentDashboard: FullDashboard | null = null;

  // Expose shared utility function to template
  protected readonly getTextColor = getTextColor;

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

    // Store current dashboard for search and update page title
    this.dashboard$.subscribe((dashboard) => {
      this.currentDashboard = dashboard;
      if (dashboard) {
        this.titleService.setTitle(`${dashboard.dashboard.title} | BookmarkBureau`);
      }
      this.cdr.markForCheck();
    });
  }

  @HostListener('document:keydown', ['$event'])
  handleKeyboardShortcut(event: KeyboardEvent): void {
    // Cmd+K (Mac) or Ctrl+K (Windows/Linux)
    if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
      event.preventDefault();
      this.openSearch();
    }
  }

  openSearch(): void {
    if (!this.currentDashboard) {
      return;
    }

    // Collect all links from favorites and categories
    const allLinks: SearchResult[] = [];

    // Add favorites
    if (this.currentDashboard.favorites) {
      for (const link of this.currentDashboard.favorites) {
        allLinks.push({
          ...link,
          isFavorite: true,
        });
      }
    }

    // Add links from categories
    if (this.currentDashboard.categories) {
      for (const category of this.currentDashboard.categories) {
        for (const link of category.links) {
          // Check if already added as favorite
          const existingIndex = allLinks.findIndex((l) => l.link_id === link.link_id);
          if (existingIndex >= 0) {
            // Already exists, just add category info
            allLinks[existingIndex].category = category.title;
          } else {
            // New link
            allLinks.push({
              ...link,
              category: category.title,
              isFavorite: false,
            });
          }
        }
      }
    }

    const dialogRef = this.dialog.open(LinkSearchDialogComponent, {
      width: '600px',
      maxWidth: '90vw',
      data: { links: allLinks },
      panelClass: 'search-dialog',
    });

    dialogRef.afterClosed().subscribe((result: SearchResult | undefined) => {
      if (result) {
        this.openLink(result.url);
      }
    });
  }

  openLink(url: string): void {
    const newWindow = window.open(url, '_blank');
    // Note: Some browsers return null even on success when using noopener/noreferrer
    // Check both null and if the window is closed immediately (actual popup block)
    if (!newWindow || newWindow.closed) {
      this.snackBar.open(
        'Pop-up blocked. Please allow pop-ups for this site to open links.',
        'Close',
        { duration: 5000 },
      );
    }
  }
}
