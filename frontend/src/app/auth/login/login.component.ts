import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatDividerModule } from '@angular/material/divider';
import { Router } from '@angular/router';
import { AuthService, LoginRequest } from '../../core/services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatCardModule,
    MatCheckboxModule,
    MatProgressSpinnerModule,
    MatDividerModule,
  ],
  templateUrl: './login.component.html',
  styleUrl: './login.component.scss',
})
export class LoginComponent {
  private fb = inject(FormBuilder);
  private auth = inject(AuthService);
  private router = inject(Router);

  form: FormGroup;
  isLoading = false;
  errorMessage = '';
  showTotpField = false;
  requiresTotpFirst = false;

  constructor() {
    this.form = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.minLength(6)]],
      totp_code: [''],
      remember_me: [false],
    });
  }

  get email() {
    return this.form.get('email');
  }

  get password() {
    return this.form.get('password');
  }

  get totpCode() {
    return this.form.get('totp_code');
  }

  get rememberMe() {
    return this.form.get('remember_me');
  }

  toggleTotpField(): void {
    this.showTotpField = !this.showTotpField;
    if (!this.showTotpField) {
      this.form.get('totp_code')?.reset();
    }
  }

  onSubmit(): void {
    if (!this.form.valid) {
      return;
    }

    this.isLoading = true;
    this.errorMessage = '';
    this.requiresTotpFirst = false;

    const request: LoginRequest = {
      email: this.form.get('email')?.value,
      password: this.form.get('password')?.value,
      remember_me: this.form.get('remember_me')?.value || false,
    };

    if (this.showTotpField && this.form.get('totp_code')?.value) {
      request.totp_code = this.form.get('totp_code')?.value;
    }

    this.auth.login(request).subscribe({
      next: () => {
        this.isLoading = false;
        this.router.navigate(['/admin']);
      },
      error: (httpError) => {
        this.isLoading = false;

        // Check if error indicates TOTP is required
        if (httpError.status === 400 && httpError.error?.error?.includes('TOTP code required')) {
          this.requiresTotpFirst = true;
          this.showTotpField = true;
          this.errorMessage = 'Please enter your TOTP code';
          return;
        }

        // Handle other errors
        if (httpError.status === 400) {
          this.errorMessage = 'Invalid credentials. Please check your email and password.';
        } else if (httpError.status === 0) {
          this.errorMessage = 'Network error. Please check your connection and try again.';
        } else {
          this.errorMessage = 'Login failed. Please try again.';
        }

        // Reset TOTP field on error
        this.showTotpField = false;
        this.form.get('totp_code')?.reset();
      },
    });
  }

  onCancel(): void {
    if (this.requiresTotpFirst) {
      // Reset form for new login attempt
      this.form.reset();
      this.requiresTotpFirst = false;
      this.showTotpField = false;
      this.errorMessage = '';
    } else {
      this.router.navigate(['/dashboard']);
    }
  }
}
