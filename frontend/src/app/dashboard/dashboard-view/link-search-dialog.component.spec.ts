import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { vi } from 'vitest';
import {
  LinkSearchDialogComponent,
  LinkSearchDialogData,
  SearchResult,
} from './link-search-dialog.component';
import { Link, Tag } from '../../core/models';

describe('LinkSearchDialogComponent', () => {
  let component: LinkSearchDialogComponent;
  let fixture: ComponentFixture<LinkSearchDialogComponent>;
  let mockDialogRef: { close: ReturnType<typeof vi.fn>; keydownEvents: ReturnType<typeof vi.fn> };

  const mockTag1: Tag = { tag_name: 'typescript', color: '#3178C6' };
  const mockTag2: Tag = { tag_name: 'angular', color: '#DD0031' };
  const mockTag3: Tag = { tag_name: 'testing', color: '#00FF00' };

  const mockLinks: Link[] = [
    {
      link_id: 'link-1',
      url: 'https://example1.com',
      title: 'TypeScript Documentation',
      description: 'Official TypeScript documentation for developers',
      icon: 'book',
      created_at: '2024-01-01T00:00:00Z',
      updated_at: '2024-01-01T00:00:00Z',
      tags: [mockTag1],
    },
    {
      link_id: 'link-2',
      url: 'https://example2.com',
      title: 'Angular Guide',
      description: 'Complete guide to Angular framework',
      icon: 'article',
      created_at: '2024-01-02T00:00:00Z',
      updated_at: '2024-01-02T00:00:00Z',
      tags: [mockTag2],
    },
    {
      link_id: 'link-3',
      url: 'https://example3.com',
      title: 'Testing Best Practices',
      description: 'Learn about testing strategies and patterns',
      icon: 'check_circle',
      created_at: '2024-01-03T00:00:00Z',
      updated_at: '2024-01-03T00:00:00Z',
      tags: [mockTag3, mockTag1],
    },
    {
      link_id: 'link-4',
      url: 'https://example4.com',
      title: 'React Basics',
      description: 'Introduction to React library',
      icon: 'code',
      created_at: '2024-01-04T00:00:00Z',
      updated_at: '2024-01-04T00:00:00Z',
      tags: [],
    },
  ];

  const mockData: LinkSearchDialogData = {
    links: mockLinks,
  };

  beforeEach(async () => {
    mockDialogRef = {
      close: vi.fn(),
      keydownEvents: vi.fn().mockReturnValue({ subscribe: vi.fn() }),
    };

    await TestBed.configureTestingModule({
      imports: [LinkSearchDialogComponent],
      providers: [
        { provide: MatDialogRef, useValue: mockDialogRef },
        { provide: MAT_DIALOG_DATA, useValue: mockData },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(LinkSearchDialogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  describe('initialization', () => {
    it('should create', () => {
      expect(component).toBeTruthy();
    });

    it('should initialize with empty search query', () => {
      expect(component.searchQuery).toBe('');
    });

    it('should initialize with empty filtered links', () => {
      expect(component.filteredLinks).toEqual([]);
    });

    it('should initialize selectedIndex to 0', () => {
      expect(component.selectedIndex).toBe(0);
    });

    it('should display search input placeholder', () => {
      const input = fixture.nativeElement.querySelector('.search-input');
      expect(input).toBeTruthy();
      expect(input.placeholder).toContain('Search links');
    });

    it('should display search hint when no query', () => {
      const hint = fixture.nativeElement.querySelector('.search-hint');
      expect(hint).toBeTruthy();
      expect(hint.textContent).toContain('Start typing to search');
    });

    it('should display keyboard hints', () => {
      const hints = fixture.nativeElement.querySelectorAll('.keyboard-hints .hint');
      expect(hints.length).toBeGreaterThan(0);
    });
  });

  describe('search functionality', () => {
    it('should filter links by title', () => {
      component.searchQuery = 'Documentation';
      component.onSearchChange();
      fixture.detectChanges();

      expect(component.filteredLinks.length).toBe(1);
      expect(component.filteredLinks[0].title).toBe('TypeScript Documentation');
    });

    it('should filter links by description', () => {
      component.searchQuery = 'framework';
      component.onSearchChange();
      fixture.detectChanges();

      expect(component.filteredLinks.length).toBe(1);
      expect(component.filteredLinks[0].title).toBe('Angular Guide');
    });

    it('should filter links by tag name', () => {
      component.searchQuery = 'testing';
      component.onSearchChange();
      fixture.detectChanges();

      expect(component.filteredLinks.length).toBe(1);
      expect(component.filteredLinks[0].title).toBe('Testing Best Practices');
    });

    it('should be case insensitive', () => {
      component.searchQuery = 'ANGULAR';
      component.onSearchChange();
      fixture.detectChanges();

      expect(component.filteredLinks.length).toBe(1);
      expect(component.filteredLinks[0].title).toBe('Angular Guide');
    });

    it('should return empty array for no matches', () => {
      component.searchQuery = 'nonexistent';
      component.onSearchChange();
      fixture.detectChanges();

      expect(component.filteredLinks.length).toBe(0);
    });

    it('should return multiple matches', () => {
      component.searchQuery = 'typescript';
      component.onSearchChange();
      fixture.detectChanges();

      // Should match "TypeScript Documentation" by title and "Testing Best Practices" by tag
      expect(component.filteredLinks.length).toBe(2);
    });

    it('should trim whitespace from query', () => {
      component.searchQuery = '  angular  ';
      component.onSearchChange();
      fixture.detectChanges();

      expect(component.filteredLinks.length).toBe(1);
    });

    it('should return empty array for empty query', () => {
      component.searchQuery = '';
      component.onSearchChange();
      fixture.detectChanges();

      expect(component.filteredLinks.length).toBe(0);
    });

    it('should return empty array for whitespace-only query', () => {
      component.searchQuery = '   ';
      component.onSearchChange();
      fixture.detectChanges();

      expect(component.filteredLinks.length).toBe(0);
    });

    it('should prioritize title matches over description matches', () => {
      component.searchQuery = 'guide';
      component.onSearchChange();
      fixture.detectChanges();

      // "Angular Guide" has it in title, "Complete guide to Angular" has it in description
      const results = component.filteredLinks;
      expect(results.length).toBeGreaterThan(0);
      expect(results[0].title).toBe('Angular Guide');
    });

    it('should reset selectedIndex when search changes', () => {
      component.selectedIndex = 5;
      component.onSearchChange();

      expect(component.selectedIndex).toBe(0);
    });

    it('should limit results to 50 items', () => {
      const manyLinks: Link[] = Array.from({ length: 100 }, (_, i) => ({
        link_id: `link-${i}`,
        url: `https://example${i}.com`,
        title: `Test Link ${i}`,
        description: 'test description',
        icon: 'link',
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z',
        tags: [],
      }));

      component.data.links = manyLinks;
      component.searchQuery = 'test';
      component.onSearchChange();

      expect(component.filteredLinks.length).toBe(50);
    });
  });

  describe('UI rendering', () => {
    beforeEach(() => {
      // Reset data to original mockLinks before each test in this group
      component.data.links = [...mockLinks];
    });

    it('should display search results when query exists', () => {
      component.searchQuery = 'Documentation';
      component.onSearchChange();
      fixture.detectChanges();

      const results = fixture.nativeElement.querySelector('.search-results');
      expect(results).toBeTruthy();
    });

    it('should display no results message when no matches', () => {
      component.searchQuery = 'nonexistent';
      component.onSearchChange();
      fixture.detectChanges();

      const noResults = fixture.nativeElement.querySelector('.no-results');
      expect(noResults).toBeTruthy();
      expect(noResults.textContent).toContain('No results found');
      expect(noResults.textContent).toContain('nonexistent');
    });

    it('should render result items', () => {
      component.searchQuery = 'Documentation';
      component.onSearchChange();

      // Verify filtered links exist
      expect(component.filteredLinks.length).toBe(1);

      fixture.detectChanges();
      const resultItems = fixture.nativeElement.querySelectorAll('.result-item');
      expect(resultItems.length).toBe(1);
    });

    it('should display link title in result', () => {
      component.searchQuery = 'Documentation';
      component.onSearchChange();
      expect(component.filteredLinks.length).toBeGreaterThan(0);

      fixture.detectChanges();
      const title = fixture.nativeElement.querySelector('.result-title');
      expect(title).toBeTruthy();
      expect(title.textContent).toContain('TypeScript Documentation');
    });

    it('should display link description in result', () => {
      component.searchQuery = 'Documentation';
      component.onSearchChange();
      expect(component.filteredLinks.length).toBeGreaterThan(0);

      fixture.detectChanges();
      const description = fixture.nativeElement.querySelector('.result-description');
      expect(description).toBeTruthy();
      expect(description.textContent).toContain('Official TypeScript documentation');
    });

    it('should display tags in result', () => {
      component.searchQuery = 'Documentation';
      component.onSearchChange();
      expect(component.filteredLinks.length).toBeGreaterThan(0);

      fixture.detectChanges();
      const tags = fixture.nativeElement.querySelectorAll('.result-tags .tag');
      expect(tags.length).toBeGreaterThan(0);
    });

    it('should display favorite badge for favorite links', () => {
      const favoriteLink: SearchResult = {
        ...mockLinks[0],
        isFavorite: true,
      };
      component.data.links = [favoriteLink];
      component.searchQuery = 'Documentation';
      component.onSearchChange();
      fixture.detectChanges();

      const badge = fixture.nativeElement.querySelector('.favorite-badge');
      expect(badge).toBeTruthy();
      expect(badge.textContent).toContain('★');
    });

    it('should display category label when present', () => {
      const categorizedLink: SearchResult = {
        ...mockLinks[0],
        category: 'Development',
      };
      component.data.links = [categorizedLink];
      component.searchQuery = 'Documentation';
      component.onSearchChange();
      fixture.detectChanges();

      const categoryLabel = fixture.nativeElement.querySelector('.category-label');
      expect(categoryLabel).toBeTruthy();
      expect(categoryLabel.textContent).toContain('Development');
    });

    it('should truncate long descriptions', () => {
      const longDescLink: Link = {
        ...mockLinks[0],
        description: 'a'.repeat(100),
      };
      component.data.links = [longDescLink];
      component.searchQuery = 'TypeScript';
      component.onSearchChange();
      fixture.detectChanges();

      const description = fixture.nativeElement.querySelector('.result-description');
      expect(description.textContent).toContain('...');
    });

    it('should display icon when present', () => {
      component.searchQuery = 'Documentation';
      component.onSearchChange();
      fixture.detectChanges();

      const icon = fixture.nativeElement.querySelector('.result-item mat-icon');
      expect(icon).toBeTruthy();
      expect(icon.textContent).toContain('book');
    });

    it('should display default link icon when no icon present', () => {
      const noIconLink: Link = {
        ...mockLinks[0],
        icon: undefined,
      };
      component.data.links = [noIconLink];
      component.searchQuery = 'Documentation';
      component.onSearchChange();
      fixture.detectChanges();

      const icon = fixture.nativeElement.querySelector('.result-item mat-icon');
      expect(icon).toBeTruthy();
      expect(icon.textContent).toContain('link');
    });
  });

  describe('highlighting', () => {
    it('should highlight matching text in title', () => {
      component.searchQuery = 'Documentation';
      const highlighted = component.highlightText('TypeScript Documentation');

      expect(highlighted).toContain('<span class="highlight">');
    });

    it('should escape regex special characters', () => {
      const escaped = component.escapeRegex('test[regex]');
      expect(escaped).toBe('test\\[regex\\]');
    });

    it('should not highlight when no query', () => {
      component.searchQuery = '';
      const highlighted = component.highlightText('Test');

      expect(highlighted).toBe('Test');
    });

    it('should highlight multiple occurrences', () => {
      component.searchQuery = 'test';
      const highlighted = component.highlightText('test test test');
      const highlightedStr = String(highlighted);
      const matches = (highlightedStr.match(/class="highlight"/g) || []).length;

      expect(matches).toBe(3);
    });

    it('should sanitize HTML entities in link titles to prevent XSS', () => {
      component.searchQuery = 'test';
      const maliciousInput = '<script>alert("XSS")</script>test';
      const highlighted = component.highlightText(maliciousInput);
      const highlightedStr = String(highlighted);

      // Should escape the script tags from the link title
      expect(highlightedStr).not.toContain('<script>');
      expect(highlightedStr).toContain('&lt;script&gt;');
      // Should still highlight the search term
      expect(highlightedStr).toContain('<span class="highlight">test</span>');
    });

    it('should prevent regex injection via search query', () => {
      // Attempt to inject HTML through malicious text that matches a safe query
      component.searchQuery = 'test';
      const text = 'test)(<script>alert("XSS")</script>)(test';
      const highlighted = component.highlightText(text);
      const highlightedStr = String(highlighted);

      // Should not contain unescaped script tags - the malicious HTML should be escaped
      expect(highlightedStr).not.toContain('<script>alert');
      expect(highlightedStr).toContain('&lt;script&gt;');
      // Should still highlight the safe search term
      expect(highlightedStr).toContain('<span class="highlight">test</span>');
    });

    it('should escape regex special characters in query and treat them literally', () => {
      // Try to use regex special characters in the search query
      component.searchQuery = '$1'; // Potential regex backreference
      const text = 'Price: $100 for item';
      const highlighted = component.highlightText(text);
      const highlightedStr = String(highlighted);

      // The $ character in the query should be escaped and treated literally
      // So it WILL find and highlight the literal "$1" inside "$100"
      expect(highlightedStr).toContain('<span class="highlight">$1</span>00');
      // The highlighting should not break the text
      expect(highlightedStr).toContain('Price:');
    });

    it('should escape HTML tags in text content', () => {
      component.searchQuery = 'click';
      const maliciousText = 'click <img src=x onerror=alert(1)> here';
      const highlighted = component.highlightText(maliciousText);
      const highlightedStr = String(highlighted);

      // The malicious img tag should be escaped
      expect(highlightedStr).not.toContain('<img src=x onerror');
      expect(highlightedStr).toContain('&lt;img');
      // Should still highlight the search term
      expect(highlightedStr).toContain('<span class="highlight">click</span>');
    });
  });

  describe('keyboard navigation', () => {
    beforeEach(() => {
      component.searchQuery = 'test';
      component.filteredLinks = [mockLinks[0], mockLinks[1], mockLinks[2]];
      fixture.detectChanges();
    });

    it('should move selection down with ArrowDown', () => {
      const event = new KeyboardEvent('keydown', { key: 'ArrowDown' });
      Object.defineProperty(event, 'preventDefault', { value: vi.fn() });

      component.handleKeyboardEvent(event);

      expect(component.selectedIndex).toBe(1);
    });

    it('should move selection up with ArrowUp', () => {
      component.selectedIndex = 2;
      const event = new KeyboardEvent('keydown', { key: 'ArrowUp' });
      Object.defineProperty(event, 'preventDefault', { value: vi.fn() });

      component.handleKeyboardEvent(event);

      expect(component.selectedIndex).toBe(1);
    });

    it('should not go below 0 with ArrowUp', () => {
      component.selectedIndex = 0;
      const event = new KeyboardEvent('keydown', { key: 'ArrowUp' });
      Object.defineProperty(event, 'preventDefault', { value: vi.fn() });

      component.handleKeyboardEvent(event);

      expect(component.selectedIndex).toBe(0);
    });

    it('should not exceed max index with ArrowDown', () => {
      component.selectedIndex = 2;
      const event = new KeyboardEvent('keydown', { key: 'ArrowDown' });
      Object.defineProperty(event, 'preventDefault', { value: vi.fn() });

      component.handleKeyboardEvent(event);

      expect(component.selectedIndex).toBe(2);
    });

    it('should select link with Enter key', () => {
      component.selectedIndex = 1;
      const event = new KeyboardEvent('keydown', { key: 'Enter' });
      Object.defineProperty(event, 'preventDefault', { value: vi.fn() });

      component.handleKeyboardEvent(event);

      expect(mockDialogRef.close).toHaveBeenCalledWith(mockLinks[1]);
    });

    it('should not handle keyboard events when no results', () => {
      component.filteredLinks = [];
      component.selectedIndex = 0;
      const event = new KeyboardEvent('keydown', { key: 'ArrowDown' });

      component.handleKeyboardEvent(event);

      expect(component.selectedIndex).toBe(0);
    });

    it('should not handle keyboard events when no query', () => {
      component.searchQuery = '';
      component.selectedIndex = 0;
      const event = new KeyboardEvent('keydown', { key: 'ArrowDown' });

      component.handleKeyboardEvent(event);

      expect(component.selectedIndex).toBe(0);
    });

    it('should apply selected class to selected item', () => {
      component.searchQuery = 'test';
      component.filteredLinks = [mockLinks[0], mockLinks[1]];
      component.selectedIndex = 1;
      fixture.detectChanges();

      const items = fixture.nativeElement.querySelectorAll('.result-item');
      expect(items[1].classList.contains('selected')).toBe(true);
    });

    it('should update selectedIndex on mouseenter', () => {
      component.searchQuery = 'test';
      component.filteredLinks = [mockLinks[0], mockLinks[1]];
      fixture.detectChanges();

      const items = fixture.nativeElement.querySelectorAll('.result-item');
      items[1].dispatchEvent(new MouseEvent('mouseenter'));
      fixture.detectChanges();

      expect(component.selectedIndex).toBe(1);
    });
  });

  describe('link selection', () => {
    it('should close dialog with selected link', () => {
      const link = mockLinks[0];
      component.selectLink(link);

      expect(mockDialogRef.close).toHaveBeenCalledWith(link);
    });

    it('should select link on click', () => {
      component.searchQuery = 'TypeScript';
      component.onSearchChange();
      fixture.detectChanges();

      const resultItem = fixture.nativeElement.querySelector('.result-item');
      resultItem.click();

      expect(mockDialogRef.close).toHaveBeenCalledWith(component.filteredLinks[0]);
    });
  });

  describe('dialog controls', () => {
    it('should close dialog on close button click', () => {
      const closeButton = fixture.nativeElement.querySelector('.close-button');
      closeButton.click();

      expect(mockDialogRef.close).toHaveBeenCalled();
    });

    it('should close dialog without data when calling close()', () => {
      component.close();

      expect(mockDialogRef.close).toHaveBeenCalledWith();
    });
  });

  describe('filter logic', () => {
    beforeEach(() => {
      // Reset data to original mockLinks before each test in this group
      component.data.links = [...mockLinks];
    });

    it('should call filterLinks on search change', () => {
      const spy = vi.spyOn(component, 'filterLinks');
      component.searchQuery = 'test';
      component.onSearchChange();

      expect(spy).toHaveBeenCalledWith('test');
    });

    it('should handle links without tags', () => {
      const results = component.filterLinks('Basics');

      expect(results.length).toBe(1);
      expect(results[0].title).toBe('React Basics');
    });

    it('should handle links without description', () => {
      const linkWithoutDesc: Link = {
        ...mockLinks[0],
        description: '',
      };
      component.data.links = [linkWithoutDesc];

      const results = component.filterLinks('Documentation');

      expect(results.length).toBe(1);
    });

    it('should match partial words', () => {
      const results = component.filterLinks('Type');

      expect(results.length).toBeGreaterThan(0);
      expect(results[0].title).toContain('Type');
    });

    it('should match across multiple fields', () => {
      const results = component.filterLinks('typescript');

      // Should match title and tag
      expect(results.length).toBeGreaterThan(0);
    });
  });

  describe('edge cases', () => {
    it('should handle empty links array', () => {
      component.data.links = [];
      component.searchQuery = 'test';
      component.onSearchChange();

      expect(component.filteredLinks.length).toBe(0);
    });

    it('should handle special characters in search', () => {
      component.searchQuery = 'test-123';
      const results = component.filterLinks('test-123');

      expect(results).toEqual([]);
    });

    it('should handle very long search queries', () => {
      component.searchQuery = 'a'.repeat(1000);
      component.onSearchChange();

      expect(component.filteredLinks).toBeDefined();
    });

    it('should handle links with undefined fields gracefully', () => {
      const incompleteLink: any = {
        link_id: 'incomplete',
        url: 'https://example.com',
        title: 'Test',
      };
      component.data.links = [incompleteLink];
      component.searchQuery = 'test';

      expect(() => component.onSearchChange()).not.toThrow();
    });
  });
});
