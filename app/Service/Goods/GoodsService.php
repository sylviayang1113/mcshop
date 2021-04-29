<?php


namespace App\Service\Goods;


use App\Models\Goods\Goods;
use App\Models\Goods\GoodsAttribute;
use App\Models\Goods\GoodsProduct;
use App\Models\Goods\GoodsSepecification;
use App\Models\Goods\Issue;
use App\Service\BaseService;
use Illuminate\Database\Query\Builder;

class GoodsService extends BaseService
{
    public function getGoods(int $id)
    {
        return Goods::query()->find($id);
    }

    public function getGoodsAttribute(int $goodsId)
    {
        return GoodsAttribute::query()->where('goods_id', $goodsId)
            ->where('deleted', 0)->get();
    }

    public function getGoodsSpecification(int $goodsId)
    {
        $spec = GoodsSepecification::query()->where('goods_id', $goodsId)
            ->where('deleted', 0)->get()->groupBy('specification');
        return $spec->map(function ($v, $k) {
           return ['name' => $k, 'valueList' => $v];
        });

    }

    public function getGoodsProduct(int $goodsId)
    {
        return GoodsProduct::query()->where('goods_id', $goodsId)
            ->where('deleted', 0)->get();
    }

    public function getGoodsIssue(int $page = 1, int $limit = 4)
    {
        return Issue::query()->forPage($page, $limit)->get();
    }
    /**
     * 获取在售商品的数量
     * @return int
     */
    public function countGoodsOnSale()
    {
        return Goods::query()->where('is_on_sale', 1)
            ->where('deleted', 0)->count('id');
    }

    public function listGoods(
        $categoryId,
        $brandId,
        $isNew,
        $isHot,
        $keyword,
        $sort = 'add_time',
        $order = 'desc',
        $page = 1,
        $limit = 10
    )
    {
        $query = $this->getQueryByGoodsFilter($brandId, $isNew, $isHot, $keyword);
        if (!empty($categoryId)) {
            $query = $query->where('category_id', $categoryId);
        }

        return $query->orderBy($sort, $order)
            ->paginate($limit, ['*'], 'page', $page);

    }

    public function listL2Category($brandId, $isNew, $isHot, $keyword)
    {
        $query = $this->getQueryByGoodsFilter($brandId, $isNew, $isHot, $keyword);
        $categoryIds = $query->select(['category_id'])->pluck('category_id')->toArray();
        return CatalogService::getInstance()->getL2ListByIds($categoryIds);
    }

    private function getQueryByGoodsFilter($brandId, $isNew, $isHot, $keyword)
    {
        $query = Goods::query()->where('is_on_sale', 1)
            ->where('deleted', 0);
        if (!empty($brandId)) {
            $query = $query->where('brand_id', $brandId);
        }

        if (!empty($isNew)) {
            $query = $query->where('brand_id', $isNew);
        }

        if (!empty($isHot)) {
            $query = $query->where('brand_id', $isHot);
        }

        if (!empty($keyword)) {
            $query = $query->where(function (Builder $query) use ($keyword) {
                $query->where('keywords', 'like', "%$keyword%")
                    ->orWhere('name', 'like', "%$keyword%");
            });
        }
        return $query;
    }

}