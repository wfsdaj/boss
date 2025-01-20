<?php

namespace app\controller;

use boss\Captcha;

class Auth
{
    public $table = 'user';

    /**
     * 显示验证码图片.
     *
     * 该方法负责生成并返回一个验证码图片给客户端。
     * 它会创建一个新的验证码实例，并调用其 make 方法来生成图像。
     *
     * @return void
     */
    public function captcha(): void
    {
        /**
         * Captcha constructor.
         * @param int $width         图片宽度
         * @param int $height        图片高度
         * @param int $totalChars    总字符数
         * @param int $numbers       数字字符数
         * @param string $fontFamily 字体文件路径
         */
        $captcha = new Captcha();
        $captcha->make();
        return;
    }

    public function logout()
    {
        // 清空当前会话数据
        $_SESSION = [];

        // 重新生成会话 ID 并销毁旧会话
        session_regenerate_id(true);
        session_destroy();

        // 重定向到登录页面或其他安全页面
        return redirect(url('/'));
    }
}
