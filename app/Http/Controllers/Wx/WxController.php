<?php


namespace App\Http\Controllers\Wx;


use App\CodeResponse;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Ramsey\Collection\Collection;

class WxController extends Controller
{
    protected $only;
    protected $except;
    public function __construct()
    {
        $option = [];
        if (!is_null($this->only)) {
            $option['only'] = $this->only;
        }
        if (!is_null($this->except)) {
            $option['only'] = $this->only;
        }
        $this->middleware('auth:wx', $option);
    }

    protected function codeReturn(array $codeResponse, $data = null, $info = '')
    {
        list($errno, $errmsg) = $codeResponse;
        $ret =  ['errno' => $errno, 'errmsg'=> $info ?: $errmsg];
        if (!is_null($data)) {
            if (is_array($data)) {
                $data = array_filter($data, function($item) {
                   return $item !== null;
                });
            }
            $ret['data'] = $data;
        }
        return response()->json($ret);
    }

    protected function successPaginate($page)
    {
        $this->success($this->paginate($page));
    }

    public function paginate($page)
    {
        if ($page instanceof LengthAwarePaginator) {
            return [
              'total' => $page->total(),
              'page' => $page->currentPage(),
              'limit' => $page->perPage(),
              'pages' => $page->lastPage()
            ];
        }
        if ($page instanceof Collection) {
            $page = $page->toArray();
        }

        if (is_array($page)) {
            return $page;
        }

        $total = count($page);
        return [
            'total' => $total,
            'page' => 1,
            'limit' => $total,
            'pages' => 1,
            'list' => $page
        ];
    }

    protected function success($data = null)
    {
        return $this->codeReturn(CodeResponse::SUCCESS, $data);
    }

    protected function fail(array $codeResponse = CodeResponse::FAIL, $info = '')
    {
        return $this->codeReturn(CodeResponse::PARAM_ILLEGAL);
    }

    protected function failOrSuccess(
        $isSuccess,
        array $codeResponse = CodeResponse::FAIL,
        $data = null,
        $info = ''
    ) {
      if ($isSuccess) {
          return $this->success($data);
      }
      return $this->fail($codeResponse, $info);
    }
    /**
     * @return User|null
     */
    public  function user()
    {
        return $user = Auth::guard('wx')->user();
    }

    public function isLogin()
    {
        return !is_null($this->user());
    }

    public function userId()
    {
        return $this->user()->getAuthIdentifier();
    }
}