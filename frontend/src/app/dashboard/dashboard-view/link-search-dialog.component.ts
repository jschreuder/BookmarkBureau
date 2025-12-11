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
import { getTextColor } from '../../shared/utils/color.util';

export interface LinkSearchDialogData {
  links: Link[];
}

export interface SearchResult extends Link {
  category?: string;
  categoryColor?: string;
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
  templateUrl: './link-search-dialog.component.html',
  styleUrl: './link-search-dialog.component.scss',
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

  getTextColor(backgroundColor: string): string {
    return getTextColor(backgroundColor);
  }

  private scrollToSelected(): void {
    setTimeout(() => {
      const element = document.querySelector('.result-item.selected');
      element?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    });
  }
}
