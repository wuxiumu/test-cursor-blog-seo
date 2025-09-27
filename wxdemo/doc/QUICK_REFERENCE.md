# index.php 快速参考

## 🚀 快速开始

### 访问地址
- **支付页面**: `index.php` 或 `index.php?action=pay`
- **管理面板**: `index.php?action=admin`
- **系统测试**: `index.php?action=test`

## 📋 API 接口

### 支付接口
```bash
# 创建支付订单
POST /index.php?action=pay
Content-Type: application/x-www-form-urlencoded

body=商品名称&total_fee=100&product_id=PRODUCT_123
```

### 支付回调
```bash
# 微信支付回调
POST /index.php?action=notify
Content-Type: application/xml
```

### 二维码接口
```bash
# 生成二维码
GET /index.php?action=qr&sub=generate&text=内容&size=200

# 检查资源
GET /index.php?action=qr&sub=check&path=资源路径

# API状态
GET /index.php?action=qr&sub=status
```

### 管理接口
```bash
# 仪表板
GET /index.php?action=admin&sub=dashboard

# 配置管理
GET /index.php?action=admin&sub=config

# 日志查看
GET /index.php?action=admin&sub=logs
```

## 🔧 配置说明

### 主要配置项
```php
// config/app.php
'wechat' => [
    'app_id' => 'your_app_id',
    'mch_id' => 'your_mch_id',
    'key' => 'your_key',
    'notify_url' => 'https://your-domain.com/index.php?action=notify',
    'sandbox' => true
],
'qr_code' => [
    'default_size' => 200,
    'default_logo' => './favicon.ico'
],
'debug' => [
    'enabled' => true,
    'show_errors' => true
]
```

## 📝 响应格式

### 支付成功响应
```json
{
    "success": true,
    "code_url": "weixin://wxpay/bizpayurl?pr=xxx",
    "prepay_id": "wx123456789",
    "order_no": "ORDER_1234567890_1234"
}
```

### 二维码生成响应
```json
{
    "success": true,
    "qr_code": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...",
    "text": "二维码内容",
    "size": 200
}
```

### 错误响应
```json
{
    "success": false,
    "error": "错误信息"
}
```

## 🛠️ 部署步骤

### 1. 文件部署
```bash
# 上传文件
scp index.php user@server:/var/www/html/

# 设置权限
chmod 644 index.php
```

### 2. 配置设置
```bash
# 编辑配置
vim config/app.php

# 设置环境变量
export WECHAT_APP_ID="your_app_id"
export WECHAT_MCH_ID="your_mch_id"
export WECHAT_KEY="your_key"
```

### 3. 测试验证
```bash
# 测试功能
curl http://localhost/index.php?action=test

# 检查管理面板
curl http://localhost/index.php?action=admin
```

## 🔍 故障排除

### 常见问题

**问题**: 页面无法访问
```bash
# 检查文件权限
ls -la index.php

# 检查Web服务器
systemctl status apache2
```

**问题**: 支付功能失败
```bash
# 检查配置
curl http://localhost/index.php?action=admin&sub=config

# 查看日志
tail -f debug.log
```

**问题**: 二维码生成失败
```bash
# 测试二维码API
curl "http://localhost/index.php?action=qr&sub=generate&text=test&size=200"

# 检查网络
ping api.qrserver.com
```

### 调试方法

**启用调试模式**:
```php
// config/app.php
'debug' => [
    'enabled' => true,
    'show_errors' => true,
    'log_requests' => true
]
```

**查看日志**:
```bash
# 实时查看日志
tail -f debug.log

# 搜索错误
grep "ERROR" debug.log
```

## 📊 功能测试

### 测试命令
```bash
# 测试配置
curl http://localhost/index.php?action=admin&sub=config

# 测试二维码
curl "http://localhost/index.php?action=qr&sub=generate&text=https://example.com&size=200"

# 测试支付
curl -X POST http://localhost/index.php?action=pay \
  -d "body=测试商品&total_fee=1&product_id=TEST_123"
```

### 测试页面
访问 `index.php?action=test` 进行完整功能测试

## 🔐 安全建议

### 1. 文件权限
```bash
# 设置正确权限
chmod 644 index.php
chmod 600 config/app.php
chmod 666 debug.log
```

### 2. 访问控制
```apache
# Apache .htaccess
<Files "config/app.php">
    Order allow,deny
    Deny from all
</Files>
```

### 3. 环境变量
```bash
# 使用环境变量存储敏感信息
export WECHAT_APP_ID="your_app_id"
export WECHAT_MCH_ID="your_mch_id"
export WECHAT_KEY="your_key"
```

## 📈 性能优化

### 1. PHP配置
```ini
; php.ini
memory_limit = 256M
max_execution_time = 30
opcache.enable = 1
```

### 2. Web服务器
```nginx
# Nginx配置
location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
}
```

### 3. 日志轮转
```bash
# 设置日志轮转
cat > /etc/logrotate.d/wxdemo << EOF
/var/www/html/debug.log {
    daily
    rotate 7
    compress
    delaycompress
}
EOF
```

## 🎯 最佳实践

### 1. 代码组织
- 功能模块化
- 函数职责单一
- 代码复用性高

### 2. 错误处理
- 统一异常处理
- 详细错误日志
- 用户友好提示

### 3. 性能优化
- 配置缓存
- 日志优化
- 资源管理

### 4. 安全防护
- 输入验证
- 权限控制
- 敏感信息保护

## 📞 技术支持

### 日志位置
- 应用日志: `debug.log`
- Web服务器日志: `/var/log/apache2/` 或 `/var/log/nginx/`
- PHP错误日志: `/var/log/php_errors.log`

### 配置文件
- 主配置: `config/app.php`
- 环境变量: `.env` 文件
- Web服务器配置: Apache/Nginx 配置文件

### 监控指标
- 响应时间
- 错误率
- 内存使用
- 磁盘空间

---

**注意**: 这是一个最小化的单文件系统，所有功能都通过 `index.php` 提供。确保在生产环境中正确配置安全设置和性能优化。
