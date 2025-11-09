<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Command\User;

use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Exception\UserNotFoundException;
use jschreuder\BookmarkBureau\Service\UserServiceInterface;
use Ramsey\Uuid\UuidInterface;
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

        if (!$this->isValidAction($action)) {
            $output->writeln(
                "<error>Invalid action. Use 'enable' or 'disable'</error>",
            );
            return Command::FAILURE;
        }

        try {
            $email = new Email($emailString);
            $user = $this->userService->getUserByEmail($email);

            if ($action === "enable") {
                $this->displayTotpEnable($output, $user->userId, $emailString);
            } else {
                $this->displayTotpDisable($output, $user->userId, $emailString);
            }

            return Command::SUCCESS;
        } catch (UserNotFoundException) {
            $output->writeln(
                "<error>User with email '{$emailString}' not found</error>",
            );
        } catch (\InvalidArgumentException $e) {
            $output->writeln("<error>Error: {$e->getMessage()}</error>");
        }

        return Command::FAILURE;
    }

    private function isValidAction(string $action): bool
    {
        return \in_array($action, ["enable", "disable"]);
    }

    private function displayTotpEnable(
        OutputInterface $output,
        UuidInterface $userId,
        string $emailString,
    ): void {
        $totpSecret = $this->userService->enableTotp($userId);
        $output->writeln("<info>TOTP enabled for user '{$emailString}'</info>");
        $output->writeln("  Secret: <comment>{$totpSecret->value}</comment>");
        $output->writeln(
            "<fg=yellow>Save this secret in your authenticator app!</>",
        );
    }

    private function displayTotpDisable(
        OutputInterface $output,
        UuidInterface $userId,
        string $emailString,
    ): void {
        $this->userService->disableTotp($userId);
        $output->writeln(
            "<info>TOTP disabled for user '{$emailString}'</info>",
        );
    }
}
