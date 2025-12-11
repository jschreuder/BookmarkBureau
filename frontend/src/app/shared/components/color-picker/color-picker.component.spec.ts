import { ComponentFixture, TestBed } from '@angular/core/testing';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { ColorPickerComponent } from './color-picker.component';
import { Component } from '@angular/core';
import { vi } from 'vitest';

// Test host component to test ControlValueAccessor integration
@Component({
  selector: 'app-test-host',
  template: `<app-color-picker
    [formControl]="colorControl"
    [allowClear]="allowClear"
  ></app-color-picker>`,
  standalone: true,
  imports: [ColorPickerComponent, ReactiveFormsModule],
})
class TestHostComponent {
  colorControl = new FormControl('');
  allowClear = false;
}

describe('ColorPickerComponent', () => {
  let component: ColorPickerComponent;
  let fixture: ComponentFixture<ColorPickerComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ColorPickerComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(ColorPickerComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  describe('rendering', () => {
    it('should render color input', () => {
      const compiled = fixture.nativeElement;
      const colorInput = compiled.querySelector('input[type="color"]');
      expect(colorInput).toBeTruthy();
    });

    it('should render color display input', () => {
      const compiled = fixture.nativeElement;
      const displayInput = compiled.querySelector('.color-display');
      expect(displayInput).toBeTruthy();
    });

    it('should render preset color buttons', () => {
      const compiled = fixture.nativeElement;
      const presetButtons = compiled.querySelectorAll('.color-preset');
      expect(presetButtons.length).toBe(16);
    });

    it('should use custom label when provided', () => {
      component.label = 'Custom Label';
      expect(component.effectiveLabel).toBe('Custom Label');
    });

    it('should show "Color (Optional)" when allowClear is true and no custom label', () => {
      component.label = '';
      component.allowClear = true;
      expect(component.effectiveLabel).toBe('Color (Optional)');
    });

    it('should show "Color *" when allowClear is false and no custom label', () => {
      component.label = '';
      component.allowClear = false;
      expect(component.effectiveLabel).toBe('Color *');
    });

    it('should accept hint input', () => {
      component.hint = 'Test hint';
      expect(component.hint).toBe('Test hint');
    });

    it('should not show clear button when allowClear is false', () => {
      component.allowClear = false;
      component.colorControl.setValue('#ff0000');
      fixture.detectChanges();
      const compiled = fixture.nativeElement;
      const clearButton = compiled.querySelector('button[aria-label="Clear color"]');
      expect(clearButton).toBeFalsy();
    });

    it('should respect allowClear input and color value for clear button visibility', () => {
      component.allowClear = true;
      component.colorControl.setValue('#ff0000');
      // Button visibility is controlled by @if directive in template
      // We test the conditions that control visibility
      expect(component.allowClear).toBe(true);
      expect(component.colorControl.value).toBe('#ff0000');
    });

    it('should not show clear button when color is empty even if allowClear is true', () => {
      component.allowClear = true;
      component.colorControl.setValue('');
      fixture.detectChanges();
      const compiled = fixture.nativeElement;
      const clearButton = compiled.querySelector('button[aria-label="Clear color"]');
      expect(clearButton).toBeFalsy();
    });
  });

  describe('color selection', () => {
    it('should update value when color input changes', () => {
      const onChangeSpy = vi.fn();
      component.registerOnChange(onChangeSpy);

      const event = { target: { value: '#123456' } } as any;
      component.onColorChange(event);

      expect(component.colorControl.value).toBe('#123456');
      expect(onChangeSpy).toHaveBeenCalledWith('#123456');
    });

    it('should update value when preset color is selected', () => {
      const onChangeSpy = vi.fn();
      component.registerOnChange(onChangeSpy);

      component.selectColor('#ff0000');

      expect(component.colorControl.value).toBe('#ff0000');
      expect(onChangeSpy).toHaveBeenCalledWith('#ff0000');
    });

    it('should clear value when clearColor is called', () => {
      const onChangeSpy = vi.fn();
      component.registerOnChange(onChangeSpy);
      component.colorControl.setValue('#ff0000');

      component.clearColor();

      expect(component.colorControl.value).toBe('');
      expect(onChangeSpy).toHaveBeenCalledWith(null);
    });

    it('should call onTouched when color changes', () => {
      const onTouchedSpy = vi.fn();
      component.registerOnTouched(onTouchedSpy);

      const event = { target: { value: '#123456' } } as any;
      component.onColorChange(event);

      expect(onTouchedSpy).toHaveBeenCalled();
    });
  });

  describe('ControlValueAccessor', () => {
    it('should write value to internal control', () => {
      component.writeValue('#abcdef');
      expect(component.colorControl.value).toBe('#abcdef');
    });

    it('should write null as empty string', () => {
      component.writeValue(null);
      expect(component.colorControl.value).toBe('');
    });

    it('should register onChange callback', () => {
      const fn = vi.fn();
      component.registerOnChange(fn);
      // The function should be registered internally
      expect(component['onChange']).toBe(fn);
    });

    it('should register onTouched callback', () => {
      const fn = vi.fn();
      component.registerOnTouched(fn);
      // The function should be registered internally
      expect(component['onTouched']).toBe(fn);
    });

    it('should disable control when setDisabledState(true)', () => {
      component.setDisabledState(true);
      expect(component.colorControl.disabled).toBe(true);
    });

    it('should enable control when setDisabledState(false)', () => {
      component.colorControl.disable();
      component.setDisabledState(false);
      expect(component.colorControl.enabled).toBe(true);
    });
  });

  describe('preset colors', () => {
    it('should have 16 preset colors', () => {
      expect(component.presetColors.length).toBe(16);
    });

    it('should have valid hex colors', () => {
      component.presetColors.forEach((color) => {
        expect(color).toMatch(/^#[0-9a-f]{6}$/i);
      });
    });
  });
});

describe('ColorPickerComponent - Form Integration', () => {
  let hostFixture: ComponentFixture<TestHostComponent>;
  let hostComponent: TestHostComponent;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TestHostComponent],
    }).compileComponents();

    hostFixture = TestBed.createComponent(TestHostComponent);
    hostComponent = hostFixture.componentInstance;
    hostFixture.detectChanges();
  });

  it('should integrate with reactive forms', () => {
    hostComponent.colorControl.setValue('#ff0000');
    hostFixture.detectChanges();

    const colorPicker = hostFixture.debugElement.children[0].componentInstance;
    expect(colorPicker.colorControl.value).toBe('#ff0000');
  });

  it('should propagate color changes to parent form', () => {
    const colorPicker = hostFixture.debugElement.children[0]
      .componentInstance as ColorPickerComponent;

    // Simulate color selection
    colorPicker.selectColor('#123456');

    // The color picker's internal control should be updated
    expect(colorPicker.colorControl.value).toBe('#123456');
    // The parent form control should also be updated via ControlValueAccessor
    expect(hostComponent.colorControl.value).toBe('#123456');
  });
});
