<?php

namespace app\model;

class User extends \boss\Model
{
    private static $upload_file_failed = false;
    private static $store_file_failed  = false;
    private static $uploaded_file_path = null;
    private static $error_message      = null;

    /**
     * 创建用户
     */
    public function create($data)
    {
        $this->model = db('user');

        $data['password']   = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['created_at'] = time();
        $data['group_id']   = 101;  // 默认用户组
        $data['golds']      = 6;  // 注册时送6金币
        // $data['created_ip'] = ip2long(get_ip());

        try {
            $this->model->beginTransaction();

            // 插入用户数据
            $inserted_id = $this->model->insert($data);

            if ($inserted_id) {
                $this->model->commit();
                return $inserted_id;
            }

            throw new \Exception('用户创建失败');
        } catch (\Throwable $th) {
            return $this->model->rollback();
        }
    }

    /**
     * 登录用户
     */
    public function login(string $username, string $password)
    {
        $this->model = db('user');

        $user = $this->findByName($username);

        $user_id = $user['id'];
        $currentTimestamp = ['updated_at' => time()];

        if ($user && password_verify($password, $user['password'])) {
            // 更新登录时间
            $this->model->where('id = ?', [$user_id])->update($currentTimestamp);
            return $user;
        }
    }

    /**
     * 根据用户 id 查询用户数据
     */
    public function find(int $user_id)
    {
        $this->model = db('user');

        $field = 'id, group_id, email, username, password, created_at, updated_at, golds, avatar';

        return $this->model->where('id = ?', [$user_id])->first($field);
    }

    /**
     * 根据用户名查询用户数据
     */
    public function findByName(string $username)
    {
        $this->model = db('user');
        return $this->model->where('username = ?', [$username])
                            ->first('id, username, password, created_at, golds');
        // $this->model->debugSql();
    }

    /**
     * 重置密码
     */
    public function resetPassword($user_id, $newPassword)
    {
        $userModel = db('user');
        $newPassword = [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ];
        return $userModel->where('id = ?', [$user_id])->update($newPassword);
    }

    /**
     * [使用缓存]用户收藏列表
     */
    public function favorite($user_id)
    {
        $this->parameter = $user_id;

        return $this->cache('favoriteList', $user_id, 'getFavList', 10);
    }

    /**
     * [原始查询]用户收藏列表
     */
    public function getFavList($pages = 10)
    {
        $favorite = db('favorite');

        $fields = 'f.id,
                   f.post_id,
                   f.user_id,
                   f.created_at,
                   p.user_id as p_user_id,
                   p.created_at as p_created_at,
                   p.images,
                   p.files,
                   p.content,
                   p.comments,
                   p.likes,
                   p.favorites,
                   u.username,
                   a.post_id as a_post_id,
                   a.filename,
                   a.type';

        return $favorite->join('AS f LEFT JOIN post   AS p ON p.id = f.post_id
                                     LEFT JOIN user   AS u ON u.id = f.user_id
                                     LEFT JOIN attach AS a ON a.post_id = p.id')
                        ->where('f.user_id = ?', $this->parameter)
                        ->orderBy('f.id DESC')
                        ->paginate($pages)
                        ->get($fields);

    }

    /**
     * 提交头像
     */
    public function uploadPhoto()
    {
        if($_FILES['userAvatar']['name']) {
            self::uploadAvatar();
            if (self::$upload_file_failed) {
                json(self::$error_message);
                return false;
            }

            self::storeFile();
            if (self::$store_file_failed) {
                json('保存附件失败');
                return false;
            }
        }
    }

    /**
     * 检查表单内容并上传头像
     */
    private static function uploadAvatar()
    {
        $file = new File('userAvatar');
        $uploaded_file_path = $file->upload();

        if ($uploaded_file_path) {
            self::$uploaded_file_path = $uploaded_file_path;
            return true;
        }

        self::$upload_file_failed = true;
        self::$error_message = $file->error;
    }

    /**
     * 存储头像文件
     */
    private static function storeFile()
    {
        $user_id = session('user_id');
        $data = [
            'created_at' => time()
        ];

        $img = new Image();
        $uploaded_file_path = config('app.upload_path').self::$uploaded_file_path;

        if ($img->load($uploaded_file_path)) {
            $storage_path = 'upload/avatar/000/' . $img->getImageFormat();
            $img->resizeToWidth(140);
            $img->save($storage_path, $img->getImageFormat());
            // 更新用户表头像修改时间
            db('user')->where('id = ?', $user_id)->update($data);
            return true;
        }

        self::$store_file_failed = true;
    }

    /**
     * 列出所有用户
     */
    public static function getAll(int $pageSize = 10)
    {
        $userModel = db('user');

        $field = 'id, group_id, username, email, password, created_at, updated_at, golds, avatar';

        return $userModel->paginate($pageSize)->orderBy('id DESC')->get($field);
    }
}