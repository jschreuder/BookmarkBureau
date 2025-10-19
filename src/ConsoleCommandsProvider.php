<?php declare(strict_types = 1);

namespace jschreuder\BookmarkBureau;

use jschreuder\BookmarkBureau\Command\ExampleCommand;
use jschreuder\BookmarkBureau\Command\StartWebserverCommand;
use Symfony\Component\Console\Application;

class ConsoleCommandsProvider
{
    public function __construct(
        private ServiceContainer $container
    )
    {
    }

    public function registerCommands(Application $application): void
    {
        $application->add(new StartWebserverCommand());
    }
}
