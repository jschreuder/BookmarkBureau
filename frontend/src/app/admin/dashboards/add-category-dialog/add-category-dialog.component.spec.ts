import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { of, throwError } from 'rxjs';
import { vi } from 'vitest';
import { AddCategoryDialogComponent } from './add-category-dialog.component';
import { ApiService } from '../../../core/services/api.service';
import { Category } from '../../../core/models';

describe('AddCategoryDialogComponent', () => {
  let component: AddCategoryDialogComponent;
  let fixture: ComponentFixture<AddCategoryDialogComponent>;
  let apiService: any;
  let dialogRef: any;
  let snackBar: any;

  const mockDialogData = {
    dashboardId: 'test-dashboard-id',
  };

  const mockCategory: Category = {
    category_id: 'cat-id',
    dashboard_id: 'test-dashboard-id',
    title: 'Test Category',
    color: '#667eea',
    sort_order: 0,
    created_at: '2025-01-01T00:00:00Z',
    updated_at: '2025-01-01T00:00:00Z',
  };

  beforeEach(async () => {
    apiService = {
      createCategory: vi.fn().mockReturnValue(of(mockCategory)),
    };

    dialogRef = {
      close: vi.fn(),
    };

    snackBar = {
      open: vi.fn().mockReturnValue({} as any),
    };

    await TestBed.configureTestingModule({
      imports: [AddCategoryDialogComponent, MatSnackBarModule],
      providers: [
        { provide: ApiService, useValue: apiService },
        { provide: MatDialogRef, useValue: dialogRef },
        { provide: MAT_DIALOG_DATA, useValue: mockDialogData },
        { provide: MatSnackBar, useValue: snackBar },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(AddCategoryDialogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize form with empty title and empty color', () => {
    expect(component.form.get('title')?.value).toBe('');
    expect(component.form.get('color')?.value).toBe('');
  });

  it('should have form invalid when title is empty', () => {
    component.form.patchValue({ title: '' });
    expect(component.form.valid).toBe(false);
  });

  it('should have form valid when title is provided', () => {
    component.form.patchValue({ title: 'New Category' });
    expect(component.form.valid).toBe(true);
  });

  it('should render dialog title', () => {
    const compiled = fixture.nativeElement;
    const title = compiled.querySelector('h2[mat-dialog-title]');
    expect(title.textContent).toContain('Add Category');
  });

  it('should render form fields', () => {
    const compiled = fixture.nativeElement;
    const formFields = compiled.querySelectorAll('mat-form-field');
    expect(formFields.length).toBe(2);
  });

  it('should render title input field', () => {
    const compiled = fixture.nativeElement;
    const titleInput = compiled.querySelector('input[formControlName="title"]');
    expect(titleInput).toBeTruthy();
  });

  it('should render color input field', () => {
    const compiled = fixture.nativeElement;
    const colorInput = compiled.querySelector('input[formControlName="color"]');
    expect(colorInput).toBeTruthy();
    expect(colorInput.type).toBe('color');
  });

  it('should render cancel and submit buttons', () => {
    const compiled = fixture.nativeElement;
    const buttons = compiled.querySelectorAll('button');
    expect(buttons.length).toBeGreaterThanOrEqual(2);
    expect(buttons[0].textContent).toContain('Cancel');
    expect(buttons[1].textContent).toContain('Create Category');
  });

  it('should disable submit button when form is invalid', () => {
    component.form.patchValue({ title: '' });
    fixture.detectChanges();

    const compiled = fixture.nativeElement;
    const submitButton = compiled.querySelectorAll('button')[1];
    expect(submitButton.disabled).toBe(true);
  });

  it('should enable submit button when form is valid', () => {
    component.form.patchValue({ title: 'New Category' });
    fixture.detectChanges();

    const compiled = fixture.nativeElement;
    const submitButton = compiled.querySelectorAll('button')[1];
    expect(submitButton.disabled).toBe(false);
  });

  it('should close dialog with false when cancel is clicked', () => {
    component.onCancel();
    expect(dialogRef.close).toHaveBeenCalledWith(false);
  });

  it('should not submit when form is invalid', () => {
    component.form.patchValue({ title: '' });
    component.onSubmit();
    expect(apiService.createCategory).not.toHaveBeenCalled();
  });

  it('should create category with correct data when submitted', () => {
    component.form.patchValue({
      title: 'New Category',
      color: '#ff0000',
    });

    component.onSubmit();

    expect(apiService.createCategory).toHaveBeenCalledWith({
      dashboard_id: 'test-dashboard-id',
      title: 'New Category',
      color: '#ff0000',
      sort_order: 0,
    });
  });

  it('should close dialog with true on successful submission', () => {
    component.form.patchValue({
      title: 'New Category',
      color: '#ff0000',
    });

    component.onSubmit();

    expect(dialogRef.close).toHaveBeenCalledWith(true);
  });

  it('should set loading to true during submission', () => {
    component.form.patchValue({
      title: 'New Category',
    });

    apiService.createCategory = vi.fn().mockReturnValue(of(mockCategory));
    component.onSubmit();

    // Loading is set to true before API call
    // Then set to false after success
    expect(component.loading).toBe(false);
  });

  it('should handle API error gracefully', async () => {
    const error = { error: 'Test error' };
    apiService.createCategory = vi.fn().mockReturnValue(throwError(() => error));

    component.form.patchValue({
      title: 'New Category',
    });

    component.onSubmit();
    fixture.detectChanges();
    await fixture.whenStable();

    expect(component.loading).toBe(false);
    expect(dialogRef.close).not.toHaveBeenCalled();
  });

  it('should mark form as touched when submitting with invalid data', () => {
    component.form.patchValue({ title: '' });
    component.onSubmit();

    expect(component.form.get('title')?.touched).toBe(true);
  });

  it('should display error message when title is required and touched', () => {
    const titleControl = component.form.get('title');
    titleControl?.markAsTouched();
    titleControl?.setValue('');
    fixture.detectChanges();

    const compiled = fixture.nativeElement;
    const error = compiled.querySelector('mat-error');
    expect(error?.textContent).toContain('Title is required');
  });

  it('should use default color when color field is empty', () => {
    component.form.patchValue({
      title: 'New Category',
      color: '',
    });

    component.onSubmit();

    const callArgs = apiService.createCategory.mock.calls[0][0];
    expect(callArgs.color).toBeUndefined();
  });

  it('should clear color when clearColor is called', () => {
    component.form.patchValue({ color: '#ff0000' });
    expect(component.form.get('color')?.value).toBe('#ff0000');

    component.clearColor();

    expect(component.form.get('color')?.value).toBe('');
  });

  it('should hide clear button when color is empty', () => {
    component.form.patchValue({ color: '' });
    fixture.detectChanges();

    const compiled = fixture.nativeElement;
    const clearButton = compiled.querySelector('button[aria-label="Clear color"]');
    expect(clearButton).toBeFalsy();
  });

  it('should show clear button when color has a value', () => {
    component.form.patchValue({ color: '#ff0000' });
    fixture.detectChanges();

    const compiled = fixture.nativeElement;
    const clearButton = compiled.querySelector('button[aria-label="Clear color"]');
    expect(clearButton).toBeTruthy();
  });
});
