<?php
namespace App\Inputs;

use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Http\VerifyRequestInput;
use Illuminate\Support\Facades\Validator;

class Input
{
    use VerifyRequestInput;

    /**
     * @param null|array $data
     * @return $this
     * @throws BusinessException
     */
    public function fill($data = null)
    {
        if (is_null($data)) {
            $data = request()->input();
        }
        $data = request()->input();
        $validator = Validator::make($data, $this->rules());
        if ($validator->fails()) {
            throw new BusinessException(CodeResponse::PARAM_VALUE_ILLEGAL);
        }

        $map = get_object_vars($this);
        $keys = array_keys($map);

        collect($data)->map(function ($value, $key) use ($keys) {
            if (in_array($key, $keys)) {
                $this->$key = $value;
            }
        });
        return $this;
    }

    public function rules()
    {
        return [];
    }

    /**
     * @param null|array  $data
     * @return Input|static
     * @throws BusinessException
     */
    public static function new($data = null)
    {
        return (new static())->fill($data);
    }

}
