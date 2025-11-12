<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Command\User;

use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Exception\UserNotFoundException;
use jschreuder\BookmarkBureau\Service\UserServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ChangePasswordCommand extends Command
{
    use PasswordPromptTrait;

    public function __construct(private UserServiceInterface $userService)
    {
        parent::__construct("user:change-password");
    }

    protected function configure(): void
    {
        $this->setDescription("Change a user password by email");
        $this->addArgument(
            "email",
            InputArgument::REQUIRED,
            "The user email address",
        )->addArgument(
            "password",
            InputArgument::OPTIONAL,
            "The new password (will be prompted if not provided)",
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $emailString = $input->getArgument("email");
        $passwordArg = $input->getArgument("password");

        try {
            $email = new Email($emailString);
            $user = $this->userService->getUserByEmail($email);
            $password = $this->resolvePassword($input, $output, $passwordArg);

            if ($password === null) {
                return Command::FAILURE;
            }

            $this->userService->changePassword($user->userId, $password);
            $output->writeln(
                "<info>Password changed successfully for user '{$emailString}'</info>",
            );

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
}
