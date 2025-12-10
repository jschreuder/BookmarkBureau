import {
  Component,
  inject,
  OnInit,
  ChangeDetectionStrategy,
  ChangeDetectorRef,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTableModule } from '@angular/material/table';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { Tag } from '../../../core/models';
import { TagService } from '../../../core/services/tag.service';
import { TagFormDialogComponent } from '../tag-form-dialog/tag-form-dialog.component';

@Component({
  selector: 'app-tag-list',
  standalone: true,
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    CommonModule,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatTableModule,
    MatDialogModule,
    MatSnackBarModule,
  ],
  template: `
    <div class="page-header">
      <h1>Tags</h1>
      <button
        mat-raised-button
        color="primary"
        (click)="openCreateDialog()"
        data-testid="create-btn"
      >
        <mat-icon>add</mat-icon>
        New Tag
      </button>
    </div>
    <mat-card>
      <mat-card-content>
        @if (tags.length === 0) {
          <div class="empty-state">
            <mat-icon>local_offer</mat-icon>
            <p>No tags yet</p>
            <p class="hint">Create your first tag to organize your links</p>
          </div>
        } @else {
          <table mat-table [dataSource]="tags" class="tag-table">
            <ng-container matColumnDef="color">
              <th mat-header-cell *matHeaderCellDef>Color</th>
              <td mat-cell *matCellDef="let tag">
                <div
                  class="color-indicator"
                  [style.background-color]="tag.color || '#e0e0e0'"
                  [attr.aria-label]="'Color: ' + (tag.color || 'default')"
                ></div>
              </td>
            </ng-container>

            <ng-container matColumnDef="tag_name">
              <th mat-header-cell *matHeaderCellDef>Name</th>
              <td mat-cell *matCellDef="let tag">
                <span
                  class="tag-chip"
                  [style.background-color]="tag.color || '#e0e0e0'"
                  [style.color]="getTextColor(tag.color)"
                >
                  {{ tag.tag_name }}
                </span>
              </td>
            </ng-container>

            <ng-container matColumnDef="actions">
              <th mat-header-cell *matHeaderCellDef>Actions</th>
              <td mat-cell *matCellDef="let tag">
                <button
                  mat-icon-button
                  color="primary"
                  (click)="openEditDialog(tag)"
                  [attr.aria-label]="'Edit tag ' + tag.tag_name"
                  data-testid="edit-btn"
                >
                  <mat-icon>edit</mat-icon>
                </button>
                <button
                  mat-icon-button
                  color="warn"
                  (click)="deleteTag(tag)"
                  [attr.aria-label]="'Delete tag ' + tag.tag_name"
                  data-testid="delete-btn"
                >
                  <mat-icon>delete</mat-icon>
                </button>
              </td>
            </ng-container>

            <tr mat-header-row *matHeaderRowDef="displayedColumns"></tr>
            <tr mat-row *matRowDef="let row; columns: displayedColumns"></tr>
          </table>
        }
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

      .empty-state {
        text-align: center;
        padding: 48px 24px;
        color: rgba(0, 0, 0, 0.6);
      }

      .empty-state mat-icon {
        font-size: 64px;
        width: 64px;
        height: 64px;
        color: rgba(0, 0, 0, 0.3);
        margin-bottom: 16px;
      }

      .empty-state p {
        margin: 8px 0;
      }

      .empty-state .hint {
        font-size: 14px;
        color: rgba(0, 0, 0, 0.4);
      }

      .tag-table {
        width: 100%;
      }

      .color-indicator {
        width: 24px;
        height: 24px;
        border-radius: 4px;
        border: 1px solid rgba(0, 0, 0, 0.1);
      }

      .tag-chip {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 16px;
        font-size: 14px;
        font-weight: 500;
      }

      td.mat-cell,
      th.mat-header-cell {
        padding: 8px 16px;
      }
    `,
  ],
})
export class TagListComponent implements OnInit {
  private tagService = inject(TagService);
  private dialog = inject(MatDialog);
  private snackBar = inject(MatSnackBar);
  private cdr = inject(ChangeDetectorRef);

  tags: Tag[] = [];
  displayedColumns = ['color', 'tag_name', 'actions'];

  ngOnInit(): void {
    this.loadTags();
  }

  loadTags(): void {
    this.tagService.loadTags().subscribe({
      next: (tags) => {
        this.tags = tags;
        this.cdr.markForCheck();
      },
      error: () => {
        this.snackBar.open('Failed to load tags', 'Close', { duration: 5000 });
        this.cdr.markForCheck();
      },
    });

    // Subscribe to tag changes
    this.tagService.tags$.subscribe({
      next: (tags) => {
        this.tags = tags;
        this.cdr.markForCheck();
      },
    });
  }

  openCreateDialog(): void {
    const dialogRef = this.dialog.open(TagFormDialogComponent, {
      width: '500px',
      data: {},
    });

    dialogRef.afterClosed().subscribe((result) => {
      if (result) {
        this.snackBar.open('Tag created successfully', 'Close', { duration: 3000 });
      }
    });
  }

  openEditDialog(tag: Tag): void {
    const dialogRef = this.dialog.open(TagFormDialogComponent, {
      width: '500px',
      data: { tag },
    });

    dialogRef.afterClosed().subscribe((result) => {
      if (result) {
        this.snackBar.open('Tag updated successfully', 'Close', { duration: 3000 });
      }
    });
  }

  deleteTag(tag: Tag): void {
    if (!confirm(`Are you sure you want to delete the tag "${tag.tag_name}"?`)) {
      return;
    }

    this.tagService.deleteTag(tag.tag_name).subscribe({
      next: () => {
        this.snackBar.open('Tag deleted successfully', 'Close', { duration: 3000 });
        this.cdr.markForCheck();
      },
      error: () => {
        this.snackBar.open('Failed to delete tag', 'Close', { duration: 5000 });
        this.cdr.markForCheck();
      },
    });
  }

  getTextColor(backgroundColor: string | undefined): string {
    if (!backgroundColor) {
      return '#000000';
    }

    // Convert hex to RGB
    const hex = backgroundColor.replace('#', '');
    const r = parseInt(hex.substring(0, 2), 16);
    const g = parseInt(hex.substring(2, 4), 16);
    const b = parseInt(hex.substring(4, 6), 16);

    // Calculate luminance
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

    // Return black or white based on luminance
    return luminance > 0.5 ? '#000000' : '#ffffff';
  }
}
