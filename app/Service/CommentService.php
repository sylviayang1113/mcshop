<?php


namespace App\Service;


use App\Constant;
use App\Models\Comment;
use App\Service\User\UserService;
use Illuminate\Support\Arr;

class CommentService extends BaseService
{
    public function getCommentByGoodsId($goodsId, $page = 1, $limit = 2, $sort='add_time', $order='desc')
    {
        return Comment::query()->where('value_id', $goodsId)
            ->where('type', Constant::COLLETCT_TYPE_GOODS)
            ->where('deleted', 0)
            ->orderBy($sort, $order)
            ->paginate($limit, ['*'], 'page', page);
    }

    public function getCommentWithUserInfo($goodsId, $page = 1, $limit = 2)
    {
        $comments = $this->getCommentBygoodsId($goodsId, $page, $limit);
        $userIds = Arr::pluck($comments->items(), 'user_id');
        $userIds = array_unique($userIds);
        $users = UserService::getInstance()->getUsers($userIds)->keyBy('id');
        $data = collect($comments->items())->map(function(Comment $comment) use ($users){
           $user = $users->get($comment->user_id);
           $comment = $comment->toArray();
           $comment['picList'] = $comment['picUrls'];
            $comment = Arr::only($comment, ['id', 'addTime', 'content', 'adminContent', 'picList']);
           $comment['nickname'] = $user->nickname ?? '';
           $comment['avatar'] = $user->avatar ?? '';
           return $comment;
        });
        return ['count'=>$comments->total(), 'data'=>$data];
    }
}