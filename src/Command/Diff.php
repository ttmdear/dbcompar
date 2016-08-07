<?php
namespace Dbcompar\Command;

use Dbcompar\Command;
use Dbcompar\Report;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Diff extends Command
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('diff')
            ->setDescription('Compares two tables and prints a report differences.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $diff = Report::factory('diff');
        $diff
            ->source($input->getArgument('source'))
            ->target($input->getArgument('target'))
            ->render()
        ;
    }
}
