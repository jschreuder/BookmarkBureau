<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Command\User;

use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Exception\UserNotFoundException;
use jschreuder\BookmarkBureau\Service\UserServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class TotpCommand extends Command
{
    protected static ?string $defaultName = "user:totp";
    protected static ?string $defaultDescription = "Manage TOTP for a user (enable/disable)";

    public function __construct(private UserServiceInterface $userService)
    {
        parent::__construct("user:totp");
    }

    protected function configure(): void
    {
        $this->setDescription("Manage TOTP for a user (enable/disable)");
        $this->addArgument(
            "action",
            InputArgument::REQUIRED,
            "Action: enable or disable",
        )->addArgument(
            "email",
            InputArgument::REQUIRED,
            "The user email address",
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $action = strtolower($input->getArgument("action"));
        $emailString = $input->getArgument("email");

        if (!in_array($action, ["enable", "disable"])) {
            $output->writeln(
                "<error>Invalid action. Use 'enable' or 'disable'</error>",
            );
            return Command::FAILURE;
        }

        try {
            $email = new Email($emailString);
            $user = $this->userService->getUserByEmail($email);

            if ($action === "enable") {
                $totpSecret = $this->userService->enableTotp($user->userId);
                $output->writeln(
                    "<info>TOTP enabled for user '{$emailString}'</info>",
                );
                $output->writeln(
                    "  Secret: <comment>{$totpSecret->getSecret()}</comment>",
                );
                $output->writeln(
                    "<fg=yellow>Save this secret in your authenticator app!</>",
                );
            } else {
                $this->userService->disableTotp($user->userId);
                $output->writeln(
                    "<info>TOTP disabled for user '{$emailString}'</info>",
                );
            }

            return Command::SUCCESS;
        } catch (UserNotFoundException) {
            $output->writeln(
                "<error>User with email '{$emailString}' not found</error>",
            );
            return Command::FAILURE;
        } catch (\InvalidArgumentException $e) {
            $output->writeln("<error>Error: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}
