<?php


namespace App;


class Constant
{
    /**
     * 搜索关键词派员
     */
    const SEARCH_HISTORY_FROM_WX = 'wx';
    const SEARCH_HISTORY_FROM_APP = 'app';
    const SEARCH_HISTORY_FROM_PC = 'pc';

    /**
     * 收藏类型
     */
    const COLLETCT_TYPE_GOODS = 0;
    const COLLETCT_TYPE_TOPIC = 1;

    /**
     * 评价类型
     */
    const COMMET_TYPE_GOODS = 0;
    const COMMET_TYPE_TOPIC = 1;

    /**
     * 优惠券类型
     */
    const TYPE_COMMON = 0;
    const TYPE_REGISTER = 1;
    const TYPE_CODE = 2;


    /**
     * 优惠券商品限制
     */
    const GOODS_TYPE_ALL = 0;
    const GOODS_TYPE_CATEGORY = 1;
    const GOODS_TYPE_ARRAY = 2;

    /**
     * 优惠券状态
     */
    const STATUS_NORMAL = 0;
    const STATUS_EXPIRED = 1;
    const STATUS_OUT = 2;

    /**
     * 优惠券时间类型
     */
    const TIME_TYPE_DAYS = 0;
    const TIME_TYPE_TIME = 1;
}