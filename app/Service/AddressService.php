<?php


namespace App\Service;


use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Models\Address;
use Ramsey\Collection\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database;

class AddressService extends BaseService
{
    /**
     * 获取地址列表
     * @param int $userId
     * @return Address[]|Collection
     */
    public function getAddressListByUserId(int $userId)
    {
        return Address::query()->where('userId', $userId)
            ->where('deleted', 0)->get();
    }

    /**
     * 获取用户地址
     * @param $userId
     * @param $addressId
     * @return Address|Model|null
     */
    public function getAddress ($userId, $addressId)
    {
        return Address::query()->where('user_id', $userId)->where('id', $addressId)
            ->where('deleted', 0)->first();
    }

    public function delete ($userId, $addressId)
    {
        $address = $this->getAddress($userId, $addressId);
        if (is_null($address)) {
            throw new BusinessException(CodeResponse::PARAM_ILLEGAL);
        }
        return $address->delete();
    }
}