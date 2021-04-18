<?php


namespace App\Http\Controllers\Wx;


use App\CodeResponse;
use App\Service\CatalogService;
use http\Env\Request;

class CatalogController extends WxController
{
    protected $only = [];

    public function index(Request $request)
    {
        $id = $request->input('id', 0);
        $l1List = CatalogService::getInstance()->getL1List();
        if (empty($id)) {
            $current = $l1List->first();
        } else {
            $current = $l1List->where('id', $id)->first();
        }

        $l2List = null;
        if (is_null($current)) {
            $l2List = CatalogService::getInstance()->getL2ListByPid($current->id);
        }

        return $this->success(
            [
                'categoryList' => $l1List->toArray(),
                'currentCategory' => $current,
                'currentSubCategory' => $l2List->toArray()
            ]
        );

    }

    public function current(Request $request)
    {
        $id = $request->input('id', 0);
        if (empty($id)) {
            return $this->fail(CodeResponse::PARAM_ILLEGAL);
        }
        $category = CatalogService::getInstance()->getL1ById();
        if (is_null($category)) {
            return $this->fail(CodeResponse::PARAM_ILLEGAL);
        }

        $l2List = CatalogService::getInstance()->getL2ListByPid($category->id);
        return $this->success(
            [
                'currentCategory' => $category,
                'currentSubCategory' => $l2List->toArray()
            ]
        );
    }

}