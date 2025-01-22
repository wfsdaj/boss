<?php

namespace boss;

use PDO;
use Exception;
use PDOStatement;
use InvalidArgumentException;

class Database
{
    public static array $operator = [];         // 静态属性保存单例实例
    public string $table = '';                  // 表名
    public PDO $pdo;                            // PDO对象
    public string $sql = '';                    // SQL语句
    public ?PDOStatement $pretreatment = null;  // 预处理语句
    public ?array $where = null;                // where条件
    public ?string $groupBy = null;             // group by条件
    public ?string $join = null;                // 联合查询条件
    public ?string $orderBy = null;             // 排序条件
    public ?array $limit = null;                // limit条件
    public ?int $eachPage = null;               // 每页条数
    public int $totalRows = 0;                  // 总条数
    public array $conf;                         // 数据库配置

    /**
     * 构造函数，初始化数据库连接
     *
     * @param array $conf 数据库配置
     * @throws Exception 数据库连接失败
     */
    public function __construct(array $conf)
    {
        $this->conf = $conf;

        try {
            switch ($this->conf['driver']) {
                case "sqlsrv":
                    $this->pdo = new PDO(
                        "{$conf['driver']}:Server={$conf['host']},{$conf['port']};Database={$conf['database']}",
                        $conf['username'],
                        $conf['password']
                    );
                    break;
                case "mysql":
                    $this->pdo = new PDO(
                        "mysql:host={$conf['host']};port={$conf['port']};dbname={$conf['database']};charset={$conf['charset']}",
                        $conf['username'],
                        $conf['password']
                    );
                    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    break;
                default:
                    throw new Exception('不支持的数据库驱动');
            }
        } catch (Exception $e) {
            throw new Exception('数据库连接失败，请检查数据库相关配置: ' . $e->getMessage());
        }
    }

    /**
     * 克隆方法，重置实例状态
     */
    public function __clone()
    {
        $this->sql          = '';
        $this->pretreatment = null;
        $this->where        = null;
        $this->groupBy      = null;
        $this->join         = null;
        $this->orderBy      = null;
        $this->limit        = null;
        $this->eachPage     = null;
        $this->totalRows    = 0;
    }

    /**
     * 获取单例实例
     *
     * @param array $conf 配置参数
     * @param string $table 表名
     * @param string $configName 配置名
     * @return Database 返回数据库实例
     * @throws InvalidArgumentException
     */
    /**
     * 获取单例实例
     *
     * @param array $conf 配置参数
     * @param string $table 表名
     * @param string $configName 配置名
     * @return Database 返回数据库实例
     * @throws InvalidArgumentException
     */
    public static function getInstance(array $conf, string $table, string $configName): Database
    {
        // 输入验证
        if (empty($table) || empty($configName)) {
            throw new InvalidArgumentException('表名和配置名不能为空');
        }

        // 校验表名是否合法
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new InvalidArgumentException('表名包含非法字符');
        }

        // 构建带前缀的表名
        $prefixedTable = $conf['prefix'] . $table;

        // 检查是否已经存在对应配置的实例
        if (!isset(self::$operator[$configName])) {
            // 如果不存在，创建新实例
            self::$operator[$configName] = new self($conf);
        }

        // 设置表名
        self::$operator[$configName]->table = $prefixedTable;

        return self::$operator[$configName];
    }

    /**
     * 记录 SQL 运行过程
     *
     * @param mixed $res SQL 执行结果
     * @param float $startTime SQL 执行的开始时间（微秒）
     * @param string|null $sql 自定义 SQL 语句，默认为当前对象的 SQL 语句
     */
    protected function trace($res, float $startTime, ?string $sql = null): void
    {
        // 如果未传入自定义 SQL，则使用当前对象的 SQL
        $sql = $sql ?? $this->sql;

        // 初始化 SQL 记录数组
        $sqlRec = [
            'status' => $res !== false ? '成功' : '失败',              // 执行状态
            'sql'    => $sql,                                              // SQL 语句
            'time'   => round((microtime(true) - $startTime) * 1000, 2),   // 执行时间（毫秒）
            'error'  => $res ? '' : $this->error(),                        // 错误信息（如果有）
        ];

        // 将 SQL 记录添加到全局变量中
        // if (!isset($GLOBALS['traceSql'])) {
        //     $GLOBALS['traceSql'] = [];
        // }
        $GLOBALS['traceSql'][] = $sqlRec;
    }

    /**
     * 执行 SQL 查询
     *
     * @param string $sql SQL语句
     * @param array|null $execute 参数数组
     * @return bool 返回执行结果
     */
    public function query(string $sql, ?array $execute = null): bool
    {
        try {
            $this->sql = $sql;
            $this->pretreatment = $this->pdo->prepare($sql);
            return $execute ? $this->pretreatment->execute($execute) : $this->pretreatment->execute();
        } catch (\PDOException $e) {
            throw new Exception('SQL 执行失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取来自 query 查询的单条数据
     *
     * @return array|null 返回查询到的数据对象，如果查询失败或没有数据则返回 null。
     */
    public function queryFetch(): ?array
    {
        return $this->pretreatment ? $this->pretreatment->fetch(PDO::FETCH_ASSOC) : null;
    }

    /**
     * 获取来自 query 查询的全部数据
     *
     * @return array 返回查询到的数据对象数组
     */
    public function queryFetchAll(): array
    {
        return $this->pretreatment ? $this->pretreatment->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * 插入数据
     *
     * 返回写入数据对应的主键数据值
     *
     * @param array $data 插入的数据
     * @return int 返回插入的主键ID
     * @throws Exception 插入数据错误
     */
    public function insert(array $data): int
    {
        $startTime = microtime(true);
        if (empty($data)) {
            throw new Exception('插入数据错误，插入数据应为非空的一维数组');
        }

        $fields = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $this->sql = "INSERT INTO `{$this->table}` ({$fields}) VALUES ({$placeholders})";

        try {
            $this->pretreatment = $this->pdo->prepare($this->sql);
            $this->pretreatment->execute(array_values($data));

            $res = (int) $this->pdo->lastInsertId();
            $this->trace($res, $startTime);
            return $res;
        } catch (Exception $e) {
            throw new Exception('插入数据失败：' . $e->getMessage());
        }
    }

    /**
     * 批量插入数据
     *
     * 返回最后一条写入数据对应的主键数据值
     *
     * @param array $dataList 批量插入的数据，二维数组
     * @return int
     * @throws Exception 批量插入数据错误
     */
    public function batchInsert(array $dataList): int
    {
        $startTime = microtime(true);
        if (empty($dataList)) {
            throw new Exception('插入数据错误, 插入数据应为一个非空的二维数组');
        }

        $fields = array_keys($dataList[0]);
        $placeholders = array_fill(0, count($dataList), '(' . implode(', ', array_fill(0, count($fields), '?')) . ')');
        $this->sql = "INSERT INTO `{$this->table}` (" . implode(', ', $fields) . ") VALUES " . implode(', ', $placeholders);

        $insertData = [];
        foreach ($dataList as $data) {
            array_push($insertData, ...array_values($data));
        }

        try {
            $this->pretreatment = $this->pdo->prepare($this->sql);
            $this->pretreatment->execute($insertData);
            $res = (int) $this->pdo->lastInsertId();
            $this->trace($res, $startTime);
            return $res;
        } catch (Exception $e) {
            throw new Exception('批量插入数据失败：' . $e->getMessage());
        }
    }

    /**
     * 删除数据
     *
     * @return bool 返回删除是否成功
     * @throws Exception 删除条件未设置
     */
    public function delete(): bool
    {
        $startTime = microtime(true);
        if (empty($this->where)) {
            throw new Exception('请使用模型对象的 where() 函数设置删除条件');
        }
        $where = $this->getWhere();
        $this->sql = "DELETE FROM `{$this->table}` {$where[0]}";

        $this->pretreatment = $this->pdo->prepare($this->sql);
        $res = $this->pretreatment->execute($where[1]);
        $this->trace($res, $startTime);
        return $res;
    }

    /**
     * 更新数据
     *
     * @param array $data 更新的数据
     * @return bool 返回更新是否成功
     * @throws Exception 参数应该为一维数组且更新条件未设置
     */
    public function update(array $data): bool
    {
        $startTime = microtime(true);
        if (empty($data)) {
            throw new Exception('update 的参数应该为一个一维数组');
        }
        if (empty($this->where)) {
            throw new Exception('请使用模型对象的 where() 方法设置更新条件');
        }

        $where = $this->getWhere();
        $set = [];
        foreach ($data as $k => $v) {
            $set[] = "`$k` = ?";
        }
        $this->sql = "UPDATE `{$this->table}` SET " . implode(', ', $set) . $where[0];

        $this->pretreatment = $this->pdo->prepare($this->sql);
        $res = $this->pretreatment->execute(array_merge(array_values($data), $where[1]));

        $this->trace($res, $startTime);
        return $res;
    }

    /**
     * 增加或减少字段的值
     *
     * @param string $key 字段名称
     * @param int $value 增量值（正数表示增加，负数表示减少）
     * @return bool 更新结果
     */
    public function increment(string $key, int $value = 1): bool
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
            throw new InvalidArgumentException('字段名包含非法字符');
        }

        $this->sql = "UPDATE `{$this->table}` SET `{$key}` = `{$key}` + ?";
        $where = $this->getWhere();
        $this->sql .= $where[0] . ';';

        return $this->query($this->sql, array_merge([$value], $where[1]));
    }

    /**
     * 获取查询结果的第一行
     *
     * @param string|null $fields 要选择的字段
     * @return array|null 返回第一行作为数组，如果没有结果则返回null
     */
    public function first(?string $fields = '*'): ?array
    {
        $preArray = $this->prepare($fields);
        $this->sql = $preArray[0];

        $startTime = microtime(true);
        $this->pretreatment = $this->pdo->prepare($this->sql);
        $this->pretreatment->execute($preArray[1]);
        $res = $this->pretreatment->fetch(PDO::FETCH_ASSOC);
        $this->trace($res, $startTime);
        return $res === false ? null : $res;
    }

    /**
     * 获取查询结果的多行数据
     *
     * @param string|null $fields 要选择的字段
     * @return array 返回对象数组
     */
    public function get(?string $fields = '*'): array
    {
        $preArray = $this->prepare($fields, false);
        $this->sql = $preArray[0];

        if (is_null($this->eachPage)) {
            $this->sql .= $this->getLimit();
        } else {
            if (empty($this->totalRows)) {
                $countSql = preg_replace('/SELECT .* FROM/', 'SELECT COUNT(*) AS total FROM', $this->sql);
                if (strpos($countSql, 'GROUP BY')) {
                    $countSql = 'SELECT COUNT(*) AS total FROM (' . $countSql . ') AS countTable';
                }
                $countStmt = $this->pdo->prepare($countSql);
                $countStmt->execute($preArray[1]);
                $this->totalRows = $countStmt->fetch(PDO::FETCH_OBJ)->total;
            }
            $this->sql .= (new Paginate($this->totalRows, $this->eachPage))->limit;
        }

        $startTime = microtime(true);
        $this->pretreatment = $this->pdo->prepare($this->sql);
        $this->pretreatment->execute($preArray[1]);
        $res = $this->pretreatment->fetchAll(PDO::FETCH_ASSOC);
        $this->trace($res, $startTime);

        return $res;
    }

    /**
     * 准备SQL查询
     *
     * @param string|null $fields 要选择的字段
     * @param bool $limit 是否包含LIMIT子句
     * @return array 返回准备好的SQL查询和参数
     */
    private function prepare(?string $fields = '*', bool $limit = true): array
    {
        $sql = "SELECT {$fields} FROM `{$this->table}`";
        $exeArray = [];

        if ($join = $this->getJoin()) {
            $sql .= " {$join}";
        }

        if ($where = $this->getWhere()) {
            $sql .= $where[0];
            $exeArray = $where[1];
        }

        if ($group = $this->getGroup()) {
            $sql .= $group;
        }

        if ($order = $this->getOrder()) {
            $sql .= $order;
        }

        if ($limit && ($limitClause = $this->getLimit())) {
            $sql .= $limitClause;
        }

        return [$sql, $exeArray];
    }

    /**
     * 设置WHERE条件
     *
     * @param string $where 条件
     * @param array $array 条件参数
     * @return $this
     */
    public function where(string $where, array $array): self
    {
        $this->where[0] = $where;
        $this->where[1] = $array;
        return $this;
    }

    /**
     * 获取WHERE条件
     *
     * @return array|null 返回条件和参数
     */
    private function getWhere(): ?array
    {
        if (empty($this->where)) {
            return null;
        }

        $whereClause = ' WHERE ' . $this->where[0] . ' ';
        $bindParams = $this->where[1];
        $this->where = null;
        return [$whereClause, $bindParams];
    }

    /**
     * 设置GROUP BY子句
     *
     * @param string $group GROUP BY子句
     * @return $this
     */
    public function groupBy(string $group): self
    {
        $this->groupBy = $group;
        return $this;
    }

    /**
     * 获取GROUP BY子句
     *
     * @return string|null 返回GROUP BY子句
     */
    private function getGroup(): ?string
    {
        if (empty($this->groupBy)) {
            return null;
        }

        $group = ' GROUP BY ' . $this->groupBy . ' ';
        $this->groupBy = null;
        return $group;
    }

    /**
     * 设置ORDER BY子句
     *
     * @param string $order ORDER BY子句
     * @return $this
     */
    public function orderBy(string $order): self
    {
        $this->orderBy = $order;
        return $this;
    }

    /**
     * 获取ORDER BY子句
     *
     * @return string|null 返回ORDER BY子句
     */
    private function getOrder(): ?string
    {
        if (empty($this->orderBy)) {
            return null;
        }

        $order = ' ORDER BY ' . $this->orderBy;
        $this->orderBy = null;
        return $order;
    }

    /**
     * 设置JOIN子句
     *
     * @param string $join JOIN子句
     * @return $this
     */
    public function join(string $join): self
    {
        $this->join = $join;
        return $this;
    }

    /**
     * 获取JOIN子句
     *
     * @return string|null 返回JOIN子句
     */
    private function getJoin(): ?string
    {
        if (empty($this->join)) {
            return null;
        }

        $join = $this->join;
        $this->join = null;
        return $join;
    }

    /**
     * 设置LIMIT子句
     *
     * @param int $start 起始索引
     * @param int $length 要获取的行数
     * @return $this
     */
    public function limit(int $start, int $length): self
    {
        $this->limit = [$start, $length];
        return $this;
    }

    /**
     * 获取LIMIT子句
     *
     * @return string|null 返回LIMIT子句
     */
    private function getLimit(): ?string
    {
        // 如果 $this->limit 未设置或长度不足，返回 null 或默认值
        if (empty($this->limit) || count($this->limit) < 2) {
            return null; // 或者返回默认值，例如 'LIMIT 10'
        }

        // 校验 $this->limit 中的值是否为非负整数
        $start = (int)$this->limit[0];
        $length = (int)$this->limit[1];
        if ($start < 0 || $length < 0) {
            throw new InvalidArgumentException('LIMIT 参数必须为非负整数');
        }

        // 生成 LIMIT 子句
        $limit = ' LIMIT ' . $start . ', ' . $length;

        // 重置 $this->limit
        $this->limit = null;

        return $limit;
    }

    /**
     * 设置分页
     *
     * @param int $eachPage 每页的条目数
     * @param int $totalRows 总行数
     * @return $this
     */
    public function paginate(int $eachPage = 10, int $totalRows = 0): self
    {
        $this->eachPage = $eachPage;
        $this->totalRows = $totalRows;
        return $this;
    }

    /**
     * 获取SQL查询
     *
     * @return string 返回SQL查询
     */
    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * 获取错误信息
     *
     * @return string|null 返回错误信息，如果没有错误则返回null
     */
    public function error(): ?string
    {
        return $this->pretreatment ? $this->pretreatment->errorInfo()[2] : null;
    }

    /**
     * 获取受影响的数据库行数
     *
     * @return int|null 返回受影响的行数，如果预处理语句未初始化则返回null
     */
    public function rowCount(): ?int
    {
        if (empty($this->pretreatment)) {
            return null;
        }
        return $this->pretreatment->rowCount();
    }

    /**
     * 获取最后插入数据的主键值
     *
     * @return string 返回最后插入数据的主键值
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * 获取PDO数据库连接对象
     *
     * @return PDO 返回PDO对象
     */
    public function getDb(): PDO
    {
        return clone $this->pdo;
    }

    /**
     * 获取数据表中的总行数
     *
     * @return int 返回数据表中的总行数，如果查询失败则返回0
     */
    public function count(): int
    {
        // 准备SQL语句的基本部分
        $this->sql = "SELECT COUNT(*) AS total FROM `" . $this->table . "`";

        // 获取WHERE条件
        $where = $this->getWhere();

        // 如果有WHERE条件，则拼接到SQL语句中
        if ($where !== null && !empty($where[0])) {
            $this->sql .= $where[0];
            $bindParams = $where[1];
        } else {
            $bindParams = [];
        }

        // 执行查询
        $this->query($this->sql, $bindParams);

        // 获取查询结果
        $result = $this->pretreatment->fetch();

        // 返回总数，如果结果为空或'total'键不存在，则返回0
        return isset($result['total']) ? (int)$result['total'] : 0;
    }

    /**
     * 获取某个字段的最大值
     *
     * @param string $field 字段名称
     * @return mixed 返回字段的最大值，如果查询失败则返回0
     */
    public function max(string $field)
    {
        $this->sql = "SELECT MAX($field) AS max FROM `$this->table`";
        $where = $this->getWhere();
        $this->sql .= $where[0] . ';';
        $this->query($this->sql, $where[1]);
        $return = $this->pretreatment->fetch();
        return $return['max'] ?? 0;
    }

    /**
     * 获取某个字段的最小值
     *
     * @param string $field 字段名称
     * @return mixed 返回字段的最小值，如果查询失败则返回0
     */
    public function min(string $field)
    {
        $this->sql = "SELECT MIN($field) AS min FROM `$this->table`";
        $where = $this->getWhere();
        $this->sql .= $where[0] . ';';
        $this->query($this->sql, $where[1]);
        $return = $this->pretreatment->fetch();
        return $return['min'] ?? 0;
    }

    /**
     * 获取某个字段的平均值
     *
     * @param string $field 字段名称
     * @return float 返回字段的平均值，如果查询失败则返回0
     */
    public function avg(string $field): float
    {
        $this->sql = "SELECT AVG($field) AS avg FROM `$this->table`";
        $where = $this->getWhere();
        $this->sql .= $where[0] . ';';
        $this->query($this->sql, $where[1]);
        $return = $this->pretreatment->fetch();
        return (float)($return['avg'] ?? 0);
    }

    /**
     * 获取某个字段的总和
     *
     * @param string $field 字段名称
     * @return float 返回字段的总和，如果查询失败则返回0
     */
    public function sum(string $field): float
    {
        $this->sql = "SELECT SUM($field) AS sum FROM `$this->table`";
        $where = $this->getWhere();
        $this->sql .= $where[0] . ';';
        $this->query($this->sql, $where[1]);
        $return = $this->pretreatment->fetch();
        return (float)($return['sum'] ?? 0);
    }

    /**
     * 获取MySQL服务器版本
     *
     * @return string 返回MySQL服务器版本
     */
    public function mysqlVersion(): string
    {
        $this->query('SELECT version();');
        $return = $this->pretreatment->fetch();
        return $return[0];
    }

    /**
     * 获取当前数据表的表结构
     *
     * @return array 返回数据表的结构信息
     */
    public function structure(): array
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $this->table)) {
            throw new InvalidArgumentException('表名包含非法字符');
        }

        $this->query(
            'SELECT ORDINAL_POSITION, COLUMN_NAME, DATA_TYPE, COLUMN_COMMENT
         FROM information_schema.columns
         WHERE table_schema = ?
         AND table_name = ?
         ORDER BY ORDINAL_POSITION ASC;',
            [$this->conf['database'], $this->table]
        );
        return $this->queryFetchAll();
    }

    /**
     * 开启事务
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * 提交事务
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * 回滚事务
     */
    public function rollback(): void
    {
        $this->pdo->rollback();
    }

    /**
     * 在控制台打印刚刚执行的SQL语句（用于调试）
     */
    public function debugSql(): void
    {
        if (defined('DEBUG') && DEBUG) {
            echo '<script>console.log("log - sql 命令 : ' . htmlspecialchars($this->sql) . '");</script>';
        }
    }
}
