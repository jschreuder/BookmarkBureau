import { TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { firstValueFrom } from 'rxjs';
import { vi } from 'vitest';
import { TagService } from './tag.service';
import { ApiService } from './api.service';
import { Tag } from '../models';

describe('TagService', () => {
  let service: TagService;
  let apiService: ApiService;

  const mockTags: Tag[] = [
    { tag_name: 'work', color: '#2196f3' },
    { tag_name: 'personal', color: '#4caf50' },
    { tag_name: 'urgent', color: '#f44336' },
  ];

  beforeEach(() => {
    const apiServiceMock = {
      listTags: vi.fn().mockReturnValue(of(mockTags)),
      createTag: vi.fn(),
      updateTag: vi.fn(),
      deleteTag: vi.fn(),
    };

    TestBed.configureTestingModule({
      providers: [TagService, { provide: ApiService, useValue: apiServiceMock }],
    });

    service = TestBed.inject(TagService);
    apiService = TestBed.inject(ApiService);
  });

  describe('loadTags', () => {
    it('should load tags from API and update cache', async () => {
      const tags = await firstValueFrom(service.loadTags());
      expect(tags).toEqual(mockTags);
      expect(service.getTags()).toEqual(mockTags);
    });

    it('should not call API if tags already loaded', async () => {
      await firstValueFrom(service.loadTags());
      vi.clearAllMocks();
      await firstValueFrom(service.loadTags());
      expect(apiService.listTags).not.toHaveBeenCalled();
    });
  });

  describe('reloadTags', () => {
    it('should force reload tags from API', async () => {
      await firstValueFrom(service.loadTags());
      vi.clearAllMocks();
      await firstValueFrom(service.reloadTags());
      expect(apiService.listTags).toHaveBeenCalled();
    });
  });

  describe('createTag', () => {
    it('should create tag and update cache', async () => {
      const newTag: Tag = { tag_name: 'new', color: '#ff9800' };
      vi.spyOn(apiService, 'createTag').mockReturnValue(of(newTag));

      await firstValueFrom(service.loadTags());
      await firstValueFrom(service.createTag(newTag));

      const tags = service.getTags();
      expect(tags.some((t) => t.tag_name === newTag.tag_name)).toBe(true);
      expect(tags.length).toBe(mockTags.length + 1);
    });
  });

  describe('updateTag', () => {
    it('should update tag in cache', async () => {
      const updatedTag: Tag = { tag_name: 'work', color: '#ff5722' };
      vi.spyOn(apiService, 'updateTag').mockReturnValue(of(updatedTag));

      await firstValueFrom(service.loadTags());
      await firstValueFrom(service.updateTag('work', updatedTag));

      const tags = service.getTags();
      const tag = tags.find((t) => t.tag_name === 'work');
      expect(tag).toEqual(updatedTag);
    });
  });

  describe('deleteTag', () => {
    it('should delete tag from cache', async () => {
      vi.spyOn(apiService, 'deleteTag').mockReturnValue(of(void 0));

      await firstValueFrom(service.loadTags());
      await firstValueFrom(service.deleteTag('work'));

      const tags = service.getTags();
      expect(tags.find((t) => t.tag_name === 'work')).toBeUndefined();
      expect(tags.length).toBe(mockTags.length - 1);
    });
  });

  describe('filterTags', () => {
    beforeEach(async () => {
      await firstValueFrom(service.loadTags());
    });

    it('should return all tags when search is empty', () => {
      const result = service.filterTags('');
      expect(result).toEqual(mockTags);
    });

    it('should filter tags by partial name match (case-insensitive)', () => {
      const result = service.filterTags('wo');
      expect(result.length).toBe(1);
      expect(result[0].tag_name).toBe('work');
    });

    it('should handle case-insensitive search', () => {
      const result = service.filterTags('WORK');
      expect(result.length).toBe(1);
      expect(result[0].tag_name).toBe('work');
    });
  });

  describe('tagExists', () => {
    beforeEach(async () => {
      await firstValueFrom(service.loadTags());
    });

    it('should return true for existing tag', () => {
      expect(service.tagExists('work')).toBe(true);
    });

    it('should return false for non-existing tag', () => {
      expect(service.tagExists('nonexistent')).toBe(false);
    });

    it('should be case-insensitive', () => {
      expect(service.tagExists('WORK')).toBe(true);
      expect(service.tagExists('WoRk')).toBe(true);
    });
  });
});
