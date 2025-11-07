<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau;

use Symfony\Component\Console\Application;

class ConsoleCommandsProvider
{
    public function __construct(private ServiceContainer $container) {}

    public function registerCommands(Application $application): void
    {
        // Example: $application->add(new StartWebserverCommand());
    }
}
