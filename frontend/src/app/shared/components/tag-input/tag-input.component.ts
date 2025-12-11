import {
  Component,
  Input,
  forwardRef,
  inject,
  OnInit,
  signal,
  ViewChild,
  ElementRef,
  ChangeDetectionStrategy,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  ControlValueAccessor,
  NG_VALUE_ACCESSOR,
  FormControl,
  ReactiveFormsModule,
} from '@angular/forms';
import { MatChipsModule } from '@angular/material/chips';
import {
  MatAutocompleteModule,
  MatAutocompleteSelectedEvent,
} from '@angular/material/autocomplete';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { getTextColor } from '../../utils/color.util';
import { MatSnackBar } from '@angular/material/snack-bar';
import { Observable } from 'rxjs';
import { map, startWith } from 'rxjs/operators';
import { Tag } from '../../../core/models';
import { TagService } from '../../../core/services/tag.service';

@Component({
  selector: 'app-tag-input',
  standalone: true,
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatChipsModule,
    MatAutocompleteModule,
    MatFormFieldModule,
    MatIconModule,
  ],
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => TagInputComponent),
      multi: true,
    },
  ],
  templateUrl: './tag-input.component.html',
  styleUrl: './tag-input.component.scss',
})
export class TagInputComponent implements ControlValueAccessor, OnInit {
  private readonly tagService = inject(TagService);
  private readonly snackBar = inject(MatSnackBar);

  @Input() placeholder = 'Add tag...';
  @ViewChild('tagInput') tagInputElement!: ElementRef<HTMLInputElement>;

  selectedTags = signal<Tag[]>([]);
  tagCtrl = new FormControl('');
  filteredTags$: Observable<Tag[]>;
  showCreateOption$: Observable<boolean>;

  private onChange: (tags: Tag[]) => void = () => {};
  onTouched: () => void = () => {};
  private lastInputValue = ''; // Track the user's typed input

  // Expose shared utility function to template
  protected readonly getTextColor = getTextColor;

  constructor() {
    this.filteredTags$ = this.tagCtrl.valueChanges.pipe(
      startWith(''),
      map((value) => this.filterAvailableTags(value || '')),
    );

    this.showCreateOption$ = this.tagCtrl.valueChanges.pipe(
      startWith(''),
      map((value) => this.shouldShowCreateOption(value || '')),
    );
  }

  onInput(event: Event): void {
    const input = event.target as HTMLInputElement;
    const sanitized = this.sanitizeTagName(input.value);

    // Only update if the value changed after sanitization
    if (input.value !== sanitized) {
      const cursorPos = input.selectionStart || 0;
      const lengthDiff = input.value.length - sanitized.length;
      input.value = sanitized;
      this.tagCtrl.setValue(sanitized, { emitEvent: false });
      // Adjust cursor position after removing invalid characters
      input.setSelectionRange(cursorPos - lengthDiff, cursorPos - lengthDiff);
    }

    this.lastInputValue = sanitized;
  }

  /**
   * Sanitizes tag name to match backend validation rules:
   * - Only lowercase letters (a-z), numbers (0-9), and hyphens (-)
   * - Maximum 100 characters
   * - Automatically converts to lowercase
   */
  private sanitizeTagName(value: string): string {
    // Convert to lowercase and remove any characters that aren't a-z, 0-9, or hyphen
    const sanitized = value
      .toLowerCase()
      .replaceAll(/[^a-z0-9-]/g, '')
      .slice(0, 100); // Enforce max length

    return sanitized;
  }

  ngOnInit(): void {
    // Load tags when component initializes
    this.tagService.loadTags().subscribe();
  }

  writeValue(tags: Tag[] | null): void {
    this.selectedTags.set(tags || []);
  }

  registerOnChange(fn: (tags: Tag[]) => void): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: () => void): void {
    this.onTouched = fn;
  }

  setDisabledState(isDisabled: boolean): void {
    if (isDisabled) {
      this.tagCtrl.disable();
    } else {
      this.tagCtrl.enable();
    }
  }

  selected(event: MatAutocompleteSelectedEvent): void {
    const value = event.option.value;

    if (value === '__CREATE__') {
      // Use the last input value before autocomplete changed it
      const tagName = this.lastInputValue.trim();
      this.createNewTagWithName(tagName);
    } else {
      const tag = this.tagService.getTags().find((t) => t.tag_name === value);
      if (tag && !this.isTagSelected(tag)) {
        this.addTag(tag);
      }
    }

    // Clear input after selection
    this.tagCtrl.setValue('');
    this.lastInputValue = '';
    // Also clear the native input element to ensure UI is updated
    if (this.tagInputElement) {
      this.tagInputElement.nativeElement.value = '';
    }
  }

  removeTag(tag: Tag): void {
    const tags = this.selectedTags();
    const index = tags.findIndex((t) => t.tag_name === tag.tag_name);
    if (index >= 0) {
      const newTags = [...tags];
      newTags.splice(index, 1);
      this.selectedTags.set(newTags);
      this.onChange(newTags);
    }
  }

  private addTag(tag: Tag): void {
    const newTags = [...this.selectedTags(), tag];
    this.selectedTags.set(newTags);
    this.onChange(newTags);
  }

  private createNewTagWithName(tagName: string): void {
    if (!tagName || this.tagService.tagExists(tagName)) {
      return;
    }

    const newTag: Tag = {
      tag_name: tagName,
      color: this.getRandomColor(),
    };

    this.tagService.createTag(newTag).subscribe({
      next: (createdTag) => {
        this.addTag(createdTag);
      },
      error: () => {
        this.snackBar.open('Failed to create tag', 'Close', { duration: 5000 });
      },
    });
  }

  private filterAvailableTags(value: string): Tag[] {
    const filtered = this.tagService.filterTags(value);
    // Filter out already selected tags
    return filtered.filter((tag) => !this.isTagSelected(tag));
  }

  private shouldShowCreateOption(value: string): boolean {
    const trimmed = value.trim();
    if (!trimmed) {
      return false;
    }

    // Show create option if the input doesn't match any existing tag (case-insensitive)
    // and the tag is not already selected
    return !this.tagService.tagExists(trimmed);
  }

  private isTagSelected(tag: Tag): boolean {
    return this.selectedTags().some((t) => t.tag_name === tag.tag_name);
  }

  private getRandomColor(): string {
    const colors = [
      '#f44336',
      '#e91e63',
      '#9c27b0',
      '#673ab7',
      '#3f51b5',
      '#2196f3',
      '#03a9f4',
      '#00bcd4',
      '#009688',
      '#4caf50',
      '#8bc34a',
      '#cddc39',
      '#ffeb3b',
      '#ffc107',
      '#ff9800',
      '#ff5722',
      '#795548',
      '#607d8b',
    ];
    return colors[Math.floor(Math.random() * colors.length)];
  }
}
