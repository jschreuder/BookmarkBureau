import {
  Component,
  Inject,
  inject,
  ChangeDetectionStrategy,
  ChangeDetectorRef,
} from '@angular/core';
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
  changeDetection: ChangeDetectionStrategy.OnPush,
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
  templateUrl: './edit-link-dialog.component.html',
  styleUrl: './edit-link-dialog.component.scss',
})
export class EditLinkDialogComponent {
  private readonly apiService = inject(ApiService);
  private readonly fb = inject(FormBuilder);
  private readonly dialogRef = inject(MatDialogRef<EditLinkDialogComponent>);
  private readonly snackBar = inject(MatSnackBar);
  private readonly cdr = inject(ChangeDetectorRef);

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
          this.cdr.markForCheck();
          this.dialogRef.close(true);
        },
        error: () => {
          this.loading = false;
          this.cdr.markForCheck();
          this.snackBar.open('Failed to update link', 'Close', { duration: 5000 });
        },
      });
  }

  onCancel(): void {
    this.dialogRef.close(false);
  }
}
