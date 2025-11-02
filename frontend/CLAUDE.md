# BookmarkBureau Frontend - Angular Context

## Overview

Angular 20 SPA for BookmarkBureau bookmark management. Part of a monorepo with PHP backend at root.

**Build Output:** Builds to `../web/` directory (served alongside PHP backend)

## Tech Stack

- **Framework:** Angular 20.3+
- **UI:** Angular Material 20.2+
- **Language:** TypeScript 5.9+ (strict mode)
- **Testing:** Vitest 3.2+ (NOT Karma/Jasmine)
- **Package Manager:** npm

## Project Structure

```
frontend/
├── src/
│   ├── app/
│   │   ├── admin/          - Admin CRUD interfaces
│   │   ├── dashboard/      - Public dashboard views
│   │   ├── core/           - Services and models
│   │   └── shared/         - Shared components
│   ├── testing/
│   │   └── test-helpers.ts - Mock factories and test utilities
│   └── environments/       - Environment configs (not committed)
├── docs/                   - Frontend documentation
└── dist/                   - Build output (excluded from git)
```

## Commands

### Testing
- `npm run test:ci` - Run tests ONCE with coverage (IMPORTANT: Use for CI/automation)
- `npm test` - Watch mode (WARNING: Keeps processes running in background)
- `npm run test:ci -- path/to/file.spec.ts` - Run specific test

### Development
- `npm start` - Start dev server (ng serve, proxy to PHP backend)
- `npm run lint` - Run ESLint
- `npm run format` - Run Prettier

### Build
- `npm run build` - Production build (outputs to ../web/)
- `npm run build:dev` - Development build

## Testing Practices

**Location:** `src/**/*.spec.ts` with test helpers in `src/testing/test-helpers.ts`

**Structure:**
```typescript
describe('ClassName', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({ ... });
  });

  it('should do something', () => { ... });
});
```

### Key Patterns

**1. Vitest Mocking (NOT Jasmine):**
```typescript
import { vi } from 'vitest';
const mockFn = vi.fn().mockReturnValue(value);
vi.spyOn(object, 'method');
```

**2. HTTP Testing:**
```typescript
const httpMock = TestBed.inject(HttpTestingController);
const req = httpMock.expectOne(url);
req.flush(mockData);
httpMock.verify(); // In afterEach
```

**3. Test Helpers (`src/testing/test-helpers.ts`):**
- Mock factories: `createMockDashboard()`, `createMockLink()`
- Service mocks: `createMockApiService()`, `createMockRouter()`
- DOM helpers: `queryByTestId()`, `clickElement()`

**4. Component Testing:**
- Use `ComponentFixture<T>` for rendering
- Call `fixture.detectChanges()` after state changes
- Query: `fixture.nativeElement.querySelector()`

## Code Style

### Component Structure
```typescript
@Component({
  selector: 'app-component-name',
  standalone: true,
  imports: [CommonModule, MaterialModules],
  template: '...'
})
export class ComponentNameComponent {
  private service = inject(ApiService);

  // Component logic
}
```

### Conventions
- **Standalone components** (NO NgModules)
- Use `inject()` function or constructor injection
- RxJS operators for data transformation
- Interfaces for all models
- API responses wrapped in `ApiResponse<T>`, extract with `.pipe(map(r => r.data!))`

### Naming
- Services: `*Service` (e.g., `ApiService`)
- Components: `*Component` (e.g., `DashboardListComponent`)
- Models: Interface matching backend entity (e.g., `Dashboard`, `Link`)

### TypeScript Config
- Strict mode enabled
- Prettier: 100 char width, single quotes, 2 space indent

## Documentation References

- [docs/TESTING.md](docs/TESTING.md) - Complete testing guide (why Vitest, mocking patterns, troubleshooting)
- [docs/FRONTEND_SETUP.md](docs/FRONTEND_SETUP.md) - Architecture, directory structure, API mapping, routing
- [docs/BUILD_INSTRUCTIONS.md](docs/BUILD_INSTRUCTIONS.md) - Build and deployment instructions
- [docs/STYLING_GUIDE.md](docs/STYLING_GUIDE.md) - Angular Material styling guide
- Backend context: See [../CLAUDE.md](../CLAUDE.md)

## Do Not

### Critical Commands
- ❌ **DO NOT** use `npm test` for CI (keeps processes running) → ✅ Use `npm run test:ci`

### Testing
- ❌ **DO NOT** use Jasmine mocking (`jasmine.createSpyObj`) → ✅ Use Vitest `vi.fn()`
- ❌ **DO NOT** use `.and.returnValue()` → ✅ Use `.mockReturnValue()`

### Architecture
- ❌ **DO NOT** create NgModules → ✅ Use standalone components
- ❌ **DO NOT** commit `environment.ts` (gitignored) → ✅ Copy from `environment.ts.dist`

## API Integration

- **Base URL:** `/api/` (proxied to PHP backend in dev)
- **Response Format:** All responses wrapped in `ApiResponse<T>` with `data` property
- **Error Handling:** Backend returns validation errors in structured format
- **Models:** TypeScript interfaces match PHP entity structures
