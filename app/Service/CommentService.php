<?php


namespace App\Service;


use App\Constant;
use App\Models\Comment;
use App\Service\User\UserService;
use Illuminate\Support\Arr;

class CommentService extends BaseService
{
    public function getCommentByGoodsId($goodsId, $page = 1, $limit = 2)
    {
        return Comment::query()->where('value_id', $goodsId)
            ->where('type', Constant::COLLETCT_TYPE_GOODS)->where('deleted', 0)
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
            return [
              'id' => $comment->id,
              'addTime' => $comment->add_time,
                'content' => $comment->admin_content,
                'picList' => $comment->pic_list,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar

            ];
        });
        return ['count'=>$comments->total(), 'data'=>$data];
    }
}