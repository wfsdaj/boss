<?php

namespace app\models;

use core\Model;

class Tag extends Model
{
    /**
     * 存储帖子中标签数据
     */
    public static function store($tags, int $postId)
    {
        $tagModel = db('tag');

        // 预处理所有标签
        $cleanedTags = array_map(function ($tag) {
            return trim(strip_tags(html_entity_decode($tag)));
        }, $tags);

        // 过滤掉重复和空的标签
        $cleanedTags = array_unique(array_filter($cleanedTags));

        foreach ($cleanedTags as $tag) {
            $result = $tagModel->where('name = ?', $tag)->first('id, name');

            // 检查标签是否存在
            if ($result) {
                db('post_tags')->insert(['post_id'=>$postId, 'tag_id'=>$result->id]);
                return $result->id;
            } else {
                // 向 tag 表插入新标签
                $tagsId = $tagModel->insert(['name' => $tag]);
                // 向 post_tags 表插入数据
                db('post_tags')->insert(['post_id'=>$postId, 'tag_id'=>$tagsId]);
                return $tagsId;
                // return 0;
            }
        }
    }

    /**
     * 根据 id 查询标签内容
     */
    public static function find(int $tag_id)
    {
        $tag = db('tag');

        $fields = 'id, name, intro';
        $data = $tag->where('id = ?', $tag_id)
                    ->first($fields);
        return $data;
    }

    /**
     * 查询全部标签列表
     */
    public static function list(int $page = 10)
    {
        $tag = db('tag');
        $fields = 'id, name, intro';
        $data = $tag->paginate($page)->get($fields);

        return $data;
    }

    /**
     * 根据标签id查询内容列表
     */
    public static function getListByTagId(int $tag_id, int $pages = 10)
    {
        $fields =  'p.id AS p_id,
                    p.user_id AS p_user_id,
                    p.created_at AS p_created_at,
                    p.content,
                    p.comments,
                    p.images,
                    p.files,
                    p.likes,
                    p.favorites,
                    u.username,
                    a.post_id,
                    a.filename,
                    a.type,
                    t.id AS t_id,
                    t.name';
        return db('post')->join('AS p
                            LEFT JOIN post_tags AS pg
                                   ON p.id = pg.post_id
                            LEFT JOIN user AS u
                                   ON p.user_id = u.id
                            LEFT JOIN attach AS a
                                   ON a.post_id = p.id
                            LEFT JOIN tag AS t
                                   ON pg.post_id = t.id')
                        ->where('t.id = ?', $tag_id)
                        ->orderBy('p.id DESC')
                        ->paginate($pages)
                        ->get($fields);
    }
}