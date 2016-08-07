<?php
namespace Dbcompar;

use Dbcompar\Dbcompar;
use Midata\Object\Column;
use Midata\Object\Constraint;
use Midata\Object\Trigger;
use Midata\Object\Index;
use Midata\Object\View;

class Diff extends Dbcompar
{
    const NO_CHANGE = 'no_change';
    const CHANGED = 'changed';
    const DROPED = 'droped';
    const CREATED = 'created';

    const DIFF_TABLES = 'tables';
    const DIFF_VIEWS = 'views';
    const DIFF_COLUMNS = 'columns';
    const DIFF_TRIGGERS = 'triggers';
    const DIFF_INDEXES = 'indexes';

    const DIFF_CONSTRAINTS = 'constraints';

    /**
     * Name of source for which the raport will be generated.
     *
     * @var string $source
     **/
    private $source;

    /**
     * Name of target for which the raport will be generated.
     *
     * @var string $target
     **/
    private $target;

    /**
     * Inited adapters for target and source.
     *
     * @var array $tables
     **/
    private $adapters;

    /**
     * Generate diff raport for source and target database.
     *
     * @return array
     */
    public function diff()
    {
        $tables = array();
        $views = array();

        // tables
        $sourceTables = $this->adapter('source')->tables();
        $targetTables = $this->adapter('target')->tables();

        // baseDiff which check which tables are at source and target
        $baseDiff = $this->baseDiff($sourceTables, $targetTables);
        $tables = $baseDiff['diff'];

        foreach ($tables as $table => &$diffTable) {
            if ($diffTable['status'] != self::NO_CHANGE) {
                continue;
            }

            $diffTable = $this->diffTable($table);
        }

        // views
        $sourceViews = $this->adapter('source')->views();
        $targetViews = $this->adapter('target')->views();

        $baseDiff = $this->baseDiff($sourceViews, $targetViews);
        $views = $baseDiff['diff'];

        foreach ($views as $view => &$diffView) {
            if ($diffView['status'] != self::NO_CHANGE) {
                continue;
            }

            $diffView = $this->diffView($view);
        }

        return array(
            self::DIFF_TABLES => $tables,
            self::DIFF_VIEWS => $views,
        );
    }

    /**
     * Generate subpart of raport for table.
     *
     * @param string $table
     * @return array
     */
    private function diffTable($table)
    {
        $diff = array();
        $diff['status'] = self::NO_CHANGE;

        // Columns
        $diffColumns = $this->diffColumns($table);
        if ($diffColumns['status'] != self::NO_CHANGE) {
            // byla cojanmniej jedna zmiana w kolumnach wiec cala tabela jest
            // nie jest spojna
            $diff['status'] = self::CHANGED;
        }

        $diff[self::DIFF_COLUMNS] = array(
            'status' => $diffColumns['status'],
            'diff' => $diffColumns['diff'],
        );

        // Constraint
        $diffConstraint = $this->diffConstraint($table);
        if ($diffConstraint['status'] != self::NO_CHANGE) {
            $diff['status'] = self::CHANGED;
        }

        $diff[self::DIFF_CONSTRAINTS] = array(
            'status' => $diffConstraint['status'],
            'diff' => $diffConstraint['diff'],
        );

        // Triggers
        $diffTriggers = $this->diffTriggers($table);
        if ($diffTriggers['status'] != self::NO_CHANGE) {
            $diff['status'] = self::CHANGED;
        }

        $diff[self::DIFF_TRIGGERS] = array(
            'status' => $diffTriggers['status'],
            'diff' => $diffTriggers['diff'],
        );

        // Indexes
        $diffIndexes = $this->diffIndexes($table);
        if ($diffIndexes['status'] != self::NO_CHANGE) {
            $diff['status'] = self::CHANGED;
        }

        $diff[self::DIFF_INDEXES] = array(
            'status' => $diffIndexes['status'],
            'diff' => $diffIndexes['diff'],
        );

        return $diff;
    }

    /**
     * Generate subpart of raport for columns of table.
     *
     * @param string $table
     * @return array
     */
    private function diffColumns($table)
    {
        $sourceTable = $this->adapter('source')->table($table);
        $targetTable = $this->adapter('target')->table($table);

        $sourceColumns = $sourceTable->columns();
        $targetColumns = $targetTable->columns();

        $baseDiff = $this->baseDiff($sourceColumns, $targetColumns);
        $diff = $baseDiff['diff'];
        $status = $baseDiff['status'];

        foreach ($diff as $column => &$diffColumn) {
            if ($diffColumn['status'] != self::NO_CHANGE) {
                // pomijam kolumny usuniete lub dodane
                continue;
            }

            // tworze kolumny ze zrodla oraz z docelowej bazy
            $sourceColumn = $sourceTable->column($column);
            $targetColumn = $targetTable->column($column);

            // przechodze po wszystkich dostepnych atrybutach kolumny
            foreach (Column::allAttributes() as $attribute) {
                if($this->omitAttribute($attribute, 'column')){
                    continue;
                }

                // pobieram wartosci tych atrybytow
                $sourceAttribute = $sourceColumn->$attribute();
                $targetAttribute = $targetColumn->$attribute();

                if ($sourceAttribute == Column::NOT_SUPPORTED || $targetAttribute == Column::NOT_SUPPORTED) {
                    // jesli ktorykolwiek z atrybutow nie jest wspierany przez
                    // ktorykolwiek z adapterow to pomijam cala procedure
                    // porownania
                    continue;
                }

                // @todo : trzeba zmodyfikowac metoda sprawdzania
                if ($sourceAttribute !== $targetAttribute) {
                    // atrybuty sa rozne wiec zaznaczam ze kolumna sie rozni,
                    // zmienna status oznacza ze w calej tabeli cos sie rozni
                    $diffColumn['status'] = $status = self::CHANGED;

                    // zapisuje wartosci tych atrybutow
                    $diffColumn['diff'][$attribute] = array(
                        'source' => $sourceAttribute,
                        'target' => $targetAttribute,
                    );
                }
            }
        }

        return array(
            'diff' => $diff,
            'status' => $status,
        );
    }

    private function diffConstraint($table)
    {
        $assert = $this->service('assert');

        $sourceTable = $this->adapter('source')->table($table);
        $targetTable = $this->adapter('target')->table($table);

        $sourceConstraints = $sourceTable->constraints();
        $targetConstraints = $targetTable->constraints();

        $baseDiff = $this->baseDiff($sourceConstraints, $targetConstraints);
        $diff = $baseDiff['diff'];
        $status = $baseDiff['status'];

        foreach ($diff as $constraint => &$diffConstraint) {
            if ($diffConstraint['status'] != self::NO_CHANGE) {
                // constraint zostal usuniete lub stworzony wiec pomijam
                continue;
            }

            // po obu stronach mamy constraint z taka sama nazwa wiec sprawdzam
            // jakie sa roznice pomiedzy nimi
            $sourceConstraint = $sourceTable->constraint($constraint);
            $targetConstraint = $targetTable->constraint($constraint);

            if (($sourceConstraint->isPrimaryKey() && $targetConstraint->isForeignKey()) || ($sourceConstraint->isForeignKey() && $targetConstraint->isPrimaryKey())) {
                // constrainty o takich samych nazwach sa innego typu, moze sie
                // pojawic jak porownujemy rozne typy bazdanych
                $name = $sourceConstraint->name();
                $assert->exception("Incompatible type of constraint with same name $name");
            }

            if ($sourceConstraint->isPrimaryKey()) {
                $sourceAttribute = $sourceConstraint->columns();
                $targetAttribute = $targetConstraint->columns();

                $changed = false;
                if (md5(var_export($sourceAttribute, true)) !== md5(var_export($targetAttribute, true))) {
                    $changed = true;
                }

                if ($changed) {
                    $diffConstraint['status'] = $status = self::CHANGED;
                    // pojawila sie zmiana dodaje rowniez informacje o type
                    // constrainta
                    $diffConstraint['constraintType'] = 'primaryKey';

                    $diffConstraint['diff'] = array(
                        'source' => $sourceAttribute,
                        'target' => $targetAttribute,
                    );
                }

            }elseif($sourceConstraint->isForeignKey()){
                foreach (Constraint::allAttributes() as $attribute) {
                    $sourceAttribute = $sourceConstraint->$attribute();
                    $targetAttribute = $targetConstraint->$attribute();

                    if ($sourceAttribute == Constraint::NOT_SUPPORTED || $targetAttribute == Constraint::NOT_SUPPORTED) {
                        continue;
                    }

                    $changed = false;
                    if ($attribute == "columns") {
                        if (md5(var_export($sourceAttribute, true)) !== md5(var_export($targetAttribute, true))) {
                            $changed = true;
                        }
                    }else{
                        if ($sourceAttribute !== $targetAttribute) {
                            $changed = true;
                        }
                    }

                    if ($changed) {
                        $diffConstraint['status'] = $status = self::CHANGED;
                        $diffConstraint['constraintType'] = 'foreignKey';

                        $diffConstraint['diff'][$attribute] = array(
                            'source' => $sourceAttribute,
                            'target' => $targetAttribute,
                        );
                    }
                }

            }else{
                $assert->exception("Unsupported type of constraint");
            }

        }

        return array(
            'status' => $status,
            'diff' => $diff,
        );
    }

    /**
     * Generate subpart of raport for triggers of table.
     *
     * @param string $table
     * @return array
     */
    private function diffTriggers($table)
    {
        $sourceTable = $this->adapter('source')->table($table);
        $targetTable = $this->adapter('target')->table($table);

        $sourceTriggers = $sourceTable->triggers();
        $targetTriggers = $targetTable->triggers();

        $baseDiff = $this->baseDiff($sourceTriggers, $targetTriggers);
        $diff = $baseDiff['diff'];
        $status = $baseDiff['status'];

        foreach ($diff as $trigger => &$diffTrigger) {
            if ($diffTrigger['status'] != self::NO_CHANGE) {
                continue;
            }

            $sourceTrigger = $sourceTable->trigger($trigger);
            $targetTrigger = $targetTable->trigger($trigger);

            foreach (Trigger::allAttributes() as $attribute) {
                $sourceAttribute = $sourceTrigger->$attribute();
                $targetAttribute = $targetTrigger->$attribute();

                if ($sourceAttribute == Column::NOT_SUPPORTED || $targetAttribute == Column::NOT_SUPPORTED) {
                    continue;
                }

                if ($attribute == Trigger::ATTRIBUTE_STATEMENT) {
                    $sourceAttribute = preg_replace('/\s+/', '', $sourceAttribute);
                    $targetAttribute = preg_replace('/\s+/', '', $targetAttribute);
                }

                if ($sourceAttribute !== $targetAttribute) {
                    $diffTrigger['status'] = $status = self::CHANGED;
                    $diffTrigger['diff'][$attribute] = array(
                        'source' => $sourceAttribute,
                        'target' => $targetAttribute,
                    );
                }
            }
        }

        return array(
            'diff' => $diff,
            'status' => $status,
        );
    }

    /**
     * Generate subpart of raport for indexes of table.
     *
     * @param string $table
     * @return array
     */
    private function diffIndexes($table)
    {
        $sourceTable = $this->adapter('source')->table($table);
        $targetTable = $this->adapter('target')->table($table);

        $sourceIndexes = $sourceTable->indexes();
        $targetIndexes = $targetTable->indexes();

        $baseDiff = $this->baseDiff($sourceIndexes, $targetIndexes);
        $diff = $baseDiff['diff'];
        $status = $baseDiff['status'];

        foreach ($diff as $index => &$diffIndex) {
            if ($diffIndex['status'] != self::NO_CHANGE) {
                continue;
            }

            $sourceIndex = $sourceTable->index($index);
            $targetIndex = $targetTable->index($index);

            foreach (Index::allAttributes() as $attribute) {
                $sourceAttribute = $sourceIndex->$attribute();
                $targetAttribute = $targetIndex->$attribute();

                if ($sourceAttribute == Column::NOT_SUPPORTED || $targetAttribute == Column::NOT_SUPPORTED) {
                    continue;
                }

                if ($sourceAttribute !== $targetAttribute) {
                    $diffIndex['status'] = $status = self::CHANGED;
                    $diffIndex['diff'][$attribute] = array(
                        'source' => $sourceAttribute,
                        'target' => $targetAttribute,
                    );
                }
            }
        }

        return array(
            'diff' => $diff,
            'status' => $status,
        );
    }

    private function diffView($view)
    {
        $diff = array();

        $status = self::NO_CHANGE;

        $sourceView = $this->adapter('source')->view($view);
        $targetView = $this->adapter('target')->view($view);

        foreach (View::allAttributes() as $attribute) {
            $sourceAttribute = $sourceView->$attribute();
            $targetAttribute = $targetView->$attribute();

            if ($sourceAttribute == Column::NOT_SUPPORTED || $targetAttribute == Column::NOT_SUPPORTED) {
                continue;
            }

            if ($sourceAttribute !== $targetAttribute) {
                $status = self::CHANGED;
                $diff[$attribute] = array(
                    'source' => $sourceAttribute,
                    'target' => $targetAttribute,
                );
            }
        }

        return array(
            'status' => $status,
            'diff' => $diff,
        );
    }

    /**
     * Set or return current setting of target name which the raport will be
     * generated.
     *
     * @param string $target
     * @return self|string Depending on whether the parameter is specified.
     */
    public function target($target = null)
    {
        if (is_null($target)) {
            return $this->target;
        }

        $this->target = $target;
        return $this;
    }

    /**
     * Set or return current setting of source name which the raport will be
     * generated.
     *
     * @param string $source
     * @return self|string Depending on whether the parameter is specified.
     */
    public function source($source = null)
    {
        if (is_null($source)) {
            return $this->source;
        }

        $this->source = $source;
        return $this;
    }

    /**
     * Return adapter instance for target or source.
     *
     * @param string $name target|source
     * @return Midata\Adapter
     */
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

    private function omitAttribute($attribute, $object)
    {
        $config = $this->service('config');
        $omitAttributes = $config->getArray("omitAttributes.$object", array());

        if (in_array($attribute, $omitAttributes)) {
            return true;
        }

        return false;
    }

    /**
     * Generate base diff raport. Base diff raport check if some object of
     * source exists at target and vice verse.
     *
     * @param array $sourceElements
     * @param array $targetElements
     * @return array
     */
    private function baseDiff($sourceElements, $targetElements)
    {
        $diff = array();
        $status = self::NO_CHANGE;

        foreach ($sourceElements as $element) {
            $diffElement['status'] = self::NO_CHANGE;

            if (!in_array($element, $targetElements)) {
                $diffElement['status'] = $status = self::DROPED;
            }

            $diff[$element] = $diffElement;
        }

        foreach ($targetElements as $element) {
            if (isset($diff[$element])) {
                continue;
            }

            $diffElement['status'] = $status = self::CREATED;
            $diff[$element] = $diffElement;
        }

        return array(
            'diff' => $diff,
            'status' => $status,
        );
    }

}
