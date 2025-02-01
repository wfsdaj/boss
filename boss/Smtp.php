<?php

namespace boss;

use Exception;

class Smtp
{
    // 邮件传输代理服务器地址
    protected string $sendServer;

    // 邮件传输代理服务器端口
    protected int $port;

    // 是否是安全连接
    protected bool $isSecurity;

    // 邮件传输代理用户名
    protected string $userName;

    // 邮件传输代理密码
    protected string $password;

    // 发件人
    protected string $from;

    // 收件人
    protected array $to = [];

    // 抄送
    protected array $cc = [];

    // 秘密抄送
    protected array $bcc = [];

    // 主题
    protected string $subject;

    // 邮件正文
    protected string $body;

    // 附件
    protected array $attachment = [];

    // 调试模式
    protected bool $debug;

    // 错误信息
    protected string $errorMessage = '';

    // 资源句柄
    protected $socket;

    /**
     * 构造函数，初始化 SMTP 配置
     *
     * @param string $server 代理服务器的 IP 或域名
     * @param string $username 认证账号
     * @param string $password 认证密码
     * @param int $port 代理服务器的端口，默认 465
     * @param bool $isSecurity 是否为安全连接，默认 true
     * @param bool $debug 是否启用调试模式，默认 false
     */
    public function __construct(
        string $server = "",
        string $username = "",
        string $password = "",
        int $port = 465,
        bool $isSecurity = true,
        bool $debug = false
    ) {
        if ($server) {
            $this->sendServer = $server;
            $this->port = $port;
            $this->isSecurity = $isSecurity;
            $this->debug = $debug;
            $this->userName = base64_encode($username);
            $this->password = base64_encode($password);
            $this->from = $username;
        } else {
            $smtp = config('smtp');
            $this->sendServer = $smtp['server'];
            $this->port = $smtp['port'];
            $this->isSecurity = (bool)$smtp['ssl'];
            $this->debug = $debug;
            $this->userName = base64_encode($smtp['username']);
            $this->password = base64_encode($smtp['password']);
            $this->from = $smtp['username'];
        }
    }

    /**
     * 设置收件人，多个收件人可用逗号隔开
     *
     * @param string $to 收件人地址
     * @return bool
     */
    public function setReceiver(string $to): bool
    {
        $this->to = array_filter(explode(',', $to));
        return true;
    }

    /**
     * 设置抄送，多个抄送可用逗号隔开
     *
     * @param string $cc 抄送地址
     * @return bool
     */
    public function setCc(string $cc): bool
    {
        $this->cc = array_filter(explode(',', $cc));
        return true;
    }

    /**
     * 设置秘密抄送，多个秘密抄送可用逗号隔开
     *
     * @param string $bcc 秘密抄送地址
     * @return bool
     */
    public function setBcc(string $bcc): bool
    {
        $this->bcc = array_filter(explode(',', $bcc));
        return true;
    }

    /**
     * 添加邮件附件
     *
     * @param string $file 文件路径
     * @return bool
     */
    public function addAttachment(string $file): bool
    {
        if (!file_exists($file)) {
            $this->errorMessage = "文件 {$file} 不存在";
            return false;
        }
        $this->attachment[] = $file;
        return true;
    }

    /**
     * 设置邮件主题和正文
     *
     * @param string $subject 邮件主题
     * @param string $body 邮件正文
     * @return bool
     */
    public function setMail(string $subject, string $body): bool
    {
        $this->subject = base64_encode($subject);
        $this->body = base64_encode($body);
        return true;
    }

    /**
     * 发送邮件
     *
     * @param string $to 收件人地址
     * @param string $subject 邮件主题
     * @param string $body 邮件正文
     * @return bool
     */
    public function sendMail(string $to = '', string $subject = '', string $body = ''): bool
    {
        if ($to) {
            $this->setReceiver($to);
            $this->setMail($subject, $body);
        }

        $command = $this->getCommand();
        if (!$this->socket($this->isSecurity)) {
            return false;
        }

        foreach ($command as $value) {
            if (!$this->sendCommand($value[0], $value[1])) {
                return false;
            }
        }

        // 关闭连接
        $this->close();
        return true;
    }

    /**
     * 返回错误信息
     *
     * @return string
     */
    public function error(): string
    {
        return $this->errorMessage;
    }

    /**
     * 获取邮件命令
     *
     * @return array
     */
    protected function getCommand(): array
    {
        $separator = "----=_Part_" . md5($this->from . time()) . uniqid(); // 分隔符
        $command = [
            ["HELO sendmail\r\n", 250]
        ];

        if (!empty($this->userName)) {
            $command[] = ["AUTH LOGIN\r\n", 334];
            $command[] = [$this->userName . "\r\n", 334];
            $command[] = [$this->password . "\r\n", 235];
        }

        // 设置发件人
        $command[] = ["MAIL FROM: <{$this->from}>\r\n", 250];
        $header = "FROM: <{$this->from}>\r\n";

        // 设置收件人
        if (!empty($this->to)) {
            $header .= "TO: " . implode(',', array_map(fn($email) => "<{$email}>", $this->to)) . "\r\n";
            foreach ($this->to as $email) {
                $command[] = ["RCPT TO: <{$email}>\r\n", 250];
            }
        }

        // 设置抄送
        if (!empty($this->cc)) {
            $header .= "CC: " . implode(',', array_map(fn($email) => "<{$email}>", $this->cc)) . "\r\n";
            foreach ($this->cc as $email) {
                $command[] = ["RCPT TO: <{$email}>\r\n", 250];
            }
        }

        // 设置秘密抄送
        if (!empty($this->bcc)) {
            foreach ($this->bcc as $email) {
                $command[] = ["RCPT TO: <{$email}>\r\n", 250];
            }
        }

        // 主题
        $header .= "Subject: =?UTF-8?B?{$this->subject}?=\r\n";
        $header .= "Content-Type: multipart/mixed; boundary=\"{$separator}\"\r\n";
        $header .= "MIME-Version: 1.0\r\n";

        // 邮件正文
        $header .= "\r\n--{$separator}\r\n";
        $header .= "Content-Type:text/html; charset=utf-8\r\n";
        $header .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $header .= $this->body . "\r\n";

        // 附件
        if (!empty($this->attachment)) {
            foreach ($this->attachment as $file) {
                $header .= "\r\n--{$separator}\r\n";
                $header .= "Content-Type: " . $this->getMIMEType($file) . '; name="=?UTF-8?B?' . base64_encode(basename($file)) . '?="' . "\r\n";
                $header .= "Content-Transfer-Encoding: base64\r\n";
                $header .= 'Content-Disposition: attachment; filename="=?UTF-8?B?' . base64_encode(basename($file)) . '?="' . "\r\n\r\n";
                $header .= $this->readFile($file) . "\r\n";
            }
        }

        // 结束邮件数据发送
        $header .= "\r\n--{$separator}--\r\n.\r\n";

        $command[] = ["DATA\r\n", 354];
        $command[] = [$header, 250];
        $command[] = ["QUIT\r\n", 221];

        return $command;
    }

    /**
     * 发送命令到服务器
     *
     * @param string $command 命令
     * @param int $code 期望的响应码
     * @return bool
     */
    protected function sendCommand(string $command, int $code): bool
    {
        if ($this->debug) {
            echo "Send command: {$command}, expected code: {$code}\n";
        }

        try {
            if (fwrite($this->socket, $command)) {
                $data = trim(fread($this->socket, 1024));
                if ($this->debug) {
                    echo "Response: {$data}\n";
                }
                if (preg_match("/^{$code}/", $data)) {
                    return true;
                } else {
                    $this->errorMessage = $data;
                    return false;
                }
            } else {
                $this->errorMessage = "命令发送失败: {$command}";
                return false;
            }
        } catch (Exception $e) {
            $this->errorMessage = "错误: " . $e->getMessage();
            return false;
        }
    }

    /**
     * 读取文件内容并返回 base64 编码
     *
     * @param string $file 文件路径
     * @return string
     */
    protected function readFile(string $file): string
    {
        return base64_encode(file_get_contents($file));
    }

    /**
     * 获取文件的 MIME 类型
     *
     * @param string $file 文件路径
     * @return string
     */
    protected function getMIMEType(string $file): string
    {
        return mime_content_type($file);
    }

    /**
     * 建立到服务器的连接
     *
     * @param bool $ssl 是否使用 SSL
     * @return bool
     */
    protected function socket(bool $ssl = true): bool
    {
        if ($ssl && !extension_loaded('openssl')) {
            $this->errorMessage = '服务器未启用 OpenSSL 扩展，无法使用加密方式发送邮件！';
            return false;
        }

        $remoteAddr = ($ssl ? "ssl://" : "tcp://") . $this->sendServer . ":" . $this->port;
        $this->socket = stream_socket_client($remoteAddr, $errno, $errstr, 30);

        if (!$this->socket) {
            $this->errorMessage = "连接失败: {$errstr}";
            return false;
        }

        stream_set_blocking($this->socket, true);
        $response = fread($this->socket, 1024);

        if (!preg_match("/^220/", $response)) {
            $this->errorMessage = "服务器响应错误: {$response}";
            return false;
        }

        return true;
    }

    /**
     * 关闭连接
     *
     * @return bool
     */
    protected function close(): bool
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
            return true;
        }
        $this->errorMessage = "没有可关闭的资源";
        return false;
    }
}