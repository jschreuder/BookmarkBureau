import { Component, Inject, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatDialogModule, MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatSelectModule } from '@angular/material/select';
import { MatIconModule } from '@angular/material/icon';
import { MatAutocompleteModule } from '@angular/material/autocomplete';
import { ApiService } from '../../../core/services/api.service';
import { Link } from '../../../core/models';
import { Observable } from 'rxjs';
import { startWith, map, debounceTime } from 'rxjs/operators';

export interface AddLinkDialogData {
  dashboardId: string;
  categoryId?: string;
  isFavorite: boolean;
}

@Component({
  selector: 'app-add-link-dialog',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatSelectModule,
    MatIconModule,
    MatAutocompleteModule
  ],
  template: `
    <h2 mat-dialog-title>
      {{ data.isFavorite ? 'Add Link to Favorites' : 'Add Link to Category' }}
    </h2>
    <mat-dialog-content>
      <form [formGroup]="form">
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>URL</mat-label>
          <input matInput formControlName="url" placeholder="https://example.com">
          <mat-error *ngIf="form.get('url')?.hasError('required')">
            URL is required
          </mat-error>
          <mat-error *ngIf="form.get('url')?.hasError('pattern')">
            Please enter a valid URL
          </mat-error>
        </mat-form-field>

        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Title</mat-label>
          <input matInput formControlName="title" placeholder="Link title">
          <mat-error *ngIf="form.get('title')?.hasError('required')">
            Title is required
          </mat-error>
        </mat-form-field>

        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Description (Optional)</mat-label>
          <textarea matInput formControlName="description" placeholder="Link description" rows="3"></textarea>
        </mat-form-field>

        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Icon (Optional)</mat-label>
          <input matInput formControlName="icon" placeholder="e.g., link, language, book">
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
        {{ loading ? 'Adding...' : 'Add Link' }}
      </button>
    </mat-dialog-actions>
  `,
  styles: [`
    .full-width {
      width: 100%;
      margin-bottom: 16px;
    }

    mat-dialog-content {
      min-width: 400px;
    }

    mat-dialog-actions {
      padding: 16px 0 0 0;
      gap: 8px;
    }
  `]
})
export class AddLinkDialogComponent {
  private readonly apiService = inject(ApiService);
  private readonly fb = inject(FormBuilder);
  private readonly dialogRef = inject(MatDialogRef<AddLinkDialogComponent>);

  form: FormGroup;
  loading = false;

  constructor(@Inject(MAT_DIALOG_DATA) public data: AddLinkDialogData) {
    this.form = this.fb.group({
      url: ['', [Validators.required, Validators.pattern(/^https?:\/\/.+/)]],
      title: ['', [Validators.required, Validators.minLength(1)]],
      description: [''],
      icon: ['']
    });
  }

  onSubmit(): void {
    if (!this.form.valid) {
      return;
    }

    this.loading = true;
    const linkData: Partial<Link> = {
      url: this.form.get('url')?.value,
      title: this.form.get('title')?.value,
      description: this.form.get('description')?.value || '',
      icon: this.form.get('icon')?.value || undefined
    };

    // First, create the link
    this.apiService.createLink(linkData).subscribe({
      next: (link) => {
        if (this.data.isFavorite) {
          // Add to favorites
          this.apiService.addFavorite(this.data.dashboardId, link.id).subscribe({
            next: () => {
              this.loading = false;
              this.dialogRef.close(true);
            },
            error: (error) => {
              console.error('Error adding favorite:', error);
              this.loading = false;
            }
          });
        } else if (this.data.categoryId) {
          // Add to category - this would need a backend endpoint
          // For now, we'll just close and let the parent component reload
          this.loading = false;
          this.dialogRef.close(true);
        }
      },
      error: (error) => {
        console.error('Error creating link:', error);
        this.loading = false;
      }
    });
  }

  onCancel(): void {
    this.dialogRef.close(false);
  }
}
