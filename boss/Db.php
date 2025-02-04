<?php

declare(strict_types=1);

namespace boss;

use PDO;
use Exception;
use PDOStatement;

class Db
{
    /** @var Db[] 数据库操作实例 */
    public static array $operater = [];

    /** @var string 表名 */
    public string $tableName;

    /** @var PDO PDO 实例 */
    public PDO $pdo;

    /** @var string SQL 语句 */
    public string $sql;

    /** @var PDOStatement|null 预处理语句 */
    public ?PDOStatement $pretreatment = null;

    /** @var array WHERE 条件 */
    public array $where = [];

    /** @var string GROUP BY 条件 */
    public string $groupBy = '';

    /** @var string JOIN 条件 */
    public string $join = '';

    /** @var string ORDER BY 条件 */
    public string $orderBy = '';

    /** @var array LIMIT 条件 */
    public array $limit = [];

    /** @var int 每页记录数 */
    public int $eachPage = 0;

    /** @var int 总记录数 */
    public int $totalRows = 0;

    /** @var array 数据库配置 */
    public array $conf;

    /**
     * Db 构造函数
     * @param array $conf 数据库配置
     * @throws Exception
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
                    $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                    break;
                default:
                    throw new Exception('不支持的数据库驱动');
            }
        } catch (Exception $e) {
            throw new Exception('数据库连接失败，请检查数据库相关配置: ' . $e->getMessage());
        }
    }

    /**
     * 克隆方法
     */
    public function __clone()
    {
        $this->sql = '';
        $this->pretreatment = null;
        $this->where = [];
        $this->groupBy = '';
        $this->join = '';
        $this->orderBy = '';
        $this->limit = [];
        $this->eachPage = 0;
        $this->totalRows = 0;
    }

    /**
     * 获取数据库操作实例
     * @param array $conf 数据库配置
     * @param string $tableName 表名
     * @param string $configName 配置名称
     */
    public static function getInstance(array $conf, string $tableName, string $configName)
    {
        $tableName = $conf['prefix'] . $tableName;
        if (empty(self::$operater[$configName])) {
            self::$operater[$configName] = new Db($conf);
            self::$operater[$configName]->tableName = $tableName;
            return self::$operater[$configName];
        }
        if (self::$operater[$configName]->tableName === $tableName) {
            return self::$operater[$configName];
        }
        $cloner = clone self::$operater[$configName];
        $cloner->tableName = $tableName;
        return $cloner;
    }

    /**
     * 记录 SQL 运行过程
     * @param mixed $res 执行结果
     * @param float $startTime 开始时间
     * @param string|null $sql SQL 语句
     */
    protected function trace($res, float $startTime, ?string $sql = null): void
    {
        $sql = $sql ?? $this->sql;
        $sqlRec = [
            'status' => $res !== false ? '成功' : '失败',
            'sql'    => $sql,
            'time'   => round((microtime(true) - $startTime) * 1000, 2),
            'error'  => $res ? '' : $this->error(),
        ];
        $GLOBALS['traceSql'][] = $sqlRec;
    }

    /**
     * 执行 SQL 语句
     * @param string $sql SQL 语句
     * @param array|null $execute 绑定参数
     * @return bool
     */
    public function query(string $sql, ?array $execute = null): bool
    {
        $startTime = microtime(true);
        $this->pretreatment = $this->pdo->prepare($sql);
        if ($this->pretreatment === false) {
            throw new Exception('SQL 预处理失败: ' . implode(' ', $this->pdo->errorInfo()));
        }
        $res = $this->pretreatment->execute($execute);
        $this->trace($res, $startTime, $sql);
        return $res;
    }

    /**
     * 获取单条查询结果
     * @return array|null
     */
    public function queryFetch(): ?array
    {
        return $this->pretreatment->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 获取全部查询结果
     * @return array
     */
    public function queryFetchAll(): array
    {
        return $this->pretreatment->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 插入数据
     * @param array|null $data 插入数据
     * @return string
     * @throws Exception
     */
    public function insert(?array $data = null): string
    {
        $startTime = microtime(true);
        if (!is_array($data)) {
            throw new Exception('插入数据错误，插入数据应为一个一维数组');
        }
        $this->sql = "INSERT INTO $this->tableName (";
        $fields = [];
        $placeHolder = [];
        $insertData = [];
        foreach ($data as $k => $v) {
            $fields[] = "$k";
            $placeHolder[] = "?";
            $insertData[] = $v;
        }
        $this->sql .= implode(', ', $fields) . ') VALUES (' . implode(', ', $placeHolder) . ');';
        $this->pretreatment = $this->pdo->prepare($this->sql);
        $this->pretreatment->execute($insertData);
        $res = $this->pdo->lastInsertId();
        $this->trace($res, $startTime);
        return $res;
    }

    /**
     * 删除数据
     * @return bool
     * @throws Exception
     */
    public function delete(): bool
    {
        $startTime = microtime(true);
        if (empty($this->where)) {
            throw new Exception('请使用模型对象的 where() 函数设置删除条件');
        }
        $where = $this->getWhere();
        $this->sql = "DELETE FROM $this->tableName {$where[0]};";
        $this->pretreatment = $this->pdo->prepare($this->sql);
        $res = $this->pretreatment->execute($where[1]);
        $this->trace($res, $startTime);
        return $res;
    }

    /**
     * 更新数据
     * @param array|null $data 更新数据
     * @return bool
     * @throws Exception
     */
    public function update(?array $data = null): bool
    {
        $startTime = microtime(true);
        if (is_null($data)) {
            $data = $_POST;
        }
        if (empty($data) || !is_array($data)) {
            throw new Exception('update($data) 函数的参数应该为一个一维数组');
        }
        if (empty($this->where)) {
            throw new Exception('请使用模型对象的 where() 方法设置更新条件');
        }
        $where = $this->getWhere();
        $this->sql = "UPDATE {$this->tableName} SET ";
        $updateData = [];
        foreach ($data as $k => $v) {
            $this->sql .= "$k = ?, ";
            $updateData[] = $v;
        }
        $this->sql = substr($this->sql, 0, -2) . $where[0] . ';';
        $updateData = array_merge($updateData, $where[1]);
        $this->pretreatment = $this->pdo->prepare($this->sql);
        $res = $this->pretreatment->execute($updateData);
        $this->trace($res, $startTime);
        return $res;
    }

    /**
     * 字段值增加
     * @param string $filedName 字段名
     * @param int $addVal 增加值
     * @return bool
     */
    public function increment(string $filedName, int $addVal): bool
    {
        $startTime = microtime(true);
        $this->sql = "UPDATE {$this->tableName} SET {$filedName} = {$filedName} + {$addVal}";
        $where = $this->getWhere();
        $this->sql .= $where[0] . ';';
        return $this->query($this->sql, $where[1]);
    }

    /**
     * 查询单条数据
     * @param string|null $fields 查询字段
     * @return array|null
     */
    public function first(?string $fields = null): ?array
    {
        $startTime = microtime(true);
        $preArray = $this->prepare($fields);
        $this->sql = $preArray[0];
        $this->pretreatment = $this->pdo->prepare($this->sql);
        $this->pretreatment->execute($preArray[1]);
        $res = $this->pretreatment->fetch(PDO::FETCH_ASSOC);
        $this->trace($res, $startTime);

        return $res === false ? null : $res;
    }

    /**
     * 查询多条数据
     * @param string|null $fields 查询字段
     * @return array
     */
    public function get(?string $fields = null): array
    {
        $startTime = microtime(true);
        $preArray = $this->prepare($fields, false);
        $this->sql = $preArray[0];
        if (is_null($this->eachPage)) {
            $this->sql .= $this->getLimit() . ';';
        } else {
            if (empty($this->totalRows)) {
                $mode = '/^select .* from (.*)$/Uis';
                preg_match($mode, $this->sql, $arr_preg);
                $sql = 'SELECT COUNT(*) AS total FROM ' . $arr_preg['1'];
                if (strpos($sql, 'GROUP BY ')) {
                    $sql = 'SELECT COUNT(*) AS total FROM (' . $sql . ') AS witCountTable;';
                }
                $pretreatment = $this->pdo->prepare($sql);
                $pretreatment->execute($preArray[1]);
                $arrTotal = $pretreatment->fetch(PDO::FETCH_ASSOC);
                $pager = new \boss\Paginate($arrTotal['total'], $this->eachPage);
            } else {
                $pager = new \boss\Paginate($this->totalRows, $this->eachPage);
            }
            $this->sql .= $pager->limit . ';';
        }
        $this->pretreatment = $this->pdo->prepare($this->sql);
        $this->pretreatment->execute($preArray[1]);
        $res = $this->pretreatment->fetchAll(PDO::FETCH_ASSOC);
        $this->trace($res, $startTime);
        if (is_null($this->eachPage)) {
            return $res;
        } else {
            $this->eachPage = 0;
            return [$res, $pager];
        }
    }

    /**
     * 预处理 SQL
     * @param string|null $fields 查询字段
     * @param bool $limit 是否包含 LIMIT
     * @return array
     */
    public function prepare(?string $fields, bool $limit = true): array
    {
        $exeArray = [];
        $join = $this->getJoin();
        if (!empty($join)) {
            $sql = is_null($fields) ? 'SELECT * FROM ' . $this->tableName . ' ' . $join . ' ' : 'SELECT ' . $fields . ' FROM ' . $this->tableName . ' ' . $join . ' ';
        } else {
            $sql = is_null($fields) ? 'SELECT * FROM ' . $this->tableName . ' ' : 'SELECT ' . $fields . ' FROM ' . $this->tableName . ' ';
        }
        $where = $this->getWhere();
        if (!is_null($where)) {
            $sql .= $where[0];
            $exeArray = $where[1];
        }
        $limit ? $sql .= $this->getGroup() . $this->getOrder() . $this->getLimit() . ';' : $sql .= $this->getGroup() . $this->getOrder();
        return [$sql, $exeArray];
    }

    /**
     * 设置 WHERE 条件
     * @param string $where WHERE 条件
     * @param array|string $array 绑定参数
     * @return $this
     */
    public function where(string $where, $array): self
    {
        $this->where[0] = $where;
        $this->where[1] = is_array($array) ? $array : [$array];
        return $this;
    }

    /**
     * 获取 WHERE 条件
     * @return array|null
     */
    public function getWhere(): ?array
    {
        if (empty($this->where) || !isset($this->where[0])) {
            return null;
        }
        $return = [' WHERE ' . $this->where[0] . ' ', $this->where[1]];
        $this->where = [];
        return $return;
    }

    /**
     * 设置 GROUP BY 条件
     * @param string $group GROUP BY 条件
     * @return $this
     */
    public function groupBy(string $group): self
    {
        $this->groupBy = $group;
        return $this;
    }

    /**
     * 获取 GROUP BY 条件
     * @return string|null
     */
    public function getGroup(): ?string
    {
        if (empty($this->groupBy)) {
            return null;
        }
        $group = $this->groupBy;
        $this->groupBy = '';
        return ' GROUP BY ' . $group . ' ';
    }

    /**
     * 设置 ORDER BY 条件
     * @param string $order ORDER BY 条件
     * @return $this
     */
    public function orderBy(string $order): self
    {
        $this->orderBy = $order;
        return $this;
    }

    /**
     * 获取 ORDER BY 条件
     * @return string|null
     */
    public function getOrder(): ?string
    {
        if (empty($this->orderBy)) {
            return null;
        }
        $return = ' ORDER BY ' . $this->orderBy . ' ';
        $this->orderBy = '';
        return $return;
    }

    /**
     * 设置 JOIN 条件
     * @param string $join_sql JOIN 条件
     * @return $this
     */
    public function join(string $join_sql): self
    {
        $this->join = $join_sql;
        return $this;
    }

    /**
     * 获取 JOIN 条件
     * @return string|null
     */
    public function getJoin(): ?string
    {
        if (empty($this->join)) {
            return null;
        }
        $return = $this->join;
        $this->join = '';
        return $return;
    }

    /**
     * 设置 LIMIT 条件
     * @param int $start 起始位置
     * @param int $length 长度
     * @return $this
     */
    public function limit(int $start, int $length): self
    {
        $this->limit = [$start, $length];
        return $this;
    }

    /**
     * 获取 LIMIT 条件
     * @return string|null
     */
    public function getLimit(): ?string
    {
        if (empty($this->limit)) {
            return null;
        }
        $return = ' LIMIT ' . $this->limit[0] . ',' . $this->limit[1] . ' ';
        $this->limit = [];
        return $return;
    }

    /**
     * 设置分页
     * @param int $eachPage 每页记录数
     * @param int $totalRows 总记录数
     * @return $this
     */
    public function paginate(int $eachPage = 10, int $totalRows = 0): self
    {
        $this->eachPage = $eachPage;
        $this->totalRows = $totalRows;
        return $this;
    }

    /**
     * 获取 SQL 语句
     * @return string
     */
    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * 获取错误信息
     * @return string|null
     */
    public function error(): ?string
    {
        $error = is_null($this->pretreatment) ? $this->pdo->errorInfo() : $this->pretreatment->errorInfo();
        return $error[2] ?? null;
    }

    /**
     * 获取影响的数据条目数
     * @return int|null
     */
    public function rowCount(): ?int
    {
        return $this->pretreatment ? $this->pretreatment->rowCount() : null;
    }

    /**
     * 获取刚刚插入数据的主键值
     * @return string
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * 获取 PDO 对象
     * @return PDO
     */
    public function getDb(): PDO
    {
        return $this->pdo;
    }

    /**
     * 获取数据总数
     * @return int
     */
    public function count(): int
    {
        $this->sql = "SELECT COUNT(*) AS total FROM $this->tableName ";
        $where = $this->getWhere();
        $this->sql .= $where[0] . ';';
        $this->query($this->sql, $where[1]);
        $return = $this->pretreatment->fetch();
        return $return['total'] ?? 0;
    }

    /**
     * 获取某个字段的最大值
     * @param string $field 字段名
     * @return int
     */
    public function max(string $field): int
    {
        $this->sql = "SELECT MAX($field) AS max FROM $this->tableName";
        $where = $this->getWhere();
        $this->sql .= $where[0] . ';';
        $this->query($this->sql, $where[1]);
        $return = $this->pretreatment->fetch();
        return $return['max'] ?? 0;
    }

    /**
     * 获取某个字段的最小值
     * @param string $field 字段名
     * @return int
     */
    public function min(string $field): int
    {
        $this->sql = "SELECT MIN($field) AS min FROM $this->tableName";
        $where = $this->getWhere();
        $this->sql .= $where[0] . ';';
        $this->query($this->sql, $where[1]);
        $return = $this->pretreatment->fetch();
        return $return['min'] ?? 0;
    }

    /**
     * 获取某个字段的平均值
     * @param string $field 字段名
     * @return float
     */
    public function avg(string $field): float
    {
        $this->sql = "SELECT AVG($field) AS avg FROM $this->tableName";
        $where = $this->getWhere();
        $this->sql .= $where[0] . ';';
        $this->query($this->sql, $where[1]);
        $return = $this->pretreatment->fetch();
        return $return['avg'] ?? 0;
    }

    /**
     * 获取某个字段的总和
     * @param string $field 字段名
     * @return int
     */
    public function sum(string $field): int
    {
        $this->sql = "SELECT SUM($field) AS sum FROM $this->tableName";
        $where = $this->getWhere();
        $this->sql .= $where[0] . ';';
        $this->query($this->sql, $where[1]);
        $return = $this->pretreatment->fetch();
        return $return['sum'] ?? 0;
    }

    /**
     * 获取 MySQL 版本
     * @return string
     */
    public function mysqlVersion(): string
    {
        $this->query('SELECT VERSION();');
        $return = $this->pretreatment->fetch();
        return $return[0] ?? '';
    }

    /**
     * 获取当前数据表表结构
     * @return array
     */
    public function structure(): array
    {
        $this->query('SELECT ORDINAL_POSITION, COLUMN_NAME, DATA_TYPE, COLUMN_COMMENT
        FROM information_schema.columns WHERE table_schema = ? AND table_name = ?
        ORDER BY ORDINAL_POSITION ASC;', [$this->conf['dbname'], $this->tableName]);
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
     * 控制台打印 SQL 语句
     */
    public function debugSql(): void
    {
        $sql = addslashes($this->sql);
        echo '<script>console.log("phpGrace log : sql 命令 : ' . $sql . '");</script>';
    }
}
