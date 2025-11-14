import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { Dashboard, Category, Link, Tag, Favorite, FullDashboard, ApiResponse } from '../models';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root',
})
export class ApiService {
  private http = inject(HttpClient);
  private readonly API_BASE = environment.apiBaseUrl;

  private httpOptions = {
    headers: new HttpHeaders({
      'Content-Type': 'application/json',
    }),
  };

  // Dashboard endpoints
  listDashboards(): Observable<Dashboard[]> {
    return this.http
      .get<ApiResponse<{ dashboards: Dashboard[] }>>(`${this.API_BASE}/dashboard/list`)
      .pipe(map((response) => response.data?.dashboards || []));
  }

  getDashboard(id: string): Observable<FullDashboard> {
    return this.http
      .get<ApiResponse<FullDashboard>>(`${this.API_BASE}/dashboard/${id}`)
      .pipe(map((response) => response.data!));
  }

  createDashboard(dashboard: Partial<Dashboard>): Observable<Dashboard> {
    return this.http
      .post<ApiResponse<Dashboard>>(`${this.API_BASE}/dashboard`, dashboard, this.httpOptions)
      .pipe(map((response) => response.data!));
  }

  updateDashboard(id: string, dashboard: Partial<Dashboard>): Observable<Dashboard> {
    return this.http
      .put<ApiResponse<Dashboard>>(`${this.API_BASE}/dashboard/${id}`, dashboard, this.httpOptions)
      .pipe(map((response) => response.data!));
  }

  deleteDashboard(id: string): Observable<void> {
    return this.http
      .delete<ApiResponse<void>>(`${this.API_BASE}/dashboard/${id}`)
      .pipe(map(() => undefined));
  }

  // Category endpoints
  getCategory(id: string): Observable<Category> {
    return this.http
      .get<ApiResponse<Category>>(`${this.API_BASE}/category/${id}`)
      .pipe(map((response) => response.data!));
  }

  createCategory(category: Partial<Category>): Observable<Category> {
    return this.http
      .post<ApiResponse<Category>>(`${this.API_BASE}/category`, category, this.httpOptions)
      .pipe(map((response) => response.data!));
  }

  updateCategory(id: string, category: Partial<Category>): Observable<Category> {
    return this.http
      .put<ApiResponse<Category>>(`${this.API_BASE}/category/${id}`, category, this.httpOptions)
      .pipe(map((response) => response.data!));
  }

  deleteCategory(id: string): Observable<void> {
    return this.http
      .delete<ApiResponse<void>>(`${this.API_BASE}/category/${id}`)
      .pipe(map(() => undefined));
  }

  // Link endpoints
  getLink(id: string): Observable<Link> {
    return this.http
      .get<ApiResponse<Link>>(`${this.API_BASE}/link/${id}`)
      .pipe(map((response) => response.data!));
  }

  createLink(link: Partial<Link>): Observable<Link> {
    return this.http
      .post<ApiResponse<Link>>(`${this.API_BASE}/link`, link, this.httpOptions)
      .pipe(map((response) => response.data!));
  }

  updateLink(id: string, link: Partial<Link>): Observable<Link> {
    return this.http
      .put<ApiResponse<Link>>(`${this.API_BASE}/link/${id}`, link, this.httpOptions)
      .pipe(map((response) => response.data!));
  }

  deleteLink(id: string): Observable<void> {
    return this.http
      .delete<ApiResponse<void>>(`${this.API_BASE}/link/${id}`)
      .pipe(map(() => undefined));
  }

  // Tag endpoints
  getTag(tagName: string): Observable<Tag> {
    return this.http
      .get<ApiResponse<Tag>>(`${this.API_BASE}/tag/${tagName}`)
      .pipe(map((response) => response.data!));
  }

  createTag(tag: Partial<Tag>): Observable<Tag> {
    return this.http
      .post<ApiResponse<Tag>>(`${this.API_BASE}/tag`, tag, this.httpOptions)
      .pipe(map((response) => response.data!));
  }

  updateTag(tagName: string, tag: Partial<Tag>): Observable<Tag> {
    return this.http
      .put<ApiResponse<Tag>>(`${this.API_BASE}/tag/${tagName}`, tag, this.httpOptions)
      .pipe(map((response) => response.data!));
  }

  deleteTag(tagName: string): Observable<void> {
    return this.http
      .delete<ApiResponse<void>>(`${this.API_BASE}/tag/${tagName}`)
      .pipe(map(() => undefined));
  }

  // Link-Tag association endpoints
  assignTagToLink(linkId: string, tag: Partial<Tag>): Observable<void> {
    return this.http
      .post<ApiResponse<void>>(`${this.API_BASE}/link/${linkId}/tag`, tag, this.httpOptions)
      .pipe(map(() => undefined));
  }

  removeTagFromLink(linkId: string, tagName: string): Observable<void> {
    return this.http
      .delete<ApiResponse<void>>(`${this.API_BASE}/link/${linkId}/tag/${tagName}`)
      .pipe(map(() => undefined));
  }

  // Favorite endpoints
  addFavorite(dashboardId: string, linkId: string): Observable<Favorite> {
    return this.http
      .post<
        ApiResponse<Favorite>
      >(`${this.API_BASE}/dashboard/${dashboardId}/favorites`, { dashboard_id: dashboardId, link_id: linkId }, this.httpOptions)
      .pipe(map((response) => response.data!));
  }

  removeFavorite(dashboardId: string, linkId: string): Observable<void> {
    return this.http
      .delete<ApiResponse<void>>(`${this.API_BASE}/dashboard/${dashboardId}/favorites`, {
        ...this.httpOptions,
        body: { dashboard_id: dashboardId, link_id: linkId },
      })
      .pipe(map(() => undefined));
  }

  reorderFavorites(dashboardId: string, favorites: { [linkId: string]: number }): Observable<void> {
    return this.http
      .put<
        ApiResponse<void>
      >(`${this.API_BASE}/dashboard/${dashboardId}/favorites`, { dashboard_id: dashboardId, favorites }, this.httpOptions)
      .pipe(map(() => undefined));
  }
}
