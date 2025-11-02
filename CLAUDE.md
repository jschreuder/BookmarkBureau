# BookmarkBureau - Claude Context File

## Project Overview

BookmarkBureau is a bookmark management application demonstrating clean architecture with PHP 8.4 and Angular 20. This is a **demonstration project** showcasing the Middle Framework's patterns and compile-time dependency injection.

## Tech Stack

### Backend (Root Directory)
- **Language:** PHP 8.4+ (uses property hooks)
- **Framework:** Middle (custom PSR-15 micro-framework with compile-time DI)
- **Testing:** Pest 4.1+ (NOT PHPUnit)
- **Database:** PDO with binary UUID storage (CHAR(16))
- **Routing:** Symfony Router
- **Validation:** Respect/Validation
- **Migrations:** Phinx
- **Mocking:** Mockery

### Frontend (frontend/ directory)
- **Framework:** Angular 20.3+
- **UI:** Angular Material 20.2+
- **Language:** TypeScript 5.9+ (strict mode)
- **Testing:** Vitest 3.2+ (NOT Karma/Jasmine)
- **Build Output:** `../web/` (monorepo setup)

## Project Structure

```
/                           - PHP backend root
├── src/                    - PHP source (108 files)
│   ├── Entity/            - Domain models with business logic
│   ├── Repository/        - Data access interfaces + PDO implementations
│   ├── Service/           - Business logic coordination
│   ├── Action/            - Request handlers (filter/validate/execute pattern)
│   └── Controller/        - HTTP layer (ActionController, DashboardViewController)
├── tests/                  - Pest tests (85 files, ~95% coverage)
├── frontend/               - Angular 20 SPA
│   ├── src/app/admin/     - Admin CRUD interfaces
│   ├── src/app/dashboard/ - Public dashboard views
│   ├── src/app/core/      - Services and models
│   └── src/app/shared/    - Shared components
├── config/                 - DI container configuration
├── migrations/             - Phinx database migrations
└── web/                    - Public directory (frontend build output + api.php)
```

## Commands

### Backend Testing
- `vendor/bin/pest` - Run ALL tests (IMPORTANT: Use this, NOT phpunit)
- `vendor/bin/pest --filter TestName` - Run specific test
- `vendor/bin/pest tests/Path/ToTest.php` - Run specific file

### Frontend Testing
- `cd frontend && npm run test:ci` - Run tests ONCE with coverage (IMPORTANT: Use test:ci, NOT npm test)
- `cd frontend && npm test` - Watch mode (WARNING: Keeps processes running in background)
- `cd frontend && npm run test:ci -- path/to/file.spec.ts` - Run specific test

### Development
- `php -S localhost:8080 -t web` - Start PHP dev server
- `cd frontend && npm start` - Start Angular dev server (ng serve)
- `vendor/bin/phinx migrate` - Run database migrations

### Build
- `cd frontend && npm run build` - Production build (outputs to ../web/)

## Architecture: Clean Architecture + DDD

**Pattern:** Dependency flow from inside → outside. Inner layers define interfaces, outer layers implement.

**Layers (inside → outside):**

1. **Domain** (`/src/Entity`) - Entities with business logic, Value Objects with validation
2. **Repository** (`/src/Repository`) - Data access interfaces + PDO implementations
3. **Service** (`/src/Service`) - Business logic coordination, UnitOfWork pattern
4. **Action** (`/src/Action`) - Three-phase request handlers: `filter() → validate() → execute()`
5. **Controller** (`/src/Controller`) - HTTP layer, error handling

**Key Principles (SOLID):**
- **Single Responsibility** - Small, focused classes (Actions: 35-55 lines, Entities: 11-69 lines)
- **Open/Closed** - Extension through interfaces and traits, not modification
- **Liskov Substitution** - All implementations honor their interface contracts
- **Interface Segregation** - Small, focused interfaces (2-5 methods typical; repositories larger but cohesive)
- **Dependency Inversion** - High-level modules depend on abstractions, constructor injection throughout
- **Zero Magic** - Explicit code, no hidden behavior, no convention-based routing
- **Compile-Time Safety** - Middle-DI provides type-safe container (no string-based resolution)

## Testing Practices

### PHP/Pest Testing

**Location:** `tests/` with `Pest.php` containing test helpers

**Structure:**
```php
describe('ClassName', function () {
    describe('method name', function () {
        test('should do something specific', function () {
            // Arrange, Act, Assert
        });
    });
});
```

**Key Patterns:**

1. **TestEntityFactory** (in Pest.php) - Use for creating test entities:
   ```php
   $dashboard = TestEntityFactory::createDashboard();
   $link = TestEntityFactory::createLink(title: 'Custom Title');
   ```

2. **Mockery for Mocking** (NOT PHPUnit mocks):
   ```php
   $service = Mockery::mock(ServiceInterface::class);
   $service->shouldReceive('method')->with($arg)->andReturn($result);
   ```

3. **Pest Expectations:**
   - `expect($value)->toBe($expected)`
   - `expect($value)->toHaveKey('key')`
   - `expect(fn() => $action())->toThrow(ExceptionClass::class)`

4. **Testing Actions:** For Actions using the three-phase pattern, test each phase separately plus integration tests

### Frontend/Vitest Testing

**Location:** `frontend/src/**/*.spec.ts` with `frontend/src/testing/test-helpers.ts`

**Structure:**
```typescript
describe('ClassName', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({ ... });
  });

  it('should do something', () => { ... });
});
```

**Key Patterns:**

1. **Vitest Mocking** (NOT Jasmine):
   ```typescript
   import { vi } from 'vitest';
   const mockFn = vi.fn().mockReturnValue(value);
   vi.spyOn(object, 'method');
   ```

2. **HTTP Testing:**
   ```typescript
   const httpMock = TestBed.inject(HttpTestingController);
   const req = httpMock.expectOne(url);
   req.flush(mockData);
   httpMock.verify(); // In afterEach
   ```

3. **Test Helpers** (`test-helpers.ts`):
   - Mock factories: `createMockDashboard()`, `createMockLink()`
   - Service mocks: `createMockApiService()`, `createMockRouter()`
   - DOM helpers: `queryByTestId()`, `clickElement()`

4. **Component Testing:**
   - Use `ComponentFixture<T>` for rendering
   - Call `fixture.detectChanges()` after state changes
   - Query: `fixture.nativeElement.querySelector()`

## Code Style

### PHP Conventions

**File Structure:**
```php
<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Action;

final readonly class ImplementationClass implements SpecificInterface
{
    public function __construct(
        private ServiceInterface $service,
        private OtherService $secondService
    ) {}

    #[\Override]
    public function publicMethod(array $rawData): array { }

    private function privateMethod(array $data): array { }
}
```

**Conventions:**
- Readonly classes where possible (especially for stateless and value objects)
- Constructor property promotion
- Typed properties (strict types enforced)
- Interface-first design (use interfaces where replacing a service makes sense, not everywhere)
- Final classes by default (no inheritance)
- Override attribute for interface methods
- Traits for sharing code between implementations (e.g., `UnitOfWorkTrait`, `OutputSpecTrait`, `CollectionTrait`)
- Utility classes in `src/Util/` for shared functionality (e.g., `Filter` for input sanitization, `SqlFormat` for constants)

**Naming:**
- Interfaces: `*Interface` (e.g., `DashboardServiceInterface`)
- Implementations: `Pdo*Repository`, `*Service`
- Actions: `{Entity}{Operation}Action` (e.g., `LinkCreateAction`)
- Entities: `final class` (NOT readonly - mutable with property hooks)
- Actions/Controllers: `final readonly class` (immutable)
- Value Objects: `Value\*` namespace (immutable, validate in constructor)
- Traits: `*Trait` suffix

**The Action Pattern (For Simple CRUD):**
Use the three-phase Action pattern for **simple CRUD operations**:
1. `filter()` - Sanitize raw input via InputSpec
2. `validate()` - Validate filtered data (throws ValidationException)
3. `execute()` - Execute business logic, return data for OutputSpec

**When NOT to use Action Pattern:**
Skip the Action pattern when operations don't fit this mold. Complex operations should use Controllers directly.
See `DashboardViewController` - implements `ControllerInterface` + filter/validate interfaces, but handles complex view composition that doesn't fit the Action pattern.

**Key Patterns - See Examples:**
- **Entity mutations with property hooks**: See `src/Entity/Link.php` - `set { markAsUpdated() }` pattern when entity has an updatedAt property
- **Input filtering**: See `src/InputSpec/*` - uses `Util\Filter` fluent API for sanitization
- **Route registration**: See `src/*RoutingProvider.php` - uses `Util\ResourceRouteBuilder` for RESTful routes

### TypeScript/Angular Conventions

**Component Structure:**
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

**Conventions:**
- Standalone components (NO NgModules)
- Use `inject()` function or constructor injection
- RxJS operators for data transformation
- Interfaces for all models
- API responses wrapped in `ApiResponse<T>`, extract with `.pipe(map(r => r.data!))`

**Naming:**
- Services: `*Service` (e.g., `ApiService`)
- Components: `*Component` (e.g., `DashboardListComponent`)
- Models: Interface matching backend entity (e.g., `Dashboard`, `Link`)

**Config:**
- TypeScript strict mode enabled
- Prettier: 100 char width, single quotes, 2 space indent

## Documentation References

**Root Documentation:**
- [README.md](README.md) - Project overview, Middle Framework rationale, Action Pattern explanation
- [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md) - Complete implementation checklist, API endpoints, database schema

**Frontend Documentation:**
- [frontend/docs/TESTING.md](frontend/docs/TESTING.md) - Complete testing guide (why Vitest, mocking patterns, troubleshooting)
- [frontend/docs/FRONTEND_SETUP.md](frontend/docs/FRONTEND_SETUP.md) - Architecture, directory structure, API mapping, routing
- [frontend/docs/BUILD_INSTRUCTIONS.md](frontend/docs/BUILD_INSTRUCTIONS.md) - Build and deployment instructions
- [frontend/docs/STYLING_GUIDE.md](frontend/docs/STYLING_GUIDE.md) - Angular Material styling guide

## Do Not

### Critical Commands
- ❌ **DO NOT** use `npm test` for CI (keeps processes running) → ✅ Use `npm run test:ci`
- ❌ **DO NOT** use `phpunit` directly → ✅ Use `vendor/bin/pest`

### PHP Backend
- ❌ **DO NOT** use PHPUnit assertions → ✅ Use Pest `expect()` syntax
- ❌ **DO NOT** use PHPUnit mocks → ✅ Use Mockery
- ❌ **DO NOT** use string-based container resolution → ✅ Use typed container methods
- ❌ **DO NOT** force the Action pattern on complex operations → ✅ Use Controllers for operations that don't fit simple CRUD
- ❌ **DO NOT** create interfaces unnecessarily → ✅ Only use interfaces where replacing a service makes sense
- ❌ **DO NOT** use inheritance → ✅ Use composition or traits

### Frontend
- ❌ **DO NOT** use Jasmine mocking (`jasmine.createSpyObj`) → ✅ Use Vitest `vi.fn()`
- ❌ **DO NOT** use `.and.returnValue()` → ✅ Use `.mockReturnValue()`
- ❌ **DO NOT** create NgModules → ✅ Use standalone components
- ❌ **DO NOT** commit `environment.ts` (gitignored) → ✅ Copy from `environment.ts.dist`

### Architecture
- ❌ **DO NOT** add magic/hidden behavior
- ❌ **DO NOT** use facades or auto-wiring
- ❌ **DO NOT** break compile-time safety
- ❌ **DO NOT** implement auth/authorization (intentionally omitted for demo)

## Workflow Notes

- **Git Branch:** Working on `master` (no branch protection for demo)
- **Testing Requirement:** Run tests before commits (but no CI enforcing it)
- **Build Output:** Frontend builds to `../web/`, backend serves from `web/api.php`
