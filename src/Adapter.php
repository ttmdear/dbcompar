<?php
namespace Dbcompar;

use Dbcompar\Dbcompar;
use Midata\Adapter as MidataAdapter;

class Adapter extends Dbcompar
{
    public static function factory($name)
    {
        $assert = static::service('assert');
        $config = static::service('config');

        $config = $config->get('databases.#1', null, $name);
        $adapter = $config['adapter'];

        $assert->notNull($config, "There is no config for $name.");
        $assert->notNull($adapter, "Please define adapter to database $name.");

        return MidataAdapter::factory($adapter, $config, $name);
    }
}
