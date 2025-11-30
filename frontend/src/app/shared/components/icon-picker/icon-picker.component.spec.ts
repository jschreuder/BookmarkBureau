import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ReactiveFormsModule, FormControl } from '@angular/forms';
import { IconPickerComponent } from './icon-picker.component';
import { describe, it, expect, beforeEach } from 'vitest';

describe('IconPickerComponent', () => {
  let component: IconPickerComponent;
  let fixture: ComponentFixture<IconPickerComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [IconPickerComponent, ReactiveFormsModule],
    }).compileComponents();

    fixture = TestBed.createComponent(IconPickerComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  describe('component initialization', () => {
    it('should create', () => {
      expect(component).toBeTruthy();
    });

    it('should initialize with empty search control', () => {
      expect(component.searchControl.value).toBe('');
    });

    it('should initialize with empty value', () => {
      expect(component.value).toBe('');
    });

    it('should initialize filteredIcons$ observable', () => {
      expect(component.filteredIcons$).toBeDefined();
    });
  });

  describe('template rendering', () => {
    it('should render mat-form-field', () => {
      const formField = fixture.nativeElement.querySelector('mat-form-field');
      expect(formField).toBeTruthy();
    });

    it('should render input field', () => {
      const input = fixture.nativeElement.querySelector('input[matInput]');
      expect(input).toBeTruthy();
    });

    it('should render autocomplete', () => {
      const autocomplete = fixture.nativeElement.querySelector('mat-autocomplete');
      expect(autocomplete).toBeTruthy();
    });

    it('should show icon prefix when value is set', () => {
      component.value = 'home';
      fixture.detectChanges();

      const icon = fixture.nativeElement.querySelector('mat-icon[matPrefix]');
      expect(icon).toBeTruthy();
      expect(icon.textContent).toContain('home');
    });

    it('should not show icon prefix when value is empty', () => {
      component.value = '';
      fixture.detectChanges();

      const icon = fixture.nativeElement.querySelector('mat-icon[matPrefix]');
      expect(icon).toBeFalsy();
    });
  });

  describe('icon filtering', () => {
    it('should filter icons based on search term', () => {
      let receivedIcons: string[] = [];
      const sub = component.filteredIcons$.subscribe((icons) => {
        receivedIcons = icons;
      });

      component.searchControl.setValue('bookmark');
      fixture.detectChanges();

      expect(receivedIcons.length).toBeGreaterThan(0);
      expect(receivedIcons.some((icon) => icon.includes('bookmark'))).toBe(true);
      sub.unsubscribe();
    });

    it('should return limited results (max 50)', () => {
      let receivedIcons: string[] = [];
      const sub = component.filteredIcons$.subscribe((icons) => {
        receivedIcons = icons;
      });

      component.searchControl.setValue('a');
      fixture.detectChanges();

      expect(receivedIcons.length).toBeLessThanOrEqual(50);
      sub.unsubscribe();
    });

    it('should filter case-insensitively', () => {
      let receivedIcons: string[] = [];
      const sub = component.filteredIcons$.subscribe((icons) => {
        receivedIcons = icons;
      });

      component.searchControl.setValue('STAR');
      fixture.detectChanges();

      expect(receivedIcons.length).toBeGreaterThan(0);
      expect(receivedIcons.some((icon) => icon.includes('star'))).toBe(true);
      sub.unsubscribe();
    });

    it('should return all icons when search is empty', () => {
      let receivedIcons: string[] = [];
      const sub = component.filteredIcons$.subscribe((icons) => {
        receivedIcons = icons;
      });

      component.searchControl.setValue('');
      fixture.detectChanges();

      expect(receivedIcons.length).toBe(50); // Limited to 50 results
      sub.unsubscribe();
    });
  });

  describe('ControlValueAccessor implementation', () => {
    it('should write value to component', () => {
      component.writeValue('dashboard');
      expect(component.value).toBe('dashboard');
      expect(component.searchControl.value).toBe('dashboard');
    });

    it('should handle null value', () => {
      component.writeValue(null as any);
      expect(component.value).toBe('');
      expect(component.searchControl.value).toBe('');
    });

    it('should handle undefined value', () => {
      component.writeValue(undefined as any);
      expect(component.value).toBe('');
      expect(component.searchControl.value).toBe('');
    });

    it('should register onChange callback', () => {
      let changedValue = '';
      component.registerOnChange((value) => {
        changedValue = value;
      });

      component.onIconSelected('star');
      expect(changedValue).toBe('star');
    });

    it('should register onTouched callback', () => {
      let touched = false;
      component.registerOnTouched(() => {
        touched = true;
      });

      component.onIconSelected('bookmark');
      expect(touched).toBe(true);
    });

    it('should disable control when setDisabledState is true', () => {
      component.setDisabledState(true);
      expect(component.searchControl.disabled).toBe(true);
    });

    it('should enable control when setDisabledState is false', () => {
      component.setDisabledState(false);
      expect(component.searchControl.enabled).toBe(true);
    });
  });

  describe('icon selection', () => {
    it('should update value when icon is selected', () => {
      component.onIconSelected('settings');
      expect(component.value).toBe('settings');
    });

    it('should call onChange when icon is selected', () => {
      let changedValue = '';
      component.registerOnChange((value) => {
        changedValue = value;
      });

      component.onIconSelected('favorite');
      expect(changedValue).toBe('favorite');
    });

    it('should call onTouched when icon is selected', () => {
      let touched = false;
      component.registerOnTouched(() => {
        touched = true;
      });

      component.onIconSelected('label');
      expect(touched).toBe(true);
    });
  });

  describe('form integration', () => {
    it('should work with FormControl', () => {
      const formControl = new FormControl('');
      component.registerOnChange((value) => formControl.setValue(value));

      component.onIconSelected('folder');
      expect(formControl.value).toBe('folder');
    });

    it('should update when FormControl value changes', () => {
      component.writeValue('search');
      expect(component.value).toBe('search');
      expect(component.searchControl.value).toBe('search');
    });
  });
});
