<?php

namespace app\controller;

use app\model\{Post, User};

class Home
{
    /**
     * 网站首页
     */
    public function index()
    {
        // 获取最新的帖子列表，最多10条
        $posts = (new Post())->list(10);
        $data = ['posts' => $posts];

        if (session('user_id')) {
            $data['user'] = (new User())->find(session('user_id'), 'id, username, golds');
        }

        $log = new \boss\Logger();
        $log->error('asdf');

        return view('home/index', $data);
    }

    public function a()
    {
        view('home/a');
    }

    public function b()
    {
        $file = new \boss\File();
        $a = $file->upload();

        dd($a);
    }

    public function c()
    {
        $file = PUBLIC_PATH . 'img/logo.png';
        $img = new \boss\Image();
        $img->load($file);

        echo $file;

        dd($img->getWidth());
    }

    public function d()
    {
        $a = PUBLIC_PATH . 'upload/2025/01/26/472dee4b89bea9bdec1632dc5b21482b.jpg';

        $imgInfo = pathinfo($a);

        // 构造小图路径
        $thumbnailPath = "{$imgInfo['dirname']}/{$imgInfo['filename']}_thumb.{$imgInfo['extension']}";

        $result = is_file($thumbnailPath) ? $thumbnailPath : $a;

        echo $thumbnailPath;

        dd($imgInfo);
    }
}
