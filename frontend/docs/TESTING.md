# Testing Guide

This document explains how testing is set up in the BookmarkBureau frontend application.

## Test Framework

We use **Vitest** as our test runner with **Jasmine-compatible syntax** for assertions. This setup is officially supported by Angular 20+ and will become the default in Angular 21.

### Why Vitest?

- **Node.js based**: Tests run in Node.js using jsdom for DOM simulation - no browser required
- **Fast**: Significantly faster than traditional browser-based test runners
- **ESM native**: Full support for modern JavaScript modules
- **Built-in coverage**: Code coverage reports via v8

## Running Tests

```bash
# Run tests in watch mode (re-runs on file changes)
npm test

# Run tests once with coverage report
npm run test:ci
```

## Key Dependencies

- **vitest** (v3.2.4): Test runner
- **jsdom** (v27.1.0): DOM implementation for Node.js
- **@vitest/coverage-v8** (v3.2.4): Code coverage reporting
- **jasmine-core** (~5.9.0): Provides Jasmine syntax compatibility

## Writing Tests

### Test File Structure

Test files should be colocated with the code they test and use the `.spec.ts` extension:

```
src/
  app/
    services/
      api.service.ts
      api.service.spec.ts  ← test file
    components/
      dashboard/
        dashboard.component.ts
        dashboard.component.spec.ts  ← test file
```

### Basic Test Template

```typescript
import { TestBed } from '@angular/core/testing';
import { MyComponent } from './my-component';

describe('MyComponent', () => {
  let component: MyComponent;
  let fixture: ComponentFixture<MyComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [MyComponent]  // For standalone components
    }).compileComponents();

    fixture = TestBed.createComponent(MyComponent);
    component = fixture.componentInstance;
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
```

### Mocking with Vitest

**Important**: Use Vitest's `vi` for mocking, not Jasmine's `jasmine.createSpyObj`:

```typescript
import { vi } from 'vitest';

// Create mock functions
const mockService = {
  getData: vi.fn(),
  saveData: vi.fn()
};

// Mock return values
mockService.getData.mockReturnValue(of(mockData));

// Spy on methods
vi.spyOn(component, 'onSubmit');
vi.spyOn(console, 'error');

// Verify calls
expect(mockService.getData).toHaveBeenCalled();
expect(mockService.getData).toHaveBeenCalledWith('arg1', 'arg2');
```

### Testing HTTP Services

```typescript
import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { ApiService } from './api.service';

describe('ApiService', () => {
  let service: ApiService;
  let httpMock: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [ApiService]
    });

    service = TestBed.inject(ApiService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify(); // Ensure no outstanding requests
  });

  it('should fetch data', () => {
    const mockData = { id: '1', name: 'Test' };

    service.getData().subscribe(data => {
      expect(data).toEqual(mockData);
    });

    const req = httpMock.expectOne('/api/data');
    expect(req.request.method).toBe('GET');
    req.flush(mockData);
  });
});
```

### Testing Components with Dependencies

```typescript
import { vi } from 'vitest';
import { provideRouter } from '@angular/router';

describe('DashboardComponent', () => {
  let component: DashboardComponent;
  let fixture: ComponentFixture<DashboardComponent>;
  let apiService: {
    getDashboard: ReturnType<typeof vi.fn>;
    updateDashboard: ReturnType<typeof vi.fn>;
  };

  beforeEach(async () => {
    const apiServiceMock = {
      getDashboard: vi.fn(),
      updateDashboard: vi.fn()
    };

    await TestBed.configureTestingModule({
      imports: [DashboardComponent],
      providers: [
        { provide: ApiService, useValue: apiServiceMock },
        provideRouter([])  // For components using Router
      ]
    }).compileComponents();

    apiService = TestBed.inject(ApiService) as any;
    fixture = TestBed.createComponent(DashboardComponent);
    component = fixture.componentInstance;
  });

  it('should load dashboard on init', () => {
    const mockDashboard = { id: '1', title: 'Test' };
    apiService.getDashboard.mockReturnValue(of(mockDashboard));

    fixture.detectChanges();

    expect(apiService.getDashboard).toHaveBeenCalled();
    expect(component.dashboard).toEqual(mockDashboard);
  });
});
```

## Common Testing Patterns

### Testing Forms

```typescript
it('should validate required fields', () => {
  const titleControl = component.form.get('title');

  titleControl?.setValue('');
  expect(titleControl?.hasError('required')).toBe(true);

  titleControl?.setValue('Valid Title');
  expect(titleControl?.hasError('required')).toBe(false);
});
```

### Testing DOM Elements

```typescript
it('should display error message', () => {
  component.errorMessage = 'Something went wrong';
  fixture.detectChanges();

  const errorElement = fixture.nativeElement.querySelector('.error');
  expect(errorElement).toBeTruthy();
  expect(errorElement.textContent).toContain('Something went wrong');
});
```

### Testing Router Navigation

```typescript
it('should navigate to dashboard', () => {
  vi.spyOn(component['router'], 'navigate');

  component.goToDashboard();

  expect(component['router'].navigate).toHaveBeenCalledWith(['/dashboard']);
});
```

### Testing Error Handling

```typescript
it('should handle API errors', () => {
  const error = new Error('API failed');
  apiService.getData.mockReturnValue(throwError(() => error));
  vi.spyOn(console, 'error');

  component.loadData();

  expect(console.error).toHaveBeenCalledWith('Error loading data:', error);
  expect(component.loading).toBe(false);
});
```

## Configuration

### Angular Configuration

Test configuration is defined in [angular.json](../angular.json):

```json
"test": {
  "builder": "@angular/build:unit-test",
  "options": {
    "buildTarget": "frontend:build",
    "runner": "vitest",
    "tsConfig": "tsconfig.spec.json"
  }
}
```

### TypeScript Configuration

Test-specific TypeScript configuration is in [tsconfig.spec.json](../tsconfig.spec.json).

## Best Practices

1. **One test file per source file**: Keep tests colocated with the code they test
2. **Descriptive test names**: Use clear descriptions that explain what is being tested
3. **Arrange-Act-Assert**: Structure tests with setup, action, and verification
4. **Mock external dependencies**: Always mock services, HTTP calls, and router
5. **Test behavior, not implementation**: Focus on what the component does, not how
6. **Use `fixture.detectChanges()`**: Call this after changing component state to update the view
7. **Clean up**: Use `afterEach` to verify HTTP mocks and clean up resources

## Troubleshooting

### Test fails with "jasmine is not defined"

You're using Jasmine-specific APIs. Replace with Vitest equivalents:

- `jasmine.createSpyObj()` → Use object with `vi.fn()` functions
- `spyOn()` → `vi.spyOn()`
- `.and.returnValue()` → `.mockReturnValue()`

### Component fails with "Can't bind to 'ngForOf'"

Import required directives in your test configuration:

```typescript
await TestBed.configureTestingModule({
  imports: [MyComponent, CommonModule]  // Add CommonModule for *ngFor, *ngIf, etc.
}).compileComponents();
```

### Tests are slow

Vitest runs tests in parallel by default. If tests are still slow:

1. Check for unnecessary `fixture.detectChanges()` calls
2. Minimize DOM queries
3. Use `vi.fn()` instead of real implementations

## Coverage Reports

After running `npm run test:ci`, coverage reports are generated showing:

- Line coverage
- Branch coverage
- Function coverage
- Statement coverage

The coverage report helps identify untested code paths.
