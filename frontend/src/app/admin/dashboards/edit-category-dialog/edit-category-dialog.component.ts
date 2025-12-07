import { Component, Inject, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatDialogModule, MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { Category } from '../../../core/models';
import { ApiService } from '../../../core/services/api.service';

export interface EditCategoryDialogData {
  category: Category;
}

@Component({
  selector: 'app-edit-category-dialog',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatIconModule,
    MatSnackBarModule,
  ],
  template: `
    <h2 mat-dialog-title>Edit Category</h2>
    <mat-dialog-content>
      <form [formGroup]="form" class="dialog-form">
        <mat-form-field appearance="outline">
          <mat-label>Category Title</mat-label>
          <input
            matInput
            formControlName="title"
            placeholder="e.g., Work, Personal, Blogs"
            required
          />
          @if (form.get('title')?.hasError('required') && form.get('title')?.touched) {
            <mat-error>Title is required</mat-error>
          }
        </mat-form-field>

        <mat-form-field appearance="outline">
          <mat-label>Color (Optional)</mat-label>
          <input matInput formControlName="color" type="color" placeholder="#667eea" />
          @if (form.get('color')?.value) {
            <button
              matSuffix
              mat-icon-button
              type="button"
              (click)="clearColor()"
              aria-label="Clear color"
            >
              <mat-icon>close</mat-icon>
            </button>
          }
        </mat-form-field>
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
        {{ loading ? 'Updating...' : 'Update Category' }}
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

      mat-dialog-actions {
        padding: 8px 24px 16px !important;
        margin: 0;
      }
    `,
  ],
})
export class EditCategoryDialogComponent {
  private readonly apiService = inject(ApiService);
  private readonly fb = inject(FormBuilder);
  private readonly dialogRef = inject(MatDialogRef<EditCategoryDialogComponent>);
  private readonly snackBar = inject(MatSnackBar);

  form: FormGroup;
  loading = false;

  constructor(@Inject(MAT_DIALOG_DATA) public data: EditCategoryDialogData) {
    this.form = this.fb.group({
      title: [data.category.title, [Validators.required, Validators.minLength(1)]],
      color: [data.category.color || ''],
    });
  }

  onSubmit(): void {
    if (!this.form.valid) {
      this.form.markAllAsTouched();
      return;
    }

    this.loading = true;
    const categoryData: Partial<Category> = {
      title: this.form.get('title')?.value,
      color: this.form.get('color')?.value || undefined,
      dashboard_id: this.data.category.dashboard_id,
    };

    this.apiService.updateCategory(this.data.category.category_id, categoryData).subscribe({
      next: () => {
        this.loading = false;
        this.dialogRef.close(true);
      },
      error: (_error) => {
        this.loading = false;
        this.snackBar.open('Failed to update category', 'Close', { duration: 5000 });
      },
    });
  }

  onCancel(): void {
    this.dialogRef.close(false);
  }

  clearColor(): void {
    this.form.patchValue({ color: '' });
  }
}
