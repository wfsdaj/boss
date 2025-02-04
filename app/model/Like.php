<?php

namespace app\model;

use boss\Model;

class Like extends Model
{
    /**
     * 通过用户 ID 和文章 ID 来检查用户是否已经点赞过该帖子
     *
     * @param int $post_id 帖子 id
     */
    public function checkIfLiked(int $post_id)
    {
        $this->model = db('likes');

        $user_id = session('user_id');
        return $this->model->where('user_id = ? AND post_id = ?', [$user_id, $post_id])->first();
    }

    /**
     * 喜欢
     *
     * @param array $data
     * @return bool|string 成功返回true，失败返回错误信息
     */
    public function add(array $data)
    {
        $this->model = db('likes');

        // $data['user_id'] = session('user_id');
        // $data['post_id'] = segment(3, 'int');
        // $data['created_at'] = time();

        // 开启事务
        $this->model->beginTransaction();

        try {
            // likes 表写入点赞数据
            $this->model->insert($data);
            // post 表帖子点赞数 +1
            db('post')->where('id = ?', $data['post_id'])->increment('likes', 1);

            // 提交事务
            $this->model->commit();

            return true;
        } catch (\Throwable $th) {
            // 回滚
            $this->model->rollback();
        }

    }

    /**
     * 取消喜欢
     */
    public function del($data)
    {
        $this->model = db('likes');

        try {
            // 开启事务
            $this->model->beginTransaction();

            $liked = $this->checkIfLiked($data['post_id']);

            // likes 表删除点赞数据
            $this->model->where('id = ? AND user_id = ?', [$liked->id, $data['user_id']])->delete();
            // post 表帖子点赞数 -1
            db('post')->where('id = ?', $data['post_id'])->increment('likes', -1);

            // 提交事务
            $this->model->commit();

            return true;
        } catch (\Throwable $th) {
            // 回滚
            $this->model->rollback();

            return false;
        }
    }

    /**
     * 根据用户 id 获取喜欢列表
     */
    public function list(int $user_id, int $pages = 10)
    {
        $this->model = db('likes');

        $fields = 'k.user_id,
                   k.post_id,
                   k.created_at,
                   p.user_id as author_id,
                   p.created_at as p_created_at,
                   p.images,
                   p.files,
                   p.content,
                   p.comments,
                   p.likes,
                   p.favorites,
                   p.is_sticky,
                   u.username,
                   a.post_id as a_post_id,
                   a.filename,
                   a.type';
        return $this->model->join('AS k
                                INNER JOIN post AS p
                                        ON p.id = k.post_id
                                INNER JOIN user AS u
                                    ON u.id = p.user_id
                                LEFT JOIN attach AS a
                                    ON a.post_id = p.id')
                           ->where('k.user_id = ?', $user_id)
                           ->orderBy('k.id DESC')
                           ->paginate($pages)
                           ->get($fields);
        // $this->model->debugSql();
    }
}