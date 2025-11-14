import { Component, Inject, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatDialogModule, MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { ApiService } from '../../../core/services/api.service';

export interface AddCategoryDialogData {
  dashboardId: string;
}

@Component({
  selector: 'app-add-category-dialog',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
  ],
  template: `
    <h2 mat-dialog-title>Add Category</h2>
    <mat-dialog-content>
      <form [formGroup]="form">
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Category Title</mat-label>
          <input matInput formControlName="title" placeholder="e.g., Work, Personal, Blogs" />
          <mat-error *ngIf="form.get('title')?.hasError('required')"> Title is required </mat-error>
        </mat-form-field>

        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Color (Optional)</mat-label>
          <input matInput formControlName="color" type="color" placeholder="#667eea" />
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button (click)="onCancel()">Cancel</button>
      <button
        mat-raised-button
        color="primary"
        (click)="onSubmit()"
        [disabled]="!form.valid || loading"
      >
        {{ loading ? 'Creating...' : 'Create Category' }}
      </button>
    </mat-dialog-actions>
  `,
  styles: [
    `
      .full-width {
        width: 100%;
        margin-bottom: 16px;
      }

      mat-dialog-content {
        min-width: 300px;
      }

      mat-dialog-actions {
        padding: 16px 0 0 0;
        gap: 8px;
      }
    `,
  ],
})
export class AddCategoryDialogComponent {
  private readonly apiService = inject(ApiService);
  private readonly fb = inject(FormBuilder);
  private readonly dialogRef = inject(MatDialogRef<AddCategoryDialogComponent>);

  form: FormGroup;
  loading = false;

  constructor(@Inject(MAT_DIALOG_DATA) public data: AddCategoryDialogData) {
    this.form = this.fb.group({
      title: ['', [Validators.required, Validators.minLength(1)]],
      color: ['#667eea'],
    });
  }

  onSubmit(): void {
    if (!this.form.valid) {
      return;
    }

    this.loading = true;
    const categoryData = {
      dashboard_id: this.data.dashboardId,
      title: this.form.get('title')?.value,
      color: this.form.get('color')?.value || undefined,
      sort_order: 0,
    };

    this.apiService.createCategory(categoryData).subscribe({
      next: () => {
        this.loading = false;
        this.dialogRef.close(true);
      },
      error: (error) => {
        console.error('Error creating category:', error);
        this.loading = false;
      },
    });
  }

  onCancel(): void {
    this.dialogRef.close(false);
  }
}
