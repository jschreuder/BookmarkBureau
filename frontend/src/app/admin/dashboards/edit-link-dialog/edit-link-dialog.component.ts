import { Component, Inject, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatDialogModule, MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { forkJoin, of } from 'rxjs';
import { switchMap } from 'rxjs/operators';
import { Link, Tag } from '../../../core/models';
import { ApiService } from '../../../core/services/api.service';
import { IconPickerComponent } from '../../../shared/components/icon-picker/icon-picker.component';
import { TagInputComponent } from '../../../shared/components/tag-input/tag-input.component';

export interface EditLinkDialogData {
  link: Link;
}

@Component({
  selector: 'app-edit-link-dialog',
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
    <h2 mat-dialog-title>Edit Link</h2>
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
        {{ loading ? 'Updating...' : 'Update Link' }}
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
export class EditLinkDialogComponent {
  private readonly apiService = inject(ApiService);
  private readonly fb = inject(FormBuilder);
  private readonly dialogRef = inject(MatDialogRef<EditLinkDialogComponent>);
  private readonly snackBar = inject(MatSnackBar);

  form: FormGroup;
  loading = false;

  constructor(@Inject(MAT_DIALOG_DATA) public data: EditLinkDialogData) {
    this.form = this.fb.group({
      url: [data.link.url, [Validators.required, Validators.pattern(/^https?:\/\/.+/)]],
      title: [data.link.title, [Validators.required, Validators.minLength(1)]],
      description: [data.link.description || ''],
      icon: [data.link.icon || ''],
      tags: [data.link.tags || []],
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

    this.apiService
      .updateLink(this.data.link.link_id, linkData)
      .pipe(
        switchMap(() => {
          // Handle tag changes
          const currentTags: Tag[] = this.data.link.tags || [];
          const newTags: Tag[] = this.form.get('tags')?.value || [];

          // Find tags to add and remove
          const currentTagNames = new Set(currentTags.map((t) => t.tag_name));
          const newTagNames = new Set(newTags.map((t) => t.tag_name));

          const tagsToAdd = newTags.filter((t) => !currentTagNames.has(t.tag_name));
          const tagsToRemove = currentTags.filter((t) => !newTagNames.has(t.tag_name));

          const operations = [
            ...tagsToAdd.map((tag) => this.apiService.assignTagToLink(this.data.link.link_id, tag)),
            ...tagsToRemove.map((tag) =>
              this.apiService.removeTagFromLink(this.data.link.link_id, tag.tag_name),
            ),
          ];

          if (operations.length === 0) {
            return of(null);
          }

          return forkJoin(operations);
        }),
      )
      .subscribe({
        next: () => {
          this.loading = false;
          this.dialogRef.close(true);
        },
        error: (error: unknown) => {
          this.loading = false;
          this.snackBar.open('Failed to update link', 'Close', { duration: 5000 });
        },
      });
  }

  onCancel(): void {
    this.dialogRef.close(false);
  }
}
