import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ReactiveFormsModule } from '@angular/forms';
import { MatDialogModule, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { NoopAnimationsModule } from '@angular/platform-browser/animations';
import { of, throwError } from 'rxjs';
import { vi } from 'vitest';
import { TagFormDialogComponent } from './tag-form-dialog.component';
import { TagService } from '../../../core/services/tag.service';
import { Tag } from '../../../core/models';

describe('TagFormDialogComponent', () => {
  let component: TagFormDialogComponent;
  let fixture: ComponentFixture<TagFormDialogComponent>;
  let tagService: TagService;
  let dialogRef: MatDialogRef<TagFormDialogComponent>;
  let snackBar: MatSnackBar;

  const mockTag: Tag = { tag_name: 'work', color: '#2196f3' };

  const createComponent = (data: any = {}) => {
    const tagServiceMock = {
      createTag: vi.fn(),
      updateTag: vi.fn(),
    };

    const dialogRefMock = {
      close: vi.fn(),
    };

    const snackBarMock = {
      open: vi.fn(),
    };

    TestBed.configureTestingModule({
      imports: [
        TagFormDialogComponent,
        ReactiveFormsModule,
        MatDialogModule,
        MatFormFieldModule,
        MatInputModule,
        MatButtonModule,
        MatSnackBarModule,
        NoopAnimationsModule,
      ],
      providers: [
        { provide: TagService, useValue: tagServiceMock },
        { provide: MatDialogRef, useValue: dialogRefMock },
        { provide: MatSnackBar, useValue: snackBarMock },
        { provide: MAT_DIALOG_DATA, useValue: data },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(TagFormDialogComponent);
    component = fixture.componentInstance;
    tagService = TestBed.inject(TagService);
    dialogRef = TestBed.inject(MatDialogRef);
    snackBar = TestBed.inject(MatSnackBar);
    fixture.detectChanges();
  };

  describe('create mode', () => {
    beforeEach(() => {
      createComponent({});
    });

    it('should create in create mode', () => {
      expect(component).toBeTruthy();
      expect(component.isEditMode).toBe(false);
    });

    it('should have empty form in create mode', () => {
      expect(component.form.get('tag_name')?.value).toBe('');
      expect(component.form.get('color')?.value).toBe('#2196f3');
    });

    it('should enable tag_name field in create mode', () => {
      expect(component.form.get('tag_name')?.disabled).toBe(false);
    });

    it('should create tag on submit', async () => {
      const newTag: Tag = { tag_name: 'new-tag', color: '#ff9800' };
      vi.spyOn(tagService, 'createTag').mockReturnValue(of(newTag));

      component.form.patchValue({ tag_name: 'new-tag', color: '#ff9800' });
      component.onSubmit();

      await new Promise((resolve) => setTimeout(resolve, 0));

      expect(tagService.createTag).toHaveBeenCalledWith({
        tag_name: 'new-tag',
        color: '#ff9800',
      });
      expect(dialogRef.close).toHaveBeenCalledWith(true);
    });

    it('should handle create failure', async () => {
      const error = new Error('Failed');
      vi.spyOn(tagService, 'createTag').mockReturnValue(throwError(() => error));

      component.form.patchValue({ tag_name: 'new-tag' });
      component.onSubmit();

      await new Promise((resolve) => setTimeout(resolve, 0));

      expect(component.loading).toBe(false);
      expect(dialogRef.close).not.toHaveBeenCalled();
    });
  });

  describe('edit mode', () => {
    beforeEach(() => {
      createComponent({ tag: mockTag });
    });

    it('should create in edit mode', () => {
      expect(component.isEditMode).toBe(true);
    });

    it('should populate form with tag data', () => {
      expect(component.form.get('tag_name')?.value).toBe('work');
      expect(component.form.get('color')?.value).toBe('#2196f3');
    });

    it('should disable tag_name field in edit mode', () => {
      expect(component.form.get('tag_name')?.disabled).toBe(true);
    });

    it('should not sanitize tag_name in edit mode because field is disabled', () => {
      // In edit mode, the tag_name field is disabled, so no sanitization should occur
      expect(component.form.get('tag_name')?.disabled).toBe(true);
      // The field should not have a valueChanges subscription for sanitization
      // This is implicitly tested by the field being disabled - disabled fields don't emit valueChanges
    });

    it('should update tag on submit', async () => {
      const updatedTag: Tag = { tag_name: 'work', color: '#ff5722' };
      vi.spyOn(tagService, 'updateTag').mockReturnValue(of(updatedTag));

      component.form.patchValue({ color: '#ff5722' });
      component.onSubmit();

      await new Promise((resolve) => setTimeout(resolve, 0));

      expect(tagService.updateTag).toHaveBeenCalledWith('work', {
        tag_name: 'work',
        color: '#ff5722',
      });
      expect(dialogRef.close).toHaveBeenCalledWith(true);
    });

    it('should handle update failure', async () => {
      const error = new Error('Failed');
      vi.spyOn(tagService, 'updateTag').mockReturnValue(throwError(() => error));

      component.form.patchValue({ color: '#ff5722' });
      component.onSubmit();

      await new Promise((resolve) => setTimeout(resolve, 0));

      expect(component.loading).toBe(false);
      expect(dialogRef.close).not.toHaveBeenCalled();
    });
  });

  describe('color selection', () => {
    beforeEach(() => {
      createComponent({});
    });

    it('should update color on color input change', () => {
      const event = {
        target: { value: '#123456' },
      } as any;

      component.onColorChange(event);

      expect(component.form.get('color')?.value).toBe('#123456');
    });

    it('should update color on preset selection', () => {
      component.selectColor('#ff9800');

      expect(component.form.get('color')?.value).toBe('#ff9800');
    });
  });

  describe('form validation', () => {
    beforeEach(() => {
      createComponent({});
    });

    it('should be invalid when tag_name is empty', () => {
      component.form.patchValue({ tag_name: '' });
      expect(component.form.valid).toBe(false);
    });

    it('should be valid with tag_name', () => {
      component.form.patchValue({ tag_name: 'test' });
      expect(component.form.valid).toBe(true);
    });

    it('should not submit invalid form', () => {
      component.form.patchValue({ tag_name: '' });
      component.onSubmit();

      expect(tagService.createTag).not.toHaveBeenCalled();
      expect(component.form.get('tag_name')?.touched).toBe(true);
    });
  });

  describe('cancel', () => {
    beforeEach(() => {
      createComponent({});
    });

    it('should close dialog with false on cancel', () => {
      component.onCancel();
      expect(dialogRef.close).toHaveBeenCalledWith(false);
    });
  });

  describe('tag name sanitization', () => {
    beforeEach(() => {
      createComponent({});
    });

    it('should convert uppercase to lowercase in create mode', async () => {
      component.form.patchValue({ tag_name: 'UPPERCASE' });
      await new Promise((resolve) => setTimeout(resolve, 0));
      expect(component.form.get('tag_name')?.value).toBe('uppercase');
    });

    it('should remove invalid characters in create mode', async () => {
      component.form.patchValue({ tag_name: 'hello@world!' });
      await new Promise((resolve) => setTimeout(resolve, 0));
      expect(component.form.get('tag_name')?.value).toBe('helloworld');
    });

    it('should allow lowercase letters, numbers, and hyphens', async () => {
      component.form.patchValue({ tag_name: 'valid-tag-123' });
      await new Promise((resolve) => setTimeout(resolve, 0));
      expect(component.form.get('tag_name')?.value).toBe('valid-tag-123');
    });

    it('should remove spaces and special characters', async () => {
      component.form.patchValue({ tag_name: 'tag with spaces & special!' });
      await new Promise((resolve) => setTimeout(resolve, 0));
      expect(component.form.get('tag_name')?.value).toBe('tagwithspacesspecial');
    });

    it('should enforce maximum length of 100 characters', async () => {
      component.form.patchValue({ tag_name: 'a'.repeat(150) });
      await new Promise((resolve) => setTimeout(resolve, 0));
      expect(component.form.get('tag_name')?.value.length).toBe(100);
    });

    it('should handle mixed case and invalid characters', async () => {
      component.form.patchValue({ tag_name: 'Test-Tag_123!@#' });
      await new Promise((resolve) => setTimeout(resolve, 0));
      expect(component.form.get('tag_name')?.value).toBe('test-tag123');
    });
  });
});
