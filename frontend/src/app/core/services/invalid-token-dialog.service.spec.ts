import { TestBed } from '@angular/core/testing';
import { MatDialog, MatDialogRef } from '@angular/material/dialog';
import { Router } from '@angular/router';
import { InvalidTokenDialogService } from './invalid-token-dialog.service';
import { AuthService } from './auth.service';
import { InvalidTokenDialogComponent } from '../../shared/components/invalid-token-dialog/invalid-token-dialog.component';
import { of } from 'rxjs';
import { vi } from 'vitest';

describe('InvalidTokenDialogService', () => {
  let service: InvalidTokenDialogService;
  let dialog: MatDialog;
  let auth: AuthService;
  let router: Router;
  let mockDialogRef: Partial<MatDialogRef<InvalidTokenDialogComponent>>;
  let mockDialogOpen: any;

  beforeEach(() => {
    mockDialogRef = {
      afterClosed: vi.fn().mockReturnValue(of(undefined)),
    };

    mockDialogOpen = vi.fn().mockReturnValue(mockDialogRef);

    TestBed.configureTestingModule({
      providers: [
        InvalidTokenDialogService,
        {
          provide: MatDialog,
          useValue: {
            open: mockDialogOpen,
          },
        },
        {
          provide: AuthService,
          useValue: {
            logout: vi.fn(),
          },
        },
        {
          provide: Router,
          useValue: {
            navigate: vi.fn(),
          },
        },
      ],
    });

    service = TestBed.inject(InvalidTokenDialogService);
    dialog = TestBed.inject(MatDialog);
    auth = TestBed.inject(AuthService);
    router = TestBed.inject(Router);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  describe('showInvalidTokenDialog', () => {
    it('should open dialog with correct component', () => {
      service.showInvalidTokenDialog();

      expect(dialog.open).toHaveBeenCalledWith(InvalidTokenDialogComponent, {
        disableClose: true,
        width: '400px',
      });
    });

    it('should call logout and navigate to login on dialog close', () => {
      service.showInvalidTokenDialog();

      expect(auth.logout).toHaveBeenCalled();
      expect(router.navigate).toHaveBeenCalledWith(['/login']);
    });

    it('should prevent multiple dialogs from opening simultaneously', () => {
      // Create a mock that returns an observable that NEVER completes
      // This simulates a dialog that stays open
      const { Observable } = require('rxjs');
      const neverClosingDialogRef = {
        afterClosed: vi.fn().mockReturnValue(new Observable(() => {})),
      };

      mockDialogOpen.mockClear();
      mockDialogOpen.mockReturnValue(neverClosingDialogRef);

      // Call multiple times while dialog is "open"
      service.showInvalidTokenDialog();
      service.showInvalidTokenDialog();
      service.showInvalidTokenDialog();

      // Only the first call should have opened a dialog
      expect(mockDialogOpen).toHaveBeenCalledTimes(1);
    });
  });
});
