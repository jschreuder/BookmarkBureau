import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute } from '@angular/router';
import { MatDialog } from '@angular/material/dialog';
import { MatSnackBar } from '@angular/material/snack-bar';
import { of } from 'rxjs';
import { vi } from 'vitest';
import { CdkDragDrop } from '@angular/cdk/drag-drop';
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
    dashboard_id: 'test-id',
    title: 'Test Dashboard',
    description: 'Test Description',
    icon: 'dashboard',
    created_at: '2025-01-01T00:00:00Z',
    updated_at: '2025-01-01T00:00:00Z',
  };

  const mockLink1: Link = {
    link_id: 'link-id-1',
    url: 'https://example.com',
    title: 'Example Link 1',
    description: 'Test description 1',
    icon: 'link',
    created_at: '2025-01-01T00:00:00Z',
    updated_at: '2025-01-01T00:00:00Z',
  };

  const mockLink2: Link = {
    link_id: 'link-id-2',
    url: 'https://example2.com',
    title: 'Example Link 2',
    description: 'Test description 2',
    icon: 'link',
    created_at: '2025-01-01T00:00:00Z',
    updated_at: '2025-01-01T00:00:00Z',
  };

  const mockLink3: Link = {
    link_id: 'link-id-3',
    url: 'https://example3.com',
    title: 'Example Link 3',
    description: 'Test description 3',
    icon: 'link',
    created_at: '2025-01-01T00:00:00Z',
    updated_at: '2025-01-01T00:00:00Z',
  };

  const mockCategory: CategoryWithLinks = {
    category_id: 'cat-id',
    dashboard_id: 'test-id',
    title: 'Test Category',
    color: '#667eea',
    sort_order: 0,
    created_at: '2025-01-01T00:00:00Z',
    updated_at: '2025-01-01T00:00:00Z',
    links: [mockLink1, mockLink2, mockLink3],
  };

  const mockFullDashboard: FullDashboard = {
    dashboard: mockDashboard,
    categories: [mockCategory],
    favorites: [mockLink1],
  };

  beforeEach(async () => {
    apiService = {
      getDashboard: vi.fn().mockReturnValue(of(mockFullDashboard)),
      reorderFavorites: vi.fn().mockReturnValue(of([])),
      reorderCategoryLinks: vi.fn().mockReturnValue(of([])),
      reorderCategories: vi.fn().mockReturnValue(of([])),
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
    expect(compiled.textContent).toContain('Example Link 1');
  });

  it('should display categories section', () => {
    fixture.detectChanges();

    const compiled = fixture.nativeElement;
    expect(compiled.textContent).toContain('Categories');
    expect(compiled.textContent).toContain('Test Category');
  });

  it('should open dashboard in new window when viewing', () => {
    fixture.detectChanges();

    const mockWindow = { closed: false } as Window;
    const openSpy = vi.spyOn(window, 'open').mockImplementation(() => mockWindow);
    component.viewDashboard();

    expect(openSpy).toHaveBeenCalledWith('/dashboard/test-id', '_blank');
    openSpy.mockRestore();
  });

  describe('Favorites Reordering', () => {
    it('should toggle reorder mode for favorites', () => {
      fixture.detectChanges();

      expect(component.favoritesReorderMode).toBe(false);

      component.favoritesReorderMode = true;
      fixture.detectChanges();

      expect(component.favoritesReorderMode).toBe(true);

      component.favoritesReorderMode = false;
      fixture.detectChanges();

      expect(component.favoritesReorderMode).toBe(false);
    });

    it('should reorder favorites when dropped', () => {
      fixture.detectChanges();

      // Ensure fullDashboard is set before calling the handler
      component.fullDashboard = {
        ...mockFullDashboard,
        favorites: [...mockFullDashboard.favorites],
      };

      const initialLength = component.fullDashboard.favorites.length;

      const event: Partial<CdkDragDrop<any>> = {
        previousIndex: 0,
        currentIndex: initialLength - 1,
      };

      component.onFavoritesDropped(event as CdkDragDrop<any>);

      // Verify the order changed
      expect(
        component.fullDashboard.favorites[component.fullDashboard.favorites.length - 1].link_id,
      ).toBe('link-id-1');
    });

    it('should not reorder when drop index is same as previous', () => {
      fixture.detectChanges();

      component.fullDashboard = mockFullDashboard;

      const event: Partial<CdkDragDrop<any>> = {
        previousIndex: 0,
        currentIndex: 0,
      };

      component.onFavoritesDropped(event as CdkDragDrop<any>);

      expect(apiService.reorderFavorites).not.toHaveBeenCalled();
    });
  });

  describe('Category Links Reordering', () => {
    it('should reorder category links when dropped', async () => {
      fixture.detectChanges();

      // Ensure fullDashboard is set before calling the handler
      component.fullDashboard = { ...mockFullDashboard };

      const category = { ...mockCategory, links: [...mockCategory.links] };

      const event: Partial<CdkDragDrop<any>> = {
        previousIndex: 0,
        currentIndex: 2,
      };

      component.onCategoryLinksDropped(event as CdkDragDrop<any>, category);

      // Verify the order changed
      expect(category.links[2].link_id).toBe('link-id-1');
    });

    it('should not reorder when drop index is same as previous', () => {
      fixture.detectChanges();

      component.fullDashboard = mockFullDashboard;

      const category = mockCategory;
      const event: Partial<CdkDragDrop<any>> = {
        previousIndex: 1,
        currentIndex: 1,
      };

      component.onCategoryLinksDropped(event as CdkDragDrop<any>, category);

      expect(apiService.reorderCategoryLinks).not.toHaveBeenCalled();
    });
  });

  describe('Categories Reordering', () => {
    const mockCategory1: CategoryWithLinks = {
      category_id: 'cat-id-1',
      dashboard_id: 'test-id',
      title: 'Category 1',
      color: '#667eea',
      sort_order: 0,
      created_at: '2025-01-01T00:00:00Z',
      updated_at: '2025-01-01T00:00:00Z',
      links: [],
    };

    const mockCategory2: CategoryWithLinks = {
      category_id: 'cat-id-2',
      dashboard_id: 'test-id',
      title: 'Category 2',
      color: '#764ba2',
      sort_order: 1,
      created_at: '2025-01-01T00:00:00Z',
      updated_at: '2025-01-01T00:00:00Z',
      links: [],
    };

    const mockCategory3: CategoryWithLinks = {
      category_id: 'cat-id-3',
      dashboard_id: 'test-id',
      title: 'Category 3',
      color: '#f093fb',
      sort_order: 2,
      created_at: '2025-01-01T00:00:00Z',
      updated_at: '2025-01-01T00:00:00Z',
      links: [],
    };

    it('should toggle reorder mode for categories', () => {
      fixture.detectChanges();

      expect(component.categoriesReorderMode).toBe(false);

      component.categoriesReorderMode = true;
      fixture.detectChanges();

      expect(component.categoriesReorderMode).toBe(true);

      component.categoriesReorderMode = false;
      fixture.detectChanges();

      expect(component.categoriesReorderMode).toBe(false);
    });

    it('should reorder categories when dropped', () => {
      fixture.detectChanges();

      // Setup fullDashboard with multiple categories
      component.fullDashboard = {
        ...mockFullDashboard,
        categories: [mockCategory1, mockCategory2, mockCategory3],
      };

      const event: Partial<CdkDragDrop<any>> = {
        previousIndex: 0,
        currentIndex: 2,
      };

      component.onCategoriesDropped(event as CdkDragDrop<any>);

      // Verify the order changed in the component
      expect(component.fullDashboard.categories[0].category_id).toBe('cat-id-2');
      expect(component.fullDashboard.categories[1].category_id).toBe('cat-id-3');
      expect(component.fullDashboard.categories[2].category_id).toBe('cat-id-1');
    });

    it('should not reorder when drop index is same as previous', () => {
      fixture.detectChanges();

      component.fullDashboard = {
        ...mockFullDashboard,
        categories: [mockCategory1, mockCategory2, mockCategory3],
      };

      const event: Partial<CdkDragDrop<any>> = {
        previousIndex: 1,
        currentIndex: 1,
      };

      component.onCategoriesDropped(event as CdkDragDrop<any>);

      expect(apiService.reorderCategories).not.toHaveBeenCalled();
    });

    it('should call API with correct payload when reordering', () => {
      fixture.detectChanges();

      component.fullDashboard = {
        ...mockFullDashboard,
        categories: [mockCategory1, mockCategory2, mockCategory3],
      };

      const event: Partial<CdkDragDrop<any>> = {
        previousIndex: 0,
        currentIndex: 2,
      };

      component.onCategoriesDropped(event as CdkDragDrop<any>);

      // Verify API was called with correct dashboard ID and reordered categories
      expect(apiService.reorderCategories).toHaveBeenCalledWith('test-id', [
        { category_id: 'cat-id-2', sort_order: 1 },
        { category_id: 'cat-id-3', sort_order: 2 },
        { category_id: 'cat-id-1', sort_order: 3 },
      ]);
    });
  });
});
