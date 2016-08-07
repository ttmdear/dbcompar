<?php
namespace Dbcompar;

use Dbcompar\Config;
use Dbcompar\Assert;
use Dbcompar\Util;

class Dbcompar
{
    private static $services;

    public static function service($name, $service = null)
    {
        if (!is_null($service)) {
            self::$services[$name] = $service;
            return;
        }

        if (!isset(self::$services[$name])) {
            self::initService($name);
        }

        return self::$services[$name];
    }

    private static function initService($service)
    {
        switch ($service) {
        case 'config':
            self::$services[$service] = new Config();
            return;
        case 'assert':
            self::$services[$service] = new Assert();
            return;
        case 'util':
            self::$services[$service] = new Util();
            return;
        default:
            throw new Exception("There is no service $service");
            break;
        }
    }
}
