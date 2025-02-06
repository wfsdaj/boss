<?php

namespace app\controller;

use app\model\User;

class Medal
{
    /**
     * 勋章首页
     */
    public function index()
    {
        // 从会话中获取用户ID，并尝试查找用户
        // $userId = (int) session('user_id');
        // $user = (new User)->find($userId);
        // $data['user'] = $user;

        return view('medal/index');
    }
}
