<?php
namespace Dbcompar;

use Dbcompar\Dbcompar;
use Dbcompar\Report\Diff;
use Dbcompar\Report\Sync;

/**
 * Base report class for Dbcompar.
 */
abstract class Report extends Dbcompar
{
    private $raport = "";

    public static function factory($report)
    {
        $assert = static::service("assert");

        switch ($report) {
        case "diff":
            return new Diff();
        case "sync":
            return new Sync();
        default:
            $assert->exception("There are not raport $report");
            break;
        }
    }

    protected function quiet($toWrite)
    {
        $this->output()->quiet($toWrite);
        return $this;
    }

    protected function normal($toWrite)
    {
        $this->output()->normal($toWrite);
        return $this;
    }

    protected function verbose($toWrite)
    {
        $this->output()->verbose($toWrite);
        return $this;
    }

    protected function veryVerbose($toWrite)
    {
        $this->output()->veryVerbose($toWrite);
        return $this;
    }

    protected function debug($toWrite)
    {
        $this->output()->debug($toWrite);
        return $this;
    }

    /**
     * Append to raport content.
     *
     * @param string $text
     * @param int $indentation
     * @return self
     */
    protected function append($text, $indentation = 0)
    {
        $offsetString = "";

        while($indentation > 0){
            $offsetString .= " ";
            $indentation--;
        }

        $this->raport .= $offsetString.$text."\n";

        return $this;
    }

    /**
     * Rnder raport to output.
     */
    public function render()
    {
        $this->output()->normal($this->raport);
    }

    private function output()
    {
        return $this->service('output');
    }

    protected function prepareToPrint($variable)
    {
        if (is_array($variable)) {
            return implode(',', $variable);
        }

        return $variable;
    }
}
