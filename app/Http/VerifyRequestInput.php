<?php


namespace App\Http;


use App\Exceptions\BusinessException;

trait VerifyRequestInput
{
    /**
     * @param $key
     * @param null $default
     * @return mixed
     * @throws BusinessException
     */
    public function  verifyString($key, $default = null)
    {
        return $this->verifyData($key, $default, 'string');
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     * @throws BusinessException
     */
    public function verifyBoolean($key, $default = null)
    {
        return $this->verifyData($key, $default, 'boolean');
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     * @throws BusinessException
     */
    public function verifyPositiveInteger($key, $default = null)
    {
        return $this->verifyData($key, $default, 'integer|min:1');
    }
}