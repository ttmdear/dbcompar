<?php
namespace Dbcompar;

use Dbcompar\Dbcompar;

class Util extends Dbcompar
{
    public function arrayHas($array, $index)
    {
        $assert = $this->service('assert');
        $assert->isArray($array);

        return in_array($index, array_keys($array));
    }
}
