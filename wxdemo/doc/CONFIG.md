# 配置说明文档

## 配置文件结构

系统使用 `config/app.php` 作为主配置文件，采用PHP数组格式，支持嵌套配置。

## 配置项说明

### 1. 微信支付配置 (wechat)

```php
'wechat' => [
    'app_id'        => 'wx930c3d66db6cb49d',    // 微信应用ID
    'mch_id'        => '1710407126',            // 商户号
    'key'           => 'J6yxQ39LmApX2VrjHK7fbwRPZqs4Dc1W',  // API密钥
    'v2_secret_key' => 'J6yxQ39LmApX2VrjHK7fbwRPZqs4Dc1W',  // V2 API密钥
    'notify_url'    => 'https://repair.gua.hk/notify.php',   // 支付回调URL
    'sandbox'       => true,                    // 是否使用沙箱环境
    'http' => [
        'timeout' => 30,                        // 请求超时时间（秒）
        'retry' => 1,                          // 重试次数
    ],
],
```

**配置说明：**
- `app_id`: 微信开放平台应用ID
- `mch_id`: 微信支付商户号
- `key`: 微信支付API密钥（32位字符串）
- `v2_secret_key`: V2版本API密钥
- `notify_url`: 支付成功后的回调地址
- `sandbox`: 开发环境设为true，生产环境设为false
- `http.timeout`: HTTP请求超时时间
- `http.retry`: 请求失败时的重试次数

### 2. 二维码生成配置 (qr_code)

```php
'qr_code' => [
    'default_size' => 200,                      // 默认图片尺寸
    'default_logo' => './favicon.ico',         // 默认logo路径
    'logo_size_ratio' => 0.2,                 // logo大小比例（相对于二维码）

    'style' => [
        'background_color' => '#ffffff',        // 背景色
        'foreground_color' => '#000000',       // 前景色
        'border' => true,                      // 是否显示边框
        'border_size' => 10,                   // 边框大小
        'border_color' => '#e1e5e9',           // 边框颜色
        'corner_radius' => 8,                  // 圆角半径
        'shadow' => true,                      // 是否显示阴影
        'shadow_color' => 'rgba(0,0,0,0.1)',  // 阴影颜色
        'shadow_offset' => [0, 4],            // 阴影偏移
        'shadow_blur' => 8,                    // 阴影模糊度
    ],

    'apis' => [
        [
            'name' => 'QR Server',             // API名称
            'url' => 'https://api.qrserver.com/v1/create-qr-code/',  // API地址
            'params' => ['data' => '', 'size' => '200x200'],        // 默认参数
            'priority' => 1,                   // 优先级（数字越小优先级越高）
            'enabled' => true,                 // 是否启用
            'timeout' => 10,                   // 超时时间
        ],
        // ... 更多API配置
    ],

    'fallback' => [
        'enable_online_api' => true,           // 是否启用在线API备用
        'enable_payment_link' => true,         // 是否启用支付链接备用
        'max_retries' => 3,                   // 最大重试次数
    ],
],
```

**配置说明：**
- `default_size`: 二维码默认尺寸（像素）
- `default_logo`: 默认logo文件路径
- `logo_size_ratio`: logo大小占二维码的比例
- `style.*`: 二维码样式配置
- `apis`: 二维码生成API列表
- `fallback`: 备用策略配置

### 3. 日志配置 (logging)

```php
'logging' => [
    'enabled' => true,                          // 是否启用日志
    'level' => 'INFO',                         // 日志级别
    'file' => __DIR__ . '/../debug.log',      // 日志文件路径
    'max_size' => 10485760,                    // 最大文件大小（字节）
],
```

**配置说明：**
- `enabled`: 是否启用日志记录
- `level`: 日志级别（DEBUG, INFO, WARNING, ERROR）
- `file`: 日志文件路径
- `max_size`: 日志文件最大大小，超过会自动轮转

### 4. 调试配置 (debug)

```php
'debug' => [
    'enabled' => true,                         // 是否启用调试模式
    'show_errors' => true,                    // 是否显示错误信息
    'log_requests' => true,                   // 是否记录请求日志
],
```

**配置说明：**
- `enabled`: 是否启用调试模式
- `show_errors`: 是否在页面上显示错误信息
- `log_requests`: 是否记录所有HTTP请求

## 环境变量配置

系统支持通过环境变量覆盖配置文件中的设置：

```bash
# 微信支付配置
export WECHAT_APP_ID="your_app_id"
export WECHAT_MCH_ID="your_mch_id"
export WECHAT_KEY="your_key"
export WECHAT_NOTIFY_URL="https://your-domain.com/notify.php"
export WECHAT_SANDBOX="true"

# 日志配置
export LOG_LEVEL="INFO"
export LOG_FILE="/path/to/logfile.log"

# 调试配置
export DEBUG_ENABLED="true"
export DEBUG_SHOW_ERRORS="true"
```

## 配置优先级

配置的优先级从高到低为：
1. 环境变量
2. 配置文件
3. 默认值

## 配置验证

系统启动时会自动验证配置的有效性：

### 必需配置项
- `wechat.app_id`
- `wechat.mch_id`
- `wechat.key`
- `wechat.notify_url`

### 配置格式验证
- `wechat.app_id`: 必须以"wx"开头
- `wechat.mch_id`: 必须为数字
- `wechat.key`: 必须为32位字符串
- `qr_code.default_size`: 必须为正整数
- `logging.level`: 必须是有效的日志级别

## 动态配置

系统支持运行时动态修改配置：

```php
$config = ConfigManager::getInstance();

// 获取配置
$appId = $config->get('wechat.app_id');

// 设置配置
$config->set('wechat.app_id', 'new_app_id');

// 检查配置是否存在
if ($config->has('wechat.app_id')) {
    // 配置存在
}

// 重新加载配置
$config->reload();
```

## 配置缓存

为了提高性能，系统会缓存配置信息：

### 缓存机制
- 配置文件只在首次加载时读取
- 配置修改后需要调用 `reload()` 方法
- 环境变量会覆盖缓存配置

### 缓存清理
```php
$config = ConfigManager::getInstance();
$config->reload(); // 重新加载配置
```

## 配置模板

### 开发环境配置
```php
return [
    'wechat' => [
        'sandbox' => true,
        'notify_url' => 'https://dev.example.com/notify.php',
    ],
    'debug' => [
        'enabled' => true,
        'show_errors' => true,
    ],
    'logging' => [
        'level' => 'DEBUG',
    ],
];
```

### 生产环境配置
```php
return [
    'wechat' => [
        'sandbox' => false,
        'notify_url' => 'https://example.com/notify.php',
    ],
    'debug' => [
        'enabled' => false,
        'show_errors' => false,
    ],
    'logging' => [
        'level' => 'ERROR',
    ],
];
```

## 配置安全

### 敏感信息保护
- API密钥等敏感信息不应直接写在配置文件中
- 建议使用环境变量或加密存储
- 配置文件不应提交到版本控制系统

### 权限设置
```bash
# 设置配置文件权限
chmod 600 config/app.php

# 设置日志文件权限
chmod 644 debug.log
```

## 配置备份

### 备份策略
- 定期备份配置文件
- 使用版本控制管理配置变更
- 保留配置变更历史

### 恢复方法
```bash
# 从备份恢复配置
cp config/app.php.backup config/app.php

# 重新加载配置
php -r "ConfigManager::getInstance()->reload();"
```

## 故障排除

### 常见配置问题

1. **配置加载失败**
   - 检查文件路径是否正确
   - 检查文件权限
   - 检查PHP语法错误

2. **环境变量不生效**
   - 检查环境变量名称是否正确
   - 检查环境变量值格式
   - 重启Web服务器

3. **配置验证失败**
   - 检查必需配置项是否完整
   - 检查配置格式是否正确
   - 查看错误日志

### 调试方法
```php
// 检查配置是否正确加载
$config = ConfigManager::getInstance();
var_dump($config->all());

// 检查特定配置项
echo $config->get('wechat.app_id');

// 检查配置是否存在
var_dump($config->has('wechat.app_id'));
```
