import { Injectable, inject } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';
import { tap } from 'rxjs/operators';
import { Tag } from '../models';
import { ApiService } from './api.service';

@Injectable({
  providedIn: 'root',
})
export class TagService {
  private apiService = inject(ApiService);
  private tagsSubject = new BehaviorSubject<Tag[]>([]);
  private loaded = false;

  tags$ = this.tagsSubject.asObservable();

  /**
   * Load tags from API if not already loaded
   */
  loadTags(): Observable<Tag[]> {
    if (this.loaded) {
      return this.tags$;
    }

    return this.apiService.listTags().pipe(
      tap((tags) => {
        this.tagsSubject.next(tags);
        this.loaded = true;
      }),
    );
  }

  /**
   * Force reload tags from API
   */
  reloadTags(): Observable<Tag[]> {
    this.loaded = false;
    return this.loadTags();
  }

  /**
   * Get current tags from cache
   */
  getTags(): Tag[] {
    return this.tagsSubject.value;
  }

  /**
   * Create a new tag and update cache
   */
  createTag(tag: Partial<Tag>): Observable<Tag> {
    return this.apiService.createTag(tag).pipe(
      tap((newTag) => {
        const currentTags = this.getTags();
        this.tagsSubject.next([...currentTags, newTag]);
      }),
    );
  }

  /**
   * Update an existing tag and update cache
   */
  updateTag(tagName: string, tag: Partial<Tag>): Observable<Tag> {
    return this.apiService.updateTag(tagName, tag).pipe(
      tap((updatedTag) => {
        const currentTags = this.getTags();
        const index = currentTags.findIndex((t) => t.tag_name === tagName);
        if (index !== -1) {
          const newTags = [...currentTags];
          newTags[index] = updatedTag;
          this.tagsSubject.next(newTags);
        }
      }),
    );
  }

  /**
   * Delete a tag and update cache
   */
  deleteTag(tagName: string): Observable<void> {
    return this.apiService.deleteTag(tagName).pipe(
      tap(() => {
        const currentTags = this.getTags();
        this.tagsSubject.next(currentTags.filter((t) => t.tag_name !== tagName));
      }),
    );
  }

  /**
   * Filter tags by partial name match (case-insensitive)
   */
  filterTags(search: string): Tag[] {
    if (!search) {
      return this.getTags();
    }
    const searchLower = search.toLowerCase();
    return this.getTags().filter((tag) => tag.tag_name.toLowerCase().includes(searchLower));
  }

  /**
   * Check if a tag exists by name (case-insensitive)
   */
  tagExists(tagName: string): boolean {
    const nameLower = tagName.toLowerCase();
    return this.getTags().some((tag) => tag.tag_name.toLowerCase() === nameLower);
  }
}
