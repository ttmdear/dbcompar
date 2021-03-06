<?php
namespace Dbcompar;

use Dbcompar\Dbcompar;

class Config extends Dbcompar
{
    private $config;

    public function load($path)
    {
        $assert = $this->service('assert');
        $assert->isReadable($path, 'Config file is not readable');

        $exploded = explode('.', $path);
        if($exploded[count($exploded)-1] == 'xml'){
            $this->xml($path);
            return $this;
        }

        $assert->exception("The config format file is not supported.");
    }

    public function get($path, $default = null, $values = array())
    {
        $assert = $this->service('assert');
        if (is_string($values)) {
            $values = array($values);
        }

        $assert->isArray($values);
        foreach ($values as $index => $value) {
            $index++;
            $path = str_replace("#$index", $value, $path);
        }

        $assert = $this->service('assert');
        $path = explode('.', $path);
        $pointer = $this->config;

        foreach ($path as $element) {
            if (!isset($pointer[$element])) {
                return $default;
            }

            $pointer = $pointer[$element];
        }

        return $pointer;
    }

    public function getArray($path, $default = null, $values = array(), $separator = ',')
    {
        $value = $this->get($path, $default, $values);

        if (!is_string($value)) {
            return $value;
        }

        return explode($separator, $value);
    }

    public function set($path, $value = null, $values = array())
    {
        $assert = $this->service('assert');
        if (is_string($values)) {
            $values = array($values);
        }

        $assert->isArray($values);

        foreach ($values as $index => $value) {
            $index++;
            $path = str_replace("#$index", $value, $path);
        }

        $path = explode('.', $path);
        $last = array_pop($path);
        $pointer = &$this->config;

        foreach ($path as $element) {
            if (!isset($pointer[$element])) {
                $path = implode(".", $path);
                $assert->exception("There are no seetings $path.");
            }

            $pointer = &$pointer[$element];
        }

        $pointer[$last] = $value;

        return $this;
    }

    private function xml($path)
    {
        $node = simplexml_load_string(file_get_contents($path));

        $config = array();
        $this->parseChildren($node->children(), $config);

        $this->config = $config;
    }

    private function parseChildren($children, &$config)
    {
        foreach ($children as $child) {
            $name = $this->getName($child);

            if ($this->hasChildren($child)) {
                $tmp = array();
                $this->parseChildren($child->children(), $tmp);
                $config[$name] = $tmp;
            }else{
                $string = $child->__toString();
                if (in_array($string, array("true", "false"))) {
                    if ($string == "true") {
                        $string = true;
                    }else{
                        $string = false;
                    }
                }
                $config[$name] = $string;
            }
        }
    }

    private function hasChildren($parentNode)
    {
        if (!empty($parentNode->children())) {
            return true;
        }

        return false;
    }

    private function getName($node)
    {
        $name = $node->attributes()->name;

        if (!empty($name)) {
            return $name->__toString();
        }

        return $node->getName();
    }
}
