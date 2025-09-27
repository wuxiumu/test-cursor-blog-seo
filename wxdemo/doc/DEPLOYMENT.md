# 部署指南

## 快速部署

### 1. 环境准备

**系统要求：**
- PHP 7.4 或更高版本
- Web服务器（Apache/Nginx）
- SSL证书（生产环境必需）
- Composer

**PHP扩展要求：**
- GD扩展（图片处理）
- cURL扩展（HTTP请求）
- JSON扩展（数据处理）
- XML扩展（微信支付）

### 2. 文件部署

```bash
# 1. 上传文件到服务器
scp -r wxdemo/ user@server:/var/www/html/

# 2. 设置文件权限
cd /var/www/html/wxdemo
chmod 755 tool/
chmod 644 config/app.php
chmod 666 debug.log

# 3. 安装依赖
composer install
```

### 3. 配置设置

**编辑配置文件：**
```bash
vim config/app.php
```

**设置环境变量：**
```bash
export WECHAT_APP_ID="your_app_id"
export WECHAT_MCH_ID="your_mch_id"
export WECHAT_KEY="your_key"
export WECHAT_NOTIFY_URL="https://your-domain.com/notify_new.php"
```

### 4. 证书配置

**上传微信支付证书：**
```bash
# 创建证书目录
mkdir -p 1710407126_20250927_cert/

# 上传证书文件
scp apiclient_cert.pem user@server:/var/www/html/wxdemo/1710407126_20250927_cert/
scp apiclient_key.pem user@server:/var/www/html/wxdemo/1710407126_20250927_cert/

# 设置证书权限
chmod 600 1710407126_20250927_cert/*.pem
```

### 5. 测试部署

**访问测试页面：**
```
https://your-domain.com/test_new_structure.php
```

**测试功能：**
- 配置系统测试
- 二维码生成测试
- 微信支付测试
- 日志系统测试

## 生产环境部署

### 1. 安全配置

**文件权限：**
```bash
# 设置正确的文件权限
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod 600 config/app.php
chmod 666 debug.log
```

**Web服务器配置：**
```apache
# Apache .htaccess
<Files "config/app.php">
    Order allow,deny
    Deny from all
</Files>

<Files "debug.log">
    Order allow,deny
    Deny from all
</Files>
```

### 2. 性能优化

**PHP配置：**
```ini
; php.ini
memory_limit = 256M
max_execution_time = 30
upload_max_filesize = 10M
post_max_size = 10M
```

**Web服务器优化：**
```nginx
# Nginx配置
location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

### 3. 监控配置

**日志监控：**
```bash
# 设置日志轮转
cat > /etc/logrotate.d/wxdemo << EOF
/var/www/html/wxdemo/debug.log {
    daily
    rotate 7
    compress
    delaycompress
    missingok
    notifempty
}
EOF
```

**性能监控：**
```bash
# 监控脚本
#!/bin/bash
# monitor.sh
while true; do
    echo "$(date): $(ps aux | grep php | wc -l) PHP processes"
    sleep 60
done
```

## 故障排除

### 1. 常见问题

**问题：配置加载失败**
```bash
# 检查配置文件
php -l config/app.php

# 检查文件权限
ls -la config/app.php
```

**问题：二维码生成失败**
```bash
# 检查GD扩展
php -m | grep gd

# 检查网络连接
curl -I https://api.qrserver.com/v1/create-qr-code/
```

**问题：微信支付失败**
```bash
# 检查证书文件
ls -la 1710407126_20250927_cert/

# 检查配置
grep -r "sandbox" config/
```

### 2. 调试方法

**启用调试模式：**
```php
// config/app.php
'debug' => [
    'enabled' => true,
    'show_errors' => true,
    'log_requests' => true,
],
```

**查看日志：**
```bash
# 实时查看日志
tail -f debug.log

# 搜索错误
grep "ERROR" debug.log
```

### 3. 性能调优

**PHP优化：**
```ini
; 启用OPcache
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
```

**数据库优化：**
```sql
-- 如果使用数据库
CREATE INDEX idx_order_no ON orders(out_trade_no);
CREATE INDEX idx_created_at ON orders(created_at);
```

## 备份和恢复

### 1. 数据备份

**配置文件备份：**
```bash
# 备份配置
cp config/app.php config/app.php.backup.$(date +%Y%m%d)

# 备份证书
tar -czf cert_backup_$(date +%Y%m%d).tar.gz 1710407126_20250927_cert/
```

**日志备份：**
```bash
# 备份日志
cp debug.log debug.log.backup.$(date +%Y%m%d)
```

### 2. 恢复操作

**从备份恢复：**
```bash
# 恢复配置
cp config/app.php.backup.20240101 config/app.php

# 恢复证书
tar -xzf cert_backup_20240101.tar.gz
```

## 更新和维护

### 1. 代码更新

**更新步骤：**
```bash
# 1. 备份当前版本
cp -r wxdemo wxdemo_backup_$(date +%Y%m%d)

# 2. 更新代码
git pull origin main

# 3. 更新依赖
composer update

# 4. 测试功能
php test_new_structure.php
```

### 2. 配置更新

**动态更新配置：**
```php
// 在代码中更新配置
$config = ConfigManager::getInstance();
$config->set('wechat.app_id', 'new_app_id');
$config->reload();
```

### 3. 监控维护

**定期检查：**
```bash
# 检查磁盘空间
df -h

# 检查内存使用
free -h

# 检查日志大小
du -sh debug.log
```

## 安全建议

### 1. 访问控制

**限制访问：**
```apache
# 禁止直接访问敏感文件
<Files "*.log">
    Order allow,deny
    Deny from all
</Files>

<Files "config/*">
    Order allow,deny
    Deny from all
</Files>
```

### 2. 数据加密

**敏感信息加密：**
```php
// 加密存储敏感配置
$encrypted = openssl_encrypt($sensitive_data, 'AES-256-CBC', $key);
```

### 3. 定期审计

**安全检查：**
```bash
# 检查文件权限
find . -type f -perm 777

# 检查可疑文件
find . -name "*.php" -exec grep -l "eval\|base64_decode" {} \;
```

## 扩展部署

### 1. 负载均衡

**Nginx配置：**
```nginx
upstream wxdemo {
    server 127.0.0.1:8001;
    server 127.0.0.1:8002;
    server 127.0.0.1:8003;
}

server {
    listen 80;
    location / {
        proxy_pass http://wxdemo;
    }
}
```

### 2. 容器化部署

**Dockerfile：**
```dockerfile
FROM php:7.4-fpm
RUN docker-php-ext-install gd curl
COPY . /var/www/html
WORKDIR /var/www/html
RUN composer install
```

### 3. 云部署

**AWS部署：**
```yaml
# docker-compose.yml
version: '3'
services:
  web:
    image: wxdemo:latest
    ports:
      - "80:80"
    environment:
      - WECHAT_APP_ID=${WECHAT_APP_ID}
      - WECHAT_MCH_ID=${WECHAT_MCH_ID}
      - WECHAT_KEY=${WECHAT_KEY}
```

## 总结

本部署指南涵盖了从开发环境到生产环境的完整部署流程，包括：

1. **环境准备**: 系统要求和依赖安装
2. **文件部署**: 代码上传和权限设置
3. **配置设置**: 环境变量和配置文件
4. **安全配置**: 访问控制和数据保护
5. **性能优化**: 缓存和监控配置
6. **故障排除**: 常见问题和解决方案
7. **备份恢复**: 数据保护和灾难恢复
8. **更新维护**: 版本管理和系统维护

遵循本指南可以确保系统安全、稳定、高效地运行。
