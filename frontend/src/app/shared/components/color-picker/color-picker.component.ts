import { Component, forwardRef, Input, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  ControlValueAccessor,
  NG_VALUE_ACCESSOR,
  FormControl,
  ReactiveFormsModule,
} from '@angular/forms';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';

@Component({
  selector: 'app-color-picker',
  standalone: true,
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatIconModule,
  ],
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => ColorPickerComponent),
      multi: true,
    },
  ],
  templateUrl: './color-picker.component.html',
  styleUrl: './color-picker.component.scss',
})
export class ColorPickerComponent implements ControlValueAccessor {
  @Input() label = 'Color (Optional)';
  @Input() placeholder = '#e0e0e0';
  @Input() hint = 'Choose a color';
  @Input() allowClear = false;

  colorControl = new FormControl('');

  presetColors = [
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
    '#ffc107',
    '#ff9800',
    '#ff5722',
    '#795548',
  ];

  private onChange: (value: string | null) => void = () => {};
  private onTouched: () => void = () => {};

  onColorChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.colorControl.setValue(input.value);
    this.onChange(input.value);
    this.onTouched();
  }

  selectColor(color: string): void {
    this.colorControl.setValue(color);
    this.onChange(color);
    this.onTouched();
  }

  clearColor(): void {
    this.colorControl.setValue('');
    this.onChange(null);
    this.onTouched();
  }

  // ControlValueAccessor implementation
  writeValue(value: string | null): void {
    this.colorControl.setValue(value || '', { emitEvent: false });
  }

  registerOnChange(fn: (value: string | null) => void): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: () => void): void {
    this.onTouched = fn;
  }

  setDisabledState(isDisabled: boolean): void {
    if (isDisabled) {
      this.colorControl.disable();
    } else {
      this.colorControl.enable();
    }
  }
}
