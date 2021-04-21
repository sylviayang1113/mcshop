<?php


namespace App\Service;


use App\Models\SearchHistory;

class SearchHistoryService extends BaseService
{
    public function save($userId, $keyword, $from)
    {
        $history = new SearchHistory();
        $history->fill(
            [
                'user_id ' => $userId,
                'keyword' => $keyword,
                'from' => $from
            ]
        );
        $history->save();
        return $history;
    }
}