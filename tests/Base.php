<?php
namespace Dbcompar\Tests;

if (!class_exists('PHPUnit_Framework_TestCase')) {
    return;
}

class Base extends \PHPUnit_Framework_TestCase
{
    public function md5($var)
    {
        return md5(var_export($var, true));
    }

    public function inline($var)
    {
        $var = explode("\n", var_export($var,true));
        $inline = "";

        foreach ($var as $element) {
            $element = str_replace(" ", "", $element);
            $inline .= $element;
        }

        return $inline;
    }

    protected function init()
    {
    }
}
