<?php


namespace App\Http;


use App\CodeResponse;
use App\Exceptions\BusinessException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait VerifyRequestInput
{
    public function verifyId($key, $default = null)
    {
        return  $this->verifyData($key, $default, 'integer|digits_between:1, 20');
    }

    public function verifyString($key, $default = null)
    {
        return  $this->verifyData($key, $default, 'string');
    }

    public function verifyBoolean($key, $default = null)
    {
        return  $this->verifyData($key, $default, 'boolean');
    }

    public function verifyInteger($key, $default = null)
    {
        return  $this->verifyData($key, $default, 'integer');
    }
    public function verifyEnum($key, $default = null, $enum = [])
    {
        return $this->verifyData($key, $default, Rule::in($enum));
    }

    /**
     * @param $key
     * @param $default
     * @param $rule
     * @return mixed
     * @throws BusinessException
     */
    public function verifyData($key, $default, $rule)
    {
        $value = request()->input($key, $default);
        $validator = Validator::make([$key => $value], [$key => $rule]);
        if (is_null($default) && is_null($value)) {
            return $value;
        }
        if ($validator->fails()) {
            throw new BusinessException(CodeResponse::PARAM_VALUE_ILLEGAL);
        }
        return $value;
    }
}