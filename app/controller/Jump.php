<?php

namespace app\controller;

class Jump
{
    /**
     * 网站首页
     */
    public function index()
    {
        $url = url(null);

        // 查找 /jump?target= 的位置
        $startPos = strpos($url, '/jump?target=');

        $data['target'] = '';

        if ($startPos !== false) {
            // 提取 target 之后的部分
            $data['target'] = substr($url, $startPos + strlen('/jump?target='));
            $data['target'] = urldecode($data['target']);
        } else {
            echo "Target parameter not found.";
        }

        return view('layout/_jump', $data);
    }
}
