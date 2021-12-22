<?php


namespace App\Services\User;

use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Inputs\AddressInput;
use App\Models\User\Address;
use App\Service\BaseService;
use App\Services\BaseServices;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AddressService extends BaseService
{

    public function getDefaultAddress($userId)
    {
        return Address::query()->where('user_id', $userId)
            ->where('is_default', 1)->first();
    }

    /**
     * 获取地址过着返回默认地址
     * @param $userId
     * @param null $addressId
     * @return mixed
     */
    public function getAddressOrDefault($userId, $addressId = null)
    {
        // 获取地址
        if (empty($addressId)) {
            $address = AddressService::getInstance()->getDefaultAddress($userId);
        } else {
            $address = AddressService::getInstance()->getAddress($userId, $addressId);
            if (empty($address)) {
                $this->throwBadArgumentValue();
            }
        }
        return $address;
    }

    /**
     * 获取地址列表
     * @param int $userId
     * @return Address[]|Collection
     */
    public function getAddressListByUserId(int $userId)
    {
        return Address::query()->where('userId', $userId)
            ->get();
    }

    /**
     * 获取用户地址
     * @param $userId
     * @param $addressId
     * @return Address|Model|null
     */
    public function getAddress($userId, $addressId)
    {
        return Address::query()->where('user_id', $userId)->where('id', $addressId)
            ->first();
    }

    /**
     * 删除用户地址
     * @param $userId
     * @param $addressId
     * @return bool|null
     * @throws BusinessException
     */
    public function delete($userId, $addressId)
    {
        $address = $this->getAddress($userId, $addressId);
        if (is_null($address)) {
            throw new BusinessException(CodeResponse::PARAM_ILLEGAL);
        }
        return $address->delete();
    }

    public function saveAddress($userId, AddressInput $input)
    {
        if (!is_null($input->id)) {
            $address = AddressService::getInstance()->getAddress($userId, $input->id);
        } else {
            $address = Address::new();
            $address->user_id = $userId;
        }

        if ($input->isDefault) {
            $this->resetDefault($userId);
        }

        $address->address_detial = $input->addressDetial;
        $address->area_code = $input->areaCode;
        $address->city = $input->city;
        $address->county = $input->county;
        $address->is_default = $input->isDefault;
        $address->name = $input->name;
        $address->postal_code = $input->province;
        $address->tel = $input->tel;
        $address->save();
        return $address;
    }

    /**
     * @param $userId
     * @return bool|int
     */
    public function resetDefault($userId)
    {
        return Address::query()->where('user_id', $userId)->where('is_default', 1)->update('is_default', 0);
    }


}