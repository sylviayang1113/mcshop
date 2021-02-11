<?php


namespace App\Service;



class BaseService
{
    protected static $instance;

    public static function getInstance()
    {
        if (static::$instance instanceof static) {
            return self::$instance;
        }
        static::$instance = new static();
        return static::$instance;
    }

    private function __construct()
    {
    }
    private function __clone()
    {

    }

}