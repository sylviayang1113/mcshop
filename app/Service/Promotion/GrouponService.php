<?php


namespace App\Service\Promotion;


use App\CodeResponse;
use App\Enums\GrouponEmuns;
use App\Exceptions\BusinessException;
use App\Inputs\PageInput;
use App\Models\Promotion\Groupon;
use App\Models\Promotion\GrouponRules;
use App\Service\BaseService;
use Illuminate\Database\Query\Builder;

class GrouponService extends BaseService
{
    public function getGrouponRules(PageInput $page, $columns = ['*'])
    {
        return GrouponRules::whereStatus(GrouponEmuns::RULE_STATUS_ON)
            ->orderBy($page->sort, $page->order)
            ->paginate($page->limit, $columns, 'page'. $page->page);
    }

    public function getGrouponRulesById($id, $columns = ['*'])
    {
        return GrouponRules::query()->find($id, $columns);
    }

    /**
     * 获取参团人数
     * @param int $openGrouponId 开团团购活动id
     * @return int
     */
    public function countGrouponJoin($openGrouponId)
    {
        return Groupon::query()->whereGrouponId($openGrouponId)->where('status', '!=', GrouponEmuns::STATUS_NONE)
            ->count(['id']);
    }

    /**
     * 用户是否参与或开启
     * @param $userId
     * @param $grouponId
     * @return mixed
     */
    public function isOpenOrJoin($userId, $grouponId)
    {
        return Groupon::query()->whereUserId($userId)
            ->where(function (Builder $builder) use ($grouponId){
                return $builder->where('groupon_id', $grouponId)
                    ->orWhere('id', $grouponId);
            })->where('status', '!=', GrouponEmuns::STATUS_NONE)->exists();
    }

    /**
     * 校验用户是否可以开启或参与某个团购活动
     * @param $userId
     * @param $ruleId
     * @param null $linkId
     * @throws BusinessException
     */
    public function checkoutGrouponValid($userId, $ruleId, $linkId = null)
    {
        if ($ruleId == null || $ruleId <= 0) {
            return;
        }

        $rule = $this->getGrouponRulesById($ruleId);
        if (is_null($rule)) {
            $this->throwBusinessException(CodeResponse::PARAM_ILLEGAL);
        }
        // 规则已过期
        if ($rule->status == GrouponEmuns::RULE_STATUS_DOWN_EXPIRE) {
            $this->throwBusinessException(CodeResponse::GROUPON_EXPIRED);
        }
        // 规则已下线
        if ($rule->status == GrouponEmuns::RULE_STATUS_DOWN_ADMIN) {
            $this->throwBusinessException(CodeResponse::GROUPON_OFFLINE);
        }

        if ($linkId == null && $linkId <= 0) {
            return;
        }

        if ($this->countGrouponJoin($linkId) >= ($rule->discount_member - 1)) {
            $this->throwBusinessException(CodeResponse::G);
        }

        if ($this->isOpenOrJoin($userId, $linkId)) {
            $this->throwBusinessException(CodeResponse::GROUPON_JOIN);
        }
        return;
    }
}