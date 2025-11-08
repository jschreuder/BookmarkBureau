<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Command\User;

use jschreuder\BookmarkBureau\Service\UserServiceInterface;
use jschreuder\BookmarkBureau\Util\SqlFormat;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

final class ListCommand extends Command
{
    protected static ?string $defaultName = "user:list";
    protected static ?string $defaultDescription = "List all users";

    public function __construct(private UserServiceInterface $userService)
    {
        parent::__construct("user:list");
    }

    protected function configure(): void
    {
        $this->setDescription("List all users");
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $users = $this->userService->listAllUsers();

        if ($users->isEmpty()) {
            $output->writeln("<info>No users found</info>");
            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders(["Email", "UUID", "Created At", "Has TOTP"]);

        foreach ($users as $user) {
            $table->addRow([
                $user->email,
                $user->userId,
                $user->createdAt->format(SqlFormat::TIMESTAMP),
                $user->requiresTotp() ? "Yes" : "No",
            ]);
        }

        $table->render();

        return Command::SUCCESS;
    }
}
