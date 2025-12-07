import {
  Dashboard,
  Category,
  CategoryWithLinks,
  Link,
  Tag,
  Favorite,
  FullDashboard,
  ApiResponse,
} from '../app/core/models';
import { vi } from 'vitest';

/**
 * Mock Data Factories
 * Create consistent test data for unit tests
 */

export function createMockDashboard(overrides?: Partial<Dashboard>): Dashboard {
  return {
    dashboard_id: '123e4567-e89b-12d3-a456-426614174000',
    title: 'Test Dashboard',
    description: 'Test Description',
    icon: 'dashboard',
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
    ...overrides,
  };
}

export function createMockDashboards(count: number): Dashboard[] {
  return Array.from({ length: count }, (_, i) =>
    createMockDashboard({
      dashboard_id: `dashboard-${i + 1}`,
      title: `Dashboard ${i + 1}`,
    }),
  );
}

export function createMockCategory(overrides?: Partial<Category>): Category {
  return {
    category_id: '223e4567-e89b-12d3-a456-426614174000',
    dashboard_id: '123e4567-e89b-12d3-a456-426614174000',
    title: 'Test Category',
    color: '#FF5733',
    sort_order: 1,
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
    ...overrides,
  };
}

export function createMockCategories(count: number): Category[] {
  return Array.from({ length: count }, (_, i) =>
    createMockCategory({
      category_id: `category-${i + 1}`,
      title: `Category ${i + 1}`,
    }),
  );
}

export function createMockCategoryWithLinks(
  overrides?: Partial<CategoryWithLinks>,
): CategoryWithLinks {
  return {
    ...createMockCategory(),
    links: [],
    ...overrides,
  };
}

export function createMockLink(overrides?: Partial<Link>): Link {
  return {
    link_id: '323e4567-e89b-12d3-a456-426614174000',
    url: 'https://example.com',
    title: 'Test Link',
    description: 'Test link description',
    icon: 'link',
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
    tags: [],
    ...overrides,
  };
}

export function createMockLinks(count: number): Link[] {
  return Array.from({ length: count }, (_, i) =>
    createMockLink({
      link_id: `link-${i + 1}`,
      title: `Link ${i + 1}`,
      url: `https://example${i + 1}.com`,
    }),
  );
}

export function createMockTag(overrides?: Partial<Tag>): Tag {
  return {
    tag_name: 'test-tag',
    color: '#0099FF',
    ...overrides,
  };
}

export function createMockTags(count: number): Tag[] {
  return Array.from({ length: count }, (_, i) =>
    createMockTag({
      tag_name: `tag-${i + 1}`,
    }),
  );
}

export function createMockFavorite(overrides?: Partial<Favorite>): Favorite {
  return {
    dashboard_id: '123e4567-e89b-12d3-a456-426614174000',
    link_id: '323e4567-e89b-12d3-a456-426614174000',
    sort_order: 1,
    created_at: '2024-01-01T00:00:00Z',
    ...overrides,
  };
}

export function createMockFullDashboard(overrides?: Partial<FullDashboard>): FullDashboard {
  return {
    dashboard: createMockDashboard(),
    categories: [
      createMockCategoryWithLinks({
        links: createMockLinks(3),
      }),
    ],
    favorites: createMockLinks(2),
    ...overrides,
  };
}

export function createMockApiResponse<T>(
  data?: T,
  overrides?: Partial<ApiResponse<T>>,
): ApiResponse<T> {
  return {
    success: true,
    data,
    ...overrides,
  };
}

export function createMockApiErrorResponse(error: string): ApiResponse<null> {
  return {
    success: false,
    error,
  };
}

/**
 * Test Query Helpers
 * Simplify common DOM query operations
 */

export function queryByTestId(fixture: any, testId: string): HTMLElement | null {
  return fixture.nativeElement.querySelector(`[data-testid="${testId}"]`);
}

export function queryAllByTestId(fixture: any, testId: string): HTMLElement[] {
  return Array.from(fixture.nativeElement.querySelectorAll(`[data-testid="${testId}"]`));
}

export function queryBySelector(fixture: any, selector: string): HTMLElement | null {
  return fixture.nativeElement.querySelector(selector);
}

export function queryAllBySelector(fixture: any, selector: string): HTMLElement[] {
  return Array.from(fixture.nativeElement.querySelectorAll(selector));
}

/**
 * Service Mocks
 * Create mock versions of common services
 */

export function createMockApiService() {
  return {
    getDashboards: vi.fn(),
    getDashboard: vi.fn(),
    createDashboard: vi.fn(),
    updateDashboard: vi.fn(),
    deleteDashboard: vi.fn(),
    getCategories: vi.fn(),
    getCategory: vi.fn(),
    createCategory: vi.fn(),
    updateCategory: vi.fn(),
    deleteCategory: vi.fn(),
    getLinks: vi.fn(),
    getLink: vi.fn(),
    createLink: vi.fn(),
    updateLink: vi.fn(),
    deleteLink: vi.fn(),
    getTags: vi.fn(),
    getTag: vi.fn(),
    createTag: vi.fn(),
    updateTag: vi.fn(),
    deleteTag: vi.fn(),
    getFullDashboard: vi.fn(),
  };
}

export function createMockRouter() {
  return {
    navigate: vi.fn().mockResolvedValue(true),
    navigateByUrl: vi.fn().mockResolvedValue(true),
    createUrlTree: vi.fn(),
  };
}

export function createMockActivatedRoute(params: any = {}) {
  return {
    snapshot: {
      params,
      queryParams: {},
      data: {},
    },
    params: { subscribe: vi.fn() },
    queryParams: { subscribe: vi.fn() },
    data: { subscribe: vi.fn() },
  };
}

/**
 * Test Setup Helpers
 * Common setup operations for component tests
 */

export function detectChanges(fixture: any): void {
  fixture.detectChanges();
}

export function expectElementToExist(fixture: any, selector: string): boolean {
  const element = fixture.nativeElement.querySelector(selector);
  return element !== null;
}

export function expectElementText(fixture: any, selector: string, expectedText: string): boolean {
  const element = fixture.nativeElement.querySelector(selector);
  return element?.textContent?.includes(expectedText) ?? false;
}

export function clickElement(fixture: any, selector: string): void {
  const element = fixture.nativeElement.querySelector(selector);
  if (element) {
    element.click();
    fixture.detectChanges();
  }
}

export function clickByTestId(fixture: any, testId: string): void {
  const element = queryByTestId(fixture, testId);
  if (element) {
    element.click();
    fixture.detectChanges();
  }
}

export function setInputValue(fixture: any, selector: string, value: string): void {
  const input = fixture.nativeElement.querySelector(selector) as HTMLInputElement;
  if (input) {
    input.value = value;
    input.dispatchEvent(new Event('input'));
    input.dispatchEvent(new Event('change'));
    fixture.detectChanges();
  }
}

export function setInputValueByTestId(fixture: any, testId: string, value: string): void {
  const input = queryByTestId(fixture, testId) as HTMLInputElement;
  if (input) {
    input.value = value;
    input.dispatchEvent(new Event('input'));
    input.dispatchEvent(new Event('change'));
    fixture.detectChanges();
  }
}

/**
 * Test Data Constants
 * Common test data sets for reuse
 */

export const MOCK_DASHBOARD_ID = '123e4567-e89b-12d3-a456-426614174000';
export const MOCK_CATEGORY_ID = '223e4567-e89b-12d3-a456-426614174000';
export const MOCK_LINK_ID = '323e4567-e89b-12d3-a456-426614174000';
export const MOCK_TAG_NAME = 'test-tag';

export const MOCK_DASHBOARD = createMockDashboard();
export const MOCK_CATEGORY = createMockCategory();
export const MOCK_LINK = createMockLink();
export const MOCK_TAG = createMockTag();
