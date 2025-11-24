<?php declare(strict_types=1);

// Load autoloader & 3rd party libraries
require_once __DIR__ . "/../vendor/autoload.php";

use jschreuder\BookmarkBureau\ServiceContainer\DefaultServiceContainer;
use jschreuder\MiddleDi\DiCompiler;

// Disable error messages in output
ini_set("display_errors", "no");

// Ensure a few local system settings
date_default_timezone_set("UTC");
mb_internal_encoding("UTF-8");

// Load environment-specific config
$environment = require __DIR__ . "/env.php";
$config = require __DIR__ . "/" . $environment . ".php";

/** @var  DefaultServiceContainer $container service container with typed config objects */
$container = new DiCompiler(DefaultServiceContainer::class)
    ->compile()
    ->newInstance(...$config);

// Have Monolog log all PHP errors
Monolog\ErrorHandler::register($container->getLogger());

return $container;
