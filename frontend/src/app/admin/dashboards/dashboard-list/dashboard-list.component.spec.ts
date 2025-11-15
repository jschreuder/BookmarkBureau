import { ComponentFixture, TestBed } from '@angular/core/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { MatSnackBarModule } from '@angular/material/snack-bar';
import { MatDialogModule } from '@angular/material/dialog';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { of, throwError, NEVER } from 'rxjs';
import { vi } from 'vitest';
import { AdminDashboardListComponent } from './dashboard-list.component';
import { ApiService } from '../../../core/services/api.service';
import { Dashboard } from '../../../core/models';

describe('AdminDashboardListComponent', () => {
  let component: AdminDashboardListComponent;
  let fixture: ComponentFixture<AdminDashboardListComponent>;
  let apiService: {
    listDashboards: ReturnType<typeof vi.fn>;
    deleteDashboard: ReturnType<typeof vi.fn>;
  };

  const mockDashboards: Dashboard[] = [
    {
      id: '123e4567-e89b-12d3-a456-426614174000',
      title: 'Home',
      description: 'Home dashboard',
      icon: 'home',
      created_at: '2024-01-01T00:00:00Z',
      updated_at: '2024-01-01T00:00:00Z',
    },
    {
      id: '223e4567-e89b-12d3-a456-426614174000',
      title: 'Work',
      description: 'Work dashboard',
      icon: 'work',
      created_at: '2024-01-02T00:00:00Z',
      updated_at: '2024-01-02T00:00:00Z',
    },
  ];

  beforeEach(async () => {
    const apiServiceSpy = {
      listDashboards: vi.fn().mockReturnValue(of([])),
      deleteDashboard: vi.fn(),
    };

    await TestBed.configureTestingModule({
      imports: [
        AdminDashboardListComponent,
        RouterTestingModule,
        MatSnackBarModule,
        MatDialogModule,
        BrowserAnimationsModule,
      ],
      providers: [{ provide: ApiService, useValue: apiServiceSpy }],
    }).compileComponents();

    apiService = TestBed.inject(ApiService) as any;
    fixture = TestBed.createComponent(AdminDashboardListComponent);
    component = fixture.componentInstance;
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should load dashboards on init', () => {
    apiService.listDashboards.mockReturnValue(of(mockDashboards));

    fixture.detectChanges();

    expect(apiService.listDashboards).toHaveBeenCalled();
    expect(component.dashboards).toEqual(mockDashboards);
    expect(component.loading).toBe(false);
  });

  it('should display loading spinner while loading', () => {
    // Mock API to return an observable that never completes (keeps loading)
    apiService.listDashboards.mockReturnValue(NEVER);

    // Trigger initial detection which will call ngOnInit and start loading
    fixture.detectChanges();

    // At this point, loading should be true because the observable hasn't completed
    expect(component.loading).toBe(true);

    // And the loading spinner should be displayed
    const html = fixture.nativeElement.innerHTML;
    expect(html).toContain('loading-spinner');
  });

  it('should display empty state when no dashboards', () => {
    apiService.listDashboards.mockReturnValue(of([]));

    fixture.detectChanges();

    const emptyState = fixture.nativeElement.querySelector('.empty-state');
    expect(emptyState).toBeTruthy();
    expect(emptyState.textContent).toContain('No dashboards found');
  });

  it('should display table when dashboards exist', () => {
    apiService.listDashboards.mockReturnValue(of(mockDashboards));

    fixture.detectChanges();

    const table = fixture.nativeElement.querySelector('.dashboards-table');
    expect(table).toBeTruthy();
  });

  it('should display dashboard data in table', () => {
    apiService.listDashboards.mockReturnValue(of(mockDashboards));
    component.dashboards = mockDashboards;

    fixture.detectChanges();

    const cells = fixture.nativeElement.querySelectorAll('td');
    expect(cells.length).toBeGreaterThan(0);
    expect(fixture.nativeElement.textContent).toContain('Home');
    expect(fixture.nativeElement.textContent).toContain('Work');
  });

  it('should handle error loading dashboards', () => {
    const error = new Error('Load failed');
    apiService.listDashboards.mockReturnValue(throwError(() => error));
    const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

    component.loadDashboards();
    fixture.detectChanges();

    expect(consoleErrorSpy).toHaveBeenCalledWith('Error loading dashboards:', error);
    expect(component.loading).toBe(false);

    consoleErrorSpy.mockRestore();
  });

  it('should navigate to new dashboard form', () => {
    vi.spyOn(component['router'], 'navigate');

    component.navigateToNew();

    expect(component['router'].navigate).toHaveBeenCalledWith(['/admin/dashboards/new']);
  });

  it('should navigate to edit dashboard form', () => {
    vi.spyOn(component['router'], 'navigate');
    const dashboardId = '123e4567-e89b-12d3-a456-426614174000';

    component.navigateToEdit(dashboardId);

    expect(component['router'].navigate).toHaveBeenCalledWith([
      '/admin/dashboards',
      dashboardId,
      'edit',
    ]);
  });

  it('should display edit and delete buttons for each dashboard', () => {
    apiService.listDashboards.mockReturnValue(of(mockDashboards));
    component.dashboards = mockDashboards;

    fixture.detectChanges();

    const editButtons = fixture.nativeElement.querySelectorAll('button[matTooltip="Edit"]');
    const deleteButtons = fixture.nativeElement.querySelectorAll('button[matTooltip="Delete"]');

    expect(editButtons.length).toBe(mockDashboards.length);
    expect(deleteButtons.length).toBe(mockDashboards.length);
  });

  it('should have correct column definitions', () => {
    expect(component.displayedColumns).toEqual(['title', 'description', 'icon', 'actions']);
  });
});
