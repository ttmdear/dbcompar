<?php
namespace Dbcompar;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Output
{
    // OutputInterface::VERBOSITY_QUIET
    // OutputInterface::VERBOSITY_NORMAL
    // OutputInterface::VERBOSITY_VERBOSE
    // OutputInterface::VERBOSITY_VERY_VERBOSE
    // OutputInterface::VERBOSITY_DEBUG

    private $output;

    function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }


    public function quiet($toWrite)
    {
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_QUIET) {
            $this->output->writeln($toWrite);
        }

        return $this;
    }

    public function normal($toWrite)
    {
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
            $this->output->writeln($toWrite);
        }

        return $this;
    }

    public function verbose($toWrite)
    {
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->writeln($toWrite);
        }

        return $this;
    }

    public function veryVerbose($toWrite)
    {
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $this->output->writeln($toWrite);
        }

        return $this;
    }

    public function debug($toWrite)
    {
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
            $this->output->writeln($toWrite);
        }

        return $this;
    }
}
