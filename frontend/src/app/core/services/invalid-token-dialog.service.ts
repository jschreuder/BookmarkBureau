import { Injectable, inject } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { Router } from '@angular/router';
import { BehaviorSubject } from 'rxjs';
import { AuthService } from './auth.service';
import { InvalidTokenDialogComponent } from '../../shared/components/invalid-token-dialog/invalid-token-dialog.component';

@Injectable({
  providedIn: 'root',
})
export class InvalidTokenDialogService {
  private dialog = inject(MatDialog);
  private auth = inject(AuthService);
  private router = inject(Router);

  private isShowingDialog = new BehaviorSubject<boolean>(false);

  /**
   * Show the invalid token dialog and handle cleanup
   */
  showInvalidTokenDialog(): void {
    // Prevent multiple dialogs from opening
    if (this.isShowingDialog.value) {
      return;
    }

    this.isShowingDialog.next(true);

    const dialogRef = this.dialog.open(InvalidTokenDialogComponent, {
      disableClose: true,
      width: '400px',
    });

    dialogRef.afterClosed().subscribe(() => {
      // Logout and redirect to login
      this.auth.logout();
      this.router.navigate(['/login']);
      this.isShowingDialog.next(false);
    });
  }
}
