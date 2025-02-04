<?php

namespace app\model;

use boss\Model;

class Fav extends Model
{
    /**
     * 通过用户 ID 和文章 ID 来检查用户是否已经收藏过该帖子
     *
     * @param int $post_id 帖子 id
     *
     */
    public function checkIfFavorited(int $post_id)
    {
        $favorite = db('favorite');
        $user_id = session('user_id');
        return $favorite->where('user_id = ? AND post_id = ?', [$user_id, $post_id])->first();
    }

    /**
     * 存储收藏数据
     */
    public function store($data)
    {
        $favorite = db('favorite');

        // $data['user_id'] = session('user_id');
        // $data['post_id'] = (int)segment(3);
        // $data['created_at'] = time();

        // 开启事务
        $favorite->beginTransaction();

        try {
            // favorite 表写入收藏数据
            $favorite->insert($data);
            // post 表帖子收藏数 +1
            db('post')->where('id = ?', $data['post_id'])->increment('favorites', 1);

            // 提交事务
            $favorite->commit();

            return true;
        } catch (\Throwable $th) {
            // 回滚
            $favorite->rollback();
        }
    }

    /**
     * 取消收藏
     */
    public function del($data)
    {
        $favorite = db('favorite');

        try {
            // 开启事务
            $favorite->beginTransaction();

            $favorited = $this->checkIfFavorited($data['post_id']);

            // favorites 表删除收藏数据
            $favorite->where('id = ? AND user_id = ?', [$favorited->id, $data['user_id']])->delete();
            // post 表帖子收藏数 -1
            db('post')->where('id = ?', $data['post_id'])->increment('favorites', -1);

            // 提交事务
            $favorite->commit();

            return true;
        } catch (\Throwable $th) {
            // 回滚
            $favorite->rollback();

            return false;
        }
    }

    /**
     * 根据用户 id 获取收藏列表
     */
    public function list(int $url_id, int $pages = 10)
    {
        $this->model = db('favorite');

        $fields = 'f.id,
                   f.user_id,
                   f.post_id,
                   f.created_at,
                   p.user_id    AS author_id,
                   p.created_at AS p_created_at,
                   p.images,
                   p.files,
                   p.content,
                   p.comments,
                   p.likes,
                   p.favorites,
                   p.is_sticky,
                   u.username,
                   a.post_id    AS a_post_id,
                   a.filename,
                   a.type';
        return $this->model->join('AS f
                                INNER JOIN post AS p
                                    ON p.id = f.post_id
                                INNER JOIN user AS u
                                    ON u.id = p.user_id
                                LEFT JOIN attach AS a
                                    ON a.post_id = p.id')
                           ->where('f.user_id = ?', [$url_id])
                           ->orderBy('f.id DESC')
                           ->paginate($pages)
                           ->get($fields);
        // $this->model->debugSql();
    }
}