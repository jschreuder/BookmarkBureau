import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { ApiService } from './api.service';
import {
  Dashboard,
  CategoryWithLinks,
  Link,
  Tag,
  Favorite,
  FullDashboard,
  ApiResponse
} from '../models';
import { environment } from '../../../environments/environment';

describe('ApiService', () => {
  let service: ApiService;
  let httpMock: HttpTestingController;
  const apiBase = environment.apiBaseUrl;

  // Mock data
  const mockDashboard: Dashboard = {
    id: '123e4567-e89b-12d3-a456-426614174000',
    title: 'Home',
    description: 'Home dashboard',
    icon: 'home',
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z'
  };

  const mockLink: Link = {
    id: 'link-1',
    url: 'https://example.com',
    title: 'Example Site',
    description: 'An example site',
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z'
  };

  const mockCategory: CategoryWithLinks = {
    id: 'cat-1',
    dashboard_id: mockDashboard.id,
    title: 'Work',
    color: '#FF5722',
    sort_order: 1,
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
    links: [mockLink]
  };

  const mockTag: Tag = {
    tag_name: 'important',
    color: '#FF5722'
  };

  const mockFavorite: Favorite = {
    dashboard_id: mockDashboard.id,
    link_id: mockLink.id,
    sort_order: 1,
    created_at: '2024-01-01T00:00:00Z'
  };

  const mockFullDashboard: FullDashboard = {
    dashboard: mockDashboard,
    categories: [mockCategory],
    favorites: [mockLink]
  };

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [ApiService]
    });
    service = TestBed.inject(ApiService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  describe('Dashboard endpoints', () => {
    it('should list dashboards', () => {
      const mockResponse: ApiResponse<{ dashboards: Dashboard[] }> = {
        success: true,
        data: { dashboards: [mockDashboard] }
      };

      service.listDashboards().subscribe(dashboards => {
        expect(dashboards.length).toBe(1);
        expect(dashboards[0]).toEqual(mockDashboard);
      });

      const req = httpMock.expectOne(`${apiBase}/dashboard`);
      expect(req.request.method).toBe('GET');
      req.flush(mockResponse);
    });

    it('should return empty array when listDashboards returns null data', () => {
      const mockResponse: ApiResponse<{ dashboards: Dashboard[] }> = {
        success: true
      };

      service.listDashboards().subscribe(dashboards => {
        expect(dashboards).toEqual([]);
      });

      const req = httpMock.expectOne(`${apiBase}/dashboard`);
      req.flush(mockResponse);
    });

    it('should get single dashboard', () => {
      const mockResponse: ApiResponse<FullDashboard> = {
        success: true,
        data: mockFullDashboard
      };

      service.getDashboard(mockDashboard.id).subscribe(dashboard => {
        expect(dashboard).toEqual(mockFullDashboard);
      });

      const req = httpMock.expectOne(`${apiBase}/dashboard/${mockDashboard.id}`);
      expect(req.request.method).toBe('GET');
      req.flush(mockResponse);
    });

    it('should create dashboard', () => {
      const newDashboard: Partial<Dashboard> = {
        title: 'New Dashboard',
        description: 'New description'
      };
      const mockResponse: ApiResponse<Dashboard> = {
        success: true,
        data: mockDashboard
      };

      service.createDashboard(newDashboard).subscribe(dashboard => {
        expect(dashboard).toEqual(mockDashboard);
      });

      const req = httpMock.expectOne(`${apiBase}/dashboard`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual(newDashboard);
      req.flush(mockResponse);
    });

    it('should update dashboard', () => {
      const updates: Partial<Dashboard> = {
        title: 'Updated Title'
      };
      const mockResponse: ApiResponse<Dashboard> = {
        success: true,
        data: mockDashboard
      };

      service.updateDashboard(mockDashboard.id, updates).subscribe(dashboard => {
        expect(dashboard).toEqual(mockDashboard);
      });

      const req = httpMock.expectOne(`${apiBase}/dashboard/${mockDashboard.id}`);
      expect(req.request.method).toBe('PUT');
      expect(req.request.body).toEqual(updates);
      req.flush(mockResponse);
    });

    it('should delete dashboard', () => {
      const mockResponse: ApiResponse<void> = {
        success: true
      };

      service.deleteDashboard(mockDashboard.id).subscribe(result => {
        expect(result).toBeUndefined();
      });

      const req = httpMock.expectOne(`${apiBase}/dashboard/${mockDashboard.id}`);
      expect(req.request.method).toBe('DELETE');
      req.flush(mockResponse);
    });
  });

  describe('Category endpoints', () => {
    it('should get category', () => {
      const mockResponse: ApiResponse<CategoryWithLinks> = {
        success: true,
        data: mockCategory
      };

      service.getCategory(mockCategory.id).subscribe(category => {
        expect(category).toEqual(mockCategory);
      });

      const req = httpMock.expectOne(`${apiBase}/category/${mockCategory.id}`);
      expect(req.request.method).toBe('GET');
      req.flush(mockResponse);
    });

    it('should create category', () => {
      const newCategory: Partial<CategoryWithLinks> = {
        title: 'New Category'
      };
      const mockResponse: ApiResponse<CategoryWithLinks> = {
        success: true,
        data: mockCategory
      };

      service.createCategory(newCategory as any).subscribe(category => {
        expect(category).toEqual(mockCategory);
      });

      const req = httpMock.expectOne(`${apiBase}/category`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual(newCategory);
      req.flush(mockResponse);
    });

    it('should update category', () => {
      const updates: Partial<CategoryWithLinks> = {
        title: 'Updated Category'
      };
      const mockResponse: ApiResponse<CategoryWithLinks> = {
        success: true,
        data: mockCategory
      };

      service.updateCategory(mockCategory.id, updates as any).subscribe(category => {
        expect(category).toEqual(mockCategory);
      });

      const req = httpMock.expectOne(`${apiBase}/category/${mockCategory.id}`);
      expect(req.request.method).toBe('PUT');
      expect(req.request.body).toEqual(updates);
      req.flush(mockResponse);
    });

    it('should delete category', () => {
      const mockResponse: ApiResponse<void> = {
        success: true
      };

      service.deleteCategory(mockCategory.id).subscribe(result => {
        expect(result).toBeUndefined();
      });

      const req = httpMock.expectOne(`${apiBase}/category/${mockCategory.id}`);
      expect(req.request.method).toBe('DELETE');
      req.flush(mockResponse);
    });
  });

  describe('Link endpoints', () => {
    it('should get link', () => {
      const mockResponse: ApiResponse<Link> = {
        success: true,
        data: mockLink
      };

      service.getLink(mockLink.id).subscribe(link => {
        expect(link).toEqual(mockLink);
      });

      const req = httpMock.expectOne(`${apiBase}/link/${mockLink.id}`);
      expect(req.request.method).toBe('GET');
      req.flush(mockResponse);
    });

    it('should create link', () => {
      const newLink: Partial<Link> = {
        url: 'https://example.com',
        title: 'Example'
      };
      const mockResponse: ApiResponse<Link> = {
        success: true,
        data: mockLink
      };

      service.createLink(newLink).subscribe(link => {
        expect(link).toEqual(mockLink);
      });

      const req = httpMock.expectOne(`${apiBase}/link`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual(newLink);
      req.flush(mockResponse);
    });

    it('should update link', () => {
      const updates: Partial<Link> = {
        title: 'Updated Title'
      };
      const mockResponse: ApiResponse<Link> = {
        success: true,
        data: mockLink
      };

      service.updateLink(mockLink.id, updates).subscribe(link => {
        expect(link).toEqual(mockLink);
      });

      const req = httpMock.expectOne(`${apiBase}/link/${mockLink.id}`);
      expect(req.request.method).toBe('PUT');
      expect(req.request.body).toEqual(updates);
      req.flush(mockResponse);
    });

    it('should delete link', () => {
      const mockResponse: ApiResponse<void> = {
        success: true
      };

      service.deleteLink(mockLink.id).subscribe(result => {
        expect(result).toBeUndefined();
      });

      const req = httpMock.expectOne(`${apiBase}/link/${mockLink.id}`);
      expect(req.request.method).toBe('DELETE');
      req.flush(mockResponse);
    });
  });

  describe('Tag endpoints', () => {
    it('should get tag', () => {
      const mockResponse: ApiResponse<Tag> = {
        success: true,
        data: mockTag
      };

      service.getTag(mockTag.tag_name).subscribe(tag => {
        expect(tag).toEqual(mockTag);
      });

      const req = httpMock.expectOne(`${apiBase}/tag/${mockTag.tag_name}`);
      expect(req.request.method).toBe('GET');
      req.flush(mockResponse);
    });

    it('should create tag', () => {
      const newTag: Partial<Tag> = {
        tag_name: 'new-tag'
      };
      const mockResponse: ApiResponse<Tag> = {
        success: true,
        data: mockTag
      };

      service.createTag(newTag).subscribe(tag => {
        expect(tag).toEqual(mockTag);
      });

      const req = httpMock.expectOne(`${apiBase}/tag`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual(newTag);
      req.flush(mockResponse);
    });

    it('should update tag', () => {
      const updates: Partial<Tag> = {
        tag_name: 'updated-tag'
      };
      const mockResponse: ApiResponse<Tag> = {
        success: true,
        data: mockTag
      };

      service.updateTag(mockTag.tag_name, updates).subscribe(tag => {
        expect(tag).toEqual(mockTag);
      });

      const req = httpMock.expectOne(`${apiBase}/tag/${mockTag.tag_name}`);
      expect(req.request.method).toBe('PUT');
      expect(req.request.body).toEqual(updates);
      req.flush(mockResponse);
    });

    it('should delete tag', () => {
      const mockResponse: ApiResponse<void> = {
        success: true
      };

      service.deleteTag(mockTag.tag_name).subscribe(result => {
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
        success: true
      };
      const tagData: Partial<Tag> = { tag_name: 'important' };

      service.assignTagToLink(mockLink.id, tagData).subscribe(result => {
        expect(result).toBeUndefined();
      });

      const req = httpMock.expectOne(`${apiBase}/link/${mockLink.id}/tag`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual(tagData);
      req.flush(mockResponse);
    });

    it('should remove tag from link', () => {
      const mockResponse: ApiResponse<void> = {
        success: true
      };

      service.removeTagFromLink(mockLink.id, mockTag.tag_name).subscribe(result => {
        expect(result).toBeUndefined();
      });

      const req = httpMock.expectOne(`${apiBase}/link/${mockLink.id}/tag/${mockTag.tag_name}`);
      expect(req.request.method).toBe('DELETE');
      req.flush(mockResponse);
    });
  });

  describe('Favorite endpoints', () => {
    it('should add favorite', () => {
      const mockResponse: ApiResponse<Favorite> = {
        success: true,
        data: mockFavorite
      };

      service.addFavorite(mockDashboard.id, mockLink.id).subscribe(favorite => {
        expect(favorite).toEqual(mockFavorite);
      });

      const req = httpMock.expectOne(`${apiBase}/dashboard/${mockDashboard.id}/favorites`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual({
        dashboard_id: mockDashboard.id,
        link_id: mockLink.id
      });
      req.flush(mockResponse);
    });

    it('should remove favorite', () => {
      const mockResponse: ApiResponse<void> = {
        success: true
      };

      service.removeFavorite(mockDashboard.id, mockLink.id).subscribe(result => {
        expect(result).toBeUndefined();
      });

      const req = httpMock.expectOne(`${apiBase}/dashboard/${mockDashboard.id}/favorites`);
      expect(req.request.method).toBe('DELETE');
      expect(req.request.body).toEqual({
        dashboard_id: mockDashboard.id,
        link_id: mockLink.id
      });
      req.flush(mockResponse);
    });

    it('should reorder favorites', () => {
      const mockResponse: ApiResponse<void> = {
        success: true
      };
      const favoriteOrder = { 'link-1': 1, 'link-2': 2 };

      service.reorderFavorites(mockDashboard.id, favoriteOrder).subscribe(result => {
        expect(result).toBeUndefined();
      });

      const req = httpMock.expectOne(`${apiBase}/dashboard/${mockDashboard.id}/favorites`);
      expect(req.request.method).toBe('PUT');
      expect(req.request.body).toEqual({
        dashboard_id: mockDashboard.id,
        favorites: favoriteOrder
      });
      req.flush(mockResponse);
    });
  });

  describe('HTTP headers', () => {
    it('should set Content-Type header to application/json for POST requests', () => {
      const mockResponse: ApiResponse<Dashboard> = {
        success: true,
        data: mockDashboard
      };

      service.createDashboard({ title: 'Test' }).subscribe();

      const req = httpMock.expectOne(`${apiBase}/dashboard`);
      expect(req.request.headers.get('Content-Type')).toBe('application/json');
      req.flush(mockResponse);
    });

    it('should set Content-Type header to application/json for PUT requests', () => {
      const mockResponse: ApiResponse<Dashboard> = {
        success: true,
        data: mockDashboard
      };

      service.updateDashboard(mockDashboard.id, { title: 'Updated' }).subscribe();

      const req = httpMock.expectOne(`${apiBase}/dashboard/${mockDashboard.id}`);
      expect(req.request.headers.get('Content-Type')).toBe('application/json');
      req.flush(mockResponse);
    });
  });
});
