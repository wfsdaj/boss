<?php

namespace app\model;

use boss\{Model, Logger};

class Reward extends Model
{
    /**
     * 检查用户是否已签到
     *
     * @param int $user_id 用户ID
     * @return array|null 返回包含签到信息的数组，如果未签到则返回 null
     */
    public function checkSignedIn(int $user_id): ?array
    {
        $this->model = db('reward');

        $fields = 'r.id, r.user_id, r.updated_at, u.golds';

        return $this->model->join('AS r LEFT JOIN user AS u ON r.user_id = u.id')
                            ->where('r.user_id = ?', [$user_id])
                            ->first($fields);
    }

    /**
     * 存储用户签到数据并增加金币
     *
     * @param array $data 包含用户ID和签到时间的数据
     * @return bool 返回操作是否成功
     * @throws \Exception 如果操作失败，抛出异常
     */
    public function store(array $data): bool
    {
        $this->model = db('reward');

        try {
            // 开启事务
            $this->model->beginTransaction();
            // 插入签到数据
            $this->model->where('user_id = ?', [$data['user_id']])->insert($data);
            // 金币增加 3 个
            db('user')->where('id = ?', [$data['user_id']])->increment('golds', 3);
            // 提交事务
            $this->model->commit();

            return true;
        } catch (\Throwable $th) {
            // 回滚
            $this->model->rollback();
            Logger::error($th);
            throw new \Exception('签到数据存储失败，请稍后重试。', 500);
        }
    }

    /**
     * 已签到过，只更新签到时间
     */
    public function update(array $data)
    {
        $this->model = db('reward');

        try {
            $this->model->beginTransaction();
            $this->model->where('user_id = ?', [$data['user_id']])->update($data);
            db('user')->where('id = ?', [$data['user_id']])->increment('golds', 3);
            $this->model->commit();

            return true;
        } catch (\Throwable $th) {
            return $this->model->rollback();
        }
    }

    /**
     * 查询今日签到人员列表
     */
    public function list()
    {
        $this->model = db('reward');

        $current_time = time() - 86400;
        return $this->model->where('updated_at >= ?', [$current_time])->get();
    }
}
