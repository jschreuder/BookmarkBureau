import { Component, Inject, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatDialogModule, MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { Observable, forkJoin, of } from 'rxjs';
import { switchMap } from 'rxjs/operators';
import { ApiService } from '../../../core/services/api.service';
import { Link, Tag } from '../../../core/models';
import { IconPickerComponent } from '../../../shared/components/icon-picker/icon-picker.component';
import { TagInputComponent } from '../../../shared/components/tag-input/tag-input.component';

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
    MatSnackBarModule,
    IconPickerComponent,
    TagInputComponent,
  ],
  template: `
    <h2 mat-dialog-title>
      {{ data.isFavorite ? 'Add Link to Favorites' : 'Add Link to Category' }}
    </h2>
    <mat-dialog-content>
      <form [formGroup]="form" class="dialog-form">
        <mat-form-field appearance="outline">
          <mat-label>URL</mat-label>
          <input matInput formControlName="url" placeholder="https://example.com" required />
          @if (form.get('url')?.hasError('required') && form.get('url')?.touched) {
            <mat-error>URL is required</mat-error>
          }
          @if (form.get('url')?.hasError('pattern') && form.get('url')?.touched) {
            <mat-error>Please enter a valid URL</mat-error>
          }
        </mat-form-field>

        <mat-form-field appearance="outline">
          <mat-label>Title</mat-label>
          <input matInput formControlName="title" placeholder="Link title" required />
          @if (form.get('title')?.hasError('required') && form.get('title')?.touched) {
            <mat-error>Title is required</mat-error>
          }
        </mat-form-field>

        <mat-form-field appearance="outline">
          <mat-label>Description (Optional)</mat-label>
          <textarea
            matInput
            formControlName="description"
            placeholder="Link description"
            rows="3"
          ></textarea>
        </mat-form-field>

        <app-icon-picker formControlName="icon"></app-icon-picker>

        <app-tag-input formControlName="tags"></app-tag-input>
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
        {{ loading ? 'Adding...' : 'Add Link' }}
      </button>
    </mat-dialog-actions>
  `,
  styles: [
    `
      mat-dialog-content {
        min-width: 500px;
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
export class AddLinkDialogComponent {
  private readonly apiService = inject(ApiService);
  private readonly fb = inject(FormBuilder);
  private readonly dialogRef = inject(MatDialogRef<AddLinkDialogComponent>);
  private readonly snackBar = inject(MatSnackBar);

  form: FormGroup;
  loading = false;

  constructor(@Inject(MAT_DIALOG_DATA) public data: AddLinkDialogData) {
    this.form = this.fb.group({
      url: ['', [Validators.required, Validators.pattern(/^https?:\/\/.+/)]],
      title: ['', [Validators.required, Validators.minLength(1)]],
      description: [''],
      icon: [''],
      tags: [[]],
    });
  }

  onSubmit(): void {
    if (!this.form.valid) {
      this.form.markAllAsTouched();
      return;
    }

    this.loading = true;
    const linkData: Partial<Link> = {
      url: this.form.get('url')?.value,
      title: this.form.get('title')?.value,
      description: this.form.get('description')?.value || '',
      icon: this.form.get('icon')?.value || undefined,
    };

    let linkOperation: Observable<Link>;

    if (this.data.isFavorite) {
      // Create link and add to favorites in one operation
      linkOperation = this.apiService.createLinkAsFavorite(this.data.dashboardId, linkData);
    } else if (this.data.categoryId) {
      // Create link and add to category in one operation
      linkOperation = this.apiService.createLinkInCategory(this.data.categoryId, linkData);
    } else {
      // Just create the link
      linkOperation = this.apiService.createLink(linkData);
    }

    linkOperation
      .pipe(
        switchMap((link) => {
          const tags: Tag[] = this.form.get('tags')?.value || [];
          if (tags.length === 0) {
            return of(link);
          }

          // Assign all tags to the newly created link
          const tagAssignments = tags.map((tag) =>
            this.apiService.assignTagToLink(link.link_id, tag),
          );
          return forkJoin(tagAssignments).pipe(switchMap(() => of(link)));
        }),
      )
      .subscribe({
        next: () => {
          this.loading = false;
          this.dialogRef.close(true);
        },
        error: (error: unknown) => {
          this.loading = false;
          this.snackBar.open('Failed to add link', 'Close', { duration: 5000 });
        },
      });
  }

  onCancel(): void {
    this.dialogRef.close(false);
  }
}
