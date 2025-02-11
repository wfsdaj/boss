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

            // 有图片则上传
            $this->uploadImage($post_id);

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
                    a.width,
                    a.filename,
                    a.type';

        $data = db('post')->join('AS p
                            LEFT JOIN user AS u
                                ON p.user_id = u.id
                            LEFT JOIN attach AS a
                                ON a.post_id = p.id
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
    public function findByUserId($user_id, int $pages = 10)
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
            ->where('u.id = ?', [$user_id])
            ->orderBy('p.id DESC')
            ->paginate($pages)
            ->get($fields);

        // $this->model->debugSql();
    }

    /**
     * 根据标签id查询内容列表
     */
    public static function findListByTagId(int $tag_id, int $pages = 10)
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
    private function uploadImage(int $post_id): bool
    {
        // 检查是否有文件上传
        if (!isset($_FILES['imageUpload']) || $_FILES['imageUpload']['error'] === UPLOAD_ERR_NO_FILE) {
            return false;
        }

        try {
            // 上传文件并获取上传后的文件数据
            $uploadedFileData = (new File())->upload()[0];

            // 构建附件数据
            $attachmentsData = [
                'width'      => $uploadedFileData['width'],
                'filename'   => ltrim($uploadedFileData['file_path'], "upload/"),
                'type'       => $uploadedFileData['file_ext'],
                'post_id'    => $post_id,
                'user_id'    => session('user_id'),
                'created_at' => time(),
            ];

            $img_path = ROOT_PATH . $uploadedFileData['file_path'];
            $this->resizeImages($img_path);

            // 写入附件表
            db('attach')->insert($attachmentsData);

            // 更新帖子字段文件数
            $this->model->where('id = ?', [$post_id])->increment('images', 1);

            return true;
        } catch (\Throwable $th) {
            throw new Exception($th);
        }
    }

    /**
     * 生成缩略图
     *
     * @param array|string $uploaded_file_paths 文件路径或包含文件路径的数组
     * @param int $width 缩略图的宽度
     */
    private function resizeImages(string $image_path, int $targetWidth = 300)
    {
        $image = new Image();
        $image->load($image_path);

        if ($image->getWidth() >= 500) {
            $image->resizeToWidth($targetWidth);
            $image->addPrefix('_thumb');
            $image->save($image->getFilePath(), $image->getImageFormat());
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
        } catch (\Throwable $th) {
            // 这里可以根据需要进一步处理或记录异常
            throw new Exception($th);
        }
    }

    private function updatePageViews()
    {
        $this->model = db('post');

        // $currentCount = $this->model->where('', [$]);
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
