import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ReactiveFormsModule } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { vi } from 'vitest';
import { EditLinkDialogComponent } from './edit-link-dialog.component';
import { ApiService } from '../../../core/services/api.service';
import { of, throwError } from 'rxjs';
import { Link } from '../../../core/models';

describe('EditLinkDialogComponent', () => {
  let component: EditLinkDialogComponent;
  let fixture: ComponentFixture<EditLinkDialogComponent>;
  let apiService: any;
  let dialogRef: any;
  const mockLink: Link = {
    id: '1',
    url: 'https://example.com',
    title: 'Example Link',
    description: 'A test link',
    icon: 'link',
    created_at: '2025-01-01T00:00:00Z',
    updated_at: '2025-01-01T00:00:00Z',
  };

  beforeEach(async () => {
    apiService = {
      updateLink: vi.fn().mockReturnValue(of(mockLink)),
    };
    dialogRef = {
      close: vi.fn(),
    };

    await TestBed.configureTestingModule({
      imports: [
        EditLinkDialogComponent,
        ReactiveFormsModule,
        MatFormFieldModule,
        MatInputModule,
        MatButtonModule,
        BrowserAnimationsModule,
      ],
      providers: [
        { provide: ApiService, useValue: apiService },
        { provide: MatDialogRef, useValue: dialogRef },
        { provide: MAT_DIALOG_DATA, useValue: { link: mockLink } },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(EditLinkDialogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  describe('initialization', () => {
    it('should create', () => {
      expect(component).toBeTruthy();
    });

    it('should initialize form with link data', () => {
      expect(component.form.get('url')?.value).toBe(mockLink.url);
      expect(component.form.get('title')?.value).toBe(mockLink.title);
      expect(component.form.get('description')?.value).toBe(mockLink.description);
      expect(component.form.get('icon')?.value).toBe(mockLink.icon);
    });

    it('should have valid form with link data', () => {
      expect(component.form.valid).toBe(true);
    });
  });

  describe('form validation', () => {
    it('should invalidate form when URL is empty', () => {
      component.form.get('url')?.setValue('');
      expect(component.form.get('url')?.hasError('required')).toBe(true);
      expect(component.form.valid).toBe(false);
    });

    it('should invalidate form when URL is invalid', () => {
      component.form.get('url')?.setValue('invalid-url');
      expect(component.form.get('url')?.hasError('pattern')).toBe(true);
      expect(component.form.valid).toBe(false);
    });

    it('should invalidate form when title is empty', () => {
      component.form.get('title')?.setValue('');
      expect(component.form.get('title')?.hasError('required')).toBe(true);
      expect(component.form.valid).toBe(false);
    });

    it('should allow empty description and icon', () => {
      component.form.get('description')?.setValue('');
      component.form.get('icon')?.setValue('');
      expect(component.form.valid).toBe(true);
    });
  });

  describe('onSubmit', () => {
    it('should update link with form data', () => {
      component.form.patchValue({
        url: 'https://updated.com',
        title: 'Updated Title',
        description: 'Updated description',
        icon: 'updated-icon',
      });

      component.onSubmit();

      expect(apiService.updateLink).toHaveBeenCalledWith(mockLink.id, {
        url: 'https://updated.com',
        title: 'Updated Title',
        description: 'Updated description',
        icon: 'updated-icon',
      });
    });

    it('should handle undefined icon gracefully', () => {
      component.form.patchValue({
        icon: '',
      });

      component.onSubmit();

      const calls = apiService.updateLink.mock.calls;
      expect(calls[0][1].icon).toBeUndefined();
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

    it('should handle API errors', () => {
      const error = new Error('Update failed');
      apiService.updateLink.mockReturnValue(throwError(() => error));
      vi.spyOn(console, 'error').mockImplementation(() => {});

      component.onSubmit();

      expect(console.error).toHaveBeenCalledWith('Error updating link:', error);
      expect(component.loading).toBe(false);
    });

    it('should not submit if form is invalid', () => {
      component.form.get('url')?.setValue('');

      component.onSubmit();

      expect(apiService.updateLink).not.toHaveBeenCalled();
    });

    it('should mark all fields as touched when form is invalid', () => {
      component.form.get('url')?.setValue('');

      component.onSubmit();

      expect(component.form.get('url')?.touched).toBe(true);
    });
  });

  describe('onCancel', () => {
    it('should close dialog with false', () => {
      component.onCancel();

      expect(dialogRef.close).toHaveBeenCalledWith(false);
    });
  });
});
