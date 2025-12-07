import { ComponentFixture, TestBed } from '@angular/core/testing';
import { vi } from 'vitest';
import { ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { MatSnackBarModule } from '@angular/material/snack-bar';
import { of, throwError } from 'rxjs';
import { convertToParamMap } from '@angular/router';
import { DashboardFormComponent } from './dashboard-form.component';
import { ApiService } from '../../../core/services/api.service';
import { Dashboard } from '../../../core/models';

describe('DashboardFormComponent', () => {
  let component: DashboardFormComponent;
  let fixture: ComponentFixture<DashboardFormComponent>;
  let apiService: {
    getDashboardBasic: ReturnType<typeof vi.fn>;
    createDashboard: ReturnType<typeof vi.fn>;
    updateDashboard: ReturnType<typeof vi.fn>;
  };

  const mockDashboard: Dashboard = {
    dashboard_id: '123e4567-e89b-12d3-a456-426614174000',
    title: 'Home',
    description: 'Home dashboard',
    icon: 'home',
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
  };

  beforeEach(async () => {
    const apiServiceSpy = {
      getDashboardBasic: vi.fn(),
      createDashboard: vi.fn(),
      updateDashboard: vi.fn(),
    };

    await TestBed.configureTestingModule({
      imports: [DashboardFormComponent, ReactiveFormsModule, MatSnackBarModule],
      providers: [
        { provide: ApiService, useValue: apiServiceSpy },
        {
          provide: ActivatedRoute,
          useValue: {
            paramMap: of(convertToParamMap({})),
          },
        },
      ],
    }).compileComponents();

    apiService = TestBed.inject(ApiService) as any;
    fixture = TestBed.createComponent(DashboardFormComponent);
    component = fixture.componentInstance;
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize form with empty values for create mode', () => {
    expect(component.form.get('title')?.value).toBe('');
    expect(component.form.get('description')?.value).toBe('');
    expect(component.form.get('icon')?.value).toBe('');
  });

  it('should be in create mode by default', () => {
    expect(component.isEditMode).toBe(false);
  });

  it('should validate required title', () => {
    const titleControl = component.form.get('title');
    titleControl?.setValue('');
    expect(titleControl?.hasError('required')).toBe(true);

    titleControl?.setValue('Valid Title');
    expect(titleControl?.hasError('required')).toBe(false);
  });

  it('should validate title max length', () => {
    const titleControl = component.form.get('title');
    const longTitle = 'a'.repeat(257);
    titleControl?.setValue(longTitle);
    expect(titleControl?.hasError('maxlength')).toBe(true);

    titleControl?.setValue('a'.repeat(256));
    expect(titleControl?.hasError('maxlength')).toBe(false);
  });

  it('should validate required description', () => {
    const descControl = component.form.get('description');
    descControl?.setValue('');
    expect(descControl?.hasError('required')).toBe(true);

    descControl?.setValue('Valid Description');
    expect(descControl?.hasError('required')).toBe(false);
  });

  it('should allow optional icon', () => {
    const iconControl = component.form.get('icon');
    iconControl?.setValue('');
    expect(iconControl?.valid).toBe(true);

    iconControl?.setValue('home');
    expect(iconControl?.valid).toBe(true);
  });

  it('should create dashboard with valid form data', () => {
    apiService.createDashboard.mockReturnValue(of(mockDashboard));
    vi.spyOn(component['router'], 'navigate');

    component.form.patchValue({
      title: 'New Dashboard',
      description: 'A new dashboard',
      icon: 'dashboard',
    });

    component.onSubmit();

    expect(apiService.createDashboard).toHaveBeenCalled();
    expect(component['router'].navigate).toHaveBeenCalledWith(['/admin/dashboards']);
  });

  it('should not submit with invalid form', () => {
    component.form.patchValue({
      title: '',
      description: '',
    });

    component.onSubmit();

    expect(apiService.createDashboard).not.toHaveBeenCalled();
  });

  it('should load dashboard in edit mode', () => {
    apiService.getDashboardBasic.mockReturnValue(of(mockDashboard));
    component.isEditMode = true;
    component.dashboardId = mockDashboard.dashboard_id;

    component.loadDashboard(mockDashboard.dashboard_id);

    expect(apiService.getDashboardBasic).toHaveBeenCalledWith(mockDashboard.dashboard_id);
    expect(component.form.get('title')?.value).toBe(mockDashboard.title);
    expect(component.form.get('description')?.value).toBe(mockDashboard.description);
    expect(component.form.get('icon')?.value).toBe(mockDashboard.icon);
  });

  it('should update dashboard in edit mode', () => {
    apiService.updateDashboard.mockReturnValue(of(mockDashboard));
    vi.spyOn(component['router'], 'navigate');

    component.isEditMode = true;
    component.dashboardId = mockDashboard.dashboard_id;
    component.form.patchValue({
      title: 'Updated Title',
      description: 'Updated Description',
      icon: 'updated-icon',
    });

    component.onSubmit();

    expect(apiService.updateDashboard).toHaveBeenCalled();
    expect(component['router'].navigate).toHaveBeenCalledWith(['/admin/dashboards']);
  });

  it('should handle error on create', () => {
    const error = new Error('Create failed');
    apiService.createDashboard.mockReturnValue(throwError(() => error));

    component.form.patchValue({
      title: 'New Dashboard',
      description: 'A new dashboard',
    });

    component.onSubmit();

    expect(component.loading).toBe(false);
  });

  it('should handle error on update', () => {
    const error = new Error('Update failed');
    apiService.updateDashboard.mockReturnValue(throwError(() => error));

    component.isEditMode = true;
    component.dashboardId = mockDashboard.dashboard_id;
    component.form.patchValue({
      title: 'Updated Title',
      description: 'Updated Description',
    });

    component.onSubmit();

    expect(component.loading).toBe(false);
  });

  it('should navigate to list on cancel', () => {
    vi.spyOn(component['router'], 'navigate');

    component.onCancel();

    expect(component['router'].navigate).toHaveBeenCalledWith(['/admin/dashboards']);
  });

  it('should set heading for create mode', () => {
    component.isEditMode = false;
    fixture.detectChanges();

    const heading = fixture.nativeElement.querySelector('h1');
    expect(heading?.textContent).toContain('Create Dashboard');
  });

  it('should set heading for edit mode', () => {
    component.isEditMode = true;
    fixture.detectChanges();

    const heading = fixture.nativeElement.querySelector('h1');
    expect(heading?.textContent).toContain('Edit Dashboard');
  });

  it('should disable submit button when form is invalid', () => {
    component.form.patchValue({
      title: '',
      description: '',
    });
    fixture.detectChanges();

    const submitButton = fixture.nativeElement.querySelector('button[type="submit"]');
    expect(submitButton.disabled).toBe(true);
  });

  it('should enable submit button when form is valid', () => {
    component.form.patchValue({
      title: 'Valid Title',
      description: 'Valid Description',
    });
    fixture.detectChanges();

    const submitButton = fixture.nativeElement.querySelector('button[type="submit"]');
    expect(submitButton.disabled).toBe(false);
  });
});
