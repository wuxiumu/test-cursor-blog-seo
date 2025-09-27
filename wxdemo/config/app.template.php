<?php
/**
 * 应用配置模板文件
 * 微信支付二维码生成系统
 *
 * 使用说明：
 * 1. 复制此文件为 app.php
 * 2. 根据提示填写您的实际配置信息
 * 3. 上传必要的证书文件到指定目录
 * 4. 运行配置验证确保设置正确
 */

return [
    // ===========================================
    // 微信支付配置 - 必填项
    // ===========================================
    'wechat' => [
        // 微信小程序/公众号的AppID
        // 获取方式：微信公众平台 -> 开发 -> 基本配置 -> AppID(应用ID)
        'app_id' => 'YOUR_WECHAT_APP_ID', // 请替换为您的微信AppID

        // 微信支付商户号
        // 获取方式：微信支付商户平台 -> 账户中心 -> 商户信息 -> 商户号
        'mch_id' => 'YOUR_MERCHANT_ID', // 请替换为您的商户号

        // 微信支付API密钥
        // 获取方式：微信支付商户平台 -> 账户中心 -> API安全 -> 设置API密钥
        // 注意：此密钥用于签名，请妥善保管，不要泄露
        'key' => 'YOUR_API_KEY', // 请替换为您的API密钥

        // 微信支付V2密钥（如果使用V2接口）
        'v2_secret_key' => 'YOUR_V2_SECRET_KEY', // 请替换为您的V2密钥

        // 支付结果通知URL
        // 用户支付成功后，微信会向此URL发送支付结果通知
        // 请确保此URL可以被微信服务器访问
        'notify_url' => 'https://yourdomain.com/notify.php', // 请替换为您的域名

        // 是否使用沙箱环境（测试环境）
        // true: 使用沙箱环境，用于测试
        // false: 使用正式环境，用于生产
        'sandbox' => true, // 生产环境请设置为 false

        // HTTP请求配置
        'http' => [
            'timeout' => 30, // 请求超时时间（秒）
            'retry' => 1,    // 重试次数
        ],
    ],

    // ===========================================
    // 二维码生成配置
    // ===========================================
    'qr_code' => [
        // 默认二维码尺寸（像素）
        'default_size' => 200,

        // 默认Logo配置
        'default_logo' => './favicon.ico', // Logo文件路径
        'logo_size_ratio' => 0.2, // Logo大小为二维码的20%

        // 二维码样式配置
        'style' => [
            'background_color' => '#ffffff', // 背景色
            'foreground_color' => '#000000', // 前景色
            'border' => true,                // 是否显示边框
            'border_size' => 10,            // 边框大小
            'border_color' => '#e1e5e9',    // 边框颜色
            'corner_radius' => 8,           // 圆角半径
            'shadow' => true,               // 是否显示阴影
            'shadow_color' => 'rgba(0,0,0,0.1)', // 阴影颜色
            'shadow_offset' => [0, 4],      // 阴影偏移
            'shadow_blur' => 8,             // 阴影模糊
        ],

        // 在线API配置（用于生成二维码）
        'apis' => [
            [
                'name' => 'QR Server',
                'url' => 'https://api.qrserver.com/v1/create-qr-code/',
                'params' => ['data' => '', 'size' => '200x200'],
                'priority' => 1,    // 优先级，数字越小优先级越高
                'enabled' => true,  // 是否启用
                'timeout' => 10,    // 超时时间
            ],
            [
                'name' => 'Google Charts',
                'url' => 'https://chart.googleapis.com/chart',
                'params' => ['chs' => '200x200', 'cht' => 'qr', 'chl' => ''],
                'priority' => 2,
                'enabled' => true,
                'timeout' => 10,
            ],
            [
                'name' => 'QR Server Alt',
                'url' => 'https://qr-server.com/api/v1/create-qr-code/',
                'params' => ['data' => '', 'size' => '200x200'],
                'priority' => 3,
                'enabled' => true,
                'timeout' => 10,
            ],
        ],

        // 备用策略配置
        'fallback' => [
            'enable_online_api' => true,    // 启用在线API
            'enable_payment_link' => true,  // 启用支付链接
            'max_retries' => 3,            // 最大重试次数
        ],
    ],

    // ===========================================
    // 日志配置
    // ===========================================
    'logging' => [
        'enabled' => true,                    // 是否启用日志
        'level' => 'INFO',                    // 日志级别：DEBUG, INFO, WARNING, ERROR
        'file' => __DIR__ . '/../debug.log', // 日志文件路径
        'max_size' => 10485760,              // 最大文件大小（字节），10MB
    ],

    // ===========================================
    // 调试配置
    // ===========================================
    'debug' => [
        'enabled' => true,        // 是否启用调试模式
        'show_errors' => true,    // 是否显示错误信息
        'log_requests' => true,   // 是否记录请求日志
    ],

    // ===========================================
    // 安全配置
    // ===========================================
    'security' => [
        // 允许的IP地址（用于限制管理功能访问）
        'allowed_ips' => [
            '127.0.0.1',    // 本地访问
            '::1',          // IPv6本地访问
            // '192.168.1.100', // 添加您的IP地址
        ],

        // 管理密码（用于访问管理功能）
        'admin_password' => 'admin123', // 请修改为强密码

        // 是否启用HTTPS
        'force_https' => false, // 生产环境建议设置为 true
    ],
];
