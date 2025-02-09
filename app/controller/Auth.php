<?php

namespace app\controller;

use boss\Captcha;

class Auth
{
    /**
     * 显示验证码图片.
     */
    public function captcha()
    {
        /**
         * Captcha constructor.
         * @param int $width         图片宽度
         * @param int $height        图片高度
         * @param int $totalChars    总字符数
         * @param int $numbers       数字字符数
         * @param string $fontFamily 字体文件路径
         */
        return (new Captcha())->make();
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        $user_id       = (int)session('user_id');
        $url_token     = segment(3);
        $session_token = session('csrf_token');

        if (!$user_id || empty($url_token) || !hash_equals($url_token, $session_token)) {
            abort('403');
        }

        // 清空 $_SESSION 数组
        $_SESSION = [];

        // 销毁 session 数据
        session_destroy();

        // 重定向到登录页面或其他安全页面
        return redirect(url('/'));
    }

    /**
     * 检查用户是否已登录，如果未登录则重定向到登录页面。
     */
    public static function check()
    {
        if (!session('user_id')) {
            return redirect(url('/login'));
        }
    }
}
