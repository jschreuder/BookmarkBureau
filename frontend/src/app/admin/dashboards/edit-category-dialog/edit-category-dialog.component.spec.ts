import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ReactiveFormsModule } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { vi } from 'vitest';
import { EditCategoryDialogComponent } from './edit-category-dialog.component';
import { ApiService } from '../../../core/services/api.service';
import { of, throwError } from 'rxjs';
import { Category } from '../../../core/models';

describe('EditCategoryDialogComponent', () => {
  let component: EditCategoryDialogComponent;
  let fixture: ComponentFixture<EditCategoryDialogComponent>;
  let apiService: any;
  let dialogRef: any;
  let snackBar: any;
  const mockCategory: Category = {
    category_id: '1',
    dashboard_id: 'dashboard-1',
    title: 'Work',
    color: '#667eea',
    sort_order: 0,
    created_at: '2025-01-01T00:00:00Z',
    updated_at: '2025-01-01T00:00:00Z',
  };

  beforeEach(async () => {
    apiService = {
      updateCategory: vi.fn().mockReturnValue(of(mockCategory)),
    };
    dialogRef = {
      close: vi.fn(),
    };
    snackBar = {
      open: vi.fn().mockReturnValue({ onAction: () => of(void 0) } as any),
    };

    await TestBed.configureTestingModule({
      imports: [
        EditCategoryDialogComponent,
        ReactiveFormsModule,
        MatFormFieldModule,
        MatInputModule,
        MatButtonModule,
        MatIconModule,
        MatSnackBarModule,
      ],
      providers: [
        { provide: ApiService, useValue: apiService },
        { provide: MatDialogRef, useValue: dialogRef },
        { provide: MAT_DIALOG_DATA, useValue: { category: mockCategory } },
        { provide: MatSnackBar, useValue: snackBar },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(EditCategoryDialogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  describe('initialization', () => {
    it('should create', () => {
      expect(component).toBeTruthy();
    });

    it('should initialize form with category data', () => {
      expect(component.form.get('title')?.value).toBe(mockCategory.title);
      expect(component.form.get('color')?.value).toBe(mockCategory.color);
    });

    it('should have valid form with category data', () => {
      expect(component.form.valid).toBe(true);
    });
  });

  describe('form validation', () => {
    it('should invalidate form when title is empty', () => {
      component.form.get('title')?.setValue('');
      expect(component.form.get('title')?.hasError('required')).toBe(true);
      expect(component.form.valid).toBe(false);
    });

    it('should allow empty color', () => {
      component.form.get('color')?.setValue('');
      expect(component.form.valid).toBe(true);
    });

    it('should allow color value', () => {
      component.form.get('color')?.setValue('#ff0000');
      expect(component.form.valid).toBe(true);
    });
  });

  describe('onSubmit', () => {
    it('should update category with form data', () => {
      component.form.patchValue({
        title: 'Updated Category',
        color: '#ff0000',
      });

      component.onSubmit();

      expect(apiService.updateCategory).toHaveBeenCalledWith(mockCategory.category_id, {
        title: 'Updated Category',
        color: '#ff0000',
        dashboard_id: mockCategory.dashboard_id,
      });
    });

    it('should handle undefined color gracefully', () => {
      component.form.patchValue({
        color: '',
      });

      component.onSubmit();

      const calls = apiService.updateCategory.mock.calls;
      expect(calls[0][1].color).toBeUndefined();
    });

    it('should close dialog with true on success', () => {
      component.onSubmit();

      expect(dialogRef.close).toHaveBeenCalledWith(true);
    });

    it('should set loading to false after submission', () => {
      expect(component.loading).toBe(false);
      component.onSubmit();
      expect(component.loading).toBe(false);
    });

    it('should handle API errors', async () => {
      const error = new Error('Update failed');
      apiService.updateCategory.mockReturnValue(throwError(() => error));

      component.onSubmit();
      fixture.detectChanges();
      await fixture.whenStable();

      expect(component.loading).toBe(false);
    });

    it('should not submit if form is invalid', () => {
      component.form.get('title')?.setValue('');

      component.onSubmit();

      expect(apiService.updateCategory).not.toHaveBeenCalled();
    });

    it('should mark all fields as touched when form is invalid', () => {
      component.form.get('title')?.setValue('');

      component.onSubmit();

      expect(component.form.get('title')?.touched).toBe(true);
    });
  });

  describe('onCancel', () => {
    it('should close dialog with false', () => {
      component.onCancel();

      expect(dialogRef.close).toHaveBeenCalledWith(false);
    });
  });

  describe('clearColor', () => {
    it('should clear color value', () => {
      component.form.patchValue({ color: '#ff0000' });
      expect(component.form.get('color')?.value).toBe('#ff0000');

      component.clearColor();

      expect(component.form.get('color')?.value).toBe('');
    });
  });
});
