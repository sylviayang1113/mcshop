<?php


namespace App\Http\Controllers\Wx;


use App\CodeResponse;
use App\Constant;
use App\Http\VerifyRequestInput;
use App\Inputs\GoodsList;
use App\Inputs\GoodsListInput;
use App\Service\CollectService;
use App\Service\CommentService;
use App\Service\Goods\BrandService;
use App\Service\Goods\CatalogService;
use App\Service\Goods\GoodsService;
use App\Service\SearchHistoryService;
use http\Env\Request;

class GoodsController extends WxController
{

    use VerifyRequestInput;

    protected $only = [];

    public function count()
    {
        $count = GoodsService::getInstance()->countGoodsOnSale() ;
        return $this->success($count);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function category()
    {
        $id = $this->verifyId('id');
        $cur = CatalogService::getInstance()->getCategory($id);
        if (empty($cur)) {
            return $this->fail(CodeResponse::PARAM_VALUE_ILLEGAL);
        }

        $parent = null;
        $children = null;
        if ($cur->pid == 0) {
            $parent = $cur;
            $children = CatalogService::getInstance()->getL2ListByPid($cur->id);
            $cur = $children->first() ?? $cur;
        } else {
            $parent = CatalogService::getInstance()->getL1ById($cur->pid);
            $children = CatalogService::getInstance()->getL2ListByPid($cur->pid);
        }

        return $this->success([
           'currentCategory' => $cur,
           'parentCategory' => $parent,
           'brotherCategory' => $children
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list()
    {
        $input = GoodsListInput::new();

        if ($this->isLogin() && !empty($keyword)) {
            SearchHistoryService::getInstance()->save($this->userId(), $keyword, Constant::SEARCH_HISTORY_FROM_WX);
        }

        $goodsList = GoodsService::getInstance()->listGoods($input);

        $categoryList = GoodsService::getInstance()->list2L2Category($input);

        $goodsList = $this->paginate($goodsList);
        $goodsList['filterCategoryList'] = $categoryList;
        return $this->success($goodsList);
    }

    public function detail()
    {
        $id = $this->verifyId('id');
        $info = GoodsService::getInstance()->getGoods($id);
        if (empty($info)) {
            return $this->fail(CodeResponse::PARAM_VALUE_ILLEGAL);
        }

        $attr = GoodsService::getInstance()->getGoodsAttribute($id);
        $spec = GoodsService::getInstance()->getGoodsSpecification($id);
        $product = GoodsService::getInstance()->getGoodsProduct($id);
        $issue = GoodsService::getInstance()->getGoodsIssue();
        $brand = $info->brand_id ? BrandService::getInstance()->getBrand($info->brand_id) : (object)[];
        $comment = CommentService::getInstance()->getCommentByGoodsId($id);
        $userHasCollect = 0;
        if ($this->isLogin) {
            $userHasCollect = CollectService::getInstance()->countByGoddsId($this->userId(), $id);
            GoodsService::getInstance()->saveFootPrint($this->userId(), $id);
        }
        // todo 团购信息
        // todo 系统配置
        return $this->success([
            'info' => $info,
            'userHasCollect' => $userHasCollect,
            'issue' => $issue,
            'comment' => $comment,
            'specificationList' => $spec,
            'productList' => $product,
            'attribute' => $attr,
            'brand' => $brand,
            'groupon' => [],
            'share' => false,
            'shareImg' => $info->share_url
        ]);
    }
}