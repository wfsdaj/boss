<?php

namespace app\controller;

class Fav
{
    /**
     * 添加收藏
     */
    public function add()
    {
        check_login();

        $data['user_id']    = session('user_id');
        $data['post_id']    = (int)segment(2);
        $data['created_at'] = time();

        $favorite  = new \app\model\Fav();
        $favorited = $favorite->checkIfFavorited($data['post_id']);

        // 未收藏
        if (!$favorited) {
            try {
                if ($favorite->store($data)) {
                    return json('收藏成功', 'success');
                }
            } catch (\Throwable $th) {
                return json('收藏失败');
            }
        }

        // 已收藏
        if ($favorited) {
            try {
                if ($favorite->del($data)) {
                    return json('取消收藏成功', 'success');
                }
            } catch (\Throwable $th) {
                return json('取消收藏失败');
            }
        }
    }
}