<?php
namespace Dbcompar\Report;

use Dbcompar\Diff as DiffGenerator;
use Dbcompar\Buffor;
use Dbcompar\Report;
use Dbcompar\Adapter;

use Midata\DDL as MidataDDL;

class Sync extends Report
{
    private $source;
    private $target;
    private $adapters = null;
    private $buffor;
    private $ddlBuilders = array();
    private $removedForeignKeys = array();

    /**
     * Checks the differences between source and target, and generate proper
     * sql to sync.
     *
     * @return string
     */
    public function render()
    {
        $assert = $this->service('assert');

        $diffGenerator = new DiffGenerator();
        $diffGenerator
            ->source($this->source())
            ->target($this->target())
        ;

        $diffRaport = $diffGenerator->diff();

        // na postawie raportu tworze odpowiednie zapytania sql
        $targetAdapter = $this->adapter('target');
        $this->buffor = new Buffor();

        $this->removeForeignKey($diffRaport['tables']);

        foreach ($diffRaport['tables'] as $table => $diff) {
            if ($diff['status'] == DiffGenerator::NO_CHANGE) {
                // nic sie nie zmienilo w tabeli
                continue;
            }

            // mamy zmiane w tabeli
            $targetTable = $targetAdapter->table($table);
            $status = $diff['status'];
            $tableBuilder = $this->ddlBuilder(MidataDDL::TABLE);

            switch ($status) {
            case DiffGenerator::CREATED:
                //$this->buffor->append(1, $tableBuilder->create($targetTable));
                $this->buffor->append("create-table", $this->createTable($targetTable));
                break;
            case DiffGenerator::DROPED:
                $this->buffor->append("drop-table", $tableBuilder->drop($targetTable));
                break;
            case DiffGenerator::CHANGED:
                $this->processDiff($diff['columns']['diff'], 'column', $targetTable);
                $this->processDiff($diff['triggers']['diff'], 'trigger', $targetTable);
                $this->processDiff($diff['indexes']['diff'], 'index', $targetTable);

                // dla kazdej tabeli gdzie pojawila sie zmiana usuniete zostaly
                // klucze obce powiazane z ta tabela, dlatego przetwarzanie
                // constraintow jest troche inne niz pozostalych obiektow
                $this->processConstraintDiff($diff['constraints']['diff'], $targetTable);

                break;
            default:
                $assert->exception("Some not supported status '$status'");
                break;
            }
        }

        foreach ($diffRaport['views'] as $view => $diff) {
            if ($diff['status'] == DiffGenerator::NO_CHANGE) {
                continue;
            }

            $targetView = $targetAdapter->view($view);
            $status = $diff['status'];
            $viewBuilder = $this->ddlBuilder(MidataDDL::VIEW);

            switch ($status) {
            case DiffGenerator::CREATED:
                $this->buffor->append('create-view', $viewBuilder->create($targetView));
                break;
            case DiffGenerator::DROPED:
                $this->buffor->append("drop-view", $viewBuilder->drop($targetView));
                break;
            case DiffGenerator::CHANGED:
                $this->buffor->append("alter-view", $viewBuilder->alter($targetView));
                break;
            default:
                $assert->exception("Some not supported status '$status'");
                break;
            }
        }

        $this->append($this->buffor->get());

        parent::render();
    }

    private function createTable($targetTable)
    {
        $tableBuilder = $this->ddlBuilder(MidataDDL::TABLE);
        $triggerBuilder = $this->ddlBuilder(MidataDDL::TRIGGER);

        $createTable = $tableBuilder->create($targetTable);

        foreach ($targetTable->triggers() as $trigger) {
            $trigger = $targetTable->trigger($trigger);
            $createTrigger = $triggerBuilder->create($trigger);

            $createTable .= "\n\n$createTrigger";
        }

        return $createTable;
    }

    private function processDiff($diff, $objectType, $targetTable)
    {
        foreach ($diff as $objectName => $diffObject) {
            if ($diffObject['status'] == DiffGenerator::NO_CHANGE) {
                continue;
            }

            $objectBuilder = $this->ddlBuilder($objectType);
            $targetObject = $targetTable->$objectType($objectName);

            switch ($diffObject['status']) {
            case DiffGenerator::CREATED:
                $this->buffor->append("create-$objectType", $objectBuilder->create($targetObject));
                break;
            case DiffGenerator::CHANGED:
                $this->buffor->append("alter-$objectType", $objectBuilder->alter($targetObject));
                break;
            case DiffGenerator::DROPED:
                $this->buffor->append("drop-$objectType", $objectBuilder->drop($targetObject));
                break;
            default:
                $assert->exception("Some not supported status '$status'");
                break;
            }
        }
    }

    private function processConstraintDiff($diff, $targetTable)
    {
        $assert = $this->service('assert');

        foreach ($diff as $constraint => $diffConstraint) {
            $ddlConstraint = $this->ddlBuilder(MidataDDL::CONSTRAINT);
            $constraint = $targetTable->constraint($constraint);
            $constraintName = $constraint->name();

            switch ($diffConstraint['status']) {
            case DiffGenerator::CREATED:
                $this->buffor->append("create-constraint", $ddlConstraint->create($constraint));
                break;
            case DiffGenerator::NO_CHANGE:
            case DiffGenerator::CHANGED:
                if (in_array($constraintName, $this->removedForeignKeys)) {
                    // constraint zostal usuniety wiec tworze go na nowo
                    $this->buffor->append("create-constraint", $ddlConstraint->create($constraint));
                }else{
                    $this->buffor->append("alter-constraint", $ddlConstraint->alter($constraint));
                }
                break;
            case DiffGenerator::DROPED:
                $this->buffor->append("drop-constraint", $ddlConstraint->drop($constraint));
                break;
            default:
                $assert->exception("Some not supported status '$status'");
                break;
            }
        }

    }

    private function ddlBuilder($statement)
    {
        if (!isset($this->ddlBuilders[$statement])) {
            $this->ddlBuilders[$statement] = MidataDDL::factory($this->adapter('source'), $statement);
        }

        return $this->ddlBuilders[$statement];
    }

    private function removeForeignKey($diffTable)
    {
        $sourceAdapter = $this->adapter('source');
        $ddlBuilder = $this->ddlBuilder(MidataDDL::CONSTRAINT);

        foreach ($diffTable as $tableName => $diff) {
            if (in_array($diff['status'], array(DiffGenerator::CHANGED))) {
                $sourceTable = $sourceAdapter->table($tableName);
                $constraints = $sourceTable->constraints();

                foreach ($constraints as $constraint) {
                    $constraint = $sourceTable->constraint($constraint);
                    if ($constraint->isForeignKey()) {
                        $this->removedForeignKeys[] = $constraint->name();
                        $this->buffor->append('drop-constraint', $ddlBuilder->drop($constraint));
                    }
                }
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

    private function adapter($name)
    {
        $assert = $this->service('assert');

        if (!isset($this->adapters[$name])) {
            switch ($name) {
            case 'source':
                $this->adapters[$name] = Adapter::factory($this->source());
                break;
            case 'target':
                $this->adapters[$name] = Adapter::factory($this->target());
                break;
            default:
                $assert->exception("There are no adapter type $name");
                break;
            }
        }

        return $this->adapters[$name];
    }
}
