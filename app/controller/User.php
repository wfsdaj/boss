<?php

namespace app\controller;

use app\model\User as UserModel;
use app\model\{Auth, Post, Comment, Like, Fav};

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
        $user = $this->getUserFromUrl();

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
        $user = $this->getUserFromUrl();

        // 查询用户回复
        $replies = (new Comment())->findByUserId((int)segment(3));

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
        $user = $this->getUserFromUrl();

        // 查询用户喜欢列表
        $likes = (new Like())->list((int)segment(3));

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
        $user = $this->getUserFromUrl();

        $favorites =(new Fav())->list((int)segment(3));

        $data = [
            'user'      => $user,
            'favorites' => $favorites,
        ];

        return view('user/fav', $data);
    }

    /**
     * 编辑个人资料
     */
    public function edit()
    {
        $user = Auth::checkUser();

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
     * 从 URL 中获取用户 ID 并查询用户信息
     *
     * @return array|null 返回查询到的用户对象，如果用户 ID 无效或用户不存在则返回 null
     */
    private function getUserFromUrl(): ?array
    {
        $url_id = (int)segment(3);

        if ($url_id <= 0) {
            return null;
        }

        $user = (new UserModel())->find($url_id);

        if (!$user) {
            return abort(404);
        }

        return $user;
    }
}
