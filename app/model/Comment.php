<?php

namespace app\model;

use boss\Model;

class Comment extends Model
{
    /**
     * 保存评论内容
     *
     * @param array $data 表单获取的数据
     */
    public function create($data)
    {
        $this->model = db('comment');

        try {
            // 开启事务
            $this->model->beginTransaction();

            // 需要保存的其他数据
            $data['post_id'] = post('post_id');
            $data['user_id'] = session('user_id');
            $data['created_at'] = time();

            // 插入数据，返回评论 ID
            $comment_id = $this->model->insert($data);
            // 评论一次扣除用户一枚金币
            db('user')->where('id = ?', $data['user_id'])->increment('golds', -1);
            // 更新评论数
            db('post')->where('id = ?', $data['post_id'])->increment('comments', 1);
            // 有附件则上传
            // if($_FILES['userfile']['name']) {
            //     $file = new File('userfile');
            //     $uploaded_file = $file->upload();
            //     if($uploaded_file) {
            //         $attach['post_id'] = $post_id;
            //         $attach['name']    = $uploaded_file;
            //         $this->uploadAttach($attach);
            //         // 帖子图片字段值+1
            //         db('post')->where('id  = ?', $attach['post_id'])->increment('images', 1);
            //     } else {
            //         return json($file->error);
            //     }
            // }

            $this->model->commit();
            return $comment_id;
        } catch (\Throwable $th) {
            $this->model->rollback();
        }
    }

    /**
     * 根据帖子 id 获取评论列表
     *
     * @param int $user_id 用户id
     * @param int $pages   分页数
     */
    public function find(int $post_id, int $pages = 20)
    {
        $this->model = db('comment');

        $fields = 'c.*, u.username';
        return $this->model->join('AS c LEFT JOIN user AS u ON c.user_id = u.id')
                            ->where('c.post_id = ?', $post_id)
                            ->orderBy('c.id DESC')
                            ->paginate($pages)
                            ->get($fields);
    }

    /**
     * 根据用户 id 获取评论列表
     *
     * @param int $user_id 用户id
     * @param int $pages   分页数
     */
    public function getListByUserId(int $user_id, int $pages = 20)
    {
        $user_id ?? 1;
        $this->model = db('comment');

        $fields = 'c.*,
                    u.username,
                    p.content AS title,
                    p.user_id AS post_user_id,
                    p.created_at AS post_created_at';
        return $this->model->join('AS c LEFT JOIN user AS u ON c.user_id = u.id
                                    LEFT JOIN post AS p ON c.post_id = p.id')
                        ->where('c.user_id = ?', $user_id)
                        ->orderBy('c.id DESC')
                        ->paginate($pages)
                        ->get($fields);
    }
}
