<?php

declare(strict_types=1);

namespace core;

/**
 * 数据验证类
 *
 * 用于验证输入数据是否符合指定的规则。
 */
class Validator
{
    /**
     * 需要验证的数据
     *
     * @var array
     */
    public $data;

    /**
     * 验证规则
     *
     * @var array
     */
    public $checkRules;

    /**
     * 错误信息
     *
     * @var string|null
     */
    public $error;

    /**
     * 是否检查 CSRF Token
     *
     * @var bool
     */
    public $checkToken;

    /**
     * 构造函数
     *
     * @param array $data 需要验证的数据
     * @param array $checkRules 验证规则
     * @param bool $checkToken 是否检查 CSRF Token，默认为 true
     */
    public function __construct(array $data, array $checkRules, bool $checkToken = true)
    {
        $this->data = $data;
        $this->checkRules = $checkRules;
        $this->checkToken = $checkToken;
    }

    /**
     * 执行验证
     *
     * @return bool 如果验证通过返回 true，否则返回 false
     */
    public function check()
    {
        // 检查 CSRF Token
        if ($this->checkToken && !$this->validateCsrfToken()) {
            $this->error = 'Token 错误';
            return false;
        }

        // 遍历验证规则
        foreach ($this->checkRules as $key => $rule) {
            // 严格检查键是否存在，使用array_key_exists()更严谨
            if (!array_key_exists($key, $this->data)) {
                // 提供默认错误信息，如果规则中没有定义错误信息
                $this->error = isset($rule[2]) ? $rule[2] : "缺少字段: $key";
                return false;
            }

            // 将规则标准化为数组的数组，方便统一处理
            $rules = is_array($rule[0]) ? $rule : [$rule];

            // 遍历单个字段的多个验证规则
            foreach ($rules as $singleRule) {
                $methodName = 'check' . ucfirst($singleRule[0]); // 构建验证方法名
                // 检查验证方法是否存在，如果不存在则触发错误
                if (!method_exists($this, $methodName)) {
                    trigger_error("验证规则 '$methodName' 未找到。", E_USER_ERROR);
                    return false; // 触发错误后必须返回false，防止继续执行导致不可预测的结果
                }
                // 使用null合并运算符处理可选参数
                $res = $this->$methodName($this->data[$key], $singleRule[1] ?? null);
                if (!$res) {
                    // 提供默认错误信息，如果规则中没有定义错误信息
                    $this->error = isset($singleRule[2]) ? $singleRule[2] : "字段 $key 的值无效";
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 验证 CSRF Token
     *
     * @return bool 如果 Token 有效返回 true，否则返回 false
     */
    private function validateCsrfToken(): bool
    {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
            return false;
        }

        // 验证通过后销毁 Token
        unset($_SESSION['csrf_token']);
        return true;
    }

    // 不为空
    public function checkRequired($value, $lengthRange): bool
    {
        $value = trim($value);
        return !empty($value) && preg_match('/^.{' . $lengthRange . '}$/Uis', $value);
    }

    // 字符串及长度检查
    public function checkString(string $value, $range): bool
    {
        return preg_match('/^.{' . $range . '}$/Uis', trim($value));
    }

    // 整数及长度检查
    public function checkInt(string $value, $length): bool
    {
        return preg_match('/^-?[0-9]{' . $length . '}$/', $value);
    }

    // 整数检查
    public function checkIsInt(int $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    // 数值区间
    public function checkBetween($value, $range): bool
    {
        if (!is_numeric($value)) return false;
        list($min, $max) = explode(',', $range);
        return $value >= $min && $value <= $max;
    }

    // 小数（浮点数）检查
    public function checkIsFloat($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }

    // 小数（浮点数）区间检查
    public function checkFloatLength($value, $length): bool
    {
        if (!$this->checkIsFloat($value)) return false;
        return preg_match('/^(\d+)\.(\d{' . $length . '})$/', $value);
    }

    // 邮箱检查
    public function checkEmail($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    // 电话号码检查
    public function checkPhone($value): bool
    {
        return preg_match('/^1[3-9]\d{9}$/', $value);
    }

    // 网址检查
    public function checkUrl($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    // 邮编检查
    public function checkZipcode($value): bool
    {
        return preg_match('/^[0-9]{6}$/', $value);
    }

    // QQ号检查
    public function checkQq($value): bool
    {
        return preg_match('/^[1-9][0-9]{4,10}$/', $value);
    }

    // 比较检查，添加了数值类型检查
    public function checkGt($value, $limit): bool
    {
        return is_numeric($value) && $value > $limit;
    }
    public function checkGte($value, $limit): bool
    {
        return is_numeric($value) && $value >= $limit;
    }
    public function checkLt($value, $limit): bool
    {
        return is_numeric($value) && $value < $limit;
    }
    public function checkLte($value, $limit): bool
    {
        return is_numeric($value) && $value <= $limit;
    }
    public function checkSame($value, $expected): bool
    {
        return $value === $expected;
    }
    public function checkNotSame($value, $expected): bool
    {
        return $value !== $expected;
    }
}
