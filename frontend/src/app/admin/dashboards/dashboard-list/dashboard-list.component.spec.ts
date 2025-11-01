import { ComponentFixture, TestBed } from '@angular/core/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { MatSnackBarModule } from '@angular/material/snack-bar';
import { MatDialogModule } from '@angular/material/dialog';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { of, throwError } from 'rxjs';
import { AdminDashboardListComponent } from './dashboard-list.component';
import { ApiService } from '../../../core/services/api.service';
import { Dashboard } from '../../../core/models';

describe('AdminDashboardListComponent', () => {
  let component: AdminDashboardListComponent;
  let fixture: ComponentFixture<AdminDashboardListComponent>;
  let apiService: jasmine.SpyObj<ApiService>;

  const mockDashboards: Dashboard[] = [
    {
      id: '123e4567-e89b-12d3-a456-426614174000',
      title: 'Home',
      description: 'Home dashboard',
      icon: 'home',
      created_at: '2024-01-01T00:00:00Z',
      updated_at: '2024-01-01T00:00:00Z'
    },
    {
      id: '223e4567-e89b-12d3-a456-426614174000',
      title: 'Work',
      description: 'Work dashboard',
      icon: 'work',
      created_at: '2024-01-02T00:00:00Z',
      updated_at: '2024-01-02T00:00:00Z'
    }
  ];

  beforeEach(async () => {
    const apiServiceSpy = jasmine.createSpyObj('ApiService', [
      'listDashboards',
      'deleteDashboard'
    ]);

    await TestBed.configureTestingModule({
      imports: [
        AdminDashboardListComponent,
        RouterTestingModule,
        MatSnackBarModule,
        MatDialogModule,
        BrowserAnimationsModule
      ],
      providers: [
        { provide: ApiService, useValue: apiServiceSpy }
      ]
    }).compileComponents();

    apiService = TestBed.inject(ApiService) as jasmine.SpyObj<ApiService>;
    fixture = TestBed.createComponent(AdminDashboardListComponent);
    component = fixture.componentInstance;
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should load dashboards on init', () => {
    apiService.listDashboards.and.returnValue(of(mockDashboards));

    fixture.detectChanges();

    expect(apiService.listDashboards).toHaveBeenCalled();
    expect(component.dashboards).toEqual(mockDashboards);
    expect(component.loading).toBe(false);
  });

  it('should display loading spinner while loading', () => {
    apiService.listDashboards.and.returnValue(of(mockDashboards));
    component.loading = true;

    fixture.detectChanges();

    const spinner = fixture.nativeElement.querySelector('mat-spinner');
    expect(spinner).toBeTruthy();
  });

  it('should display empty state when no dashboards', () => {
    apiService.listDashboards.and.returnValue(of([]));

    fixture.detectChanges();

    const emptyState = fixture.nativeElement.querySelector('.empty-state');
    expect(emptyState).toBeTruthy();
    expect(emptyState.textContent).toContain('No dashboards found');
  });

  it('should display table when dashboards exist', () => {
    apiService.listDashboards.and.returnValue(of(mockDashboards));

    fixture.detectChanges();

    const table = fixture.nativeElement.querySelector('.dashboards-table');
    expect(table).toBeTruthy();
  });

  it('should display dashboard data in table', () => {
    apiService.listDashboards.and.returnValue(of(mockDashboards));
    component.dashboards = mockDashboards;

    fixture.detectChanges();

    const cells = fixture.nativeElement.querySelectorAll('td');
    expect(cells.length).toBeGreaterThan(0);
    expect(fixture.nativeElement.textContent).toContain('Home');
    expect(fixture.nativeElement.textContent).toContain('Work');
  });

  it('should handle error loading dashboards', () => {
    const error = new Error('Load failed');
    apiService.listDashboards.and.returnValue(throwError(() => error));
    spyOn(console, 'error');

    component.loadDashboards();
    fixture.detectChanges();

    expect(console.error).toHaveBeenCalledWith('Error loading dashboards:', error);
    expect(component.loading).toBe(false);
  });

  it('should navigate to new dashboard form', () => {
    spyOn(component['router'], 'navigate');

    component.navigateToNew();

    expect(component['router'].navigate).toHaveBeenCalledWith(['/admin/dashboards/new']);
  });

  it('should navigate to edit dashboard form', () => {
    spyOn(component['router'], 'navigate');
    const dashboardId = '123e4567-e89b-12d3-a456-426614174000';

    component.navigateToEdit(dashboardId);

    expect(component['router'].navigate).toHaveBeenCalledWith(['/admin/dashboards', dashboardId, 'edit']);
  });

  it('should display edit and delete buttons for each dashboard', () => {
    apiService.listDashboards.and.returnValue(of(mockDashboards));
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
