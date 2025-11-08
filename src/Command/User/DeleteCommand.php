<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Command\User;

use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Exception\UserNotFoundException;
use jschreuder\BookmarkBureau\Service\UserServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class DeleteCommand extends Command
{
    protected static ?string $defaultName = "user:delete";
    protected static ?string $defaultDescription = "Delete a user by email";

    public function __construct(private UserServiceInterface $userService)
    {
        parent::__construct("user:delete");
    }

    protected function configure(): void
    {
        $this->setDescription("Delete a user by email");
        $this->addArgument(
            "email",
            InputArgument::REQUIRED,
            "The user email address",
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $emailString = $input->getArgument("email");

        try {
            $email = new Email($emailString);
            $user = $this->userService->getUserByEmail($email);

            if (!$this->confirmDeletion($input, $output, $emailString)) {
                $output->writeln("<info>Cancelled</info>");
                return Command::SUCCESS;
            }

            $this->userService->deleteUser($user->userId);
            $output->writeln(
                "<info>User '{$emailString}' deleted successfully</info>",
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

    private function confirmDeletion(
        InputInterface $input,
        OutputInterface $output,
        string $emailString,
    ): bool {
        /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
        $helper = $this->getHelper("question");
        $question = new ConfirmationQuestion(
            "Are you sure you want to delete user '{$emailString}'? [y/N] ",
            false,
        );

        return $helper->ask($input, $output, $question);
    }
}
