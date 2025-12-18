import {
  Component,
  OnInit,
  inject,
  ChangeDetectionStrategy,
  ChangeDetectorRef,
} from '@angular/core';
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
  changeDetection: ChangeDetectionStrategy.OnPush,
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
  templateUrl: './dashboard-form.component.html',
  styleUrl: './dashboard-form.component.scss',
})
export class DashboardFormComponent implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly apiService = inject(ApiService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly snackBar = inject(MatSnackBar);
  private readonly cdr = inject(ChangeDetectorRef);

  form: FormGroup;
  loading = false;
  isEditMode = false;
  dashboardId: string | null = null;

  constructor() {
    this.form = this.fb.group({
      title: ['', [Validators.required, Validators.minLength(1), Validators.maxLength(256)]],
      description: [''],
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
      this.cdr.markForCheck();
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
        this.cdr.markForCheck();
      },
      error: () => {
        this.snackBar.open('Failed to load dashboard', 'Close', { duration: 5000 });
        this.loading = false;
        this.cdr.markForCheck();
        this.router.navigate(['/admin/dashboards']);
      },
    });
  }

  onSubmit() {
    if (this.form.invalid) {
      return;
    }

    this.loading = true;
    const descriptionValue = this.form.get('description')?.value;
    const data: Partial<Dashboard> = {
      title: this.form.get('title')?.value,
      description: descriptionValue ? descriptionValue : undefined,
      icon: this.form.get('icon')?.value || undefined,
    };

    if (this.isEditMode && this.dashboardId) {
      this.apiService.updateDashboard(this.dashboardId, data).subscribe({
        next: () => {
          this.snackBar.open('Dashboard updated successfully', 'Close', { duration: 3000 });
          this.router.navigate(['/admin/dashboards']);
        },
        error: () => {
          this.snackBar.open('Failed to update dashboard', 'Close', { duration: 5000 });
          this.loading = false;
          this.cdr.markForCheck();
        },
      });
    } else {
      this.apiService.createDashboard(data).subscribe({
        next: () => {
          this.snackBar.open('Dashboard created successfully', 'Close', { duration: 3000 });
          this.router.navigate(['/admin/dashboards']);
        },
        error: () => {
          this.snackBar.open('Failed to create dashboard', 'Close', { duration: 5000 });
          this.loading = false;
          this.cdr.markForCheck();
        },
      });
    }
  }

  onCancel() {
    this.router.navigate(['/admin/dashboards']);
  }
}
