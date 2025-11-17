# BookmarkBureau

**A bookmark management system demonstrating clean architecture with [Middle Framework](https://github.com/jschreuder/Middle)**

[![Build](https://github.com/jschreuder/BookmarkBureau/actions/workflows/ci.yml/badge.svg)](https://github.com/jschreuder/BookmarkBureau/actions)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=jschreuder_BookmarkBureau&metric=security_rating)](https://sonarcloud.io/dashboard?id=jschreuder_BookmarkBureau)
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=jschreuder_BookmarkBureau&metric=reliability_rating)](https://sonarcloud.io/dashboard?id=jschreuder_BookmarkBureau)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=jschreuder_BookmarkBureau&metric=sqale_rating)](https://sonarcloud.io/dashboard?id=jschreuder_BookmarkBureau)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=jschreuder_BookmarkBureau&metric=coverage)](https://sonarcloud.io/dashboard?id=jschreuder_BookmarkBureau)

> ⚠️ **Development Status**: Very much in development.

## What is This?

BookmarkBureau is a real-world application demonstrating how to build production-quality APIs without magic:

- ✅ **Type-safe throughout** - Compile-time dependency injection with [Middle-DI](https://github.com/jschreuder/Middle-DI)
- ✅ **Clean architecture** - Action pattern, DDD principles, Repository pattern
- ✅ **Zero magic** - Explicit code, no hidden behavior, full IDE support
- ✅ **High quality** - SonarCloud Grade A ratings, comprehensive test coverage
- ✅ **Modern PHP** - PHP 8.4 features including property hooks

This demonstrates patterns, practices, and architecture that actually work.

## Why Middle Framework?

### Coming from mainstream PHP frameworks?

Want compile-time safety instead of runtime errors? Clear patterns instead of magic? Architectural discipline instead of rapid prototyping?

```php
// Traditional PHP DI (runtime errors possible)
$service = $container->get('user.service');

// Middle-DI (compile-time safe, full IDE support)
$service = $container->getUserService();
```

### Coming from Go/Rust?

Want similar explicitness and type safety in PHP? Performance without complexity? Compiler-level guarantees?

**Middle brings these philosophies to PHP.**

## The Action Pattern

A layer built on top of the common Controller pattern, BookmarkBureau uses a three-phase action pattern throughout:

```php
// One line to wire up a complete CRUD operation
$router->post('/link', fn() => new ActionController(
    new LinkCreateAction(
        $container->getLinkService(),
        new LinkInputSpec(),
        new LinkOutputSpec()
    ),
    new JsonResponseTransformer()
));
```

Each action follows the same clear pattern:

```php
final readonly class LinkCreateAction implements ActionInterface
{
    // 1. Filter: Sanitize raw input (never throws)
    public function filter(array $rawData): array
    {
        return $this->inputSpec->filter($rawData);
    }

    // 2. Validate: Check constraints (throws on invalid)
    public function validate(array $data): void
    {
        $this->inputSpec->validate($data);
    }

    // 3. Execute: Perform the operation (transactional)
    public function execute(array $data): array
    {
        $link = $this->linkService->createLink(...);
        return $this->outputSpec->transform($link);
    }
}
```

**Result**: 30 lines of clear, testable code. No magic. Full type safety. Any developer can add new entities following this exact pattern.

## Current Features

### Implemented Entities

- **Dashboards** - Container for organizing bookmarks
- **Categories** - Grouped collections within dashboards
- **Links** - Individual bookmarks with metadata
- **Favorites** - Quick-access links per dashboard
- **Tags** - Labels for organizing links

### API Operations

All entities have complete CRUD operations:

```
POST   /dashboard              Create dashboard
PUT    /dashboard/{id}         Update dashboard
DELETE /dashboard/{id}         Delete dashboard

POST   /category               Create category
GET    /category/{id}          Read category
PUT    /category/{id}          Update category
DELETE /category/{id}          Delete category

POST   /link                   Create link
GET    /link/{id}              Read link
PUT    /link/{id}              Update link
DELETE /link/{id}              Delete link

POST   /dashboard/{id}/favorites       Add favorite
DELETE /dashboard/{id}/favorites       Remove favorite
PUT    /dashboard/{id}/favorites       Reorder favorites
```

### Architecture Highlights

**Domain Layer**
- *Value objects* with validation (Title, Url, HexColor, Icon)
- *Rich entities* with property hooks (PHP 8.4)
- *Type-safe compositions* (collections and aggregates)

**Service Layer**
- Business logic coordination
- Transaction management via UnitOfWork pattern
- Clean interfaces for testing

**Repository Layer**
- PDO-based implementations, but inferface-first and thus easily replacable
- Cross-database compatible (MySQL/SQLite)
- Optimized queries, N+1 prevention

**Action Layer**
- Consistent three-phase pattern (filter/validate/execute)
- InputSpec/OutputSpec for HTTP boundaries
- Zero coupling to HTTP framework

## Quick Start

### Requirements

- PHP 8.4+
- Composer
- MySQL 8.0+ or SQLite

### Installation

```bash
git clone https://github.com/jschreuder/BookmarkBureau.git
cd BookmarkBureau
composer install
cp config/dev.php.example config/dev.php
# Edit config/dev.php with your database credentials
vendor/bin/phinx migrate
```

### Run Development Server

```bash
php -S localhost:8080 -t web
```

### Example API Calls

```bash
# Create a dashboard
curl -X POST http://localhost:8080/dashboard \
  -H "Content-Type: application/json" \
  -d '{"title":"My Dashboard","description":"Personal bookmarks","icon":"🏠"}'

# Create a link
curl -X POST http://localhost:8080/link \
  -H "Content-Type: application/json" \
  -d '{"url":"https://example.com","title":"Example","description":"A site","icon":"🔗"}'

# Get a link
curl http://localhost:8080/link/{id}
```

## Project Structure

```
/src
  /Action              CRUD operations following three-phase pattern
  /Composite           Type-safe composition classes (collections, aggregates)
  /Controller          HTTP controllers (generic ActionController)
  /Entity              Domain entities with value objects
  /Exception           Custom exception hierarchy
  /InputSpec           Request filtering and validation
  /OutputSpec          Response serialization
  /Repository          Data access layer (interfaces + PDO)
  /Service             Business logic coordination
  /Service/UnitOfWork  Transaction management
  /Util                Shared utilities

  GeneralRoutingProvider.php  Route registration
  ServiceContainer.php        DI container definition

/migrations            Database migrations
/web                   Application entry point
/config                Configuration files
```

## Framework Comparison

### vs Magic & Conventions

```php
// Those using facades: magic methods, runtime resolution, hidden dependencies
$user = User::find($id);
Route::resource('products', ProductController::class);

// Middle: Explicit code, compile-time safety, dependency inversion
$user = $this->userRepository->findById($userId);
$router->post('/products', fn() => new ActionController(...));
```

**Middle wins on**: Type safety, explicitness, testability
**Magic/conventions wins on**: Speed of development, ecosystem

### vs Configuration Heavy

```yaml
# Those using YAML/XML configuration
services:
  App\Service\UserService:
    arguments: ['@doctrine.orm.entity_manager']
```

```php
// Middle: Zero configuration, just PHP
public function getUserService(): UserService
{
    return new UserService($this->getEntityManager());
}
```

**Middle wins on**: Zero config, IDE support, simplicity
**Configuration-heavy wins on**: Enterprise features, maturity

### vs Pure Microframework

```php
// Microframework: Minimal structure, bring everything yourself
$app->post('/products', function ($request, $response) {
    // You build everything from scratch
});
```

```php
// Middle: Patterns provided, structure included
$router->post('/products', fn() => new ActionController(
    new ProductCreateAction(...), // Clear pattern to follow
    new JsonResponseTransformer()
));
```

**Middle wins on**: Architectural patterns, DI, structure
**Microframework wins on**: Pure minimalism, flexibility

## Learn More

- [Middle Framework](https://github.com/jschreuder/Middle) - PSR-15 routing & middleware
- [Middle-DI](https://github.com/jschreuder/Middle-DI) - Compile-time dependency injection
- [MiddleAuth](https://github.com/jschreuder/MiddleAuth) - ACL/RBAC/ABAC authorization

## Contributing

This project primarily serves as a demonstration of Middle Framework patterns. However, contributions that improve the demonstration value or application quality are welcome.

## License

MIT

---

**Built with discipline. Designed for maintainability. Proven patterns for production.**
