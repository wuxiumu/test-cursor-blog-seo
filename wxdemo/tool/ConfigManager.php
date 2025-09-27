<?php
/**
 * 配置管理器
 * 统一管理应用配置
 */

class ConfigManager {
    private static $config = null;
    private static $instance = null;

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
        $this->loadConfig();
    }

    /**
     * 加载配置文件
     */
    private function loadConfig() {
        if (self::$config === null) {
            $configFile = __DIR__ . '/../config/app.php';
            if (file_exists($configFile)) {
                self::$config = include $configFile;
            } else {
                // 如果配置文件不存在，使用默认配置
                self::$config = $this->getDefaultConfig();
            }
        }
    }

    /**
     * 获取默认配置
     */
    private function getDefaultConfig() {
        return [
            'wechat' => [
                'app_id' => '',
                'mch_id' => '',
                'key' => '',
                'v2_secret_key' => '',
                'notify_url' => '',
                'sandbox' => true,
                'http' => ['timeout' => 30, 'retry' => 1],
            ],
            'qr_code' => [
                'default_size' => 200,
                'default_logo' => './favicon.ico',
                'logo_size_ratio' => 0.2,
                'style' => [
                    'background_color' => '#ffffff',
                    'foreground_color' => '#000000',
                    'border' => true,
                    'border_size' => 10,
                    'border_color' => '#e1e5e9',
                    'corner_radius' => 8,
                    'shadow' => true,
                    'shadow_color' => 'rgba(0,0,0,0.1)',
                    'shadow_offset' => [0, 4],
                    'shadow_blur' => 8,
                ],
                'apis' => [
                    [
                        'name' => 'QR Server',
                        'url' => 'https://api.qrserver.com/v1/create-qr-code/',
                        'params' => ['data' => '', 'size' => '200x200'],
                        'priority' => 1,
                        'enabled' => true,
                        'timeout' => 10,
                    ],
                ],
                'fallback' => [
                    'enable_online_api' => true,
                    'enable_payment_link' => true,
                    'max_retries' => 3,
                ],
            ],
            'logging' => [
                'enabled' => true,
                'level' => 'INFO',
                'file' => __DIR__ . '/../debug.log',
                'max_size' => 10485760,
            ],
            'debug' => [
                'enabled' => true,
                'show_errors' => true,
                'log_requests' => true,
            ],
            'security' => [
                'allowed_ips' => ['127.0.0.1', '::1'],
                'admin_password' => 'admin123',
                'force_https' => false,
            ],
        ];
    }

    /**
     * 获取配置值
     * @param string $key 配置键，支持点号分隔的嵌套键
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get($key, $default = null) {
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (is_array($value) && array_key_exists($k, $value)) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * 设置配置值
     * @param string $key 配置键
     * @param mixed $value 配置值
     */
    public function set($key, $value) {
        $keys = explode('.', $key);
        $config = &self::$config;

        foreach ($keys as $k) {
            if (!is_array($config)) {
                $config = [];
            }
            if (!array_key_exists($k, $config)) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    /**
     * 检查配置是否存在
     * @param string $key 配置键
     * @return bool
     */
    public function has($key) {
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (is_array($value) && array_key_exists($k, $value)) {
                $value = $value[$k];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * 获取所有配置
     * @return array
     */
    public function all() {
        return self::$config;
    }

    /**
     * 重新加载配置
     */
    public function reload() {
        self::$config = null;
        $this->loadConfig();
    }
}
