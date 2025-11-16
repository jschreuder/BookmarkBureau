import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { of, throwError } from 'rxjs';
import { vi } from 'vitest';
import { AddLinkDialogComponent } from './add-link-dialog.component';
import { ApiService } from '../../../core/services/api.service';
import { Link } from '../../../core/models';

describe('AddLinkDialogComponent', () => {
  let component: AddLinkDialogComponent;
  let fixture: ComponentFixture<AddLinkDialogComponent>;
  let apiService: any;
  let dialogRef: any;
  let snackBar: any;

  const mockLink: Link = {
    id: 'link-id',
    url: 'https://example.com',
    title: 'Example Link',
    description: 'Test description',
    icon: 'link',
    created_at: '2025-01-01T00:00:00Z',
    updated_at: '2025-01-01T00:00:00Z',
  };

  beforeEach(async () => {
    apiService = {
      createLink: vi.fn().mockReturnValue(of(mockLink)),
      addFavorite: vi.fn().mockReturnValue(of({})),
      addLinkToCategory: vi.fn().mockReturnValue(of({})),
      createLinkAsFavorite: vi.fn().mockReturnValue(of(mockLink)),
      createLinkInCategory: vi.fn().mockReturnValue(of(mockLink)),
    };

    dialogRef = {
      close: vi.fn(),
    };

    snackBar = {
      open: vi.fn().mockReturnValue({} as any),
    };
  });

  describe('with favorite data', () => {
    const mockDialogData = {
      dashboardId: 'test-dashboard-id',
      isFavorite: true,
    };

    beforeEach(async () => {
      await TestBed.configureTestingModule({
        imports: [AddLinkDialogComponent, MatSnackBarModule, BrowserAnimationsModule],
        providers: [
          { provide: ApiService, useValue: apiService },
          { provide: MatDialogRef, useValue: dialogRef },
          { provide: MAT_DIALOG_DATA, useValue: mockDialogData },
          { provide: MatSnackBar, useValue: snackBar },
        ],
      }).compileComponents();

      fixture = TestBed.createComponent(AddLinkDialogComponent);
      component = fixture.componentInstance;
      fixture.detectChanges();
    });

    it('should create', () => {
      expect(component).toBeTruthy();
    });

    it('should initialize form with empty values', () => {
      expect(component.form.get('url')?.value).toBe('');
      expect(component.form.get('title')?.value).toBe('');
      expect(component.form.get('description')?.value).toBe('');
      expect(component.form.get('icon')?.value).toBe('');
    });

    it('should have form invalid when url is empty', () => {
      component.form.patchValue({ url: '', title: 'Test' });
      expect(component.form.valid).toBe(false);
    });

    it('should have form invalid when title is empty', () => {
      component.form.patchValue({ url: 'https://example.com', title: '' });
      expect(component.form.valid).toBe(false);
    });

    it('should have form invalid when url pattern is incorrect', () => {
      component.form.patchValue({ url: 'not-a-url', title: 'Test' });
      expect(component.form.valid).toBe(false);
    });

    it('should have form valid when required fields are provided', () => {
      component.form.patchValue({
        url: 'https://example.com',
        title: 'Test Link',
      });
      expect(component.form.valid).toBe(true);
    });

    it('should render dialog title for favorites', () => {
      const compiled = fixture.nativeElement;
      const title = compiled.querySelector('h2[mat-dialog-title]');
      expect(title.textContent).toContain('Add Link to Favorites');
    });

    it('should render all form fields', () => {
      const compiled = fixture.nativeElement;
      const formFields = compiled.querySelectorAll('mat-form-field');
      expect(formFields.length).toBe(4);
    });

    it('should render url input field', () => {
      const compiled = fixture.nativeElement;
      const urlInput = compiled.querySelector('input[formControlName="url"]');
      expect(urlInput).toBeTruthy();
    });

    it('should render title input field', () => {
      const compiled = fixture.nativeElement;
      const titleInput = compiled.querySelector('input[formControlName="title"]');
      expect(titleInput).toBeTruthy();
    });

    it('should render description textarea field', () => {
      const compiled = fixture.nativeElement;
      const descriptionTextarea = compiled.querySelector('textarea[formControlName="description"]');
      expect(descriptionTextarea).toBeTruthy();
    });

    it('should render icon input field', () => {
      const compiled = fixture.nativeElement;
      const iconInput = compiled.querySelector('input[formControlName="icon"]');
      expect(iconInput).toBeTruthy();
    });

    it('should render cancel and submit buttons', () => {
      const compiled = fixture.nativeElement;
      const buttons = compiled.querySelectorAll('button');
      expect(buttons.length).toBeGreaterThanOrEqual(2);
      expect(buttons[0].textContent).toContain('Cancel');
      expect(buttons[1].textContent).toContain('Add Link');
    });

    it('should disable submit button when form is invalid', () => {
      component.form.patchValue({ url: '', title: '' });
      fixture.detectChanges();

      const compiled = fixture.nativeElement;
      const submitButton = compiled.querySelectorAll('button')[1];
      expect(submitButton.disabled).toBe(true);
    });

    it('should enable submit button when form is valid', () => {
      component.form.patchValue({
        url: 'https://example.com',
        title: 'Test Link',
      });
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
      component.form.patchValue({ url: '', title: '' });
      component.onSubmit();
      expect(apiService.createLink).not.toHaveBeenCalled();
    });

    it('should create link and add to favorites when submitted', () => {
      component.form.patchValue({
        url: 'https://example.com',
        title: 'Test Link',
        description: 'Test desc',
        icon: 'link',
      });

      component.onSubmit();

      expect(apiService.createLinkAsFavorite).toHaveBeenCalledWith('test-dashboard-id', {
        url: 'https://example.com',
        title: 'Test Link',
        description: 'Test desc',
        icon: 'link',
      });
    });

    it('should close dialog with true on successful submission', async () => {
      component.form.patchValue({
        url: 'https://example.com',
        title: 'Test Link',
      });

      component.onSubmit();

      await new Promise((resolve) => setTimeout(resolve, 100));
      expect(dialogRef.close).toHaveBeenCalledWith(true);
    });

    it('should handle API error gracefully when adding to favorites', async () => {
      const error = { error: 'Test error' };
      apiService.createLinkAsFavorite = vi.fn().mockReturnValue(throwError(() => error));

      component.form.patchValue({
        url: 'https://example.com',
        title: 'Test Link',
      });

      component.onSubmit();
      fixture.detectChanges();
      await fixture.whenStable();

      expect(component.loading).toBe(false);
      expect(dialogRef.close).not.toHaveBeenCalled();
    });

    it('should mark form as touched when submitting with invalid data', () => {
      component.form.patchValue({ url: '', title: '' });
      component.onSubmit();

      expect(component.form.get('url')?.touched).toBe(true);
      expect(component.form.get('title')?.touched).toBe(true);
    });

    it('should display error message when url is required and touched', () => {
      const urlControl = component.form.get('url');
      urlControl?.markAsTouched();
      urlControl?.setValue('');
      fixture.detectChanges();

      const compiled = fixture.nativeElement;
      const error = compiled.querySelector('mat-error');
      expect(error?.textContent).toContain('URL is required');
    });

    it('should display error message when url pattern is invalid and touched', () => {
      const urlControl = component.form.get('url');
      urlControl?.markAsTouched();
      urlControl?.setValue('not-a-url');
      fixture.detectChanges();

      const compiled = fixture.nativeElement;
      const error = compiled.querySelector('mat-error');
      expect(error?.textContent).toContain('Please enter a valid URL');
    });
  });

  describe('with category data', () => {
    const mockDialogData = {
      dashboardId: 'test-dashboard-id',
      categoryId: 'test-category-id',
      isFavorite: false,
    };

    beforeEach(async () => {
      await TestBed.configureTestingModule({
        imports: [AddLinkDialogComponent, MatSnackBarModule, BrowserAnimationsModule],
        providers: [
          { provide: ApiService, useValue: apiService },
          { provide: MatDialogRef, useValue: dialogRef },
          { provide: MAT_DIALOG_DATA, useValue: mockDialogData },
          { provide: MatSnackBar, useValue: snackBar },
        ],
      }).compileComponents();

      fixture = TestBed.createComponent(AddLinkDialogComponent);
      component = fixture.componentInstance;
      fixture.detectChanges();
    });

    it('should render dialog title for category', () => {
      const compiled = fixture.nativeElement;
      const title = compiled.querySelector('h2[mat-dialog-title]');
      expect(title.textContent).toContain('Add Link to Category');
    });

    it('should create link and add to category when submitted', () => {
      component.form.patchValue({
        url: 'https://example.com',
        title: 'Test Link',
      });

      component.onSubmit();

      expect(apiService.createLinkInCategory).toHaveBeenCalledWith('test-category-id', {
        url: 'https://example.com',
        title: 'Test Link',
        description: '',
        icon: undefined,
      });
      expect(apiService.addFavorite).not.toHaveBeenCalled();
    });

    it('should handle API error when adding to category', async () => {
      const error = { error: 'Test error' };
      apiService.createLinkInCategory = vi.fn().mockReturnValue(throwError(() => error));

      component.form.patchValue({
        url: 'https://example.com',
        title: 'Test Link',
      });

      component.onSubmit();
      fixture.detectChanges();
      await fixture.whenStable();

      expect(component.loading).toBe(false);
    });
  });

  describe('without category or favorite', () => {
    const mockDialogData = {
      dashboardId: 'test-dashboard-id',
      isFavorite: false,
    };

    beforeEach(async () => {
      await TestBed.configureTestingModule({
        imports: [AddLinkDialogComponent, BrowserAnimationsModule],
        providers: [
          { provide: ApiService, useValue: apiService },
          { provide: MatDialogRef, useValue: dialogRef },
          { provide: MAT_DIALOG_DATA, useValue: mockDialogData },
        ],
      }).compileComponents();

      fixture = TestBed.createComponent(AddLinkDialogComponent);
      component = fixture.componentInstance;
      fixture.detectChanges();
    });

    it('should just create link when submitted without category or favorite', () => {
      component.form.patchValue({
        url: 'https://example.com',
        title: 'Test Link',
      });

      component.onSubmit();

      expect(apiService.createLink).toHaveBeenCalledWith({
        url: 'https://example.com',
        title: 'Test Link',
        description: '',
        icon: undefined,
      });
      expect(apiService.addFavorite).not.toHaveBeenCalled();
      expect(apiService.addLinkToCategory).not.toHaveBeenCalled();
      expect(apiService.createLinkAsFavorite).not.toHaveBeenCalled();
      expect(apiService.createLinkInCategory).not.toHaveBeenCalled();
      expect(dialogRef.close).toHaveBeenCalledWith(true);
    });
  });
});
