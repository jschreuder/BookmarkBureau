<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\Command\User;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

trait PasswordPromptTrait
{
    private function resolvePassword(
        InputInterface $input,
        OutputInterface $output,
        string $passwordArg,
    ): string {
        if (!empty($passwordArg)) {
            return $passwordArg;
        }

        /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
        $helper = $this->getHelper("question");
        $question = new Question("Enter password: ");
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $password = $helper->ask($input, $output, $question);

        if (!$password || !\is_string($password)) {
            $output->writeln("<error>Password cannot be empty</error>");
            return "";
        }

        return $password;
    }
}
