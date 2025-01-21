<?php

namespace app\controller;

use boss\Validator;
use app\model\User;

class Register
{
    /**
     * 显示注册页面
     */
    public function index()
    {
        // 如果用户已登录，则重定向到首页。
        if (is_logined()) {
            return redirect(url('/'));
        }

        return view('user/register');
    }

    /**
     * 提交注册
     */
    public function submit()
    {
        // 检查请求方法
        if (!is_post()) {
            return json('请求方法不正确');
        }

        // 比对验证码
        $captcha = post('captcha');
        if (!$captcha || $captcha !== session('captcha')) {
            return json('验证码错误');
        }

        // 验证表单数据
        $data = [
            'username' => remove_all_whitespace(post('username')),
            'password' => post('password'),
        ];
        $errors = $this->validateFormData($data);
        if ($errors) {
            return json($errors);
        }

        $userModel = new user();

        // 检测重名
        if ($userModel->findByName($data['username'])) {
            return json('用户名已被占用');
        }

        $user_id = $userModel->create($data);

        if ($user_id) {
            session_set([
                'user_id' => $user_id,
                'username' => $data['username'],
            ]);
            return json('注册成功', 'success');
        }

        return json('注册失败');
    }

    /**
     * 表单数据验证
     *
     * @param array $data
     * @return string|null 错误消息或 null
     */
    private function validateFormData(array $data): ?string
    {
        $rules  = [
            'username' => ['string', '3,32', '姓名应为 3-32 个字符'],
            'password' => ['string', '6,30', '密码应为 6 个字符及以上'],
        ];

        $validate = new Validator($data, $rules, true);

        if (!$validate->check()) {
            return $validate->error;
        }

        return null;  // 无错误
    }
}
