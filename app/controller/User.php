<?php

namespace app\controller;

use app\controller\Auth;
use app\model\User as UserModel;
use app\model\{Post, Comment, Like};

class User
{
    /**
     * 显示用户信息和用户帖子列表
     *
     * 获取指定用户的信息以及该用户的帖子列表，若用户不存在则返回 404 错误。
     * 数据通过视图渲染并展示给用户。
     */
    public function profile()
    {
        $user  = Auth::checkUser();

        // 查询用户帖子
        $posts = (new Post())->findByUserId((int)segment(3));

        $data = [
            'user'  => $user,
            'posts' => $posts,
        ];

        return view('user/profile', $data);
    }

    /**
     * 我的回帖
     */
    public function replies()
    {
        $user  = Auth::checkUser();

        // 查询用户回复
        $replies = (new comment())->fetchUserReplies((int)segment(3));

        $data = [
            'user'    => $user,
            'replies' => $replies,
        ];

        return view('user/replies', $data);
    }

    /**
     * 我喜欢的
     */
    public function likes()
    {
        $user  = Auth::checkUser();

        $likeModel = new Like();

        // 查询用户喜欢列表
        $likes = $likeModel->getListByUserId((int)session('user_id'));

        $data = [
            'user'  => $user,
            'likes' => $likes,
        ];

        return view('user/likes', $data);
    }

    /**
     * 收藏列表
     */
    public function fav()
    {
        $url_id = (int)segment(2);
        $user   = model('user')::find($url_id);

        if (!$user) {
            return abort(404);
        }

        $favorites = model('fav')->getListByUrlId($url_id);

        $data = [
            'user'       => $user,
            'favorites'  => $favorites,
        ];

        return view('user/fav', $data);
    }

    /**
     * 编辑个人资料
     */
    public function edit()
    {
        loginCheck();

        $user_id = (int)segment(2);
        $user    = model('user')->find($user_id);

        $data = [
            'page_title' => '用户资料',
            'user'       => $user,
        ];

        return view('user/edit', $data);
    }

    /**
     * 更改密码页面
     */
    public function password()
    {
        loginCheck();

        $user_id = session('user_id');
        $url_id  = (int)segment(2);

        if ($user_id !== $url_id) {
            return abort(403);
        }

        $user = model('user')->find($user_id);
        $data = [
            'user' => $user,
        ];

        return view('user/password', $data);
    }

    /**
     * 修改头像页面
     */
    public function avatar()
    {
        loginCheck();

        $user_id = session('user_id');
        $url_id  = (int)segment(2);

        if ($user_id !== $url_id) {
            return abort(403);
        }

        $user = model('user')->find($user_id);
        $data = [
            'user' => $user,
        ];

        return view('user/avatar', $data);
    }

    /**
     * 检查用户是否存在
     *
     * @return \app\model\User|void
     */
    private function checkUser()
    {
        $user_id = (int)segment(3);

        if (!$user_id) {
            return abort(404);  // 用户 ID 无效，终止程序
        }

        $userModel = new UserModel();
        $user = $userModel->find($user_id);

        if (!$user) {
            return abort(404); // 用户不存在，终止程序
        }

        return $user; // 返回用户对象
    }
}
