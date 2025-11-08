<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Command\User;

use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Exception\DuplicateEmailException;
use jschreuder\BookmarkBureau\Service\UserServiceInterface;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class CreateCommand extends Command
{
    use PasswordPromptTrait;
    protected static ?string $defaultName = "user:create";
    protected static ?string $defaultDescription = "Create a new user";

    public function __construct(private UserServiceInterface $userService)
    {
        parent::__construct("user:create");
    }

    protected function configure(): void
    {
        $this->setDescription("Create a new user");
        $this->addArgument(
            "email",
            InputArgument::REQUIRED,
            "The user email address",
        )
            ->addArgument(
                "password",
                InputArgument::OPTIONAL,
                "The user password (will be prompted if not provided)",
            )
            ->addOption(
                "enable-totp",
                "t",
                InputOption::VALUE_NONE,
                "Enable TOTP during creation",
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $email = $input->getArgument("email");
        $passwordArg = $input->getArgument("password");
        $enableTotp = $input->getOption("enable-totp");

        $password = $this->resolvePassword($input, $output, $passwordArg);
        if ($password === null) {
            return Command::FAILURE;
        }

        try {
            $emailValue = new Email($email);
            $user = $this->userService->createUser($emailValue, $password);

            $output->writeln("<info>User created successfully!</info>");
            $output->writeln("  Email: <comment>{$user->email}</comment>");
            $output->writeln("  UUID: <comment>{$user->userId}</comment>");

            if ($enableTotp) {
                $this->displayTotpSetup($output, $user->userId);
            }

            return Command::SUCCESS;
        } catch (DuplicateEmailException) {
            $output->writeln(
                "<error>User with email '{$email}' already exists</error>",
            );
        } catch (\InvalidArgumentException $e) {
            $output->writeln("<error>Error: {$e->getMessage()}</error>");
        }

        return Command::FAILURE;
    }

    private function displayTotpSetup(
        OutputInterface $output,
        UuidInterface $userId,
    ): void {
        $totpSecret = $this->userService->enableTotp($userId);
        $output->writeln("");
        $output->writeln("<info>TOTP enabled!</info>");
        $output->writeln(
            "  Secret: <comment>{$totpSecret->getSecret()}</comment>",
        );
        $output->writeln(
            "<fg=yellow>Save this secret in your authenticator app!</>",
        );
    }
}
