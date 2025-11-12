<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

// uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

/*expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});*/

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

use jschreuder\BookmarkBureau\Collection\TagCollection;
use jschreuder\BookmarkBureau\Entity\Category;
use jschreuder\BookmarkBureau\Entity\CategoryLink;
use jschreuder\BookmarkBureau\Entity\Dashboard;
use jschreuder\BookmarkBureau\Entity\Favorite;
use jschreuder\BookmarkBureau\Entity\Link;
use jschreuder\BookmarkBureau\Entity\Tag;
use jschreuder\BookmarkBureau\Entity\User;
use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Entity\Value\HashedPassword;
use jschreuder\BookmarkBureau\Entity\Value\HexColor;
use jschreuder\BookmarkBureau\Entity\Value\Icon;
use jschreuder\BookmarkBureau\Entity\Value\TagName;
use jschreuder\BookmarkBureau\Entity\Value\Title;
use jschreuder\BookmarkBureau\Entity\Value\TotpSecret;
use jschreuder\BookmarkBureau\Entity\Value\Url;
use jschreuder\BookmarkBureau\ServiceContainer;
use jschreuder\MiddleDi\DiCompiler;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Test entity factory for creating test entities with sensible defaults.
 * Using static methods keeps the global namespace clean and avoids needing use() clauses in closures.
 */
class TestEntityFactory
{
    public static function createDashboard(
        ?UuidInterface $id = null,
        ?Title $title = null,
        ?string $description = null,
        ?Icon $icon = null,
        ?DateTimeInterface $createdAt = null,
        ?DateTimeInterface $updatedAt = null,
    ): Dashboard {
        return new Dashboard(
            dashboardId: $id ?? Uuid::uuid4(),
            title: $title ?? new Title("Test Dashboard"),
            description: $description ?? "Test Description",
            icon: $icon ?? new Icon("dashboard-icon"),
            createdAt: $createdAt ??
                new DateTimeImmutable("2024-01-01 12:00:00"),
            updatedAt: $updatedAt ??
                new DateTimeImmutable("2024-01-01 12:00:00"),
        );
    }

    public static function createLink(
        ?UuidInterface $id = null,
        ?Url $url = null,
        ?Title $title = null,
        ?string $description = null,
        ?Icon $icon = null,
        ?DateTimeInterface $createdAt = null,
        ?DateTimeInterface $updatedAt = null,
        ?TagCollection $tags = null,
    ): Link {
        return new Link(
            linkId: $id ?? Uuid::uuid4(),
            url: $url ?? new Url("https://example.com"),
            title: $title ?? new Title("Example Title"),
            description: $description ?? "Example Description",
            icon: $icon,
            createdAt: $createdAt ??
                new DateTimeImmutable("2024-01-01 12:00:00"),
            updatedAt: $updatedAt ??
                new DateTimeImmutable("2024-01-01 12:00:00"),
            tags: $tags ?? new TagCollection(),
        );
    }

    public static function createCategory(
        ?UuidInterface $id = null,
        ?Dashboard $dashboard = null,
        ?Title $title = null,
        ?HexColor $color = null,
        ?int $sortOrder = null,
        ?DateTimeInterface $createdAt = null,
        ?DateTimeInterface $updatedAt = null,
    ): Category {
        return new Category(
            categoryId: $id ?? Uuid::uuid4(),
            dashboard: $dashboard ?? self::createDashboard(),
            title: $title ?? new Title("Test Category"),
            color: $color,
            sortOrder: $sortOrder ?? 0,
            createdAt: $createdAt ??
                new DateTimeImmutable("2024-01-01 12:00:00"),
            updatedAt: $updatedAt ??
                new DateTimeImmutable("2024-01-01 12:00:00"),
        );
    }

    public static function createTag(
        ?TagName $tagName = null,
        ?HexColor $color = null,
    ): Tag {
        return new Tag(
            tagName: $tagName ?? new TagName("example-tag"),
            color: $color,
        );
    }

    public static function createFavorite(
        ?Dashboard $dashboard = null,
        ?Link $link = null,
        ?int $sortOrder = null,
        ?DateTimeInterface $createdAt = null,
    ): Favorite {
        return new Favorite(
            dashboard: $dashboard ?? self::createDashboard(),
            link: $link ?? self::createLink(),
            sortOrder: $sortOrder ?? 0,
            createdAt: $createdAt ??
                new DateTimeImmutable("2024-01-01 12:00:00"),
        );
    }

    public static function createCategoryLink(
        ?Category $category = null,
        ?Link $link = null,
        ?int $sortOrder = null,
        ?DateTimeInterface $createdAt = null,
    ): CategoryLink {
        return new CategoryLink(
            category: $category ?? self::createCategory(),
            link: $link ?? self::createLink(),
            sortOrder: $sortOrder ?? 0,
            createdAt: $createdAt ??
                new DateTimeImmutable("2024-01-01 12:00:00"),
        );
    }

    public static function createUser(
        ?UuidInterface $id = null,
        ?Email $email = null,
        ?HashedPassword $passwordHash = null,
        ?TotpSecret $totpSecret = null,
        ?DateTimeInterface $createdAt = null,
        ?DateTimeInterface $updatedAt = null,
    ): User {
        return new User(
            userId: $id ?? Uuid::uuid4(),
            email: $email ?? new Email("test@example.com"),
            passwordHash: $passwordHash ??
                new HashedPassword(
                    password_hash("password123", PASSWORD_BCRYPT),
                ),
            totpSecret: $totpSecret,
            createdAt: $createdAt ??
                new DateTimeImmutable("2024-01-01 12:00:00"),
            updatedAt: $updatedAt ??
                new DateTimeImmutable("2024-01-01 12:00:00"),
        );
    }
}

/**
 * Test container helper for integration tests.
 * Manages container compilation and configuration to prevent recompilation errors.
 */
class TestContainerHelper
{
    /**
     * Get the compiled container class (only compiles once per PHP process).
     * This is shared across all integration tests to prevent recompilation errors.
     * This mimics what app_init.php does in production.
     */
    public static function getCompiledContainerClass()
    {
        static $compiledClass = null;

        if ($compiledClass === null) {
            $compiler = new DiCompiler(ServiceContainer::class);
            $compiledClass = $compiler->compile();
        }

        return $compiledClass;
    }

    /**
     * Create a test configuration for the container.
     * Uses in-memory SQLite database for testing.
     */
    public static function createTestConfig(): array
    {
        return [
            "site.url" => "http://test-localhost",
            "logger.name" => "test-logger",
            "logger.path" => "php://memory",
            "db.dsn" => "sqlite:",
            "db.dbname" => ":memory:",
            "db.user" => "",
            "db.pass" => "",
            "ratelimit.db.dsn" =>
                "sqlite:" . sys_get_temp_dir() . "/test_ratelimit.db",
            "ratelimit.db.dbname" => "",
            "ratelimit.db.user" => "",
            "ratelimit.db.pass" => "",
            "ratelimit.username_threshold" => 10,
            "ratelimit.ip_threshold" => 100,
            "ratelimit.window_minutes" => 10,
            "ratelimit.trust_proxy_headers" => false,
            "users.storage.type" => "pdo",
            "users.storage.path" => sys_get_temp_dir() . "/test_users.json",
            "auth.jwt_secret" => "test-secret-key-32-bytes-long!!!", // Exactly 32 bytes
            "auth.application_name" => "bookmark-bureau",
            "auth.session_ttl" => 86400,
            "auth.remember_me_ttl" => 1209600,
        ];
    }

    /**
     * Create a new container instance with test configuration.
     */
    public static function createContainerInstance(): ServiceContainer
    {
        $compiledClass = self::getCompiledContainerClass();
        return $compiledClass->newInstance(self::createTestConfig());
    }
}
