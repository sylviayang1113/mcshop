<?php


namespace App\Service\Goods;

use App\Models\Goods\Brand;
use App\Service\BaseService;

class BrandService extends BaseService
{
    public function getBrand(int $id)
    {
        return Brand::query()->find();
    }

    public function getBrandList(int $page, int $limit, $sort, $order, $columns = ['*'])
    {

        $query = Brand::query()->where('deleted', 0);
        if (!empty($sort) && !empty($order)) {
            $query = $query->orderBy($sort, $order);
        }
        return $query->paginate($limit, $columns, 'page', $page);
    }
}