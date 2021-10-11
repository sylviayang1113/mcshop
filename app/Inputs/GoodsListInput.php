<?php
namespace App\Inputs;

use App\Http\VerifyRequestInput;

trait GoodsListInput
{
    use VerifyRequestInput;
    public $categoryId;
    public $brandId;
    public $keyword;
    public $isNew;
    public $isHot;
    public $page = 1;
    public $limit = 10;
    public $sort = 'add_time';
    public $order = 'desc';

    /**
     * @throws \App\Exceptions\BusinessException
     */
    public function fill()
    {
        $this->categoryId = $this->verifyId('categoryId');
        $this->brandId = $this->verifyId('brandId');
        $this->keyword = $this->verifyString('keyword');
        $this->isNew = $this->verifyBoolean('isNew');
        $this->isHot = $this->verifyBoolean('isHot');
        $this->page = $this->verifyInteger('page', 1);
        $this->limit = $this->verifyInteger('limit', 10);
        $this->sort = $this->verifyEnum('sort', 'add_time', ['add_time', 'retail_price', 'name']);
        $this->order = $this->verifyEnum('order', 'desc', ['desc', 'asc']);

        return $this;
    }

}
