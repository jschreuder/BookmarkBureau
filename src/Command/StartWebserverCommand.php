<?php declare(strict_types = 1);

namespace jschreuder\BookmarkBureau\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StartWebserverCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('middle:webserver')
            ->setDescription('Start the PHP webserver')
            ->addArgument('port', InputArgument::OPTIONAL, 'Localhost webserver portnumber', '8080');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        chdir('web/');
        shell_exec('php -S localhost:'.escapeshellarg($input->getArgument('port')));
        return 0;
    }
}
