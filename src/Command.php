<?php
namespace Dbcompar;

use Dbcompar\Output as Output;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base Command class for Dbcompar.
 */
class Command extends SymfonyCommand
{
    public static function service($name, $service = null)
    {
        return Dbcompar::service($name, $service);
    }

    protected function configure()
    {
        $this->addOption(
            'config',
            'c',
            InputOption::VALUE_OPTIONAL,
            'The path to the configuration file.',
            './dbcompar.xml'
        );

        $this->addArgument(
            'source',
            InputArgument::OPTIONAL,
            'Name of source database.',
            'source'
        );

        $this->addArgument(
            'target',
            InputArgument::OPTIONAL,
            'Name of target database.',
            'target'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $assert = $this->service('assert');
        $config = static::service('config');

        // init output service
        $output = new Output($output);
        $this->service('output', $output);

        // load config
        $path = realpath($input->getOption('config'));
        if ($path === false) {
            $assert->exception("Config is invalid. Can't create realpath path.");
        }

        $config->load($path);
    }
}
