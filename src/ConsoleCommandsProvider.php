<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau;

use jschreuder\BookmarkBureau\Command\User\CreateCommand;
use jschreuder\BookmarkBureau\Command\User\ListCommand;
use jschreuder\BookmarkBureau\Command\User\DeleteCommand;
use jschreuder\BookmarkBureau\Command\User\ChangePasswordCommand;
use jschreuder\BookmarkBureau\Command\User\TotpCommand;
use jschreuder\BookmarkBureau\Command\User\GenerateCliTokenCommand;
use jschreuder\BookmarkBureau\Command\User\RevokeCliTokenCommand;
use jschreuder\BookmarkBureau\Command\Security\CreateRateLimitDatabaseCommand;
use jschreuder\BookmarkBureau\Command\Security\RateLimitCleanupCommand;
use jschreuder\BookmarkBureau\InputSpec\GenerateCliTokenInputSpec;
use jschreuder\BookmarkBureau\ServiceContainer\DefaultServiceContainer;
use Symfony\Component\Console\Application;

final readonly class ConsoleCommandsProvider
{
    public function __construct(private DefaultServiceContainer $container) {}

    public function registerCommands(Application $application): void
    {
        $application->addCommand(
            new CreateCommand($this->container->getUserService()),
        );
        $application->addCommand(
            new ListCommand($this->container->getUserService()),
        );
        $application->addCommand(
            new DeleteCommand($this->container->getUserService()),
        );
        $application->addCommand(
            new ChangePasswordCommand($this->container->getUserService()),
        );
        $application->addCommand(
            new TotpCommand($this->container->getUserService()),
        );
        $application->addCommand(
            new GenerateCliTokenCommand(
                $this->container->getUserService(),
                $this->container->getJwtService(),
                new GenerateCliTokenInputSpec(),
            ),
        );
        $application->addCommand(
            new RevokeCliTokenCommand($this->container->getJwtJtiRepository()),
        );
        $application->addCommand(
            new CreateRateLimitDatabaseCommand(
                $this->container->getRateLimitDb(),
            ),
        );
        $application->addCommand(
            new RateLimitCleanupCommand(
                $this->container->getRateLimitService(),
            ),
        );
    }
}
