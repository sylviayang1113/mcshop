<?php


namespace App\Http\Controllers\Wx;


use App\CodeResponse;
use App\Http\Controllers\Controller;

class WxController extends Controller
{

    protected function codeReturn(array $codeResponse, $data = null, $info = '')
    {
        list($errno, $errmsg) = $codeResponse;
        $ret =  ['errno' => $errno, 'errmsg'=> $info ?: $errmsg];
        if (!is_null($data)) {
            $ret['data'] = $data;
        }
        return response()->json($ret);
    }

    protected function success($data)
    {
        return $this->codeReturn(CodeResponse::SUCCESS, $data);
    }

    protected function fail(array $codeResponse, $errmsg)
    {
        return $this->codeReturn(CodeResponse::PARAM_ILLEGAL);
    }
}