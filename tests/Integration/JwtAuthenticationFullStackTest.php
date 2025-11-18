<?php declare(strict_types=1);

use jschreuder\BookmarkBureau\Entity\Value\TokenType;
use jschreuder\BookmarkBureau\GeneralRoutingProvider;
use jschreuder\Middle\Router\RoutingProviderCollection;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;

/**
 * Create a new container instance with test configuration for JWT tests.
 */
function createJwtContainerInstance()
{
    $container = TestContainerHelper::createContainerInstance();

    // Initialize the users table for in-memory SQLite
    $pdo = $container->getDb();
    $pdo->exec(
        <<<SQL
            CREATE TABLE IF NOT EXISTS users (
                user_id CHAR(16) PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                totp_secret VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        SQL
        ,
    );

    // Initialize the rate limiting tables for file-based SQLite
    $rateLimitDbPath = sys_get_temp_dir() . "/test_ratelimit.db";
    $rateLimitPdo = new PDO("sqlite:" . $rateLimitDbPath);
    $rateLimitPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Drop and recreate tables for clean test state
    $rateLimitPdo->exec("DROP TABLE IF EXISTS failed_login_attempts");
    $rateLimitPdo->exec("DROP TABLE IF EXISTS login_blocks");
    $rateLimitPdo->exec(
        <<<SQL
            CREATE TABLE failed_login_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                timestamp DATETIME NOT NULL,
                ip TEXT NOT NULL,
                username TEXT
            );

            CREATE TABLE login_blocks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT,
                ip TEXT,
                blocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NOT NULL
            );
        SQL
        ,
    );

    // Register routes
    new RoutingProviderCollection(
        new GeneralRoutingProvider($container),
    )->registerRoutes($container->getAppRouter());

    return $container;
}

describe("JWT Authentication Full Stack Integration", function () {
    describe("complete authentication flow", function () {
        test("user can login and receive JWT token", function () {
            $container = createJwtContainerInstance();
            $stack = $container->getApp();

            // Create a test user first
            $userRepository = $container->getUserRepository();

            $testUser = TestEntityFactory::createUser();
            $userRepository->save($testUser);

            // Simulate HTTP POST /auth/login
            $request = ServerRequestFactory::fromGlobals();
            $body = json_encode([
                "email" => (string) $testUser->email,
                "password" => "password123",
                "remember_me" => false,
            ]);
            $request = $request
                ->withMethod("POST")
                ->withUri($request->getUri()->withPath("/auth/login"))
                ->withHeader("Content-Type", "application/json")
                ->withBody(new StreamFactory()->createStream($body))
                ->withParsedBody([
                    "email" => (string) $testUser->email,
                    "password" => "test-password",
                    "remember_me" => false,
                ]);

            // Execute through the middleware stack
            $response = $stack->process($request);

            // Verify response
            if ($response->getStatusCode() !== 200) {
                $error = json_decode($response->getBody()->getContents(), true);
                throw new \Exception(
                    "Login failed with status {$response->getStatusCode()}: " .
                        json_encode($error),
                );
            }
            expect($response->getStatusCode())->toBe(200);
            $body = json_decode($response->getBody()->getContents(), true);
            expect($body["success"])->toBeTrue();
            expect($body["data"])->toHaveKey("token");
            expect($body["data"]["type"])->toBe("session");
        });

        test("authenticated request with valid token succeeds", function () {
            $container = createJwtContainerInstance();
            $stack = $container->getApp();
            $userRepository = $container->getUserRepository();
            $jwtService = $container->getJwtService();

            // Create and save test user
            $testUser = TestEntityFactory::createUser();
            $userRepository->save($testUser);

            // Generate a valid token
            $token = $jwtService->generate($testUser, TokenType::SESSION_TOKEN);

            // Make authenticated request to a protected endpoint
            $request = ServerRequestFactory::fromGlobals();
            $request = $request
                ->withMethod("POST")
                ->withUri($request->getUri()->withPath("/auth/token-refresh"))
                ->withHeader("Authorization", "Bearer " . (string) $token);

            // Execute through middleware stack
            $response = $stack->process($request);

            // Should get a 200 response with new token
            expect($response->getStatusCode())->toBe(200);
            $body = json_decode($response->getBody()->getContents(), true);
            expect($body["success"])->toBeTrue();
            expect($body["data"])->toHaveKey("token");
        });

        test(
            "request without token is allowed but not authenticated",
            function () {
                $container = createJwtContainerInstance();
                $stack = $container->getApp();

                // Make request without token
                $request = ServerRequestFactory::fromGlobals();
                $request = $request
                    ->withMethod("POST")
                    ->withUri(
                        $request->getUri()->withPath("/auth/token-refresh"),
                    );

                // Execute through middleware stack
                $response = $stack->process($request);

                // Should get 401 because middleware didn't set authenticatedUser
                // and RefreshTokenController requires it (throws AuthenticationException)
                expect($response->getStatusCode())->toBe(401);
            },
        );

        test("malformed token in header is ignored", function () {
            $container = createJwtContainerInstance();
            $stack = $container->getApp();

            // Make request with malformed token
            $request = ServerRequestFactory::fromGlobals();
            $request = $request
                ->withMethod("POST")
                ->withUri($request->getUri()->withPath("/auth/token-refresh"))
                ->withHeader("Authorization", "Bearer malformed.token.here");

            // Execute through middleware stack
            $response = $stack->process($request);

            // Should get 401 because middleware ignores bad token and RefreshTokenController fails
            expect($response->getStatusCode())->toBe(401);
        });

        test("token refresh generates new token with same type", function () {
            $container = createJwtContainerInstance();
            $stack = $container->getApp();
            $userRepository = $container->getUserRepository();
            $jwtService = $container->getJwtService();

            // Create and save test user
            $testUser = TestEntityFactory::createUser();
            $userRepository->save($testUser);

            // Generate remember-me token
            $token = $jwtService->generate(
                $testUser,
                TokenType::REMEMBER_ME_TOKEN,
            );

            // Refresh token
            $request = ServerRequestFactory::fromGlobals();
            $request = $request
                ->withMethod("POST")
                ->withUri($request->getUri()->withPath("/auth/token-refresh"))
                ->withHeader("Authorization", "Bearer " . (string) $token);

            $response = $stack->process($request);

            expect($response->getStatusCode())->toBe(200);
            $body = json_decode($response->getBody()->getContents(), true);
            expect($body["success"])->toBeTrue();
            expect($body["data"]["type"])->toBe("remember_me");
            expect($body["data"])->toHaveKey("token");
            expect($body["data"])->toHaveKey("expires_at");
        });
    });

    describe("authentication enforcement", function () {
        test(
            "public route (home) is accessible without authentication",
            function () {
                $container = createJwtContainerInstance();
                $stack = $container->getApp();

                $request = ServerRequestFactory::fromGlobals();
                $request = $request
                    ->withMethod("GET")
                    ->withUri($request->getUri()->withPath("/"));

                $response = $stack->process($request);

                expect($response->getStatusCode())->toBe(200);
                $body = json_decode($response->getBody()->getContents(), true);
                expect($body)->toHaveKey("message");
            },
        );

        test("protected route requires authentication", function () {
            $container = createJwtContainerInstance();
            $stack = $container->getApp();
            $pdo = $container->getDb();

            // Create dashboards table for this test
            $pdo->exec(
                <<<SQL
                CREATE TABLE IF NOT EXISTS dashboards (
                    dashboard_id CHAR(16) PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
                SQL
                ,
            );

            $request = ServerRequestFactory::fromGlobals();
            $request = $request
                ->withMethod("GET")
                ->withUri($request->getUri()->withPath("/dashboard"));

            $response = $stack->process($request);

            // Should get 401 Unauthenticated
            expect($response->getStatusCode())->toBe(401);
            $body = json_decode($response->getBody()->getContents(), true);
            expect($body["message"])->toBe("Unauthenticated");
        });

        test(
            "protected route is accessible with valid authentication",
            function () {
                $container = createJwtContainerInstance();
                $stack = $container->getApp();
                $pdo = $container->getDb();
                $userRepository = $container->getUserRepository();
                $jwtService = $container->getJwtService();

                // Create tables
                $pdo->exec(
                    <<<SQL
                    CREATE TABLE IF NOT EXISTS links (
                        link_id CHAR(16) PRIMARY KEY,
                        url TEXT NOT NULL,
                        title VARCHAR(255) NOT NULL,
                        description TEXT NOT NULL,
                        icon TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    );

                    CREATE TABLE IF NOT EXISTS tags (
                        tag_name VARCHAR(100) PRIMARY KEY,
                        color VARCHAR(7)
                    );

                    CREATE TABLE IF NOT EXISTS link_tags (
                        link_id CHAR(16) NOT NULL,
                        tag_name VARCHAR(100) NOT NULL,
                        PRIMARY KEY (link_id, tag_name),
                        FOREIGN KEY (link_id) REFERENCES links(link_id) ON DELETE CASCADE,
                        FOREIGN KEY (tag_name) REFERENCES tags(tag_name) ON DELETE CASCADE
                    );

                    CREATE TABLE IF NOT EXISTS dashboards (
                        dashboard_id CHAR(16) PRIMARY KEY,
                        title VARCHAR(255) NOT NULL,
                        description TEXT NOT NULL,
                        icon TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    );

                    CREATE TABLE IF NOT EXISTS categories (
                        category_id CHAR(16) PRIMARY KEY,
                        dashboard_id CHAR(16) NOT NULL,
                        title VARCHAR(255) NOT NULL,
                        color VARCHAR(7),
                        sort_order INTEGER DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (dashboard_id) REFERENCES dashboards(dashboard_id) ON DELETE CASCADE
                    );

                    CREATE TABLE IF NOT EXISTS category_links (
                        category_id CHAR(16) NOT NULL,
                        link_id CHAR(16) NOT NULL,
                        sort_order INTEGER DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (category_id, link_id),
                        FOREIGN KEY (link_id) REFERENCES links(link_id) ON DELETE CASCADE,
                        FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
                    );

                    CREATE TABLE IF NOT EXISTS favorites (
                        dashboard_id CHAR(16) NOT NULL,
                        link_id CHAR(16) NOT NULL,
                        sort_order INTEGER DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (dashboard_id, link_id),
                        FOREIGN KEY (dashboard_id) REFERENCES dashboards(dashboard_id) ON DELETE CASCADE,
                        FOREIGN KEY (link_id) REFERENCES links(link_id) ON DELETE CASCADE
                    )
                    SQL
                    ,
                );

                // Create and save test user
                $testUser = TestEntityFactory::createUser();
                $userRepository->save($testUser);

                // Generate a valid token
                $token = $jwtService->generate(
                    $testUser,
                    TokenType::SESSION_TOKEN,
                );

                $request = ServerRequestFactory::fromGlobals();
                $request = $request
                    ->withMethod("GET")
                    ->withUri($request->getUri()->withPath("/dashboard"))
                    ->withHeader("Authorization", "Bearer " . (string) $token);

                $response = $stack->process($request);

                // Should get 200 OK
                expect($response->getStatusCode())->toBe(200);
            },
        );

        test("protected route rejects invalid token", function () {
            $container = createJwtContainerInstance();
            $stack = $container->getApp();
            $pdo = $container->getDb();

            // Create all tables
            $pdo->exec(
                <<<SQL
                CREATE TABLE IF NOT EXISTS links (
                    link_id CHAR(16) PRIMARY KEY,
                    url TEXT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT NOT NULL,
                    icon TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS tags (
                    tag_name VARCHAR(100) PRIMARY KEY,
                    color VARCHAR(7)
                );

                CREATE TABLE IF NOT EXISTS link_tags (
                    link_id CHAR(16) NOT NULL,
                    tag_name VARCHAR(100) NOT NULL,
                    PRIMARY KEY (link_id, tag_name),
                    FOREIGN KEY (link_id) REFERENCES links(link_id) ON DELETE CASCADE,
                    FOREIGN KEY (tag_name) REFERENCES tags(tag_name) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS dashboards (
                    dashboard_id CHAR(16) PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    description TEXT NOT NULL,
                    icon TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS categories (
                    category_id CHAR(16) PRIMARY KEY,
                    dashboard_id CHAR(16) NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    color VARCHAR(7),
                    sort_order INTEGER DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (dashboard_id) REFERENCES dashboards(dashboard_id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS category_links (
                    category_id CHAR(16) NOT NULL,
                    link_id CHAR(16) NOT NULL,
                    sort_order INTEGER DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (category_id, link_id),
                    FOREIGN KEY (link_id) REFERENCES links(link_id) ON DELETE CASCADE,
                    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS favorites (
                    dashboard_id CHAR(16) NOT NULL,
                    link_id CHAR(16) NOT NULL,
                    sort_order INTEGER DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (dashboard_id, link_id),
                    FOREIGN KEY (dashboard_id) REFERENCES dashboards(dashboard_id) ON DELETE CASCADE,
                    FOREIGN KEY (link_id) REFERENCES links(link_id) ON DELETE CASCADE
                )
                SQL
                ,
            );

            $request = ServerRequestFactory::fromGlobals();
            $request = $request
                ->withMethod("GET")
                ->withUri($request->getUri()->withPath("/dashboard"))
                ->withHeader("Authorization", "Bearer invalid.jwt.token");

            $response = $stack->process($request);

            // Should get 401 Unauthenticated
            expect($response->getStatusCode())->toBe(401);
        });
    });
});
