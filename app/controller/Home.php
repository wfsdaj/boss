<?php

namespace app\controller;

use app\model\User;
use app\model\Post;

class Home
{
    /**
     * 网站首页
     */
    public function index()
    {
        // 获取当前登录用户的用户ID
        $user_id = (int)session('user_id');

        // 初始化数据数组
        $data = [];

        // 如果用户已登录，则查找用户信息
        if ($user_id) {
            $userModel = new User();
            $user = $userModel->find($user_id);
            $data['user'] = $user;
        }

        // 获取最新的帖子列表，最多10条
        $postModel = new Post();
        $posts = $postModel->list(10);

        // 确保帖子列表有效
        $data['posts'] = $posts;

        view('home/index', $data);
        return;
    }
}
