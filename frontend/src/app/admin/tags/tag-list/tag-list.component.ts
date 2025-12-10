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
import { ConfirmDialogComponent } from '../../../shared/components/confirm-dialog/confirm-dialog.component';
import { getTextColor } from '../../../shared/utils/color.util';

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
  templateUrl: './tag-list.component.html',
  styleUrl: './tag-list.component.scss',
})
export class TagListComponent implements OnInit {
  private tagService = inject(TagService);
  private dialog = inject(MatDialog);
  private snackBar = inject(MatSnackBar);
  private cdr = inject(ChangeDetectorRef);

  tags: Tag[] = [];
  displayedColumns = ['color', 'tag_name', 'actions'];

  // Expose shared utility function to template
  protected readonly getTextColor = getTextColor;

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
    const dialogRef = this.dialog.open(ConfirmDialogComponent, {
      width: '400px',
      data: {
        title: 'Delete Tag',
        message: `Are you sure you want to delete the tag "${tag.tag_name}"? This action cannot be undone.`,
      },
    });

    dialogRef.afterClosed().subscribe((result) => {
      if (result) {
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
    });
  }
}
