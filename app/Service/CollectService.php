<?php


namespace App\Service;


use App\Constant;
use App\Models\Collect;

class CollectService extends BaseService
{
    public function countByGoodsId($userId, $goodsId)
    {
        return Collect::query()->where('user_id', $userId)
            ->where('value_id', $goodsId)
            ->where('type', Constant::COLLETCT_TYPE_GOODS)
            ->count('id');
    }

}