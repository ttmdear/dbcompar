<?php
namespace Dbcompar\Command;

use Dbcompar\Command;
use Dbcompar\Report;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Sync extends Command
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('sync')
            ->setDescription('Generate SQL to sync databases.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $sync = Report::factory('sync');
        $sync
            ->source($input->getArgument('source'))
            ->target($input->getArgument('target'))
            ->render()
        ;

    }
}
