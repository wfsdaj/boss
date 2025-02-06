<?php

namespace app\controller;

class Tool
{
    /**
     * 工具箱首页
     */
    public function index()
    {
        // $user_id = (int)session('user_id');

        return view('tool/index');
    }

    public function symbols()
    {
        return view('tool/symbols');
    }
}