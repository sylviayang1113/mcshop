<?php


namespace App\Service\Promotion;


use App\Enums\GrouponEmuns;
use App\Inputs\PageInput;
use App\Models\Goods\Goods;
use App\Models\Promotion\GrouponRules;
use App\Service\BaseService;

class GrouponService extends BaseService
{
    public function getGrouponRules(PageInput $page, $columns = ['*'])
    {
        return GrouponRules::whereStatus(GrouponEmuns::RULE_STATUS_ON)
            ->orderBy($page->sort, $page->order)
            ->paginate($page->limit, $columns, 'page'. $page->page);
    }

}