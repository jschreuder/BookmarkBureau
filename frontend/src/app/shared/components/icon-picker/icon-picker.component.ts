import { Component, forwardRef, OnInit, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  ControlValueAccessor,
  NG_VALUE_ACCESSOR,
  FormControl,
  ReactiveFormsModule,
} from '@angular/forms';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatAutocompleteModule } from '@angular/material/autocomplete';
import { MatIconModule } from '@angular/material/icon';
import { Observable } from 'rxjs';
import { map, startWith } from 'rxjs/operators';
import materialIcons from 'material-design-icons-iconfont/dist/fonts/MaterialIcons-Regular.json';

// Get all Material Icon names from the imported JSON (2193 icons)
const MATERIAL_ICONS = Object.keys(materialIcons).sort();

@Component({
  selector: 'app-icon-picker',
  standalone: true,
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatFormFieldModule,
    MatInputModule,
    MatAutocompleteModule,
    MatIconModule,
  ],
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => IconPickerComponent),
      multi: true,
    },
  ],
  template: `
    <mat-form-field appearance="outline" class="full-width">
      <mat-label>Icon</mat-label>
      <input
        type="text"
        matInput
        [formControl]="searchControl"
        [matAutocomplete]="auto"
        placeholder="Search icons..."
      />
      <mat-icon matPrefix *ngIf="value">{{ value }}</mat-icon>
      <mat-hint>Optional. Select or type a Material Design icon name.</mat-hint>
      <mat-autocomplete
        #auto="matAutocomplete"
        (optionSelected)="onIconSelected($event.option.value)"
      >
        <mat-option *ngFor="let icon of filteredIcons$ | async" [value]="icon">
          <mat-icon>{{ icon }}</mat-icon>
          <span class="icon-name">{{ icon }}</span>
        </mat-option>
      </mat-autocomplete>
    </mat-form-field>
  `,
  styles: [
    `
      .full-width {
        width: 100%;
      }

      mat-option {
        display: flex;
        align-items: center;
        gap: 12px;
      }

      .icon-name {
        font-size: 14px;
      }
    `,
  ],
})
export class IconPickerComponent implements ControlValueAccessor, OnInit {
  searchControl = new FormControl('');
  filteredIcons$!: Observable<string[]>;
  value = '';

  private onChange: (value: string) => void = () => {};
  private onTouched: () => void = () => {};

  ngOnInit() {
    this.filteredIcons$ = this.searchControl.valueChanges.pipe(
      startWith(''),
      map((search) => this.filterIcons(search || '')),
    );
  }

  private filterIcons(search: string): string[] {
    const filterValue = search.toLowerCase();
    const filtered = MATERIAL_ICONS.filter((icon) => icon.toLowerCase().includes(filterValue));
    // Limit to 50 results for performance
    return filtered.slice(0, 50);
  }

  onIconSelected(icon: string) {
    this.value = icon;
    this.onChange(icon);
    this.onTouched();
  }

  // ControlValueAccessor implementation
  writeValue(value: string): void {
    this.value = value || '';
    this.searchControl.setValue(value || '', { emitEvent: false });
    // Emit manually to trigger change detection for template binding
    this.searchControl.updateValueAndValidity({ emitEvent: true });
  }

  registerOnChange(fn: (value: string) => void): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: () => void): void {
    this.onTouched = fn;
  }

  setDisabledState(isDisabled: boolean): void {
    if (isDisabled) {
      this.searchControl.disable();
    } else {
      this.searchControl.enable();
    }
  }
}
