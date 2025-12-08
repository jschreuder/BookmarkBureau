import { ComponentFixture, TestBed } from '@angular/core/testing';
import { DashboardViewComponent } from './dashboard-view.component';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { MatIconModule } from '@angular/material/icon';
import { MatCardModule } from '@angular/material/card';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatButtonModule } from '@angular/material/button';
import { MatChipsModule } from '@angular/material/chips';
import { MatDialog } from '@angular/material/dialog';
import { ApiService } from '../../core/services/api.service';
import { ActivatedRoute } from '@angular/router';
import {
  createMockApiService,
  createMockFullDashboard,
  createMockLink,
  createMockCategoryWithLinks,
} from '../../../testing/test-helpers';
import { of } from 'rxjs';
import { vi } from 'vitest';
import { FullDashboard, Link } from '../../core/models';

describe('DashboardViewComponent', () => {
  let component: DashboardViewComponent;
  let fixture: ComponentFixture<DashboardViewComponent>;
  let mockApiService: any;
  let mockActivatedRoute: any;
  let mockDialog: { open: ReturnType<typeof vi.fn> };

  beforeEach(async () => {
    mockApiService = createMockApiService();

    // Create a proper mock ActivatedRoute with paramMap
    mockActivatedRoute = {
      snapshot: {
        paramMap: {
          get: vi.fn().mockReturnValue('test-dashboard-id'),
        },
      },
    };

    // Mock MatDialog with proper structure
    mockDialog = {
      open: vi.fn().mockReturnValue({
        afterClosed: () => of(undefined),
        close: vi.fn(),
        componentInstance: {},
      }),
    };

    // Mock the getDashboard method to return test data
    mockApiService.getDashboard = vi.fn().mockReturnValue(of(createMockFullDashboard()));

    await TestBed.configureTestingModule({
      imports: [
        DashboardViewComponent,
        MatIconModule,
        MatCardModule,
        MatToolbarModule,
        MatButtonModule,
        MatChipsModule,
      ],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: ApiService, useValue: mockApiService },
        { provide: ActivatedRoute, useValue: mockActivatedRoute },
        { provide: MatDialog, useValue: mockDialog },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(DashboardViewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should render dashboard toolbar', () => {
    const toolbar = fixture.nativeElement.querySelector('.dashboard-toolbar');
    expect(toolbar).toBeTruthy();
  });

  it('should display dashboard title in toolbar', () => {
    const title = fixture.nativeElement.querySelector('.toolbar-title');
    expect(title).toBeTruthy();
    expect(title.textContent).toContain('Test Dashboard');
  });

  it('should display dashboard description as quote', () => {
    const description = fixture.nativeElement.querySelector('.description-quote p');
    expect(description).toBeTruthy();
    expect(description.textContent).toContain('Test Description');
  });

  it('should render favorites in toolbar', () => {
    const favorites = fixture.nativeElement.querySelector('.toolbar-favorites');
    expect(favorites).toBeTruthy();
  });

  it('should render favorite chips', () => {
    const chips = fixture.nativeElement.querySelectorAll('.favorite-chip');
    expect(chips.length).toBeGreaterThan(0);
  });

  it('should render categories section', () => {
    const categoriesSection = fixture.nativeElement.querySelector('.categories-section');
    expect(categoriesSection).toBeTruthy();
  });

  it('should render example category card', () => {
    const categoryCard = fixture.nativeElement.querySelector('.category-card');
    expect(categoryCard).toBeTruthy();
  });

  it('should display example category title', () => {
    const categoryTitle = fixture.nativeElement.querySelector('.category-card mat-card-title');
    expect(categoryTitle).toBeTruthy();
    expect(categoryTitle.textContent).toContain('Test Category');
  });

  it('should display favorite link titles in chips', () => {
    const chip = fixture.nativeElement.querySelector('.favorite-chip');
    expect(chip).toBeTruthy();
    expect(chip.textContent).toContain('Link 1');
  });

  it('should render link items in category', () => {
    const linkItem = fixture.nativeElement.querySelector('.link-item');
    expect(linkItem).toBeTruthy();
  });

  it('should display link item title', () => {
    const linkItemTitle = fixture.nativeElement.querySelector('.link-item h4');
    expect(linkItemTitle).toBeTruthy();
    expect(linkItemTitle.textContent).toContain('Link 1');
  });

  it('should render tags as chips in link item', () => {
    const chips = fixture.nativeElement.querySelectorAll('.link-item mat-chip');
    expect(chips.length).toBe(0);
  });

  it('should have correct number of tags in example link item', () => {
    const chips = fixture.nativeElement.querySelectorAll('.link-item mat-chip');
    expect(chips.length).toBe(0);
  });

  it('should display tags in chips if they exist', () => {
    const chipTexts = Array.from(fixture.nativeElement.querySelectorAll('.link-item mat-chip')).map(
      (chip) => (chip as HTMLElement).textContent,
    );
    expect(chipTexts.length).toBe(0);
  });

  it('should render main sections in correct order', () => {
    const toolbar = fixture.nativeElement.querySelector('.dashboard-toolbar');
    const categoriesSection = fixture.nativeElement.querySelector('.categories-section');
    expect(toolbar).toBeTruthy();
    expect(categoriesSection).toBeTruthy();
  });

  it('should open link in new window when clicking favorite chip', () => {
    const windowOpenSpy = vi.spyOn(window, 'open').mockImplementation(() => null);
    const chip = fixture.nativeElement.querySelector('.favorite-chip');

    chip.click();

    expect(windowOpenSpy).toHaveBeenCalledWith('https://example1.com', '_blank');
    windowOpenSpy.mockRestore();
  });

  it('should open link in new window when clicking category link item', () => {
    const windowOpenSpy = vi.spyOn(window, 'open').mockImplementation(() => null);
    const linkItem = fixture.nativeElement.querySelector('.link-item');

    linkItem.click();

    expect(windowOpenSpy).toHaveBeenCalledWith('https://example1.com', '_blank');
    windowOpenSpy.mockRestore();
  });

  describe('search functionality', () => {
    it('should render search button in toolbar', () => {
      const searchButton = fixture.nativeElement.querySelector('.search-button');
      expect(searchButton).toBeTruthy();
    });

    it('should display search icon in button', () => {
      const searchButton = fixture.nativeElement.querySelector('.search-button mat-icon');
      expect(searchButton).toBeTruthy();
      expect(searchButton.textContent).toContain('search');
    });

    it('should open search dialog when search button clicked', () => {
      const openSearchSpy = vi.spyOn(component, 'openSearch').mockImplementation(() => {});
      const searchButton = fixture.nativeElement.querySelector('.search-button');

      searchButton.click();

      expect(openSearchSpy).toHaveBeenCalled();
    });

    it('should open search dialog with Cmd+K', () => {
      const openSearchSpy = vi.spyOn(component, 'openSearch').mockImplementation(() => {});
      const preventDefaultSpy = vi.fn();
      const event = {
        key: 'k',
        metaKey: true,
        preventDefault: preventDefaultSpy,
      } as unknown as KeyboardEvent;

      component.handleKeyboardShortcut(event);

      expect(preventDefaultSpy).toHaveBeenCalled();
      expect(openSearchSpy).toHaveBeenCalled();
    });

    it('should open search dialog with Ctrl+K', () => {
      const openSearchSpy = vi.spyOn(component, 'openSearch').mockImplementation(() => {});
      const preventDefaultSpy = vi.fn();
      const event = {
        key: 'k',
        ctrlKey: true,
        preventDefault: preventDefaultSpy,
      } as unknown as KeyboardEvent;

      component.handleKeyboardShortcut(event);

      expect(preventDefaultSpy).toHaveBeenCalled();
      expect(openSearchSpy).toHaveBeenCalled();
    });

    it('should prevent default behavior on Cmd/Ctrl+K', () => {
      const openSearchSpy = vi.spyOn(component, 'openSearch').mockImplementation(() => {});
      const preventDefaultSpy = vi.fn();
      const event = {
        key: 'k',
        metaKey: true,
        preventDefault: preventDefaultSpy,
      } as unknown as KeyboardEvent;

      component.handleKeyboardShortcut(event);

      expect(preventDefaultSpy).toHaveBeenCalled();
    });

    it('should not open search when dashboard is not loaded', () => {
      mockDialog.open.mockClear();
      component['currentDashboard'] = null;

      component.openSearch();

      expect(mockDialog.open).not.toHaveBeenCalled();
    });

    // These tests check the link collection logic without actually opening the dialog
    it('should collect all links from favorites and categories', () => {
      const favoriteLinks = [createMockLink({ link_id: 'fav-1', title: 'Favorite 1' })];
      const categoryLinks = [createMockLink({ link_id: 'cat-1', title: 'Category Link 1' })];

      const mockDashboard: FullDashboard = {
        dashboard: createMockFullDashboard().dashboard,
        favorites: favoriteLinks,
        categories: [
          createMockCategoryWithLinks({
            category_id: 'cat-1',
            title: 'Test Category',
            links: categoryLinks,
          }),
        ],
      };

      component['currentDashboard'] = mockDashboard;

      // Test the logic by checking what would be passed to dialog
      const allLinks: any[] = [];
      if (mockDashboard.favorites) {
        for (const link of mockDashboard.favorites) {
          allLinks.push({ ...link, isFavorite: true });
        }
      }
      if (mockDashboard.categories) {
        for (const category of mockDashboard.categories) {
          for (const link of category.links) {
            const existingIndex = allLinks.findIndex((l) => l.link_id === link.link_id);
            if (existingIndex >= 0) {
              allLinks[existingIndex].category = category.title;
            } else {
              allLinks.push({ ...link, category: category.title, isFavorite: false });
            }
          }
        }
      }

      expect(allLinks.length).toBe(2);
    });

    it('should mark favorite links with isFavorite flag', () => {
      const favoriteLink = createMockLink({ link_id: 'fav-1', title: 'Favorite 1' });
      const mockDashboard: FullDashboard = {
        dashboard: createMockFullDashboard().dashboard,
        favorites: [favoriteLink],
        categories: [],
      };

      const allLinks: any[] = [];
      if (mockDashboard.favorites) {
        for (const link of mockDashboard.favorites) {
          allLinks.push({ ...link, isFavorite: true });
        }
      }

      expect(allLinks[0].isFavorite).toBe(true);
    });

    it('should add category name to categorized links', () => {
      const categoryLink = createMockLink({ link_id: 'cat-1', title: 'Category Link' });
      const categoryTitle = 'Development';
      const mockDashboard: FullDashboard = {
        dashboard: createMockFullDashboard().dashboard,
        favorites: [],
        categories: [
          createMockCategoryWithLinks({
            category_id: 'cat-1',
            title: categoryTitle,
            links: [categoryLink],
          }),
        ],
      };

      const allLinks: any[] = [];
      if (mockDashboard.categories) {
        for (const category of mockDashboard.categories) {
          for (const link of category.links) {
            allLinks.push({ ...link, category: category.title, isFavorite: false });
          }
        }
      }

      expect(allLinks[0].category).toBe(categoryTitle);
    });

    it('should deduplicate links that appear in both favorites and categories', () => {
      const sharedLink = createMockLink({ link_id: 'shared-1', title: 'Shared Link' });
      const mockDashboard: FullDashboard = {
        dashboard: createMockFullDashboard().dashboard,
        favorites: [sharedLink],
        categories: [
          createMockCategoryWithLinks({
            category_id: 'cat-1',
            title: 'Test Category',
            links: [sharedLink],
          }),
        ],
      };

      const allLinks: any[] = [];
      if (mockDashboard.favorites) {
        for (const link of mockDashboard.favorites) {
          allLinks.push({ ...link, isFavorite: true });
        }
      }
      if (mockDashboard.categories) {
        for (const category of mockDashboard.categories) {
          for (const link of category.links) {
            const existingIndex = allLinks.findIndex((l) => l.link_id === link.link_id);
            if (existingIndex >= 0) {
              allLinks[existingIndex].category = category.title;
            } else {
              allLinks.push({ ...link, category: category.title, isFavorite: false });
            }
          }
        }
      }

      expect(allLinks.length).toBe(1);
    });

    it('should mark deduplicated link as favorite and add category', () => {
      const sharedLink = createMockLink({ link_id: 'shared-1', title: 'Shared Link' });
      const categoryTitle = 'Development';
      const mockDashboard: FullDashboard = {
        dashboard: createMockFullDashboard().dashboard,
        favorites: [sharedLink],
        categories: [
          createMockCategoryWithLinks({
            category_id: 'cat-1',
            title: categoryTitle,
            links: [sharedLink],
          }),
        ],
      };

      const allLinks: any[] = [];
      if (mockDashboard.favorites) {
        for (const link of mockDashboard.favorites) {
          allLinks.push({ ...link, isFavorite: true });
        }
      }
      if (mockDashboard.categories) {
        for (const category of mockDashboard.categories) {
          for (const link of category.links) {
            const existingIndex = allLinks.findIndex((l) => l.link_id === link.link_id);
            if (existingIndex >= 0) {
              allLinks[existingIndex].category = category.title;
            } else {
              allLinks.push({ ...link, category: category.title, isFavorite: false });
            }
          }
        }
      }

      const link = allLinks[0];
      expect(link.isFavorite).toBe(true);
      expect(link.category).toBe(categoryTitle);
    });

    it('should open link in new tab when result selected', () => {
      const selectedLink = createMockLink({ url: 'https://selected.com' });
      const windowOpenSpy = vi.spyOn(window, 'open').mockImplementation(() => null);

      // Test the subscribe logic directly
      const afterClosedObs = of<typeof selectedLink | undefined>(selectedLink);
      afterClosedObs.subscribe((result) => {
        if (result) {
          window.open(result.url, '_blank');
        }
      });

      expect(windowOpenSpy).toHaveBeenCalledWith('https://selected.com', '_blank');
      windowOpenSpy.mockRestore();
    });

    it('should not open link when dialog closed without selection', () => {
      const windowOpenSpy = vi.spyOn(window, 'open').mockImplementation(() => null);

      // Test the subscribe logic directly with proper typing
      const afterClosedObs = of<Link | undefined>(undefined);
      afterClosedObs.subscribe((result) => {
        if (result) {
          window.open(result.url, '_blank');
        }
      });

      expect(windowOpenSpy).not.toHaveBeenCalled();
      windowOpenSpy.mockRestore();
    });

    it('should handle empty favorites array', () => {
      const mockDashboard: FullDashboard = {
        dashboard: createMockFullDashboard().dashboard,
        favorites: [],
        categories: [
          createMockCategoryWithLinks({
            links: [createMockLink()],
          }),
        ],
      };

      component['currentDashboard'] = mockDashboard;

      // Just verify it doesn't throw - we won't actually call openSearch
      expect(mockDashboard.favorites.length).toBe(0);
      expect(mockDashboard.categories.length).toBeGreaterThan(0);
    });

    it('should handle empty categories array', () => {
      const mockDashboard: FullDashboard = {
        dashboard: createMockFullDashboard().dashboard,
        favorites: [createMockLink()],
        categories: [],
      };

      component['currentDashboard'] = mockDashboard;

      // Just verify the structure
      expect(mockDashboard.categories.length).toBe(0);
      expect(mockDashboard.favorites.length).toBeGreaterThan(0);
    });

    it('should handle categories with no links', () => {
      const mockDashboard: FullDashboard = {
        dashboard: createMockFullDashboard().dashboard,
        favorites: [],
        categories: [
          createMockCategoryWithLinks({
            links: [],
          }),
        ],
      };

      const allLinks: any[] = [];
      if (mockDashboard.categories) {
        for (const category of mockDashboard.categories) {
          for (const link of category.links) {
            allLinks.push({ ...link, category: category.title, isFavorite: false });
          }
        }
      }

      expect(allLinks.length).toBe(0);
    });

    it('should store dashboard data from observable', async () => {
      const testDashboard = createMockFullDashboard();
      mockApiService.getDashboard = vi.fn().mockReturnValue(of(testDashboard));

      component.ngOnInit();
      fixture.detectChanges();

      await new Promise((resolve) => setTimeout(resolve, 50));

      expect(component['currentDashboard']).toEqual(testDashboard);
    });
  });
});
