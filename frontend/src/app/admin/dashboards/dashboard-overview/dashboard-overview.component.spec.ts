import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute } from '@angular/router';
import { MatDialog } from '@angular/material/dialog';
import { MatSnackBar } from '@angular/material/snack-bar';
import { of, throwError } from 'rxjs';
import { DashboardOverviewComponent } from './dashboard-overview.component';
import { ApiService } from '../../../core/services/api.service';
import { FullDashboard, Dashboard, CategoryWithLinks, Link } from '../../../core/models';

describe('DashboardOverviewComponent', () => {
  let component: DashboardOverviewComponent;
  let fixture: ComponentFixture<DashboardOverviewComponent>;
  let apiService: jasmine.SpyObj<ApiService>;
  let matDialog: jasmine.SpyObj<MatDialog>;
  let matSnackBar: jasmine.SpyObj<MatSnackBar>;
  let activatedRoute: any;

  const mockDashboard: Dashboard = {
    id: 'test-id',
    title: 'Test Dashboard',
    description: 'Test Description',
    icon: 'dashboard',
    created_at: '2025-01-01T00:00:00Z',
    updated_at: '2025-01-01T00:00:00Z'
  };

  const mockCategory: CategoryWithLinks = {
    id: 'cat-id',
    dashboard_id: 'test-id',
    title: 'Test Category',
    color: '#667eea',
    sort_order: 0,
    created_at: '2025-01-01T00:00:00Z',
    updated_at: '2025-01-01T00:00:00Z',
    links: []
  };

  const mockLink: Link = {
    id: 'link-id',
    url: 'https://example.com',
    title: 'Example Link',
    description: 'Test description',
    icon: 'link',
    created_at: '2025-01-01T00:00:00Z',
    updated_at: '2025-01-01T00:00:00Z'
  };

  const mockFullDashboard: FullDashboard = {
    dashboard: mockDashboard,
    categories: [mockCategory],
    favorites: [mockLink]
  };

  beforeEach(async () => {
    const apiServiceSpy = jasmine.createSpyObj('ApiService', [
      'getDashboard',
      'deleteCategory',
      'removeFavorite',
      'createCategory',
      'createLink',
      'addFavorite'
    ]);

    const matDialogSpy = jasmine.createSpyObj('MatDialog', ['open']);
    const matSnackBarSpy = jasmine.createSpyObj('MatSnackBar', ['open']);

    activatedRoute = {
      snapshot: {
        paramMap: {
          get: (key: string) => (key === 'id' ? 'test-id' : null)
        }
      }
    };

    await TestBed.configureTestingModule({
      imports: [DashboardOverviewComponent],
      providers: [
        { provide: ApiService, useValue: apiServiceSpy },
        { provide: MatDialog, useValue: matDialogSpy },
        { provide: MatSnackBar, useValue: matSnackBarSpy },
        { provide: ActivatedRoute, useValue: activatedRoute }
      ]
    }).compileComponents();

    apiService = TestBed.inject(ApiService) as jasmine.SpyObj<ApiService>;
    matDialog = TestBed.inject(MatDialog) as jasmine.SpyObj<MatDialog>;
    matSnackBar = TestBed.inject(MatSnackBar) as jasmine.SpyObj<MatSnackBar>;

    fixture = TestBed.createComponent(DashboardOverviewComponent);
    component = fixture.componentInstance;
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  describe('ngOnInit', () => {
    it('should load dashboard on init', () => {
      apiService.getDashboard.and.returnValue(of(mockFullDashboard));

      fixture.detectChanges();

      expect(apiService.getDashboard).toHaveBeenCalledWith('test-id');
      expect(component.fullDashboard).toEqual(mockFullDashboard);
      expect(component.loading).toBe(false);
    });

    it('should handle error when loading dashboard', () => {
      const error = new Error('Load error');
      apiService.getDashboard.and.returnValue(throwError(() => error));

      fixture.detectChanges();

      expect(component.error).toBe('Failed to load dashboard');
      expect(component.loading).toBe(false);
      expect(matSnackBar.open).toHaveBeenCalledWith('Failed to load dashboard', 'Close', { duration: 5000 });
    });
  });

  describe('removeCategory', () => {
    beforeEach(() => {
      apiService.getDashboard.and.returnValue(of(mockFullDashboard));
      fixture.detectChanges();
    });

    it('should remove category after confirmation', () => {
      const dialogRef = jasmine.createSpyObj('MatDialogRef', ['afterClosed']);
      dialogRef.afterClosed.and.returnValue(of(true));
      matDialog.open.and.returnValue(dialogRef);
      apiService.deleteCategory.and.returnValue(of(undefined));

      component.removeCategory(mockCategory);

      expect(matDialog.open).toHaveBeenCalled();
      expect(apiService.deleteCategory).toHaveBeenCalledWith(mockCategory.id);
      expect(matSnackBar.open).toHaveBeenCalledWith('Category deleted successfully', 'Close', { duration: 3000 });
    });

    it('should not remove category if dialog is cancelled', () => {
      const dialogRef = jasmine.createSpyObj('MatDialogRef', ['afterClosed']);
      dialogRef.afterClosed.and.returnValue(of(false));
      matDialog.open.and.returnValue(dialogRef);

      component.removeCategory(mockCategory);

      expect(apiService.deleteCategory).not.toHaveBeenCalled();
    });
  });

  describe('removeFavorite', () => {
    beforeEach(() => {
      apiService.getDashboard.and.returnValue(of(mockFullDashboard));
      fixture.detectChanges();
    });

    it('should remove favorite after confirmation', () => {
      const dialogRef = jasmine.createSpyObj('MatDialogRef', ['afterClosed']);
      dialogRef.afterClosed.and.returnValue(of(true));
      matDialog.open.and.returnValue(dialogRef);
      apiService.removeFavorite.and.returnValue(of(undefined));

      component.removeFavorite(mockLink);

      expect(apiService.removeFavorite).toHaveBeenCalledWith('test-id', mockLink.id);
      expect(matSnackBar.open).toHaveBeenCalledWith('Removed from favorites', 'Close', { duration: 3000 });
    });
  });
});
