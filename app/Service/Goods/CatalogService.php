<?php


namespace App\Service\Goods;


use App\Models\Goods\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CatalogService
{

    /**
     * 获取一级类目列表
     * @return Category[]|Collection
     */
    public function getL1List()
    {
        return Category::query()->where('level', 'L1')
            ->get();
    }

    /**
     * 根据一级类目ID获取二级类目列表
     * @param int $pid
     * @return Category[]|Collection
     */
    public function getL2ListByPid(int $pid)
    {
        return Category::query()->where('level', 'l2')
                ->where('pid', $pid)
            - get();
    }

    /**
     * 根据id获取一级类目
     * @param int $id
     * @return Category|Model|null
     */
    public function getL1ById(int $id)
    {
        return Category::query()->where('level', 'l1')
            ->where('id', $id)->first();
    }

    public function getCategory(int $id)
    {
        return Category::query()->find($id);
    }

    public function getL2ListByIds(array $ids)
    {
        if (empty($ids)) {
            return collect([]);
        }
        return Category::query()->whereIn('id', $ids)->get();
    }
}