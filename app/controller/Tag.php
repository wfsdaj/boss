<?php

namespace app\controllers;

class Tag
{
    /**
     * 标签首页
     */
    public function index()
    {
        $tags = model('tag')::list();

        $data = [
            'tags' => $tags,
        ];

        return view('tag/index', $data);
    }

    /**
     * 标签内容列表页
     */
    public function list()
    {
        $tag_id = (int)segment(2);
        $posts  = model('tag')::getListByTagId($tag_id);
        $tag    = model('tag')::find($tag_id);

        $data = [
            'posts' => $posts,
            'tag'   => $tag,
        ];

        return view('tag/list', $data);
    }

    /**
     * 内容页
     */
    public function show()
    {
        $tag_id = segment(3, 'int');
        $tag = model('tag')::find($tag_id);

        $data = [
            'page_title' => '标签名',
            'tag'        => $tag,
        ];

        // 使用公共模板
        template('public', $data);
    }
}