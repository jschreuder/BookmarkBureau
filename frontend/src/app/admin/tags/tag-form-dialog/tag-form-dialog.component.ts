import { Component, Inject, inject } from '@angular/core';
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
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatSnackBarModule,
  ],
  template: `
    <h2 mat-dialog-title>{{ isEditMode ? 'Edit Tag' : 'Create Tag' }}</h2>
    <mat-dialog-content>
      <form [formGroup]="form" class="dialog-form">
        <mat-form-field appearance="outline">
          <mat-label>Tag Name</mat-label>
          <input
            matInput
            formControlName="tag_name"
            placeholder="Enter tag name"
            required
            [readonly]="isEditMode"
          />
          @if (form.get('tag_name')?.hasError('required') && form.get('tag_name')?.touched) {
            <mat-error>Tag name is required</mat-error>
          }
          @if (form.get('tag_name')?.hasError('minlength') && form.get('tag_name')?.touched) {
            <mat-error>Tag name must be at least 1 character</mat-error>
          }
        </mat-form-field>

        <mat-form-field appearance="outline">
          <mat-label>Color (Optional)</mat-label>
          <div class="color-input-wrapper">
            <input
              type="color"
              formControlName="color"
              class="color-picker"
              (change)="onColorChange($event)"
            />
            <input
              matInput
              [value]="form.get('color')?.value || '#e0e0e0'"
              placeholder="#e0e0e0"
              readonly
              class="color-display"
            />
          </div>
          <mat-hint>Choose a color for this tag</mat-hint>
        </mat-form-field>

        <div class="color-presets">
          <span class="presets-label">Quick colors:</span>
          @for (color of presetColors; track color) {
            <button
              type="button"
              class="color-preset"
              [style.background-color]="color"
              (click)="selectColor(color)"
              [attr.aria-label]="'Select color ' + color"
            ></button>
          }
        </div>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button (click)="onCancel()" type="button">Cancel</button>
      <button
        mat-raised-button
        color="primary"
        (click)="onSubmit()"
        [disabled]="!form.valid || loading"
        type="button"
      >
        {{ loading ? 'Saving...' : isEditMode ? 'Update Tag' : 'Create Tag' }}
      </button>
    </mat-dialog-actions>
  `,
  styles: [
    `
      mat-dialog-content {
        min-width: 400px;
        padding: 20px 24px !important;
      }

      .dialog-form {
        display: flex;
        flex-direction: column;
        gap: 16px;
        padding-top: 8px;
      }

      mat-form-field {
        width: 100%;
      }

      .color-input-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
      }

      .color-picker {
        width: 60px;
        height: 40px;
        border: 1px solid #ccc;
        border-radius: 4px;
        cursor: pointer;
      }

      .color-display {
        flex: 1;
      }

      .color-presets {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
      }

      .presets-label {
        font-size: 14px;
        color: rgba(0, 0, 0, 0.6);
        margin-right: 4px;
      }

      .color-preset {
        width: 32px;
        height: 32px;
        border: 2px solid #ccc;
        border-radius: 4px;
        cursor: pointer;
        transition:
          transform 0.2s,
          border-color 0.2s;
      }

      .color-preset:hover {
        transform: scale(1.1);
        border-color: #666;
      }

      mat-dialog-actions {
        padding: 8px 24px 16px !important;
        margin: 0;
      }
    `,
  ],
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
        error: (error: unknown) => {
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
        error: (error: unknown) => {
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
