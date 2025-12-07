import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';

@Component({
  selector: 'app-invalid-token-dialog',
  standalone: true,
  imports: [CommonModule, MatDialogModule, MatButtonModule, MatIconModule],
  template: `
    <div class="dialog-container">
      <mat-icon class="error-icon">error_outline</mat-icon>
      <h2 mat-dialog-title>Session Expired</h2>
      <mat-dialog-content>
        <p>Your session is no longer valid. Please log in again.</p>
      </mat-dialog-content>
      <mat-dialog-actions align="center">
        <button mat-raised-button color="primary" (click)="onGoToLogin()">
          Go to Login
        </button>
      </mat-dialog-actions>
    </div>
  `,
  styles: [
    `
      .dialog-container {
        text-align: center;
        padding: 8px;
      }

      .error-icon {
        font-size: 48px;
        width: 48px;
        height: 48px;
        color: #d32f2f;
        margin: 0 auto 16px;
      }

      h2 {
        margin: 0 0 8px 0;
      }

      mat-dialog-content {
        padding: 16px 0;
      }

      mat-dialog-content p {
        margin: 0;
        color: rgba(0, 0, 0, 0.6);
      }

      mat-dialog-actions {
        padding: 16px 0 0 0;
        margin: 0;
      }
    `,
  ],
})
export class InvalidTokenDialogComponent {
  private readonly dialogRef = inject(MatDialogRef<InvalidTokenDialogComponent>);

  onGoToLogin(): void {
    this.dialogRef.close();
  }
}
