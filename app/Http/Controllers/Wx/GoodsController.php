<?php


namespace App\Http\Controllers\Wx;


use App\CodeResponse;
use App\Constant;
use App\Models\SearchHistory;
use App\Service\CollectService;
use App\Service\CommentService;
use App\Service\Goods\BrandService;
use App\Service\Goods\CatalogService;
use App\Service\Goods\GoodsService;
use App\Service\SearchHistoryService;
use http\Env\Request;
use Illuminate\Validation\Rule;
use phpDocumentor\Reflection\Utils;

class GoodsController extends WxController
{

    protected $only = [];

    public function count()
    {
        $count = GoodsService::getInstance()->countGoodsOnSale() ;
        return $this->success($count);
    }

    public function category(Request $request)
    {
        $id = $request->input('id', 0);
        if (empty($id)) {
            return $this->fail(CodeResponse::PARAM_VALUE_ILLEGAL);
        }
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

    public function list(Request $request)
    {
//        $input = $request->validate(
//            [
//                'categoryId' => 'integer|digits_between:1, 20',
//                'brandId' => 'integer|digits_between:1. 20',
//                'keyword' => 'string',
//                'isNew' => 'boolean',
//                'isHot' => 'boolean',
//                'page' => 'integer',
//                'limit' => 'integer',
//                'sort' => Rule::in(['add_time', 'retail_price', 'name']),
//                'order' => Rule::in(['desc', 'asc']),
//            ]
//        );
        $categoryId = $this->verifyId('categoryId');
        $brandId = $this->verifyId('categoryId');
        $keyword = $this->verifyString('keyword');
        $isNew = $this->verifyBoolean('isNew');
        $isHot = $this->verifyBoolean('isHot');

        $categoryId = $request->input('categoryId');
        $brandId = $request->input('brandId');
        $keyword = $request->input('keyword');
        $isNew = $request->input('isNew');
        $isHot = $request->input('isHot');
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'add_time');
        $order = $request->input('order', 'desc');

        // todo 验证参数

        if ($this->isLogin() && !empty($keyword)) {
            SearchHistoryService::getInstance()->save($this->userId(), $keyword, Constant::SEARCH_HISTORY_FROM_WX);
        }

        // todo 优化参数传递
        $goodsList = GoodsService::getInstance()->listGoods(
            $categoryId, $brandId, $isNew, $isHot, $keyword,
            $sort, $page, $limit);

        $categoryList = GoodsService::getInstance()->list2L2Category($brandId, $isNew, $isHot, $keyword);

        $goodsList = $this->paginate($goodsList);
        $goodsList['filterCategoryList'] = $categoryList;
        return $this->success($goodsList);
    }

    public function detail(Request $request)
    {
        $id = $request->input('id');
        if (empty($id)) {
            return $this->fail(CodeResponse::PARAM_ILLEGAL);
        }
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
            GoodsService::getInstance()->saveFootPrint($this->useId(), $id);
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