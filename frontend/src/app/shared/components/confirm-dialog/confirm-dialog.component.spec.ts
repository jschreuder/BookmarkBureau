import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { ConfirmDialogComponent, ConfirmDialogData } from './confirm-dialog.component';
import { vi } from 'vitest';

describe('ConfirmDialogComponent', () => {
  let component: ConfirmDialogComponent;
  let fixture: ComponentFixture<ConfirmDialogComponent>;
  let mockDialogRef: { close: ReturnType<typeof vi.fn> };

  const mockData: ConfirmDialogData = {
    title: 'Confirm Action',
    message: 'Are you sure you want to proceed?',
  };

  beforeEach(async () => {
    mockDialogRef = { close: vi.fn() };

    await TestBed.configureTestingModule({
      imports: [ConfirmDialogComponent],
      providers: [
        { provide: MatDialogRef, useValue: mockDialogRef },
        { provide: MAT_DIALOG_DATA, useValue: mockData },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(ConfirmDialogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should display title from data', () => {
    const title = fixture.nativeElement.querySelector('h2');
    expect(title?.textContent).toContain(mockData.title);
  });

  it('should display message from data', () => {
    const content = fixture.nativeElement.querySelector('mat-dialog-content');
    expect(content?.textContent).toContain(mockData.message);
  });

  it('should close dialog with false on cancel', () => {
    component.onCancel();

    expect(mockDialogRef.close).toHaveBeenCalledWith(false);
  });

  it('should close dialog with true on confirm', () => {
    component.onConfirm();

    expect(mockDialogRef.close).toHaveBeenCalledWith(true);
  });

  it('should have cancel and delete buttons', () => {
    const buttons = fixture.nativeElement.querySelectorAll('button');
    expect(buttons.length).toBe(2);
  });

  it('should have correct button labels', () => {
    const buttons = fixture.nativeElement.querySelectorAll('button');
    expect(buttons[0].textContent).toContain('Cancel');
    expect(buttons[1].textContent).toContain('Delete');
  });

  it('should call onCancel when cancel button clicked', () => {
    vi.spyOn(component, 'onCancel');
    const cancelButton = fixture.nativeElement.querySelectorAll('button')[0];

    cancelButton.click();

    expect(component.onCancel).toHaveBeenCalled();
  });

  it('should call onConfirm when delete button clicked', () => {
    vi.spyOn(component, 'onConfirm');
    const deleteButton = fixture.nativeElement.querySelectorAll('button')[1];

    deleteButton.click();

    expect(component.onConfirm).toHaveBeenCalled();
  });
});
