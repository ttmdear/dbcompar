<?php
namespace Dbcompar\Report;

use Dbcompar\Report;
use Dbcompar\Diff as DiffGenerator;
use Midata\Index as MidataIndex;

class Diff extends Report
{
    private $source;
    private $target;

    public function render()
    {
        $diffGenerator = new DiffGenerator();
        $diffGenerator
            ->source($this->source())
            ->target($this->target())
        ;

        $raport = $diffGenerator->diff();

        foreach ($raport['tables'] as $table => $diff) {
            if ($diff['status'] == DiffGenerator::NO_CHANGE) {
                continue;
            }

            $this->append("");
            switch ($diff['status']) {
            case DiffGenerator::DROPED:
                $this->printDroped($table);
                break;

            case DiffGenerator::CREATED:
                $this->printCreated($table);
                break;

            case DiffGenerator::CHANGED:
                $this->printChangedTable($table, $diff);
                break;
            }
        }

        if (!empty($raport['views'])) {
            foreach ($raport['views'] as $view => $diff) {
                if ($diff['status'] == DiffGenerator::NO_CHANGE) {
                    continue;
                }

                $this->append("");
                switch ($diff['status']) {
                case DiffGenerator::DROPED:
                    $this->printDroped($view);
                    break;

                case DiffGenerator::CREATED:
                    $this->printCreated($view);
                    break;

                case DiffGenerator::CHANGED:
                    $this->printChangedView($view, $diff);
                    break;
                }
            }
        }

        parent::render();
    }

    private function printDroped($table)
    {
        $this->append("<info>-</info> $table");
    }

    private function printCreated($table)
    {
        $this->append("<info>+</info> $table");
    }

    private function printChangedTable($table, $diff)
    {
        $this->append("$table :");

        // columns
        if ($diff['columns']['status'] != DiffGenerator::NO_CHANGE) {
            $this->append("columns :", 4);
            foreach ($diff['columns']['diff'] as $column => $diffColumn) {
                switch ($diffColumn['status']) {
                case DiffGenerator::DROPED:
                    $this->append("<info>-</info> $column", 8);
                    break;

                case DiffGenerator::CREATED:
                    $this->append("<info>+</info> $column", 8);
                    break;

                case DiffGenerator::CHANGED:
                    $this->append("$column", 8);
                    foreach ($diffColumn['diff'] as $attribute => $diffAtribute) {
                        $target = $diffAtribute['target'];
                        $source = $diffAtribute['source'];

                        $target = $this->prepareToPrint($target);
                        $source = $this->prepareToPrint($source);

                        $this->append("$attribute : $source -> $target", 12);
                    }

                    break;
                }
            }
        }

        // constraint
        if ($diff['constraints']['status'] != DiffGenerator::NO_CHANGE) {
            $this->append("constraints :", 4);

            foreach ($diff['constraints']['diff'] as $constraint => $diffConstraint) {
                switch ($diffConstraint['status']) {
                case DiffGenerator::DROPED:
                    $this->append("<info>-</info> $constraint", 8);
                    break;

                case DiffGenerator::CREATED:
                    $this->append("<info>+</info> $constraint", 8);
                    break;

                case DiffGenerator::CHANGED:
                    if ($diffConstraint['constraintType'] == 'primaryKey') {
                        $this->append("$constraint (primaryKey)", 8);
                        $target = $diffConstraint['diff']['target'];
                        $source = $diffConstraint['diff']['source'];
                        $this->append("columns : ".implode(",", $source)." -> ".implode(',', $target), 12);
                    }elseif($diffConstraint['constraintType'] == 'foreignKey'){
                        $this->append("$constraint", 8);
                        foreach ($diffConstraint['diff'] as $attribute => $diffAtribute) {
                            $target = $diffAtribute['target'];
                            $source = $diffAtribute['source'];

                            if ($attribute == 'columns') {
                                $source = $this->printForeignKeyDef($source);
                                $target = $this->printForeignKeyDef($target);

                                $this->append("condition : $source -> $target", 12);

                                continue;
                            }

                            $target = $this->prepareToPrint($target);
                            $source = $this->prepareToPrint($source);

                            $this->append("$attribute : $source -> $target", 12);
                        }
                    }
                    break;
                }
            }
        }

        // triggers
        if ($diff['triggers']['status'] != DiffGenerator::NO_CHANGE) {

            $this->append("triggers :", 4);
            foreach ($diff['triggers']['diff'] as $trigger => $diffTriggers) {
                switch ($diffTriggers['status']) {
                case DiffGenerator::DROPED:
                    $this->append("<info>-</info> $trigger", 8);
                    break;

                case DiffGenerator::CREATED:
                    $this->append("<info>+</info> $trigger", 8);
                    break;

                case DiffGenerator::CHANGED:
                    $this->append("$trigger", 8);
                    foreach ($diffTriggers['diff'] as $attribute => $diffAtribute) {
                        $target = $diffAtribute['target'];
                        $source = $diffAtribute['source'];

                        // todo : dodac specjalna obsluge dla tresci triggera
                        if ($attribute == 'statement') {
                            $this->append("statement  : not equal", 12);

                            //$this->append($source, 16);
                            //$this->append($target, 16);

                            continue;
                        }

                        $target = $this->prepareToPrint($target);
                        $source = $this->prepareToPrint($source);

                        $this->append("$attribute : $source -> $target", 12);
                    }

                    break;
                }
            }
        }

        // indexes
        if ($diff['indexes']['status'] != DiffGenerator::NO_CHANGE) {

            $this->append("indexes :", 4);
            foreach ($diff['indexes']['diff'] as $index => $diffIndex) {
                switch ($diffIndex['status']) {
                case DiffGenerator::DROPED:
                    $this->append("<info>-</info> $index", 8);
                    break;

                case DiffGenerator::CREATED:
                    $this->append("<info>+</info> $index", 8);
                    break;

                case DiffGenerator::CHANGED:
                    $this->append("$index", 8);
                    foreach ($diffIndex['diff'] as $attribute => $diffAtribute) {
                        $target = $diffAtribute['target'];
                        $source = $diffAtribute['source'];

                        $target = $this->prepareToPrint($target);
                        $source = $this->prepareToPrint($source);

                        $this->append("$attribute : $source -> $target", 12);
                    }

                    break;
                }
            }
        }
    }

    private function printForeignKeyDef($columns)
    {
        $def = "";

        foreach ($columns as $column) {
            $base = $column['base'];
            $ref = $column['ref'];

            $def .= "$base = $ref AND ";
        }

        return trim($def, ' AND ');
    }

    private function printChangedView($view, $diff)
    {
        $this->append("$view :");

        foreach ($diff['diff'] as $attribute => $diffAtribute) {
            $source = $diffAtribute['source'];
            $target = $diffAtribute['target'];

            if ($attribute == 'definition') {
                $this->append("$attribute : are no equal", 4);
            }else{
                $this->append("$attribute : $source -> $target", 4);
            }
        }
    }

    public function source($source = null)
    {
        if (is_null($source)) {
            return $this->source;
        }

        $this->source = $source;
        return $this;
    }

    public function target($target = null)
    {
        if (is_null($target)) {
            return $this->target;
        }

        $this->target = $target;
        return $this;
    }

}
