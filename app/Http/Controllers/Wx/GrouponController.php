<?php


namespace App\Http\Controllers\Wx;


use App\Inputs\PageInput;
use App\Service\Goods\GoodsService;
use App\Service\Promotion\GrouponService;

class GrouponController extends WxController
{
    public function list()
    {
        $page = PageInput::new();
        $list = GrouponService::getInstance()->getGrouponRules($page);

        $rules = collect($list->items());
        $goodsIds = $rules->pluck('goods_id')->toArray();
    }

}