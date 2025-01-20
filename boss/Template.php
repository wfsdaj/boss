<?php

declare(strict_types=1);

namespace boss;

use Exception;

class Template
{
    private string $view_path = ''; // 模板目录

    // 构造函数，可以指定模板目录
    public function __construct(array $config = [])
    {
        $this->view_path = $config['view_path'];
    }

    // 渲染模板
    public function render($template, $data = [])
    {
        // 获取模板文件的路径
        $templatePath = $this->view_path . $template . '.html';

        // 检查模板文件是否存在
        if (!file_exists($templatePath)) {
            throw new Exception("Template file not found: $templatePath");
        }

        // 读取模板文件内容
        $templateContent = file_get_contents($templatePath);

        /**
         * 将模板中的 {$variable} 替换为 <?php echo $variable; ?>
         */
        $templateContent = preg_replace('/\{\$(\w+)\}/', '<?php echo $$1; ?>', $templateContent);

        // 创建一个临时文件来保存替换后的内容
        $tempFile = tempnam(sys_get_temp_dir(), 'tpl');
        file_put_contents($tempFile, $templateContent);

        // 将数据数组中的键值对提取为变量
        extract($data);

        // 开启输出缓冲
        ob_start();

        // 包含临时文件
        include $tempFile;

        // 获取并清空缓冲区的内容
        $output = ob_get_clean();

        // 删除临时文件
        unlink($tempFile);

        // 返回渲染后的内容
        return $output;
    }
}
