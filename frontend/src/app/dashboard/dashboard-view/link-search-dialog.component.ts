import { Component, Inject, OnInit, HostListener } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { DomSanitizer, SafeHtml } from '@angular/platform-browser';
import { MAT_DIALOG_DATA, MatDialogRef, MatDialogModule } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatIconModule } from '@angular/material/icon';
import { MatListModule } from '@angular/material/list';
import { Link } from '../../core/models';

export interface LinkSearchDialogData {
  links: Link[];
}

export interface SearchResult extends Link {
  category?: string;
  isFavorite?: boolean;
}

@Component({
  selector: 'app-link-search-dialog',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatIconModule,
    MatListModule,
  ],
  template: `
    <div class="search-container">
      <div class="search-header">
        <mat-icon class="search-icon">search</mat-icon>
        <input
          type="text"
          class="search-input"
          placeholder="Search links by title, description, or tags..."
          [(ngModel)]="searchQuery"
          (ngModelChange)="onSearchChange()"
          #searchInput
          autofocus
        />
        <button class="close-button" (click)="close()">
          <mat-icon>close</mat-icon>
        </button>
      </div>

      <div class="search-results" *ngIf="searchQuery">
        <div *ngIf="filteredLinks.length === 0" class="no-results">
          <mat-icon>search_off</mat-icon>
          <p>No results found for "{{ searchQuery }}"</p>
        </div>

        <mat-list class="results-list" *ngIf="filteredLinks.length > 0">
          <mat-list-item
            *ngFor="let link of filteredLinks; let i = index"
            class="result-item"
            [class.selected]="i === selectedIndex"
            (click)="selectLink(link)"
            (mouseenter)="selectedIndex = i"
          >
            <mat-icon matListItemIcon *ngIf="link.icon">{{ link.icon }}</mat-icon>
            <mat-icon matListItemIcon *ngIf="!link.icon">link</mat-icon>
            <div matListItemTitle class="result-title">
              <span [innerHTML]="highlightText(link.title)"></span>
              <span class="favorite-badge" *ngIf="link.isFavorite">★</span>
            </div>
            <div matListItemLine class="result-meta">
              <span class="category-label" *ngIf="link.category">{{ link.category }}</span>
              <span class="result-description" *ngIf="link.description">
                {{ link.description | slice: 0 : 80
                }}{{ link.description.length > 80 ? '...' : '' }}
              </span>
              <span class="result-tags" *ngIf="link.tags && link.tags.length > 0">
                <span class="tag" *ngFor="let tag of link.tags" [style.color]="tag.color">
                  #{{ tag.tag_name }}
                </span>
              </span>
            </div>
          </mat-list-item>
        </mat-list>
      </div>

      <div class="search-hint" *ngIf="!searchQuery">
        <p>Start typing to search through all links...</p>
        <div class="keyboard-hints">
          <span class="hint"><kbd>↑</kbd> <kbd>↓</kbd> Navigate</span>
          <span class="hint"><kbd>↵</kbd> Open</span>
          <span class="hint"><kbd>Esc</kbd> Close</span>
        </div>
      </div>
    </div>
  `,
  styles: [
    `
      .search-container {
        width: 600px;
        max-width: 90vw;
        max-height: 70vh;
        display: flex;
        flex-direction: column;
        background: white;
        border-radius: 8px;
        overflow: hidden;
      }

      .search-header {
        display: flex;
        align-items: center;
        padding: 16px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.12);
        gap: 12px;
      }

      .search-icon {
        color: rgba(0, 0, 0, 0.54);
        flex-shrink: 0;
      }

      .search-input {
        flex: 1;
        border: none;
        outline: none;
        font-size: 16px;
        padding: 0;
        background: transparent;
      }

      .search-input::placeholder {
        color: rgba(0, 0, 0, 0.38);
      }

      .close-button {
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        transition: background-color 0.2s;
      }

      .close-button:hover {
        background-color: rgba(0, 0, 0, 0.04);
      }

      .close-button mat-icon {
        color: rgba(0, 0, 0, 0.54);
        font-size: 20px;
        width: 20px;
        height: 20px;
      }

      .search-results {
        flex: 1;
        overflow-y: auto;
        max-height: calc(70vh - 100px);
      }

      .no-results {
        text-align: center;
        padding: 48px 24px;
        color: rgba(0, 0, 0, 0.38);
      }

      .no-results mat-icon {
        font-size: 48px;
        width: 48px;
        height: 48px;
        margin-bottom: 12px;
      }

      .no-results p {
        margin: 0;
        font-size: 14px;
      }

      .results-list {
        padding: 0;
      }

      .result-item {
        cursor: pointer;
        padding: 12px 16px;
        transition: background-color 0.15s;
        height: auto !important;
        min-height: 64px;
      }

      .result-item:hover,
      .result-item.selected {
        background-color: rgba(0, 0, 0, 0.04);
      }

      .result-item ::ng-deep .mat-mdc-list-item-unscoped-content {
        width: 100%;
      }

      .result-title {
        font-size: 15px;
        font-weight: 500;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .result-title ::ng-deep .highlight {
        background-color: #fff59d;
        font-weight: 600;
      }

      .favorite-badge {
        color: #ffd700;
        font-size: 14px;
        margin-left: auto;
      }

      .result-meta {
        font-size: 13px;
        color: rgba(0, 0, 0, 0.6);
        display: flex;
        flex-direction: column;
        gap: 4px;
        margin-top: 4px;
      }

      .category-label {
        display: inline-block;
        background-color: rgba(102, 126, 234, 0.1);
        color: #667eea;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
        margin-bottom: 4px;
      }

      .result-description {
        line-height: 1.4;
      }

      .result-tags {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        margin-top: 4px;
      }

      .tag {
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
        opacity: 0.7;
      }

      .search-hint {
        padding: 24px;
        text-align: center;
        color: rgba(0, 0, 0, 0.38);
      }

      .search-hint p {
        margin: 0 0 16px 0;
        font-size: 14px;
      }

      .keyboard-hints {
        display: flex;
        justify-content: center;
        gap: 16px;
        flex-wrap: wrap;
      }

      .hint {
        font-size: 12px;
        display: flex;
        align-items: center;
        gap: 4px;
      }

      kbd {
        background-color: rgba(0, 0, 0, 0.06);
        border: 1px solid rgba(0, 0, 0, 0.12);
        border-radius: 4px;
        padding: 2px 6px;
        font-family: monospace;
        font-size: 11px;
        box-shadow: 0 1px 0 rgba(0, 0, 0, 0.1);
      }

      @media (max-width: 768px) {
        .search-container {
          width: 100vw;
          max-width: 100vw;
          max-height: 100vh;
          border-radius: 0;
        }
      }
    `,
  ],
})
export class LinkSearchDialogComponent implements OnInit {
  searchQuery = '';
  filteredLinks: SearchResult[] = [];
  selectedIndex = 0;

  constructor(
    public dialogRef: MatDialogRef<LinkSearchDialogComponent>,
    @Inject(MAT_DIALOG_DATA) public data: LinkSearchDialogData,
    private sanitizer: DomSanitizer,
  ) {}

  ngOnInit(): void {
    // Close dialog on Escape key
    this.dialogRef.keydownEvents().subscribe((event) => {
      if (event.key === 'Escape') {
        this.close();
      }
    });
  }

  @HostListener('document:keydown', ['$event'])
  handleKeyboardEvent(event: KeyboardEvent): void {
    if (!this.searchQuery || this.filteredLinks.length === 0) {
      return;
    }

    switch (event.key) {
      case 'ArrowDown':
        event.preventDefault();
        this.selectedIndex = Math.min(this.selectedIndex + 1, this.filteredLinks.length - 1);
        this.scrollToSelected();
        break;
      case 'ArrowUp':
        event.preventDefault();
        this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
        this.scrollToSelected();
        break;
      case 'Enter':
        event.preventDefault();
        if (this.filteredLinks[this.selectedIndex]) {
          this.selectLink(this.filteredLinks[this.selectedIndex]);
        }
        break;
    }
  }

  onSearchChange(): void {
    this.selectedIndex = 0;
    this.filteredLinks = this.filterLinks(this.searchQuery);
  }

  filterLinks(query: string): SearchResult[] {
    if (!query.trim()) {
      return [];
    }

    const lowerQuery = query.toLowerCase().trim();
    const results: SearchResult[] = [];

    for (const link of this.data.links) {
      // Check title
      const titleMatch = link.title.toLowerCase().includes(lowerQuery);

      // Check description
      const descriptionMatch = link.description?.toLowerCase().includes(lowerQuery) || false;

      // Check tags
      const tagMatch =
        link.tags?.some((tag) => tag.tag_name.toLowerCase().includes(lowerQuery)) || false;

      if (titleMatch || descriptionMatch || tagMatch) {
        results.push(link);
      }
    }

    // Sort by relevance: title matches first, then description, then tags
    results.sort((a, b) => {
      const aTitle = a.title.toLowerCase().includes(lowerQuery);
      const bTitle = b.title.toLowerCase().includes(lowerQuery);

      if (aTitle && !bTitle) return -1;
      if (!aTitle && bTitle) return 1;

      const aDescription = a.description?.toLowerCase().includes(lowerQuery);
      const bDescription = b.description?.toLowerCase().includes(lowerQuery);

      if (aDescription && !bDescription) return -1;
      if (!aDescription && bDescription) return 1;

      return 0;
    });

    return results.slice(0, 50); // Limit to 50 results for performance
  }

  highlightText(text: string): SafeHtml {
    if (!this.searchQuery.trim()) {
      return text;
    }

    // Escape HTML in the original text to prevent XSS
    const escapedText = this.escapeHtml(text);

    // Now apply highlighting to the escaped text
    const regex = new RegExp(`(${this.escapeRegex(this.searchQuery)})`, 'gi');
    const highlightedText = escapedText.replace(regex, '<span class="highlight">$1</span>');

    // Sanitize the result before returning
    return this.sanitizer.sanitize(1, highlightedText) || '';
  }

  private escapeHtml(text: string): string {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  escapeRegex(str: string): string {
    return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  selectLink(link: SearchResult): void {
    this.dialogRef.close(link);
  }

  close(): void {
    this.dialogRef.close();
  }

  private scrollToSelected(): void {
    setTimeout(() => {
      const element = document.querySelector('.result-item.selected');
      element?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    });
  }
}
