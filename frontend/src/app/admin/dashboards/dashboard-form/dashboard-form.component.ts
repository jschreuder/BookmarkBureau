import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { Dashboard } from '../../../core/models';
import { ApiService } from '../../../core/services/api.service';
import { IconPickerComponent } from '../../../shared/components/icon-picker/icon-picker.component';

@Component({
  selector: 'app-dashboard-form',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatCardModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatIconModule,
    MatSnackBarModule,
    MatProgressSpinnerModule,
    IconPickerComponent,
  ],
  template: `
    <div class="form-header">
      <h1>{{ isEditMode ? 'Edit Dashboard' : 'Create Dashboard' }}</h1>
    </div>

    <mat-card>
      <mat-card-content>
        <form [formGroup]="form" (ngSubmit)="onSubmit()">
          <!-- Title Field -->
          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Title</mat-label>
            <input matInput formControlName="title" required />
            <mat-error *ngIf="form.get('title')?.hasError('required')">
              Title is required
            </mat-error>
            <mat-error *ngIf="form.get('title')?.hasError('minlength')">
              Title must be at least 1 character
            </mat-error>
            <mat-error *ngIf="form.get('title')?.hasError('maxlength')">
              Title must not exceed 256 characters
            </mat-error>
          </mat-form-field>

          <!-- Description Field -->
          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Description</mat-label>
            <textarea matInput formControlName="description" rows="4" required></textarea>
            <mat-error *ngIf="form.get('description')?.hasError('required')">
              Description is required
            </mat-error>
          </mat-form-field>

          <!-- Icon Field -->
          <app-icon-picker formControlName="icon"></app-icon-picker>

          <!-- Form Actions -->
          <div class="form-actions">
            <button mat-raised-button type="button" (click)="onCancel()">Cancel</button>
            <button
              mat-raised-button
              color="primary"
              type="submit"
              [disabled]="form.invalid || loading"
            >
              <mat-icon *ngIf="!loading">save</mat-icon>
              <mat-spinner *ngIf="loading" diameter="20" style="margin-right: 8px;"></mat-spinner>
              {{ isEditMode ? 'Update' : 'Create' }}
            </button>
          </div>
        </form>
      </mat-card-content>
    </mat-card>
  `,
  styles: [
    `
      .form-header {
        margin-bottom: 24px;
      }

      .form-header h1 {
        margin: 0;
      }

      form {
        display: flex;
        flex-direction: column;
        gap: 16px;
      }

      .full-width {
        width: 100%;
      }

      .form-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 24px;
      }

      button {
        min-width: 100px;
      }

      mat-form-field {
        display: block;
      }

      mat-error {
        font-size: 12px;
      }
    `,
  ],
})
export class DashboardFormComponent implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly apiService = inject(ApiService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly snackBar = inject(MatSnackBar);

  form: FormGroup;
  loading = false;
  isEditMode = false;
  dashboardId: string | null = null;

  constructor() {
    this.form = this.fb.group({
      title: ['', [Validators.required, Validators.minLength(1), Validators.maxLength(256)]],
      description: ['', [Validators.required]],
      icon: [''],
    });
  }

  ngOnInit() {
    this.route.paramMap.subscribe((params) => {
      const id = params.get('id');
      if (id && id !== 'new') {
        this.isEditMode = true;
        this.dashboardId = id;
        this.loadDashboard(id);
      }
    });
  }

  loadDashboard(id: string) {
    this.loading = true;
    this.apiService.getDashboardBasic(id).subscribe({
      next: (dashboard) => {
        this.form.patchValue({
          title: dashboard.title,
          description: dashboard.description,
          icon: dashboard.icon || '',
        });
        this.loading = false;
      },
      error: (_error) => {
        this.snackBar.open('Failed to load dashboard', 'Close', { duration: 5000 });
        this.loading = false;
        this.router.navigate(['/admin/dashboards']);
      },
    });
  }

  onSubmit() {
    if (this.form.invalid) {
      return;
    }

    this.loading = true;
    const data: Partial<Dashboard> = {
      title: this.form.get('title')?.value,
      description: this.form.get('description')?.value,
      icon: this.form.get('icon')?.value || undefined,
    };

    if (this.isEditMode && this.dashboardId) {
      this.apiService.updateDashboard(this.dashboardId, data).subscribe({
        next: () => {
          this.snackBar.open('Dashboard updated successfully', 'Close', { duration: 3000 });
          this.router.navigate(['/admin/dashboards']);
        },
        error: (_error) => {
          this.snackBar.open('Failed to update dashboard', 'Close', { duration: 5000 });
          this.loading = false;
        },
      });
    } else {
      this.apiService.createDashboard(data).subscribe({
        next: () => {
          this.snackBar.open('Dashboard created successfully', 'Close', { duration: 3000 });
          this.router.navigate(['/admin/dashboards']);
        },
        error: (_error) => {
          this.snackBar.open('Failed to create dashboard', 'Close', { duration: 5000 });
          this.loading = false;
        },
      });
    }
  }

  onCancel() {
    this.router.navigate(['/admin/dashboards']);
  }
}
