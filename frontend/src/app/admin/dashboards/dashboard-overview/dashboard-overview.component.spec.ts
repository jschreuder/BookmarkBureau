import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute } from '@angular/router';
import { MatDialog } from '@angular/material/dialog';
import { MatSnackBar } from '@angular/material/snack-bar';
import { of } from 'rxjs';
import { vi } from 'vitest';
import { DashboardOverviewComponent } from './dashboard-overview.component';
import { ApiService } from '../../../core/services/api.service';
import { FullDashboard, Dashboard, CategoryWithLinks, Link } from '../../../core/models';

describe('DashboardOverviewComponent', () => {
  let component: DashboardOverviewComponent;
  let fixture: ComponentFixture<DashboardOverviewComponent>;
  let apiService: any;
  let matDialog: any;
  let matSnackBar: any;
  let activatedRoute: any;

  const mockDashboard: Dashboard = {
    id: 'test-id',
    title: 'Test Dashboard',
    description: 'Test Description',
    icon: 'dashboard',
    created_at: '2025-01-01T00:00:00Z',
    updated_at: '2025-01-01T00:00:00Z',
  };

  const mockCategory: CategoryWithLinks = {
    id: 'cat-id',
    dashboard_id: 'test-id',
    title: 'Test Category',
    color: '#667eea',
    sort_order: 0,
    created_at: '2025-01-01T00:00:00Z',
    updated_at: '2025-01-01T00:00:00Z',
    links: [],
  };

  const mockLink: Link = {
    id: 'link-id',
    url: 'https://example.com',
    title: 'Example Link',
    description: 'Test description',
    icon: 'link',
    created_at: '2025-01-01T00:00:00Z',
    updated_at: '2025-01-01T00:00:00Z',
  };

  const mockFullDashboard: FullDashboard = {
    dashboard: mockDashboard,
    categories: [mockCategory],
    favorites: [mockLink],
  };

  beforeEach(async () => {
    apiService = {
      getDashboard: vi.fn().mockReturnValue(of(mockFullDashboard)),
    };

    matDialog = {
      open: vi.fn(),
    };

    matSnackBar = {
      open: vi.fn(),
    };

    activatedRoute = {
      paramMap: of({ get: (key: string) => (key === 'id' ? 'test-id' : null) }),
    };

    await TestBed.configureTestingModule({
      imports: [DashboardOverviewComponent],
      providers: [
        { provide: ApiService, useValue: apiService },
        { provide: MatDialog, useValue: matDialog },
        { provide: MatSnackBar, useValue: matSnackBar },
        { provide: ActivatedRoute, useValue: activatedRoute },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(DashboardOverviewComponent);
    component = fixture.componentInstance;
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should load dashboard on init', () => {
    fixture.detectChanges();

    expect(apiService.getDashboard).toHaveBeenCalledWith('test-id');
    expect(component.fullDashboard).toEqual(mockFullDashboard);
    expect(component.loading).toBe(false);
  });

  it('should have dashboard title and description in template', () => {
    fixture.detectChanges();

    const compiled = fixture.nativeElement;
    expect(compiled.textContent).toContain('Test Dashboard');
    expect(compiled.textContent).toContain('Test Description');
  });

  it('should display favorites section', () => {
    fixture.detectChanges();

    const compiled = fixture.nativeElement;
    expect(compiled.textContent).toContain('Favorites');
    expect(compiled.textContent).toContain('Example Link');
  });

  it('should display categories section', () => {
    fixture.detectChanges();

    const compiled = fixture.nativeElement;
    expect(compiled.textContent).toContain('Categories');
    expect(compiled.textContent).toContain('Test Category');
  });
});
