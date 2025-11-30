# BookmarkBureau

**A bookmark management system demonstrating clean architecture with [Middle Framework](https://github.com/jschreuder/Middle)**

[![Build](https://github.com/jschreuder/BookmarkBureau/actions/workflows/ci.yml/badge.svg)](https://github.com/jschreuder/BookmarkBureau/actions)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%2010-brightgreen.svg?style=flat)](phpstan.neon)
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

**Action Layer**
- Consistent three-phase pattern (filter/validate/execute)
- InputSpec/OutputSpec for HTTP boundaries
- Zero coupling to HTTP framework

**Service Layer**
- Business logic coordination
- Flexible extension via OperationPipeline for cross-cutting concerns (transactions, logging, auditing)
- Clean interfaces for testing

**Repository Layer**
- PDO-based implementations, but interface-first and thus easily replaceable
- File-based alternatives for specific use cases (UserRepository, JwtJtiRepository)
- Entity mappers handle database in/out translation
- Cross-database compatible (MySQL/SQLite/PostgreSQL)
- Optimized queries, N+1 prevention

**Configuration & DI**
- Pure PHP configuration interfaces and implementations (no YAML/XML)
- Trait-based service container composition for modularity
- Compile-time type safety throughout

#### Domain Model Architecture

BookmarkBureau's domain layer is built on three complementary abstractions:

**Entity** → Domain objects with identity and lifecycle  
Examples: `User`, `Link`, `Dashboard`, `Category`  
- Mutable within transactions
- Identity is based on ID property
- Rich behaviors possible through methods
- Properties publicly accessible, but limitations possible using readonly & PHP 8.4 property hooks

**Value** → Immutable domain values without identity  
Examples: `Url`, `Title`, `HexColor`, `Icon`, `TagName`  
- Identity is based on full content
- Self-validating basic structure on construction (only formatting)
- Read only, cannot be changed after creation
- Fail-fast validation prevents invalid states

**Composite** → Type-safe compositions of entities and values  
Examples: `LinkCollection`, `CategoryWithLinks`, `DashboardWithCategoriesAndFavorites`  
- Readonly structures for returning complex data from services or repositories
- Built from domain language, not database concerns
- Aggregates for heterogeneous compositions
- Collections are a subtype for homogeneous groups

```php
// Entity: mutable within transactions, identity-based
$link = new Link($linkId, $url, $title, ...);
$link->url = new Url('https://updated.example');

// Value: immutable, self-validating, structural equality
$url = new Url('https://example.com'); // Throws if invalid

// Composite: readonly aggregation for specific use cases
$view = new CategoryWithLinks($category, $links);
```

This three-pillar approach creates a complete domain language: Entities represent your business concepts, Values ensure correctness, and Composites provide type-safe data structures. Together they enable compile-time safety while remaining framework-agnostic and testable.

## Quick Start

### Docker (Recommended)

**Requirements**: Docker or Podman

```bash
# Using Docker
docker run -d \
  --name bookmarkbureau \
  -p 8080:8080 \
  -v bb-data:/var/www/var \
  -e JWT_SECRET=$(openssl rand -hex 32) \
  -e SITE_URL=http://localhost:8080/api.php \
  ghcr.io/jschreuder/bookmark-bureau:latest

# Using Podman (rootless)
podman run -d \
  --name bookmarkbureau \
  -p 8080:8080 \
  -v bb-data:/var/www/var \
  -e JWT_SECRET=$(openssl rand -hex 32) \
  -e SITE_URL=http://localhost:8080/api.php \
  ghcr.io/jschreuder/bookmark-bureau:latest

# Using docker-compose
curl -O https://raw.githubusercontent.com/jschreuder/BookmarkBureau/master/docker-compose.yml
# Edit JWT_SECRET in docker-compose.yml
docker-compose up -d
```

**Environment Variables**:
- `JWT_SECRET` (required) - Secret key for JWT tokens (use a secure random value)
- `SITE_URL` (optional) - Base URL including `/api.php`, default: `http://localhost:8080/api.php`
- `SESSION_TTL` (optional) - Session timeout in seconds, default: `1800` (30 min)

Access the application at `http://localhost:8080`

### Local Development

**Requirements**: PHP 8.4+, Composer, MySQL 8.0+ or SQLite

```bash
git clone https://github.com/jschreuder/BookmarkBureau.git
cd BookmarkBureau
composer install
cp config/dev.php.example config/dev.php
# Edit config/dev.php with your database credentials
vendor/bin/phinx migrate
```

**Run Development Server**:

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
  /Command             CLI commands for application management
  /Composite           Immutable data structures composing entities/values
  /Config              Configuration interfaces and implementations
  /Controller          HTTP controllers (generic ActionController)
  /Entity              Domain entities with value objects
    /Mapper            Entity-to-database mapping layer
  /Exception           Custom exception hierarchy
  /HttpMiddleware      PSR-15 HTTP middleware components
  /InputSpec           Request filtering and validation
  /OperationMiddleware Transaction/logging middleware for service operations
  /OperationPipeline   Pipeline system for cross-cutting concerns
  /OutputSpec          Response serialization
  /Repository          Data access layer (interfaces + PDO/file-based)
  /Response            Response transformers (JSON, etc.)
  /Service             Business logic coordination
  /ServiceContainer    DI container trait-based composition
  /Util                Shared utilities (SqlBuilder, Filter, etc.)

  *RoutingProvider.php Route registration by domain area

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
