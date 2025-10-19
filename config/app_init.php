<?php declare(strict_types = 1);

use jschreuder\MiddleDi\DiCompiler;

// Load autoloader & 3rd party libraries
require_once __DIR__ . '/../vendor/autoload.php';

// Disable error messages in output
ini_set('display_errors', 'no');

// Ensure a few local system settings
date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

// Setup DiC with Environment config
$environment = require __DIR__ . '/env.php';
$config = require __DIR__ . '/' . $environment . '.php';
$config['environment'] = $environment;

/** @var  \Middle\Skeleton\ServiceContainer $container */
$container = (new DiCompiler(jschreuder\BookmarkBureau\ServiceContainer::class))->compile()->newInstance($config);

// Have Monolog log all PHP errors
Monolog\ErrorHandler::register($container->getLogger());

return $container;
