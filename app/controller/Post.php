<?php

namespace app\controller;

use boss\Validator;
use app\model\Post as PostModel;
use app\model\User;

class Post
{
    /**
     * 显示帖子内容
     */
    public function show()
    {
        $data['pageTitle'] = '帖子';

        // 获取帖子id，并验证是否大于1
        $post_id = (int)segment(3);

        // 获取帖子数据
        $post = (new PostModel())->find($post_id);

        // 如果未找到帖子，显示 404 错误页面
        if (!$post) {
            abort(404);
        }

        // 设置视图数据
        $data['post'] = $post;

        // 获取当前登录用户的ID
        $user_id  = session('user_id');
        if ($user_id) {
            // 获取用户数据
            $user = (new User())->find($user_id);
            $data['user'] = $user;
        }

        // 保存当前页面地址，用于登录或注册后返回
        $current_url = url('/post/show/') . $post_id;
        session('back_to_link', $current_url);

        return view('post/show', $data);
    }

    /**
     * 提交表单数据
     */
    public function submit()
    {
        if (!is_logined()) {
            return json('用户未登录');
        }

        if (!is_post()) {
            return json('请求方法不正确', 'error', 405);
        }

        // 获取表单数据
        $formData['content'] = post(filter_all_html('content'));

        // 验证规则
        $validationRules  = [
            'content' => ['string', '1,400', '内容应为 1-400 个字符'],
        ];
        // 验证
        $validator = new Validator($formData, $validationRules, true);
        $isValid = $validator->check();

        if (!$isValid) {
            return json($validator->error);
        }

        try {
            (new PostModel())->store($formData);
            return json('发帖成功', 'success');
        } catch (\Throwable $th) {
            throw new \Exception($th, 1);

            // return json($th->getMessage());
        }

    }

    /**
     * 删除帖子
     */
    public function del()
    {
        // 检查是否登录用户
        if (!session('user_id')) {
            return json('请先登录再进行操作。');
        }

        // 检查请求是否为 DELETE 方法
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            return json('提交方式不正确。');
        }

        // 通过 URL 获取帖子 ID
        $post_id = (int)segment(2);
        if (!$post_id) {
            return json('帖子不存在。');
        }

        // 加载帖子模型，查找帖子
        $postModel = new PostModel();
        $post = $postModel->find($post_id);
        if (!$post) {
            return json('帖子不存在。');
        }

        // 检查当前用户是否有权限删除帖子
        if (!is_author(session('user_id'), $post_id)) {
            return json('无权限删除此帖子');
        }

        // 尝试删除帖子
        try {
            $post->where('id = ?', $post_id)->delete();

            return json('删除成功', 'success');
        } catch (\Throwable $th) {
            return json('删除失败', 'error');
        }
    }
}
