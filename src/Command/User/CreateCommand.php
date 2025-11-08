<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Command\User;

use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Exception\DuplicateEmailException;
use jschreuder\BookmarkBureau\Service\UserServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

final class CreateCommand extends Command
{
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
        $password = $input->getArgument("password");
        $enableTotp = $input->getOption("enable-totp");

        if (!$password) {
            /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
            $helper = $this->getHelper("question");
            $question = new Question("Enter password: ");
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $password = $helper->ask($input, $output, $question);

            if (!$password) {
                $output->writeln("<error>Password cannot be empty</error>");
                return Command::FAILURE;
            }
        }

        try {
            $emailValue = new Email($email);
            $user = $this->userService->createUser($emailValue, $password);

            $output->writeln("<info>User created successfully!</info>");
            $output->writeln("  Email: <comment>{$user->email}</comment>");
            $output->writeln("  UUID: <comment>{$user->userId}</comment>");

            if ($enableTotp) {
                $totpSecret = $this->userService->enableTotp($user->userId);
                $output->writeln("");
                $output->writeln("<info>TOTP enabled!</info>");
                $output->writeln(
                    "  Secret: <comment>{$totpSecret->getSecret()}</comment>",
                );
                $output->writeln(
                    "<fg=yellow>Save this secret in your authenticator app!</>",
                );
            }

            return Command::SUCCESS;
        } catch (DuplicateEmailException) {
            $output->writeln(
                "<error>User with email '{$email}' already exists</error>",
            );
            return Command::FAILURE;
        } catch (\InvalidArgumentException $e) {
            $output->writeln("<error>Error: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}
