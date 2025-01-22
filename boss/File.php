<?php

namespace boss;

class File
{
    // 默认配置常量
    private const DEFAULT_MAX_SIZE = 2097152; // 2MB
    private const DEFAULT_EXTS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const DEFAULT_SAVE_PATH = '/upload';
    private const DEFAULT_URL_PATH = '';

    // 配置属性
    public int $maxSize;
    public array $exts;
    public string $savePath;
    public string $urlPath;
    public bool $autoSub;
    public bool $autoName;
    public bool $replace;

    /**
     * 构造函数，初始化上传配置
     *
     * @param array $config 上传配置数组
     */
    public function __construct(array $config = [])
    {
        $this->maxSize  = $config['maxSize'] ?? self::DEFAULT_MAX_SIZE;
        $this->exts     = $config['exts'] ?? self::DEFAULT_EXTS;
        $this->savePath = $config['savePath'] ?? self::DEFAULT_SAVE_PATH;
        $this->urlPath  = $config['urlPath'] ?? self::DEFAULT_URL_PATH;
        $this->autoSub  = $config['autoSub'] ?? true;
        $this->autoName = $config['autoName'] ?? true;
        $this->replace  = $config['replace'] ?? true;
    }

    /**
     * 处理文件上传
     *
     * @return array 返回上传结果数组
     */
    public function upload(): array
    {
        $results = [];
        foreach ($_FILES as $file) {
            if (is_array($file['name'])) {
                // 批量文件上传处理
                $files = $this->normalizeFilesArray($file);
                foreach ($files as $f) {
                    $results[] = $this->handleFileUpload($f);
                }
            } else {
                // 单个文件上传处理
                $results[] = $this->handleFileUpload($file);
            }
        }
        return $results;
    }

    /**
     * 处理单个文件上传
     *
     * @param array $file 文件信息数组
     * @return array 返回上传结果数组
     */
    private function handleFileUpload(array $file): array
    {
        try {
            $this->validateFile($file);

            $fileExt = $this->getFileExtension($file);
            $saveDir = $this->getSaveDirectory();
            $filename = $this->generateFilename($file, $fileExt);
            $filePath = PUBLIC_PATH . '/' . $saveDir . $filename;
            $urlPath = $this->urlPath . '/' . $saveDir . $filename;

            $this->ensureDirectoryExists($saveDir);
            $this->moveUploadedFile($file['tmp_name'], $filePath);

            return [
                'result' => true,
                'file_ext' => $fileExt,
                'file_name' => $filename,
                'file_path' => $urlPath,
            ];
        } catch (\Exception $e) {
            return ['result' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 验证文件是否符合上传要求
     *
     * @param array $file 文件信息数组
     * @throws \Exception 如果文件不符合要求则抛出异常
     */
    private function validateFile(array $file): void
    {
        if (isset($file['error']) && $file['error'] != 0) {
            throw new \Exception($this->getUploadErrorMessage($file['error']));
        }

        if ($file['size'] > $this->maxSize) {
            throw new \Exception('上传文件大小超过限制');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new \Exception('上传文件错误');
        }

        $fileExt = $this->getFileExtension($file);
        if (!in_array($fileExt, $this->exts)) {
            throw new \Exception('上传文件扩展名是不允许的扩展名');
        }
    }

    /**
     * 获取文件扩展名
     *
     * @param array $file 文件信息数组
     * @return string 文件扩展名
     */
    private function getFileExtension(array $file): string
    {
        return strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    }

    /**
     * 获取保存目录
     *
     * @return string 保存目录
     */
    private function getSaveDirectory(): string
    {
        if ($this->autoSub) {
            return $this->savePath . '/' . date('Y/m/d') . '/';
        }
        return $this->savePath . '/';
    }

    /**
     * 生成文件名
     *
     * @param array $file 文件信息数组
     * @param string $fileExt 文件扩展名
     * @return string 生成的文件名
     */
    private function generateFilename(array $file, string $fileExt): string
    {
        return $this->autoName ? uuid(32) . '.' . $fileExt : $file['name'];
    }

    /**
     * 确保目录存在，如果不存在则创建
     *
     * @param string $dir 目录路径
     * @throws \Exception 如果目录创建失败则抛出异常
     */
    private function ensureDirectoryExists(string $dir): void
    {
        if (!is_dir(PUBLIC_PATH . '/' . $dir) && !mkdir(PUBLIC_PATH . '/' . $dir, 0755, true)) {
            throw new \Exception('上传目录没有写权限');
        }
    }

    /**
     * 移动上传的文件到目标路径
     *
     * @param string $tmpName 临时文件路径
     * @param string $filePath 目标文件路径
     * @throws \Exception 如果文件移动失败则抛出异常
     */
    private function moveUploadedFile(string $tmpName, string $filePath): void
    {
        if (!$this->replace && file_exists($filePath)) {
            throw new \Exception('文件已存在');
        }

        if (!move_uploaded_file($tmpName, $filePath)) {
            throw new \Exception('上传失败');
        }
    }

    /**
     * 获取上传错误信息
     *
     * @param int $errorCode 错误代码
     * @return string 错误信息
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        $messages = [
            UPLOAD_ERR_INI_SIZE   => '超过php.ini允许的大小',
            UPLOAD_ERR_FORM_SIZE  => '超过服务器允许上传的大小',
            UPLOAD_ERR_PARTIAL    => '文件上传不完整',
            UPLOAD_ERR_NO_FILE    => '本地文件不存在',
            UPLOAD_ERR_NO_TMP_DIR => '找不到临时目录',
            UPLOAD_ERR_CANT_WRITE => '写文件到硬盘出错',
            UPLOAD_ERR_EXTENSION  => '文件上传中断'
        ];

        return $messages[$errorCode] ?? '未知错误';
    }

    /**
     * 规范化文件数组
     *
     * @param array $file 文件信息数组
     * @return array 规范化后的文件数组
     */
    private function normalizeFilesArray(array $file): array
    {
        $files = [];
        for ($i = 0; $i < count($file['name']); $i++) {
            foreach ($file as $k => $v) {
                $files[$i][$k] = $v[$i];
            }
        }
        return $files;
    }
}