<?php

namespace app\controller;

use boss\Validator;
use app\model\Comment as CommendModel;

class Comment
{
    /**
     * 根据帖子id显示评论列表
     */
    public static function list(int $post_id)
    {
        $comments = (new CommendModel())->find($post_id);

        return $comments;
    }

    /**
     * 提交评论
     */
    public function submit()
    {
        // 检查请求方法
        if (!is_post()) {
            return json('请求方法不正确', 'error', 405);
        }

        // 获取并验证表单数据
        $data['content'] = post('commentTextarea');
        $rules = [
            'content' => ['string', '1,400', '内容为 1~200 个字符'],
        ];
        $validate = new Validator($data, $rules);
        $verify = $validate->check();

        // 验证不通过
        if (!$verify) {
            return json($validate->error);
        }

        // 保存评论数据
        // $result =

        try {
            (new CommendModel())->create($data);
            return json('回帖成功，扣除 1 金币。', 'success', 200);
        } catch (\Throwable $th) {
            return json('回帖失败');
        }
    }
}
