# BookmarkBureau - Backend Context

## Project Overview

BookmarkBureau is a bookmark management application demonstrating clean architecture with PHP 8.4 and Angular 20. This is a **demonstration project** showcasing the Middle Framework's patterns and compile-time dependency injection.

**Monorepo Structure:** PHP backend at root, Angular frontend in `/frontend/` directory.

## Tech Stack

- **Language:** PHP 8.4+ (uses property hooks)
- **Framework:** Middle (custom PSR-15 micro-framework with compile-time DI)
- **Testing:** Pest 4.1+ (NOT PHPUnit)
- **Database:** PDO with binary UUID storage (CHAR(16))
- **Routing:** Symfony Router
- **Validation:** Respect/Validation
- **Migrations:** Phinx
- **Mocking:** Mockery

## Project Structure

```
/                           - PHP backend root
├── src/                    - PHP source (108 files)
│   ├── Entity/            - Domain models with business logic
│   ├── Composite/         - Type-safe compositions (collections, aggregates)
│   ├── Repository/        - Data access interfaces + PDO implementations
│   ├── Service/           - Business logic coordination with OperationPipeline
│   ├── OperationPipeline/ - Middleware pipeline for cross-cutting concerns (transactions, logging, etc.)
│   ├── Action/            - Request handlers (filter/validate/execute pattern)
│   ├── Controller/        - HTTP layer (ActionController, DashboardViewController)
│   └── Util/              - Shared utilities (Filter, SqlFormat)
├── tests/                  - Pest tests (85 files, ~95% coverage)
├── config/                 - DI container configuration
├── migrations/             - Phinx database migrations
├── var/log                 - Application logs, when CLI or integration test output is limited
├── web/                    - Public directory (api.php entry point)
└── frontend/               - Angular 20 SPA (see frontend/CLAUDE.md)
```

## Commands

### Testing
- `vendor/bin/pest --parallel` - Run ALL tests, use parallel to speed up execution (IMPORTANT: Use this, NOT phpunit)
- `vendor/bin/pest --filter TestName` - Run specific test
- `vendor/bin/pest tests/Path/ToTest.php` - Run specific file

### Development
- `php -S localhost:8080 -t web` - Start PHP dev server
- `vendor/bin/phinx migrate` - Run database migrations

## Architecture: Clean Architecture + DDD

**Pattern:** Dependency flow from inside → outside. Inner layers define interfaces, outer layers implement.

**Layers (inside → outside):**

1. **Domain** (`/src/Entity`) - Entities with business logic, Value Objects with validation
2. **Repository** (`/src/Repository`) - Data access interfaces + PDO implementations
3. **Service** (`/src/Service`) - Business logic coordination with OperationPipeline for cross-cutting concerns
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

## Code Style

### File Structure
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

### Conventions
- Readonly classes where possible (especially for stateless and value objects)
- Constructor property promotion
- Typed properties (strict types enforced)
- Interface-first design (use interfaces where replacing a service makes sense, not everywhere)
- Final classes by default (no inheritance)
- Override attribute for interface methods
- Traits for sharing code between implementations (e.g., `OutputSpecTrait`, `CollectionTrait`)
- Utility classes in `src/Util/` for shared functionality (e.g., `Filter` for input sanitization, `SqlBuilder` for SQL generation, `SqlExceptionHandler` for error detection)
- Use double quotes `"` for strings and interpolation with brackets `"{$value}"` where possible
- OperationPipeline pattern for service cross-cutting concerns (transactions, logging, auditing, caching)

### Naming
- Interfaces: `*Interface` (e.g., `DashboardServiceInterface`)
- Implementations: `Pdo*Repository`, `*Service`
- Actions: `{Entity}{Operation}Action` (e.g., `LinkCreateAction`)
- Entities: `final class` (NOT readonly - mutable with property hooks)
- Actions/Controllers: `final readonly class` (immutable)
- Value Objects: `Value\*` namespace (immutable, validate in constructor)
- Traits: `*Trait` suffix

### The Action Pattern

**For Simple CRUD Operations:**
Use the three-phase Action pattern:
1. `filter()` - Sanitize raw input via InputSpec
2. `validate()` - Validate filtered data (throws ValidationException)
3. `execute()` - Execute business logic, return data for OutputSpec

**When NOT to use Action Pattern:**
Skip when operations don't fit this mold. Complex operations should use Controllers directly.
Example: `DashboardViewController` - implements `ControllerInterface` + filter/validate interfaces, but handles complex view composition that doesn't fit the Action pattern.

### Key Patterns - See Examples
- **OperationPipeline for services**: Each service has `*ServicePipelines` companion class defining middleware per method. Replaced UnitOfWork. See `src/Service/DashboardService.php` + `src/Service/DashboardServicePipelines.php`
- **Entity mutations with property hooks**: See `src/Entity/Link.php` - `set { markAsUpdated() }` pattern when entity has an updatedAt property
- **Input filtering**: See `src/InputSpec/*` - uses `Util\Filter` fluent API for sanitization
- **Route registration**: See `src/*RoutingProvider.php` - uses `Util\ResourceRouteBuilder` for RESTful routes
- **Repository SQL**: Use `SqlBuilder` for queries (INSERT/UPDATE/DELETE return `['sql' => '...', 'params' => [...]]`, SELECT/COUNT/MAX return SQL string only). Use `SqlExceptionHandler::isForeignKeyViolation()` and `isDuplicateEntry()` for error detection. Always use named parameters (`:field_name`), never positional (`?`)

## Documentation References

- [README.md](README.md) - Project overview, Middle Framework rationale, Action Pattern explanation
- [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md) - Complete implementation checklist, API endpoints, database schema
- Frontend docs: See [frontend/CLAUDE.md](frontend/CLAUDE.md) and [frontend/docs/](frontend/docs/)

## Do Not

### Code style
- ❌ **DO NOT** use single quotes `'`

### Critical Commands
- ❌ **DO NOT** use `phpunit` directly → ✅ Use `vendor/bin/pest`

### Testing
- ❌ **DO NOT** use PHPUnit assertions → ✅ Use Pest `expect()` syntax
- ❌ **DO NOT** use PHPUnit mocks → ✅ Use Mockery

### Architecture
- ❌ **DO NOT** use string-based container resolution → ✅ Use typed container methods
- ❌ **DO NOT** force the Action pattern on complex operations → ✅ Use Controllers for operations that don't fit simple CRUD
- ❌ **DO NOT** create interfaces unnecessarily → ✅ Only use interfaces where replacing a service makes sense
- ❌ **DO NOT** use inheritance → ✅ Use composition or traits
- ❌ **DO NOT** add magic/hidden behavior
- ❌ **DO NOT** use facades or auto-wiring
- ❌ **DO NOT** break compile-time safety
- ❌ **DO NOT** implement auth/authorization (intentionally omitted for demo)

## Workflow Notes

- **Git Branch:** Working on `master` (no branch protection for demo)
- **Testing Requirement:** Run tests before commits (but no CI enforcing it)
- **API Pattern:** `/api/*` routes to PHP backend (`web/api.php`), all other routes to Angular SPA
- **Frontend Build:** Frontend builds to `web/`, served alongside API
Getting rid of some of my old code-style tendencies
