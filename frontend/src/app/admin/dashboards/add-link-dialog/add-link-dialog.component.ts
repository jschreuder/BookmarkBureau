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
  templateUrl: './add-link-dialog.component.html',
  styleUrl: './add-link-dialog.component.scss',
})
export class AddLinkDialogComponent {
  private readonly apiService = inject(ApiService);
  private readonly fb = inject(FormBuilder);
  private readonly dialogRef = inject(MatDialogRef<AddLinkDialogComponent>);
  private readonly snackBar = inject(MatSnackBar);
  private readonly cdr = inject(ChangeDetectorRef);

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
          this.cdr.markForCheck();
          this.dialogRef.close(true);
        },
        error: () => {
          this.loading = false;
          this.cdr.markForCheck();
          this.snackBar.open('Failed to add link', 'Close', { duration: 5000 });
        },
      });
  }

  onCancel(): void {
    this.dialogRef.close(false);
  }
}
