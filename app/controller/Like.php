<?php

namespace app\controller;

use app\controller\Auth;
use app\model\Like as LikeModel;

class Like
{
    public function __construct()
    {
        // 如果用户未登录，则重定向到登录页。
        if (!is_logined()) {
            return redirect(url('/login'));
        }
    }

    /**
     * 点赞
     */
    public function love()
    {
        $data['user_id']    = session('user_id');
        $data['post_id']    = (int)segment(3);
        $data['created_at'] = time();

        $LikeModel = new LikeModel();
        $liked     = $LikeModel->checkIfLiked($data['post_id']);

        // 定义点赞或取消点赞的操作
        $action = $liked ? 'del' : 'add';  // 如果已点赞则执行删除，未点赞则执行添加
        $message = $liked ? '取消赞成功' : '点赞成功';

        try {
            if ($LikeModel->$action($data)) {
                return json($message, 'success', 200);
            }
        } catch (\Throwable $th) {
            $errorMessage = $liked ? '取消赞失败' : '点赞失败';
            return json($errorMessage, 'error');
        }
    }
}
