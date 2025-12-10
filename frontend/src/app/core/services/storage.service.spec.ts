import { TestBed } from '@angular/core/testing';
import { StorageService } from './storage.service';
import { vi } from 'vitest';

describe('StorageService', () => {
  let service: StorageService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(StorageService);
    localStorage.clear();
  });

  afterEach(() => {
    localStorage.clear();
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should set and get item from localStorage', () => {
    service.setItem('test-key', 'test-value');
    expect(service.getItem('test-key')).toBe('test-value');
    expect(localStorage.getItem('test-key')).toBe('test-value');
  });

  it('should remove item from localStorage', () => {
    service.setItem('test-key', 'test-value');
    service.removeItem('test-key');
    expect(service.getItem('test-key')).toBeNull();
    expect(localStorage.getItem('test-key')).toBeNull();
  });

  it('should clear all items from localStorage', () => {
    service.setItem('key1', 'value1');
    service.setItem('key2', 'value2');
    service.clear();
    expect(service.getItem('key1')).toBeNull();
    expect(service.getItem('key2')).toBeNull();
  });

  it('should return null for non-existent keys', () => {
    expect(service.getItem('non-existent')).toBeNull();
  });

  it('should handle localStorage being disabled by using memory storage', () => {
    // Mock localStorage.setItem to throw (simulating disabled localStorage)
    const originalSetItem = Storage.prototype.setItem;
    vi.spyOn(Storage.prototype, 'setItem').mockImplementation(function (
      this: Storage,
      key: string,
      value: string,
    ) {
      throw new Error('localStorage disabled');
    });

    // Create a new service instance that will detect localStorage is unavailable
    const newService = new StorageService();

    // Should fall back to memory storage
    newService.setItem('test', 'value');
    expect(newService.getItem('test')).toBe('value');

    // Memory storage operations
    newService.removeItem('test');
    expect(newService.getItem('test')).toBeNull();

    newService.setItem('key1', 'value1');
    newService.setItem('key2', 'value2');
    newService.clear();
    expect(newService.getItem('key1')).toBeNull();
    expect(newService.getItem('key2')).toBeNull();

    // Clean up
    Storage.prototype.setItem = originalSetItem;
  });
});
