<?php


namespace App\Service;



use App\CodeResponse;
use App\Exceptions\BusinessException;

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

    /**
     * @param array $codeResponce
     * @throws BusinessException
     */
    public function throwBusinessException (array $codeResponce)
    {
        throw new BusinessException($codeResponce);
    }

}