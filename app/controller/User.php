<?php

namespace app\controller;

use app\model\User as UserModel;
use app\model\Post;

class User
{
    /**
     * 显示用户信息和用户帖子列表
     *
     * 获取指定用户的信息以及该用户的帖子列表，若用户不存在则返回 404 错误。
     * 数据通过视图渲染并展示给用户。
     */
    public function profile(int $user_id)
    {
        $userModel = new UserModel();

        dd($userModel);

        if (!$user) {
            return abort(404);
        }

        $postModel = new Post();

        // 查询用户帖子列表
        $posts = $postModel->getListByUserId($user_id);

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
        $url_id = (int)segment(2);
        $user   = model('user')->find($url_id);

        if (!$user) {
            return abort(404);
        }

        // 查询用户回复列表
        $replies = model('comment')->getListByUserId($url_id);

        // dd($replies[0][0]->post_id);

        $data = [
            'user'       => $user,
            'replies'    => $replies,
        ];

        return view('user/replies', $data);
    }

    /**
     * 我喜欢的
     */
    public function likes()
    {
        $user_id = (int)segment(2);
        $user    = model('user')->find($user_id);

        if (!$user) {
            return abort(404);
        }

        // 查询用户喜欢列表
        $likes = model('like')->getListByUserId($user_id);

        $data = [
            'user'       => $user,
            'likes'      => $likes,
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
}
