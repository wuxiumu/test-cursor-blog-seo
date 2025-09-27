<?php
/**
 * 日志工具类
 * 统一的日志记录和管理
 */

class Logger {
    private static $instance = null;
    private $config;
    private $logFile;
    private $maxSize;

    /**
     * 日志级别
     */
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';

    /**
     * 获取单例实例
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 构造函数
     */
    private function __construct() {
        $this->config = ConfigManager::getInstance();
        $this->logFile = $this->config->get('logging.file', __DIR__ . '/../debug.log');
        $this->maxSize = $this->config->get('logging.max_size', 10485760); // 10MB
    }

    /**
     * 记录日志
     * @param string $level 日志级别
     * @param string $message 日志消息
     * @param array $context 上下文信息
     */
    public function log($level, $message, $context = []) {
        if (!$this->config->get('logging.enabled', true)) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}";

        if (!empty($context)) {
            $logMessage .= " " . json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        $logMessage .= "\n";

        // 检查文件大小，如果超过限制则轮转
        if (file_exists($this->logFile) && filesize($this->logFile) > $this->maxSize) {
            $this->rotateLog();
        }

        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * 记录调试信息
     */
    public function debug($message, $context = []) {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * 记录信息
     */
    public function info($message, $context = []) {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * 记录警告
     */
    public function warning($message, $context = []) {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * 记录错误
     */
    public function error($message, $context = []) {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * 轮转日志文件
     */
    private function rotateLog() {
        if (file_exists($this->logFile)) {
            $backupFile = $this->logFile . '.' . date('Y-m-d-H-i-s');
            rename($this->logFile, $backupFile);
        }
    }

    /**
     * 清理旧日志文件
     * @param int $days 保留天数
     */
    public function cleanOldLogs($days = 7) {
        $logDir = dirname($this->logFile);
        $pattern = $this->logFile . '.*';

        $files = glob($pattern);
        $cutoffTime = time() - ($days * 24 * 60 * 60);

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }
    }

    /**
     * 获取日志内容
     * @param int $lines 返回的行数
     * @return string
     */
    public function getLogContent($lines = 100) {
        if (!file_exists($this->logFile)) {
            return '';
        }

        $content = file_get_contents($this->logFile);
        $logLines = explode("\n", $content);
        $logLines = array_filter($logLines);

        if (count($logLines) > $lines) {
            $logLines = array_slice($logLines, -$lines);
        }

        return implode("\n", $logLines);
    }
}
