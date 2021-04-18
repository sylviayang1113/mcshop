<?php


namespace App\Http\Controllers\Wx;


use App\CodeResponse;
use App\Service\AddressService;
use http\Env\Request;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Ramsey\Collection\Collection;

class AddressController extends WxController
{
    /**
     * 获取用户列表
     * @return JsonResponse
     */
    public function list ()
    {
        $list = AddressService::getInstance()->getAddressListByUserId($this->user()->id);
        $list = $list->map(function ($address) {
            $address->toArray();
            $item = [];
            foreach ($address as $key => $value) {
                $key = lcfirst(Str::studly($key));
                $item[$key] = $value;
            }
            return [
                'id' => $address->id,
                'areaCode' => $address->area_code
            ];
        });
        return $this->success([
            'total' => $list->count(),
            'page' => 1,
            'list' => $list->toArray(),
            'pages' => 1
        ]);
    }

    public function detail ()
    {
        $id = $this->verifyId('id', 0);
        $address = AddressService::getInstance()-getAddress($this->userId, $id);
        if (empty($address)) {
            return $this->badArgumentValue();
        }
        return $this->success($address);

    }

    /**
     * 保存地址
     * @return JsonResponse
     */
    public function save ()
    {
        $input = AddressInput::new();
        $address = AddressService::getInstance()->saveAddress($this->userId(), $input);
        return $this->success($address->id);
    }

    public function delete (Request $request)
    {
        $id = $request->input('id', 0);
        if (empty($id) && !is_numeric($id)) {
            return $this->throwBusinessException(CodeResponse::PARAM_ILLEGAL);
        }
        AddressService::getInstance()->delete($this->user()->id, $id);
        return $this->success();
    }
}