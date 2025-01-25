<?php

namespace app\controller;

use app\model\Post;

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
}
