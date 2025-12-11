import { Injectable } from '@angular/core';

/**
 * Storage service that provides an abstraction over localStorage
 * with fallback support for when localStorage is disabled
 */
@Injectable({
  providedIn: 'root',
})
export class StorageService {
  private readonly memoryStorage = new Map<string, string>();

  /**
   * Get an item from storage
   */
  getItem(key: string): string | null {
    if (this.isLocalStorageAvailable()) {
      return localStorage.getItem(key);
    }
    return this.memoryStorage.get(key) ?? null;
  }

  /**
   * Set an item in storage
   */
  setItem(key: string, value: string): void {
    if (this.isLocalStorageAvailable()) {
      localStorage.setItem(key, value);
    } else {
      this.memoryStorage.set(key, value);
    }
  }

  /**
   * Remove an item from storage
   */
  removeItem(key: string): void {
    if (this.isLocalStorageAvailable()) {
      localStorage.removeItem(key);
    } else {
      this.memoryStorage.delete(key);
    }
  }

  /**
   * Clear all items from storage
   */
  clear(): void {
    if (this.isLocalStorageAvailable()) {
      localStorage.clear();
    } else {
      this.memoryStorage.clear();
    }
  }

  /**
   * Check if localStorage is available
   * This handles cases where localStorage might be disabled or unavailable
   */
  private isLocalStorageAvailable(): boolean {
    try {
      const testKey = '__storage_test__';
      localStorage.setItem(testKey, 'test');
      localStorage.removeItem(testKey);
      return true;
    } catch {
      // localStorage is unavailable (disabled, private browsing, etc.)
      return false;
    }
  }
}
