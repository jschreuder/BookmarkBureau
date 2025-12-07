import { TestBed } from '@angular/core/testing';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { provideHttpClient } from '@angular/common/http';
import { vi } from 'vitest';
import { ApiService } from './api.service';
import {
  Dashboard,
  CategoryWithLinks,
  Link,
  Tag,
  Favorite,
  FullDashboard,
  ApiResponse,
} from '../models';
import { environment } from '../../../environments/environment';

describe('ApiService', () => {
  let service: ApiService;
  let httpMock: HttpTestingController;
  const apiBase = environment.apiBaseUrl;

  // Mock data
  const mockDashboard: Dashboard = {
    dashboard_id: '123e4567-e89b-12d3-a456-426614174000',
    title: 'Home',
    description: 'Home dashboard',
    icon: 'home',
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
  };

  const mockLink: Link = {
    link_id: 'link-1',
    url: 'https://example.com',
    title: 'Example Site',
    description: 'An example site',
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
  };

  const mockCategory: CategoryWithLinks = {
    category_id: 'cat-1',
    dashboard_id: mockDashboard.dashboard_id,
    title: 'Work',
    color: '#FF5722',
    sort_order: 1,
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
    links: [mockLink],
  };

  const mockTag: Tag = {
    tag_name: 'important',
    color: '#FF5722',
  };

  const mockFavorite: Favorite = {
    dashboard_id: mockDashboard.dashboard_id,
    link_id: mockLink.link_id,
    sort_order: 1,
    created_at: '2024-01-01T00:00:00Z',
  };

  const mockFullDashboard: FullDashboard = {
    dashboard: mockDashboard,
    categories: [mockCategory],
    favorites: [mockLink],
  };

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [ApiService, provideHttpClient(), provideHttpClientTesting()],
    });
    service = TestBed.inject(ApiService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
    TestBed.resetTestingModule();
  });

  describe('Dashboard endpoints', () => {
    it('should list dashboards', () => {
      const mockResponse: ApiResponse<{ dashboards: Dashboard[] }> = {
        success: true,
        data: { dashboards: [mockDashboard] },
      };

      service.listDashboards().subscribe((dashboards) => {
        expect(dashboards.length).toBe(1);
        expect(dashboards[0]).toEqual(mockDashboard);
      });

      const req = httpMock.expectOne(`${apiBase}/dashboard`);
      expect(req.request.method).toBe('GET');
      req.flush(mockResponse);
    });

    it('should return empty array when listDashboards returns null data', () => {
      const mockResponse: ApiResponse<{ dashboards: Dashboard[] }> = {
        success: true,
      };

      service.listDashboards().subscribe((dashboards) => {
        expect(dashboards).toEqual([]);
      });

      const req = httpMock.expectOne(`${apiBase}/dashboard`);
      req.flush(mockResponse);
    });

    it('should get full dashboard', () => {
      const mockResponse: ApiResponse<FullDashboard> = {
        success: true,
        data: mockFullDashboard,
      };

      service.getDashboard(mockDashboard.dashboard_id).subscribe((dashboard) => {
        expect(dashboard).toEqual(mockFullDashboard);
      });

      const req = httpMock.expectOne(`${apiBase}/${mockDashboard.dashboard_id}`);
      expect(req.request.method).toBe('GET');
      req.flush(mockResponse);
    });

    it('should get basic dashboard', () => {
      const mockResponse: ApiResponse<Dashboard> = {
        success: true,
        data: mockDashboard,
      };

      service.getDashboardBasic(mockDashboard.dashboard_id).subscribe((dashboard) => {
        expect(dashboard).toEqual(mockDashboard);
      });

      const req = httpMock.expectOne(`${apiBase}/dashboard/${mockDashboard.dashboard_id}`);
      expect(req.request.method).toBe('GET');
      req.flush(mockResponse);
    });

    it('should create dashboard', () => {
      const newDashboard: Partial<Dashboard> = {
        title: 'New Dashboard',
        description: 'New description',
      };
      const mockResponse: ApiResponse<Dashboard> = {
        success: true,
        data: mockDashboard,
      };

      service.createDashboard(newDashboard).subscribe((dashboard) => {
        expect(dashboard).toEqual(mockDashboard);
      });

      const req = httpMock.expectOne(`${apiBase}/dashboard`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual(newDashboard);
      req.flush(mockResponse);
    });

    it('should update dashboard', () => {
      const updates: Partial<Dashboard> = {
        title: 'Updated Title',
      };
      const mockResponse: ApiResponse<Dashboard> = {
        success: true,
        data: mockDashboard,
      };

      service.updateDashboard(mockDashboard.dashboard_id, updates).subscribe((dashboard) => {
        expect(dashboard).toEqual(mockDashboard);
      });

      const req = httpMock.expectOne(`${apiBase}/dashboard/${mockDashboard.dashboard_id}`);
      expect(req.request.method).toBe('PUT');
      expect(req.request.body).toEqual(updates);
      req.flush(mockResponse);
    });

    it('should delete dashboard', () => {
      const mockResponse: ApiResponse<void> = {
        success: true,
      };

      service.deleteDashboard(mockDashboard.dashboard_id).subscribe((result) => {
        expect(result).toBeUndefined();
      });

      const req = httpMock.expectOne(`${apiBase}/dashboard/${mockDashboard.dashboard_id}`);
      expect(req.request.method).toBe('DELETE');
      req.flush(mockResponse);
    });
  });

  describe('Category endpoints', () => {
    it('should get category', () => {
      const mockResponse: ApiResponse<CategoryWithLinks> = {
        success: true,
        data: mockCategory,
      };

      service.getCategory(mockCategory.category_id).subscribe((category) => {
        expect(category).toEqual(mockCategory);
      });

      const req = httpMock.expectOne(`${apiBase}/category/${mockCategory.category_id}`);
      expect(req.request.method).toBe('GET');
      req.flush(mockResponse);
    });

    it('should create category', () => {
      const newCategory: Partial<CategoryWithLinks> = {
        title: 'New Category',
      };
      const mockResponse: ApiResponse<CategoryWithLinks> = {
        success: true,
        data: mockCategory,
      };

      service.createCategory(newCategory as any).subscribe((category) => {
        expect(category).toEqual(mockCategory);
      });

      const req = httpMock.expectOne(`${apiBase}/category`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual(newCategory);
      req.flush(mockResponse);
    });

    it('should update category', () => {
      const updates: Partial<CategoryWithLinks> = {
        title: 'Updated Category',
      };
      const mockResponse: ApiResponse<CategoryWithLinks> = {
        success: true,
        data: mockCategory,
      };

      service.updateCategory(mockCategory.category_id, updates as any).subscribe((category) => {
        expect(category).toEqual(mockCategory);
      });

      const req = httpMock.expectOne(`${apiBase}/category/${mockCategory.category_id}`);
      expect(req.request.method).toBe('PUT');
      expect(req.request.body).toEqual(updates);
      req.flush(mockResponse);
    });

    it('should delete category', () => {
      const mockResponse: ApiResponse<void> = {
        success: true,
      };

      service.deleteCategory(mockCategory.category_id).subscribe((result) => {
        expect(result).toBeUndefined();
      });

      const req = httpMock.expectOne(`${apiBase}/category/${mockCategory.category_id}`);
      expect(req.request.method).toBe('DELETE');
      req.flush(mockResponse);
    });
  });

  describe('Link endpoints', () => {
    it('should get link', () => {
      const mockResponse: ApiResponse<Link> = {
        success: true,
        data: mockLink,
      };

      service.getLink(mockLink.link_id).subscribe((link) => {
        expect(link).toEqual(mockLink);
      });

      const req = httpMock.expectOne(`${apiBase}/link/${mockLink.link_id}`);
      expect(req.request.method).toBe('GET');
      req.flush(mockResponse);
    });

    it('should create link', () => {
      const newLink: Partial<Link> = {
        url: 'https://example.com',
        title: 'Example',
      };
      const mockResponse: ApiResponse<Link> = {
        success: true,
        data: mockLink,
      };

      service.createLink(newLink).subscribe((link) => {
        expect(link).toEqual(mockLink);
      });

      const req = httpMock.expectOne(`${apiBase}/link`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual(newLink);
      req.flush(mockResponse);
    });

    it('should update link', () => {
      const updates: Partial<Link> = {
        title: 'Updated Title',
      };
      const mockResponse: ApiResponse<Link> = {
        success: true,
        data: mockLink,
      };

      service.updateLink(mockLink.link_id, updates).subscribe((link) => {
        expect(link).toEqual(mockLink);
      });

      const req = httpMock.expectOne(`${apiBase}/link/${mockLink.link_id}`);
      expect(req.request.method).toBe('PUT');
      expect(req.request.body).toEqual(updates);
      req.flush(mockResponse);
    });

    it('should delete link', () => {
      const mockResponse: ApiResponse<void> = {
        success: true,
      };

      service.deleteLink(mockLink.link_id).subscribe((result) => {
        expect(result).toBeUndefined();
      });

      const req = httpMock.expectOne(`${apiBase}/link/${mockLink.link_id}`);
      expect(req.request.method).toBe('DELETE');
      req.flush(mockResponse);
    });
  });

  describe('Link-Category association compound operations', () => {
    it('should create link and add to category', () => {
      const newLink: Partial<Link> = {
        url: 'https://example.com',
        title: 'Example',
      };
      const linkResponse: ApiResponse<Link> = {
        success: true,
        data: mockLink,
      };
      const associationResponse: ApiResponse<void> = {
        success: true,
      };

      service.createLinkInCategory(mockCategory.category_id, newLink).subscribe((link) => {
        expect(link).toEqual(mockLink);
      });

      // First request: create link
      const createReq = httpMock.expectOne(`${apiBase}/link`);
      expect(createReq.request.method).toBe('POST');
      expect(createReq.request.body).toEqual(newLink);
      createReq.flush(linkResponse);

      // Second request: add link to category
      const associateReq = httpMock.expectOne(
        `${apiBase}/category/${mockCategory.category_id}/link`,
      );
      expect(associateReq.request.method).toBe('POST');
      expect(associateReq.request.body).toEqual({ link_id: mockLink.link_id });
      associateReq.flush(associationResponse);
    });

    it('should handle error when creating link fails', () => {
      const newLink: Partial<Link> = {
        url: 'https://example.com',
        title: 'Example',
      };

      const errorSpy = vi.fn();
      service.createLinkInCategory(mockCategory.category_id, newLink).subscribe({
        error: errorSpy,
      });

      const createReq = httpMock.expectOne(`${apiBase}/link`);
      createReq.error(new ErrorEvent('Network error'));

      expect(errorSpy).toHaveBeenCalled();
    });

    it('should handle error when associating link to category fails', () => {
      const newLink: Partial<Link> = {
        url: 'https://example.com',
        title: 'Example',
      };
      const linkResponse: ApiResponse<Link> = {
        success: true,
        data: mockLink,
      };

      const errorSpy = vi.fn();
      service.createLinkInCategory(mockCategory.category_id, newLink).subscribe({
        error: errorSpy,
      });

      // First request succeeds
      const createReq = httpMock.expectOne(`${apiBase}/link`);
      createReq.flush(linkResponse);

      // Second request fails
      const associateReq = httpMock.expectOne(
        `${apiBase}/category/${mockCategory.category_id}/link`,
      );
      associateReq.error(new ErrorEvent('Association failed'));

      expect(errorSpy).toHaveBeenCalled();
    });
  });

  describe('Link-Favorites compound operations', () => {
    it('should create link and add to favorites', () => {
      const newLink: Partial<Link> = {
        url: 'https://example.com',
        title: 'Example',
      };
      const linkResponse: ApiResponse<Link> = {
        success: true,
        data: mockLink,
      };
      const favoriteResponse: ApiResponse<Favorite> = {
        success: true,
        data: mockFavorite,
      };

      service.createLinkAsFavorite(mockDashboard.dashboard_id, newLink).subscribe((link) => {
        expect(link).toEqual(mockLink);
      });

      // First request: create link
      const createReq = httpMock.expectOne(`${apiBase}/link`);
      expect(createReq.request.method).toBe('POST');
      expect(createReq.request.body).toEqual(newLink);
      createReq.flush(linkResponse);

      // Second request: add link to favorites
      const favoriteReq = httpMock.expectOne(
        `${apiBase}/dashboard/${mockDashboard.dashboard_id}/favorite`,
      );
      expect(favoriteReq.request.method).toBe('POST');
      expect(favoriteReq.request.body).toEqual({
        dashboard_id: mockDashboard.dashboard_id,
        link_id: mockLink.link_id,
      });
      favoriteReq.flush(favoriteResponse);
    });

    it('should handle error when creating link fails in favorites', () => {
      const newLink: Partial<Link> = {
        url: 'https://example.com',
        title: 'Example',
      };

      const errorSpy = vi.fn();
      service.createLinkAsFavorite(mockDashboard.dashboard_id, newLink).subscribe({
        error: errorSpy,
      });

      const createReq = httpMock.expectOne(`${apiBase}/link`);
      createReq.error(new ErrorEvent('Network error'));

      expect(errorSpy).toHaveBeenCalled();
    });

    it('should handle error when adding link to favorites fails', () => {
      const newLink: Partial<Link> = {
        url: 'https://example.com',
        title: 'Example',
      };
      const linkResponse: ApiResponse<Link> = {
        success: true,
        data: mockLink,
      };

      const errorSpy = vi.fn();
      service.createLinkAsFavorite(mockDashboard.dashboard_id, newLink).subscribe({
        error: errorSpy,
      });

      // First request succeeds
      const createReq = httpMock.expectOne(`${apiBase}/link`);
      createReq.flush(linkResponse);

      // Second request fails
      const favoriteReq = httpMock.expectOne(
        `${apiBase}/dashboard/${mockDashboard.dashboard_id}/favorite`,
      );
      favoriteReq.error(new ErrorEvent('Favorite creation failed'));

      expect(errorSpy).toHaveBeenCalled();
    });
  });

  describe('Tag endpoints', () => {
    it('should get tag', () => {
      const mockResponse: ApiResponse<Tag> = {
        success: true,
        data: mockTag,
      };

      service.getTag(mockTag.tag_name).subscribe((tag) => {
        expect(tag).toEqual(mockTag);
      });

      const req = httpMock.expectOne(`${apiBase}/tag/${mockTag.tag_name}`);
      expect(req.request.method).toBe('GET');
      req.flush(mockResponse);
    });

    it('should create tag', () => {
      const newTag: Partial<Tag> = {
        tag_name: 'new-tag',
      };
      const expectedResponse: Tag = {
        tag_name: 'new-tag',
        color: undefined,
      };
      const mockResponse: ApiResponse<Tag> = {
        success: true,
        data: expectedResponse,
      };

      service.createTag(newTag).subscribe((tag) => {
        expect(tag).toEqual(expectedResponse);
      });

      const req = httpMock.expectOne(`${apiBase}/tag`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual({ tag_name: 'new-tag', color: undefined });
      req.flush(mockResponse);
    });

    it('should update tag', () => {
      const updates: Partial<Tag> = {
        tag_name: 'updated-tag',
      };
      const mockResponse: ApiResponse<Tag> = {
        success: true,
        data: mockTag,
      };

      service.updateTag(mockTag.tag_name, updates).subscribe((tag) => {
        expect(tag).toEqual(mockTag);
      });

      const req = httpMock.expectOne(`${apiBase}/tag/${mockTag.tag_name}`);
      expect(req.request.method).toBe('PUT');
      expect(req.request.body).toEqual(updates);
      req.flush(mockResponse);
    });

    it('should delete tag', () => {
      const mockResponse: ApiResponse<void> = {
        success: true,
      };

      service.deleteTag(mockTag.tag_name).subscribe((result) => {
        expect(result).toBeUndefined();
      });

      const req = httpMock.expectOne(`${apiBase}/tag/${mockTag.tag_name}`);
      expect(req.request.method).toBe('DELETE');
      req.flush(mockResponse);
    });
  });

  describe('Link-Tag association endpoints', () => {
    it('should assign tag to link', () => {
      const mockResponse: ApiResponse<void> = {
        success: true,
      };
      const tagData: Partial<Tag> = { tag_name: 'important' };

      service.assignTagToLink(mockLink.link_id, tagData).subscribe((result) => {
        expect(result).toBeUndefined();
      });

      const req = httpMock.expectOne(`${apiBase}/link/${mockLink.link_id}/tag`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual(tagData);
      req.flush(mockResponse);
    });

    it('should remove tag from link', () => {
      const mockResponse: ApiResponse<void> = {
        success: true,
      };

      service.removeTagFromLink(mockLink.link_id, mockTag.tag_name).subscribe((result) => {
        expect(result).toBeUndefined();
      });

      const req = httpMock.expectOne(`${apiBase}/link/${mockLink.link_id}/tag/${mockTag.tag_name}`);
      expect(req.request.method).toBe('DELETE');
      req.flush(mockResponse);
    });
  });

  describe('Favorite endpoints', () => {
    it('should add favorite', () => {
      const mockResponse: ApiResponse<Favorite> = {
        success: true,
        data: mockFavorite,
      };

      service.addFavorite(mockDashboard.dashboard_id, mockLink.link_id).subscribe((favorite) => {
        expect(favorite).toEqual(mockFavorite);
      });

      const req = httpMock.expectOne(`${apiBase}/dashboard/${mockDashboard.dashboard_id}/favorite`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual({
        dashboard_id: mockDashboard.dashboard_id,
        link_id: mockLink.link_id,
      });
      req.flush(mockResponse);
    });

    it('should remove favorite', () => {
      const mockResponse: ApiResponse<void> = {
        success: true,
      };

      service.removeFavorite(mockDashboard.dashboard_id, mockLink.link_id).subscribe((result) => {
        expect(result).toBeUndefined();
      });

      const req = httpMock.expectOne(
        `${apiBase}/dashboard/${mockDashboard.dashboard_id}/favorite/${mockLink.link_id}`,
      );
      expect(req.request.method).toBe('DELETE');
      expect(req.request.body).toBeNull();
      req.flush(mockResponse);
    });

    it('should reorder favorites', () => {
      const mockFavorites: Favorite[] = [
        {
          dashboard_id: mockDashboard.dashboard_id,
          link_id: 'link-1',
          sort_order: 1,
          created_at: '2025-11-15T00:00:00Z',
        },
        {
          dashboard_id: mockDashboard.dashboard_id,
          link_id: 'link-2',
          sort_order: 2,
          created_at: '2025-11-15T00:00:00Z',
        },
      ];
      const mockResponse: ApiResponse<Favorite[]> = {
        success: true,
        data: mockFavorites,
      };
      const links = [
        { link_id: 'link-1', sort_order: 1 },
        { link_id: 'link-2', sort_order: 2 },
      ];

      service.reorderFavorites(mockDashboard.dashboard_id, links).subscribe((result) => {
        expect(result).toEqual(mockFavorites);
      });

      const req = httpMock.expectOne(`${apiBase}/dashboard/${mockDashboard.dashboard_id}/favorite`);
      expect(req.request.method).toBe('PUT');
      expect(req.request.body).toEqual({
        dashboard_id: mockDashboard.dashboard_id,
        links,
      });
      req.flush(mockResponse);
    });
  });

  describe('HTTP headers', () => {
    it('should set Content-Type header to application/json for POST requests', () => {
      const mockResponse: ApiResponse<Dashboard> = {
        success: true,
        data: mockDashboard,
      };

      service.createDashboard({ title: 'Test' }).subscribe();

      const req = httpMock.expectOne(`${apiBase}/dashboard`);
      expect(req.request.headers.get('Content-Type')).toBe('application/json');
      req.flush(mockResponse);
    });

    it('should set Content-Type header to application/json for PUT requests', () => {
      const mockResponse: ApiResponse<Dashboard> = {
        success: true,
        data: mockDashboard,
      };

      service.updateDashboard(mockDashboard.dashboard_id, { title: 'Updated' }).subscribe();

      const req = httpMock.expectOne(`${apiBase}/dashboard/${mockDashboard.dashboard_id}`);
      expect(req.request.headers.get('Content-Type')).toBe('application/json');
      req.flush(mockResponse);
    });
  });
});
