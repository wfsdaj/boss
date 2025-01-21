<?php

declare(strict_types=1);

namespace boss;

class Upload
{
    // 是否处理多个文件
    protected bool $multipleFiles = false;

    // 文件输入名称
    protected string $inputName;

    // 文件数组
    protected array $files = [];

    // 文件保存路径
    protected string $savePath;

    // 文件保存名称
    protected string $saveName = '';

    // 上传状态
    protected bool $status = false;

    // 错误信息数组
    protected array $errors = [];

    // 文件最大大小限制
    protected ?array $maxSize = null;

    // 允许的文件扩展名
    protected ?array $limitExtention = null;

    // 允许的文件类型
    protected ?array $limitType = null;

    /**
     * 构造函数
     * @param string|false $name 文件输入名称
     */
    public function __construct($name = false)
    {
        if ($name) {
            $this->inputName = $name;

            if ($this->exists() && is_array($this->input()['name'])) {
                $this->multipleFiles = true;
                $this->generateFilesArray();
            } elseif ($this->exists() && is_string($this->input()['name'])) {
                $this->files = [$this->input()];
            }
        }
    }

    /**
     * 初始化单个文件
     * @param string $inputName 文件输入名称
     * @param array $files 文件数组
     * @param Upload $_class 当前类实例
     */
    public function initSingleFile(string $inputName, array $files, self $_class): void
    {
        $this->inputName = $inputName;
        $this->files = $files;

        if ($_class->maxSize) {
            $this->maxSize($_class->maxSize['number'], $_class->maxSize['errorText']);
        }

        if ($_class->limitExtention) {
            $this->allowExtensions($_class->limitExtention['array'], $_class->limitExtention['errorText']);
        }

        if ($_class->limitType) {
            $this->allowTypes($_class->limitType['array'], $_class->limitType['errorText']);
        }
    }

    /**
     * 获取文件输入
     * @return array|false 返回文件数组或false
     */
    public function input()
    {
        return $_FILES[$this->inputName] ?? false;
    }

    /**
     * 获取第一个文件
     * @return array|false 返回第一个文件数组或false
     */
    public function first()
    {
        return $this->files[0] ?? false;
    }

    /**
     * 获取文件数组
     * @return array 返回文件数组
     */
    public function getArray(): array
    {
        return $this->files;
    }

    /**
     * 检查是否存在指定名称的文件
     * @param string $name 文件输入名称
     * @return bool 返回是否存在
     */
    public function has(string $name): bool
    {
        return isset($_FILES[$name]);
    }

    /**
     * 检查文件是否存在
     * @return bool 返回文件是否存在
     */
    public function exists(): bool
    {
        return $this->has($this->inputName) && $this->input() && !empty($this->input()['name']);
    }

    /**
     * 获取文件数量
     * @return int 返回文件数量
     */
    public function count(): int
    {
        if ($this->multipleFiles) {
            return count($this->input()['name']);
        } elseif ($this->exists() && is_string($this->input()['name'])) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 生成文件数组
     * @return array 返回生成的文件数组
     */
    public function generateFilesArray(): array
    {
        $files = [];

        foreach ($this->input() as $key => $array) {
            foreach ($array as $index => $string) {
                $files[$index][$key] = $string;
            }
        }
        return $this->files = $files;
    }

    /**
     * 遍历文件数组
     * @param callable|false $func 回调函数
     * @return array 返回处理后的文件数组
     */
    public function each($func = false): array
    {
        $array = [];

        foreach ($this->getArray() as $file) {
            $one = new self;
            $one->initSingleFile($this->inputName, [$file], $this);

            if ($func) {
                $func($one);
            } else {
                $array[] = $one;
            }
        }

        return $array;
    }

    /**
     * 获取所有文件
     * @return array 返回所有文件数组
     */
    public function get(): array
    {
        return $this->each();
    }

    /**
     * 获取客户端原始文件名
     * @return string 返回文件名
     */
    public function getClientOriginalName(): string
    {
        return $this->first()['name'];
    }

    /**
     * 获取文件扩展名
     * @return string 返回文件扩展名
     */
    public function extension(): string
    {
        $name = $this->getClientOriginalName();
        $arr = explode('.', $name);
        return end($arr);
    }

    /**
     * 设置或获取文件保存名称
     * @param string|false $name 文件保存名称
     * @param string|false $setCustomExtention 自定义扩展名
     * @return string|self 返回文件保存名称或当前实例
     */
    public function name($name = false, $setCustomExtention = false)
    {
        if ($name) {
            $this->saveName = $name;

            if (!$setCustomExtention) {
                $this->saveName .= '.' . $this->extension();
            } else {
                $this->saveName .= ".$setCustomExtention";
            }

            return $this;
        } else {
            return $this->saveName;
        }
    }

    /**
     * 获取文件临时路径
     * @return string 返回文件临时路径
     */
    public function tmpName(): string
    {
        return $this->first()['tmp_name'];
    }

    /**
     * 获取文件大小
     * @return int 返回文件大小
     */
    public function size(): int
    {
        return $this->first()['size'];
    }

    /**
     * 获取文件上传错误码
     * @return int 返回错误码
     */
    public function error(): int
    {
        return $this->first()['error'];
    }

    /**
     * 获取所有错误信息
     * @return array 返回错误信息数组
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * 获取第一个错误信息
     * @return string 返回第一个错误信息
     */
    public function getFirstError(): string
    {
        return $this->getErrors()[0] ?? '';
    }

    /**
     * 获取文件类型
     * @return string 返回文件类型
     */
    public function type(): string
    {
        return $this->first()['type'];
    }

    /**
     * 设置或获取文件保存路径
     * @param string|false $savePath 文件保存路径
     * @return string|self 返回文件保存路径或当前实例
     */
    public function path($savePath = false)
    {
        if ($savePath) {
            $this->savePath = $savePath;
            return $this;
        } else {
            return $this->savePath;
        }
    }

    /**
     * 使用文件内容的MD5值作为文件名
     * @return self 返回当前实例
     */
    public function hashName(): self
    {
        $this->name(md5_file($this->tmpName()));
        return $this;
    }

    /**
     * 保存文件
     * @return bool 返回保存状态
     */
    public function save(): bool
    {
        // 如果目录不存在则创建
        if (!is_dir($this->savePath)) {
            mkdir($this->savePath, 0777, true);
        }

        if (!$this->validate()) {
            $this->status = false;
            return false;
        }

        if (!$this->name()) {
            $this->saveName = $this->getClientOriginalName();
        }

        $full_path = "$this->savePath/" . $this->name();

        $this->status = move_uploaded_file(
            $this->tmpName(),
            $full_path
        );

        return $this->status();
    }

    /**
     * 获取上传状态
     * @return bool 返回上传状态
     */
    public function status(): bool
    {
        return $this->status;
    }

    /**
     * 限制文件大小
     * @param int $number 文件大小限制（单位：KB）
     * @param string $errorText 错误提示信息
     * @return self 返回当前实例
     */
    public function maxSize(int $number, string $errorText = 'File size is more than allowed'): self
    {
        $this->maxSize = ['number' => $number, 'errorText' => $errorText];

        if (($this->size() / 1000) > $number) {
            $this->replaceStr($errorText);
            $this->errors[] = $errorText;
        }
        return $this;
    }

    /**
     * 限制文件扩展名
     * @param array $array 允许的扩展名数组
     * @param string $errorText 错误提示信息
     * @return self 返回当前实例
     */
    public function allowExtensions(array $array, string $errorText = 'File type is not allowed'): self
    {
        $this->limitExtention = ['array' => $array, 'errorText' => $errorText];

        if (!in_array($this->extension(), $array)) {
            $this->replaceStr($errorText);
            $this->errors[] = $errorText;
        }

        return $this;
    }

    /**
     * 限制文件类型
     * @param array $array 允许的文件类型数组
     * @param string $errorText 错误提示信息
     * @return self 返回当前实例
     */
    public function allowTypes(array $array, string $errorText = 'File type is not allowed'): self
    {
        $this->limitType = ['array' => $array, 'errorText' => $errorText];

        if (!in_array($this->type(), $array)) {
            $this->replaceStr($errorText);
            $this->errors[] = $errorText;
        }
        return $this;
    }

    /**
     * 检查目录是否可写
     * @return bool 返回目录是否可写
     */
    public function checkWritable(): bool
    {
        return is_writable($this->path());
    }

    /**
     * 验证文件
     * @return bool 返回验证结果
     */
    public function validate(): bool
    {
        if (!$this->checkWritable()) {
            $this->errors[] = 'There is no permission to write the file';
        }

        return empty($this->errors);
    }

    /**
     * 替换错误信息中的占位符
     * @param string $text 错误信息
     */
    protected function replaceStr(string &$text): void
    {
        $text = str_replace('@name', $this->getClientOriginalName(), $text);
        $text = str_replace('@size', $this->size(), $text);

        if ($this->maxSize) {
            $text = str_replace('@maxSize', $this->maxSize['number'], $text);
        }
    }
}