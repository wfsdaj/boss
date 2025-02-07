<?php

namespace app\controller;

use app\model\User;

class Message
{
    // public function __construct()
    // {
    //     if (!is_logined()) {
    //         return redirect(url('/login'));
    //     }
    // }
    /**
     * 私信首页
     */
    public function index()
    {
        $data = [];

        if (session('user_id')) {
            $user = new User();
            $data['user'] = $user->find(session('user_id'), 'id, username, golds');
        }

        return view('message/index', $data);
    }

    public function write()
    {
        return view('message/send');
    }
}
