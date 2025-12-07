import { TestBed, ComponentFixture } from '@angular/core/testing';
import { MatDialogRef } from '@angular/material/dialog';
import { InvalidTokenDialogComponent } from './invalid-token-dialog.component';
import { vi } from 'vitest';

describe('InvalidTokenDialogComponent', () => {
  let component: InvalidTokenDialogComponent;
  let fixture: ComponentFixture<InvalidTokenDialogComponent>;
  let mockDialogRef: Partial<MatDialogRef<InvalidTokenDialogComponent>>;

  beforeEach(async () => {
    mockDialogRef = {
      close: vi.fn(),
    };

    await TestBed.configureTestingModule({
      imports: [InvalidTokenDialogComponent],
      providers: [
        {
          provide: MatDialogRef,
          useValue: mockDialogRef,
        },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(InvalidTokenDialogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should display error icon', () => {
    const icon = fixture.nativeElement.querySelector('.error-icon');
    expect(icon).toBeTruthy();
    expect(icon.textContent).toContain('error_outline');
  });

  it('should display correct title', () => {
    const title = fixture.nativeElement.querySelector('h2');
    expect(title.textContent).toContain('Session Expired');
  });

  it('should display correct message', () => {
    const message = fixture.nativeElement.querySelector('mat-dialog-content p');
    expect(message.textContent).toContain('Your session is no longer valid');
  });

  it('should close dialog when button is clicked', () => {
    const button = fixture.nativeElement.querySelector('button');
    button.click();

    expect(mockDialogRef.close).toHaveBeenCalled();
  });

  it('should display correct button label', () => {
    const button = fixture.nativeElement.querySelector('button');
    expect(button.textContent).toContain('Go to Login');
  });
});
