<?php
namespace Dbcompar;

use Dbcompar\Dbcompar;

class Buffor extends Dbcompar
{
    private $sections = array();

    public function append($section, $text)
    {
        if (!isset($this->sections[$section])) {
            $this->sections[$section] = "";
        }

        $this->sections[$section] .= "\n\n".$text;

        return $this;
    }

    public function get()
    {
        $sections = array_keys($this->sections);

        $order = array(
            // usuwamy wszystkie constrainty
            'drop-constraint',

            // tworzymy kolumny bo byc moze beda wykorzystywane
            // w nowych constraintach
            'create-column',

            // modyfikujemy kolumny, modyfikacje moga zalezec od utworzonych
            // kolumn
            'alter-column',

            // usuwam zbedne kolumny
            'drop-column',

            // modyfikujemy istniejace constrainty
            'alter-constraint',

            // tworze, usuwam, modyfikuje tabele
            'create-table',
            'alter-table',
            'drop-table',

            // tworze, usuwam, modyfikuje triggery
            'create-trigger',
            'alter-trigger',
            'drop-trigger',

            // tworze, usuwam, modyfikuje indexy
            'create-index',
            'alter-index',
            'drop-index',

            'create-constraint',

            'create-view',
            'drop-view',
            'alter-view',
        );

        $result = "";

        foreach ($order as $section) {
            if (isset($this->sections[$section])) {
                $result .= $this->sections[$section];
            }
        }

        return $result;
    }
}
