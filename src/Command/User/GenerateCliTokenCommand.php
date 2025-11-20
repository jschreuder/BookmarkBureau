<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Command\User;

use InvalidArgumentException;
use jschreuder\BookmarkBureau\Entity\Value\Email;
use jschreuder\BookmarkBureau\Entity\Value\TokenType;
use jschreuder\BookmarkBureau\Exception\UserNotFoundException;
use jschreuder\BookmarkBureau\Service\JwtServiceInterface;
use jschreuder\BookmarkBureau\Service\UserServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class GenerateCliTokenCommand extends Command
{
    use PasswordPromptTrait;

    public function __construct(
        private UserServiceInterface $userService,
        private JwtServiceInterface $jwtService,
    ) {
        parent::__construct("user:generate-cli-token");
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription("Generate a CLI token for a user (no expiration)")
            ->addArgument(
                "email",
                InputArgument::REQUIRED,
                "The user email address",
            )
            ->addArgument(
                "password",
                InputArgument::OPTIONAL,
                "The user password (will be prompted if not provided)",
            );
    }

    #[\Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $email = $input->getArgument("email");
        $passwordArg = $input->getArgument("password");

        try {
            $emailObj = new Email($email);
            $user = $this->userService->getUserByEmail($emailObj);

            $password = $this->resolvePassword($input, $output, $passwordArg);
            if ($password === null) {
                throw new InvalidArgumentException(
                    "Cannot continue without a valid password",
                );
            }

            if (!$this->userService->verifyPassword($user, $password)) {
                throw new InvalidArgumentException(
                    "Invalid credentials for user: {$email}",
                );
            }

            $token = $this->jwtService->generate($user, TokenType::CLI_TOKEN);

            $output->writeln("<info>CLI token generated successfully:</info>");
            $output->writeln("");
            $output->writeln((string) $token);
            $output->writeln("");
            $output->writeln(
                "<comment>This token does not expire and can be used for CLI operations.</comment>",
            );

            return Command::SUCCESS;
        } catch (UserNotFoundException) {
            $output->writeln("<error>User not found: {$email}</error>");
        } catch (InvalidArgumentException $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
        }

        return Command::FAILURE;
    }
}
