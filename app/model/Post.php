<?php

namespace app\model;

use boss\{Model, Image, File};
use Exception;

class Post extends Model
{
    /**
     * 模型缓存使用说明
     *
     * 使用模型缓存时，模型初始化不连接数据库节约开销
     * 不定义 public $table 属性就不会自动初始化数据表操作
     */

    /**
     * 根据 id 查询帖子内容
     *
     * p post 帖子表
     * u user 用户表
     * a attach 附件表
     */
    public function find(int $post_id)
    {
        $this->model = db('post');

        $fields =  'p.id,
                    p.user_id,
                    p.created_at,
                    p.images,
                    p.files,
                    p.content,
                    p.comments,
                    p.likes,
                    p.favorites,
                    p.is_sticky,
                    u.username,
                    a.post_id,
                    a.filename,
                    a.type';
        $data = $this->model->join('AS p
                                    LEFT JOIN
                                        user AS u
                                    ON
                                        p.user_id = u.id
                                    LEFT JOIN
                                        attach AS a
                                    ON
                                        a.post_id = p.id')
            ->where('p.id = ?', [$post_id])
            ->first($fields);
        return $data;
    }

    /**
     * 保存帖子内容
     */
    public function store($data)
    {
        $this->model = db('post');

        $data['user_id']    = session('user_id');
        $data['created_at'] = time();
        $data['content'] = replace_urls_with_links($data['content']);
        // 使用正则表达式匹配#标签#
        // preg_match_all('/#([^#]+)#/u', $data['content'], $matches);
        // $matches[1]现在包含了所有的标签
        // $tags = $matches[1];
        // 去除内容中的标签
        // $data['content'] = trim(preg_replace('/#([^#]+)#/u', '', $data['content']));
        // 转换内容中的链接
        // $data['content'] = url2Link($data['content']);

        try {
            // 开启事务
            $this->model->beginTransaction();

            // 插入帖子数据，返回帖子 id
            $post_id = $this->model->insert($data);

            // 插入标签
            // if ($tags) {
            //     Tag::store($tags, $post_id);
            // }

            // 处理用户积分，这里发一帖扣除用户一金币
            $this->handleUserPoints(-1);

            // 有附件则上传
            $this->uploadFile($post_id);

            // 更新帖子字段文件数
            // $this->model->where('id = ?', [$post_id])->increment('images', 1);

            // 提交事务
            $this->model->commit();

            return $post_id;
        } catch (\Throwable $th) {
            // 回滚事务
            $this->model->rollback();
            throw new Exception($th, 1);
        }
    }

    /**
     * [使用缓存] 获取全部帖子列表
     * 如果需要参数，请在函数上进行参数传递
     */
    public function list(int $pages = 10)
    {
        $this->parameter = $pages;

        return $this->cache('postList', 'getList', $pages, 0);
    }

    /**
     * [直接查询] 获取全部帖子列表
     */
    protected function getList()
    {
        $fields = 'p.*, u.username, u.avatar';

        $data = db('post')->join('AS p
                            LEFT JOIN user AS u
                                   ON p.user_id = u.id
                            LEFT JOIN favorite AS f
                                   ON f.post_id = p.id
                            LEFT JOIN likes AS l
                                   ON l.post_id = p.id')
            ->paginate($this->parameter)
            ->orderBy('p.is_sticky DESC, p.id DESC')
            ->get($fields);
        return $data;
    }

    /**
     * 根据帖子id获取附件列表
     *
     * @param int $post_id 帖子id
     */
    public function getImagesList(int $post_id)
    {
        $attach = db('attach');

        $fields = 'id, filename, type';
        $data = $attach->where('post_id = ?', $post_id)
            ->orderBy('id DESC')
            ->get($fields);

        return $data;
    }

    /**
     * 根据用户 id 获取用户所有帖子列表
     */
    public function getListByUserId($user_id, int $pages = 10)
    {
        $this->model = db('post');

        $fields = 'p.*, u.username, a.post_id, a.filename, a.type';
        return $this->model->join('AS p
                                    LEFT JOIN
                                        user AS u
                                    ON
                                        p.user_id = u.id
                                    LEFT JOIN
                                        attach AS a
                                    ON
                                        a.post_id = p.id')
            ->where('u.id = ?', $user_id)
            ->orderBy('p.id DESC')
            ->paginate($pages)
            ->get($fields);

        // $this->model->debugSql();
    }

    /**
     * 根据标签id查询内容列表
     */
    public static function getListByTagId(int $tag_id, int $pages = 10)
    {
        $post = db('post');

        $fields = 'p.id AS p_id,
                    p.tag_id AS p_tag_id,
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
        return $post->join('AS p
                            LEFT JOIN
                                user AS u
                            ON
                                p.user_id = u.id
                            LEFT JOIN
                                attach AS a
                            ON
                                a.post_id = p.id
                            LEFT JOIN
                                tag AS t
                            ON
                                p.tag_id = t.id')
            ->where('t.id = ?', $tag_id)
            ->orderBy('p.id DESC')
            ->paginate($pages)
            ->get($fields);
    }

    /**
     * 将上传的文件信息存储到附件表
     *
     * @param int $postId 与文件相关联的帖子 ID
     * @return bool
     * @throws Exception 保存附件失败时抛出异常
     */
    private function uploadFile(int $post_id): bool
    {
        // 检查是否有文件上传
        if (!isset($_FILES['files']) || $_FILES['files']['error'] === UPLOAD_ERR_NO_FILE) {
            return false;
        }

        try {
            // 上传文件并获取上传后的文件数据
            $uploadedFileData = (new File())->upload()[0];

            // 构建附件数据
            $attachmentsData = [
                'filename'   => $uploadedFileData['file_name'],
                'type'       => $uploadedFileData['file_ext'],
                'post_id'    => $post_id,
                'user_id'    => session('user_id'),
                'created_at' => time(),
            ];

            // 写入附件表
            db('attach')->insert($attachmentsData);

            return true;
        } catch (\Throwable $th) {
            throw new Exception('文件存储过程中发生错误: ' . $th->getMessage(), $th->getCode(), $th);
        }
    }

    /**
     * 处理图像
     * 宽度超过指定值则生成缩略图
     *
     * @param array $data  图片数组
     * @param int   $width 目标宽度
     *
     * @return array
     */
    public function handleImagesWidth(array $imagesData, int $targetWidth = 500)
    {
        if (!is_array($imagesData)) {
            $imagesData = [$imagesData];
        }

        foreach ($imagesData as $image) {
            list($width, $height, $type, $attr) = getimagesize($image['filename']);

            if ($width > $targetWidth) {
                $this->resizeImages($imagesData);
            }
        }
    }

    /**
     * 生成缩略图
     *
     * @param array|string $uploaded_file_paths 文件路径或包含文件路径的数组
     * @param int $width 缩略图的宽度
     */
    private static function resizeImages(array $imagesData, int $targetWidth = 300)
    {
        foreach ($imagesData as $img) {
            $image = new Image();
            if ($image->load($img['filename'])) {
                $path_parts = pathinfo($img['filename']);
                $thumbnailPath = $path_parts['dirname'] . '/' . $path_parts['filename'] . '_thumb.' . $path_parts['extension'];
                // 调整图像大小
                $image->resizeToWidth($targetWidth);
                $image->save($thumbnailPath, $path_parts['extension']);
            }
        }
    }

    /**
     * 处理用户积分
     *
     * @param int $increment 积分增量，正数表示增加，负数表示减少
     * @return bool 操作是否成功
     * @throws Exception 如果数据库操作失败，则抛出异常
     */
    private function handleUserPoints(int $increment): bool
    {
        // 执行数据库操作
        try {
            $user_id = (int)session('user_id');
            db('user')->where('id = ?', [$user_id])->increment('golds', $increment);
            return true;
        } catch (\Throwable $e) {
            // 这里可以根据需要进一步处理或记录异常
            throw new Exception('更新用户积分失败。');
        }
    }

    /**
     * 插入标签和关联关系
     */
    private static function insertTags($tags)
    {
        $tagModel = db('tag');

        // 预处理所有标签
        $cleanedTags = array_map(function ($tag) {
            return strip_tags(html_entity_decode($tag));
        }, $tags);

        foreach ($cleanedTags as $tag) {
            $result = $tagModel->where('name = ?', $tag)->first('id, name');

            // 检查标签是否存在
            if ($result) {
                return $result->id;
            } else {
                // 插入新标签
                $tagData = ['name' => $tag];
                $tagsId = $tagModel->insert($tagData);
                return $tagsId;
                // return 0;
            }
        }
    }
}
