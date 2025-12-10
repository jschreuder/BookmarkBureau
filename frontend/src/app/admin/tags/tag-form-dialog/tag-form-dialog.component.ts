import { Component, Inject, inject, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatDialogModule, MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { Tag } from '../../../core/models';
import { TagService } from '../../../core/services/tag.service';

export interface TagFormDialogData {
  tag?: Tag; // If provided, we're editing; otherwise creating
}

@Component({
  selector: 'app-tag-form-dialog',
  standalone: true,
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatSnackBarModule,
  ],
  templateUrl: './tag-form-dialog.component.html',
  styleUrl: './tag-form-dialog.component.scss',
})
export class TagFormDialogComponent {
  private readonly tagService = inject(TagService);
  private readonly fb = inject(FormBuilder);
  private readonly dialogRef = inject(MatDialogRef<TagFormDialogComponent>);
  private readonly snackBar = inject(MatSnackBar);

  form: FormGroup;
  loading = false;
  isEditMode: boolean;

  presetColors = [
    '#f44336',
    '#e91e63',
    '#9c27b0',
    '#673ab7',
    '#3f51b5',
    '#2196f3',
    '#03a9f4',
    '#00bcd4',
    '#009688',
    '#4caf50',
    '#8bc34a',
    '#cddc39',
    '#ffc107',
    '#ff9800',
    '#ff5722',
    '#795548',
  ];

  constructor(@Inject(MAT_DIALOG_DATA) public data: TagFormDialogData) {
    this.isEditMode = !!data.tag;

    this.form = this.fb.group({
      tag_name: [
        { value: data.tag?.tag_name || '', disabled: this.isEditMode },
        [Validators.required, Validators.minLength(1)],
      ],
      color: [data.tag?.color || '#2196f3'],
    });

    // Add input event listener to sanitize tag name as user types
    if (!this.isEditMode) {
      this.form.get('tag_name')?.valueChanges.subscribe((value) => {
        if (value) {
          const sanitized = this.sanitizeTagName(value);
          if (value !== sanitized) {
            this.form.get('tag_name')?.setValue(sanitized, { emitEvent: false });
          }
        }
      });
    }
  }

  /**
   * Sanitizes tag name to match backend validation rules:
   * - Only lowercase letters (a-z), numbers (0-9), and hyphens (-)
   * - Maximum 100 characters
   * - Automatically converts to lowercase
   */
  private sanitizeTagName(value: string): string {
    // Convert to lowercase and remove any characters that aren't a-z, 0-9, or hyphen
    const sanitized = value
      .toLowerCase()
      .replace(/[^a-z0-9-]/g, '')
      .slice(0, 100); // Enforce max length

    return sanitized;
  }

  onColorChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.form.patchValue({ color: input.value });
  }

  selectColor(color: string): void {
    this.form.patchValue({ color });
  }

  onSubmit(): void {
    if (!this.form.valid) {
      this.form.markAllAsTouched();
      return;
    }

    this.loading = true;

    if (this.isEditMode && this.data.tag) {
      // Update existing tag (only color can change)
      const tagData: Partial<Tag> = {
        tag_name: this.data.tag.tag_name,
        color: this.form.get('color')?.value || undefined,
      };

      this.tagService.updateTag(this.data.tag.tag_name, tagData).subscribe({
        next: () => {
          this.loading = false;
          this.dialogRef.close(true);
        },
        error: () => {
          this.loading = false;
          this.snackBar.open('Failed to update tag', 'Close', { duration: 5000 });
        },
      });
    } else {
      // Create new tag
      const tagData: Partial<Tag> = {
        tag_name: this.form.get('tag_name')?.value,
        color: this.form.get('color')?.value || undefined,
      };

      this.tagService.createTag(tagData).subscribe({
        next: () => {
          this.loading = false;
          this.dialogRef.close(true);
        },
        error: () => {
          this.loading = false;
          this.snackBar.open('Failed to create tag', 'Close', { duration: 5000 });
        },
      });
    }
  }

  onCancel(): void {
    this.dialogRef.close(false);
  }
}
