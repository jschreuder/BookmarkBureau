<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau;

use jschreuder\MiddleDi\ConfigTrait;
use jschreuder\BookmarkBureau\ServiceContainer\ApplicationStackTrait;
use jschreuder\BookmarkBureau\ServiceContainer\AuthenticationTrait;
use jschreuder\BookmarkBureau\ServiceContainer\DatabaseTrait;
use jschreuder\BookmarkBureau\ServiceContainer\RepositoryTrait;
use jschreuder\BookmarkBureau\ServiceContainer\ServiceTrait;

/**
 * Extensible by design to allow overwriting service definitions and because
 * Middle DI needs it to be.
 *
 * Service definitions are organized into logical trait groups:
 * - ApplicationStackTrait: HTTP routing, middleware, error handling, logging
 * - DatabaseTrait: PDO connection setup and pipeline configuration
 * - RepositoryTrait: Data access interfaces and PDO implementations
 * - ServiceTrait: Business logic coordination with operation pipelines
 * - AuthenticationTrait: JWT, password hashing, TOTP, rate limiting
 */
class ServiceContainer
{
    use ConfigTrait;
    use ApplicationStackTrait;
    use AuthenticationTrait;
    use DatabaseTrait;
    use RepositoryTrait;
    use ServiceTrait;
}
