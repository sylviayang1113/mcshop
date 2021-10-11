<?php


namespace App\Service\Goods;


use App\Inputs\GoodsListInput;
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
        })->values();

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

    public function saveFootPrint($userId, $goodsId)
    {
        $footPrint = new FootPrint();
        $footPrint->fill(['user_id' => $userId, 'goods_id' => $goodsId]);
        return $footPrint->save();
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

    public function listGoods(GoodsListInput $input)
    {
        $query = $this->getQueryByGoodsFilter($input);
        if (!empty($input->categoryId)) {
            $query = $query->where('category_id', $input->categoryId);
        }

        return $query->orderBy($input->sort, $input->order)
            ->paginate($input->limit, ['*'], 'page', $input->page);

    }

    public function listL2Category($brandId, $isNew, $isHot, $keyword)
    {
        $query = $this->getQueryByGoodsFilter($brandId, $isNew, $isHot, $keyword);
        $categoryIds = $query->select(['category_id'])->pluck('category_id')->toArray();
        return CatalogService::getInstance()->getL2ListByIds($categoryIds);
    }

    private function getQueryByGoodsFilter(GoodsListInput  $input)
    {
        $query = Goods::query()->where('is_on_sale', 1)
            ->where('deleted', 0);
        if (!empty($input->brandId)) {
            $query = $query->where('brand_id', $input->brandId);
        }

        if (!empty($input->isNew)) {
            $query = $query->where('brand_id', $input->isNew);
        }

        if (!empty($input->isHot)) {
            $query = $query->where('brand_id', $input->isHot);
        }

        if (!empty($input->keyword)) {
            $query = $query->where(function (Builder $query) use ($input->keyword) {
                $query->where('keywords', 'like', "%$input->keyword%")
                    ->orWhere('name', 'like', "%$input->keyword%");
            });
        }
        return $query;
    }

}