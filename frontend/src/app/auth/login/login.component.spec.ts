import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ReactiveFormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { LoginComponent } from './login.component';
import { AuthService, TokenResponse } from '../../core/services/auth.service';
import { of, throwError } from 'rxjs';
import { vi } from 'vitest';

describe('LoginComponent', () => {
  let component: LoginComponent;
  let fixture: ComponentFixture<LoginComponent>;
  let authService: Partial<AuthService>;
  let router: Partial<Router>;

  const mockToken: TokenResponse = {
    token: 'test-token',
    type: 'Bearer',
    expires_at: new Date(Date.now() + 3600000).toISOString(),
  };

  beforeEach(async () => {
    authService = {
      login: vi.fn(),
    };

    router = {
      navigate: vi.fn(),
    };

    await TestBed.configureTestingModule({
      imports: [LoginComponent, ReactiveFormsModule],
      providers: [
        { provide: AuthService, useValue: authService },
        { provide: Router, useValue: router },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(LoginComponent);
    component = fixture.componentInstance;
    // Use markForCheck() to properly handle change detection
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  describe('form initialization', () => {
    it('should initialize form with empty values', () => {
      expect(component.form.get('email')?.value).toBe('');
      expect(component.form.get('password')?.value).toBe('');
      expect(component.form.get('totp_code')?.value).toBe('');
      expect(component.form.get('remember_me')?.value).toBe(false);
    });

    it('should initially hide TOTP field', () => {
      expect(component.showTotpField).toBe(false);
    });
  });

  describe('form validation', () => {
    it('should require email', () => {
      const emailControl = component.form.get('email');
      emailControl?.setValue('');
      expect(emailControl?.hasError('required')).toBe(true);
    });

    it('should validate email format', () => {
      const emailControl = component.form.get('email');
      emailControl?.setValue('invalid-email');
      expect(emailControl?.hasError('email')).toBe(true);
    });

    it('should require password', () => {
      const passwordControl = component.form.get('password');
      passwordControl?.setValue('');
      expect(passwordControl?.hasError('required')).toBe(true);
    });

    it('should require minimum password length', () => {
      const passwordControl = component.form.get('password');
      passwordControl?.setValue('short');
      expect(passwordControl?.hasError('minlength')).toBe(true);
    });

    it('should validate complete form', () => {
      component.form.patchValue({
        email: 'test@example.com',
        password: 'password123',
      });
      expect(component.form.valid).toBe(true);
    });
  });

  describe('TOTP field toggling', () => {
    it('should toggle TOTP field visibility', () => {
      expect(component.showTotpField).toBe(false);
      component.toggleTotpField();
      expect(component.showTotpField).toBe(true);
      component.toggleTotpField();
      expect(component.showTotpField).toBe(false);
    });

    it('should reset TOTP code when hiding field', () => {
      component.form.patchValue({ totp_code: '123456' });
      component.showTotpField = true;
      component.toggleTotpField();
      expect(component.form.get('totp_code')?.value).toBeNull();
    });
  });

  describe('login submission', () => {
    beforeEach(() => {
      component.form.patchValue({
        email: 'test@example.com',
        password: 'password123',
        remember_me: false,
      });
    });

    it('should call authService.login with form data', () => {
      (authService.login as any).mockReturnValue(of(mockToken));

      component.onSubmit();

      expect(authService.login).toHaveBeenCalledWith({
        email: 'test@example.com',
        password: 'password123',
        remember_me: false,
      });
    });

    it('should navigate to admin on successful login', () => {
      (authService.login as any).mockReturnValue(of(mockToken));

      component.onSubmit();
      fixture.detectChanges();

      expect(router.navigate).toHaveBeenCalledWith(['/admin']);
    });

    it('should include TOTP code if field is shown and filled', () => {
      component.showTotpField = true;
      component.form.patchValue({ totp_code: '123456' });
      (authService.login as any).mockReturnValue(of(mockToken));

      component.onSubmit();

      expect(authService.login).toHaveBeenCalledWith({
        email: 'test@example.com',
        password: 'password123',
        remember_me: false,
        totp_code: '123456',
      });
    });

    it('should set loading state during login', () => {
      expect(component.isLoading).toBe(false);

      (authService.login as any).mockReturnValue(of(mockToken));
      component.onSubmit();
      fixture.detectChanges();

      return new Promise<void>((resolve) => {
        setTimeout(() => {
          expect(component.isLoading).toBe(false);
          resolve();
        }, 50);
      });
    });

    it('should not submit if form is invalid', () => {
      component.form.patchValue({ email: 'invalid' });

      component.onSubmit();

      expect(authService.login).not.toHaveBeenCalled();
    });
  });

  describe('error handling', () => {
    beforeEach(() => {
      component.form.patchValue({
        email: 'test@example.com',
        password: 'password123',
      });
    });

    it('should show error message on login failure', () => {
      const error = {
        status: 400,
        error: { error: 'Invalid credentials' },
      };
      (authService.login as any).mockReturnValue(throwError(() => error));

      component.onSubmit();

      return new Promise<void>((resolve) => {
        setTimeout(() => {
          fixture.detectChanges();
          expect(component.errorMessage).toBe(
            'Invalid credentials. Please check your email and password.',
          );
          resolve();
        }, 50);
      });
    });

    it('should show TOTP field if TOTP is required', () => {
      const error = {
        status: 400,
        error: { error: 'TOTP code required' },
      };
      (authService.login as any).mockReturnValue(throwError(() => error));

      component.onSubmit();

      return new Promise<void>((resolve) => {
        setTimeout(() => {
          fixture.detectChanges();
          expect(component.showTotpField).toBe(true);
          expect(component.requiresTotpFirst).toBe(true);
          expect(component.errorMessage).toBe('Please enter your TOTP code');
          resolve();
        }, 50);
      });
    });

    it('should show TOTP field and set requiresTotpFirst when TOTP is required', () => {
      const error = {
        status: 400,
        error: { error: 'TOTP code required' },
      };
      (authService.login as any).mockReturnValue(throwError(() => error));

      component.onSubmit();

      return new Promise<void>((resolve) => {
        setTimeout(() => {
          fixture.detectChanges();
          expect(component.showTotpField).toBe(true);
          expect(component.requiresTotpFirst).toBe(true);
          resolve();
        }, 50);
      });
    });

    it('should handle network errors', () => {
      const error = { status: 0 };
      (authService.login as any).mockReturnValue(throwError(() => error));

      component.onSubmit();

      return new Promise<void>((resolve) => {
        setTimeout(() => {
          fixture.detectChanges();
          expect(component.errorMessage).toBe(
            'Network error. Please check your connection and try again.',
          );
          resolve();
        }, 50);
      });
    });

    it('should clear isLoading on error', () => {
      const error = { status: 400, error: {} };
      (authService.login as any).mockReturnValue(throwError(() => error));

      component.onSubmit();

      return new Promise<void>((resolve) => {
        setTimeout(() => {
          fixture.detectChanges();
          expect(component.isLoading).toBe(false);
          resolve();
        }, 50);
      });
    });
  });

  describe('cancel button', () => {
    it('should navigate to dashboard when cancel is clicked (no TOTP required)', () => {
      component.onCancel();

      expect(router.navigate).toHaveBeenCalledWith(['/dashboard']);
    });

    it('should reset form when cancel is clicked after TOTP required', () => {
      component.requiresTotpFirst = true;
      component.showTotpField = true;
      component.errorMessage = 'TOTP required';
      component.form.patchValue({
        email: 'test@example.com',
        password: 'password123',
        totp_code: '123456',
      });

      component.onCancel();

      expect(component.form.get('email')?.value).toBeNull();
      expect(component.form.get('password')?.value).toBeNull();
      expect(component.showTotpField).toBe(false);
      expect(component.requiresTotpFirst).toBe(false);
      expect(component.errorMessage).toBe('');
    });
  });
});
