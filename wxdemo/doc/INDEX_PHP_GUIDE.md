# index.php 使用说明文档

## 概述

`index.php` 是微信支付二维码生成系统的统一入口文件，采用路由模式处理所有功能请求。通过一个文件提供完整的支付、二维码生成、管理等功能。

## 文件结构

```
index.php (639行)
├── 工具类加载
├── 配置初始化
├── 路由处理
├── 支付处理函数
├── 回调处理函数
├── 二维码处理函数
├── 管理面板函数
├── 测试页面函数
└── 页面显示函数
```

## 核心功能

### 1. 路由系统

**路由规则：**
- `?action=pay` - 支付页面和支付处理
- `?action=notify` - 支付回调处理
- `?action=qr` - 二维码生成和管理
- `?action=admin` - 管理面板
- `?action=test` - 系统测试
- 默认访问 - 显示支付页面

### 2. 支付功能

**支付页面访问：**
```
GET /index.php
GET /index.php?action=pay
```

**支付订单创建：**
```
POST /index.php?action=pay
Content-Type: application/x-www-form-urlencoded

body=商品名称&total_fee=100&product_id=PRODUCT_123
```

**响应格式：**
```json
{
    "success": true,
    "code_url": "weixin://wxpay/bizpayurl?pr=xxx",
    "prepay_id": "wx123456789",
    "order_no": "ORDER_1234567890_1234"
}
```

### 3. 支付回调

**回调地址：**
```
POST /index.php?action=notify
Content-Type: application/xml
```

**处理流程：**
1. 接收微信支付回调数据
2. 解析XML格式数据
3. 验证回调签名
4. 处理支付成功逻辑
5. 返回XML响应

**响应格式：**
```xml
<xml>
    <return_code><![CDATA[SUCCESS]]></return_code>
    <return_msg><![CDATA[OK]]></return_msg>
</xml>
```

### 4. 二维码功能

**生成二维码：**
```
GET /index.php?action=qr&sub=generate&text=内容&size=200&logo=logo路径
```

**检查资源：**
```
GET /index.php?action=qr&sub=check&path=资源路径
```

**API状态：**
```
GET /index.php?action=qr&sub=status
```

**响应格式：**
```json
{
    "success": true,
    "qr_code": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...",
    "text": "二维码内容",
    "size": 200
}
```

### 5. 管理面板

**仪表板：**
```
GET /index.php?action=admin&sub=dashboard
```

**配置管理：**
```
GET /index.php?action=admin&sub=config
```

**日志查看：**
```
GET /index.php?action=admin&sub=logs
```

### 6. 系统测试

**测试页面：**
```
GET /index.php?action=test
```

**测试功能：**
- 配置系统测试
- 二维码生成测试
- 支付功能测试

## 核心函数说明

### 1. 路由处理函数

#### `handlePayRequest()`
处理支付相关请求
- **POST请求**: 创建支付订单
- **GET请求**: 显示支付页面

#### `handleNotifyRequest()`
处理微信支付回调
- 解析XML数据
- 验证回调签名
- 处理支付成功逻辑

#### `handleQRRequest()`
处理二维码相关请求
- `sub=generate`: 生成二维码
- `sub=check`: 检查资源
- `sub=status`: 获取API状态

#### `handleAdminRequest()`
处理管理面板请求
- `sub=dashboard`: 仪表板
- `sub=config`: 配置管理
- `sub=logs`: 日志查看

#### `handleTestRequest()`
处理测试页面请求

### 2. 页面显示函数

#### `showPayPage()`
显示支付页面
- 支付表单
- 二维码显示
- 支付处理逻辑

#### `showAdminDashboard()`
显示管理面板
- 系统状态
- 配置信息
- 导航菜单

#### `showConfigPage()`
显示配置管理页面
- 配置信息展示
- JSON格式显示

#### `showLogsPage()`
显示日志查看页面
- 日志内容展示
- 实时日志查看

#### `showTestPage()`
显示系统测试页面
- 功能测试按钮
- 测试结果展示

## 配置依赖

### 1. 工具类依赖
```php
require_once __DIR__ . '/tool/autoload.php';
```

**依赖的工具类：**
- `ConfigManager` - 配置管理
- `Logger` - 日志记录
- `QRCodeGenerator` - 二维码生成
- `WeChatPay` - 微信支付

### 2. 配置文件
```php
config/app.php
```

**主要配置项：**
- `wechat.*` - 微信支付配置
- `qr_code.*` - 二维码配置
- `logging.*` - 日志配置
- `debug.*` - 调试配置

## 错误处理

### 1. 异常捕获
```php
try {
    // 业务逻辑
} catch (Exception $e) {
    $logger->error('请求处理失败', [
        'action' => $action,
        'method' => $method,
        'error' => $e->getMessage()
    ]);

    if ($config->get('debug.enabled', false)) {
        echo json_encode(['error' => $e->getMessage()]);
    } else {
        echo json_encode(['error' => '系统错误']);
    }
}
```

### 2. 错误响应
- **调试模式**: 显示详细错误信息
- **生产模式**: 显示通用错误信息
- **日志记录**: 记录所有错误到日志文件

## 安全特性

### 1. 输入验证
- 参数类型检查
- 数据长度限制
- 特殊字符过滤

### 2. 错误处理
- 统一异常处理
- 敏感信息保护
- 错误日志记录

### 3. 访问控制
- 路由权限控制
- 管理功能保护
- 敏感操作验证

## 性能优化

### 1. 配置缓存
- 配置信息缓存
- 减少文件读取
- 提高响应速度

### 2. 日志优化
- 分级日志记录
- 日志轮转机制
- 减少磁盘IO

### 3. 错误处理
- 快速失败机制
- 资源及时释放
- 内存使用优化

## 使用示例

### 1. 基本使用

**访问支付页面：**
```bash
curl http://localhost/index.php
```

**创建支付订单：**
```bash
curl -X POST http://localhost/index.php?action=pay \
  -d "body=测试商品&total_fee=100&product_id=PRODUCT_123"
```

**生成二维码：**
```bash
curl "http://localhost/index.php?action=qr&sub=generate&text=https://example.com&size=200"
```

### 2. 管理功能

**访问管理面板：**
```bash
curl http://localhost/index.php?action=admin
```

**查看配置：**
```bash
curl http://localhost/index.php?action=admin&sub=config
```

**查看日志：**
```bash
curl http://localhost/index.php?action=admin&sub=logs
```

### 3. 测试功能

**运行系统测试：**
```bash
curl http://localhost/index.php?action=test
```

## 部署说明

### 1. 文件部署
```bash
# 上传文件
scp index.php user@server:/var/www/html/

# 设置权限
chmod 644 index.php
```

### 2. 配置设置
```bash
# 编辑配置文件
vim config/app.php

# 设置环境变量
export WECHAT_APP_ID="your_app_id"
export WECHAT_MCH_ID="your_mch_id"
export WECHAT_KEY="your_key"
```

### 3. 测试验证
```bash
# 访问测试页面
curl http://localhost/index.php?action=test

# 检查功能
curl http://localhost/index.php?action=admin
```

## 故障排除

### 1. 常见问题

**问题：页面无法访问**
- 检查文件权限
- 检查Web服务器配置
- 查看错误日志

**问题：支付功能失败**
- 检查微信支付配置
- 验证证书文件
- 查看支付日志

**问题：二维码生成失败**
- 检查网络连接
- 验证API配置
- 查看生成日志

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
tail -f debug.log
```

**检查配置：**
```bash
curl http://localhost/index.php?action=admin&sub=config
```

## 扩展开发

### 1. 添加新路由
```php
case 'new_action':
    handleNewAction();
    break;
```

### 2. 添加新功能
```php
function handleNewAction() {
    // 新功能逻辑
}
```

### 3. 添加新页面
```php
function showNewPage() {
    // 新页面HTML
}
```

## 最佳实践

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

## 总结

`index.php` 作为系统的统一入口，提供了：

1. **完整功能**: 支付、二维码、管理、测试
2. **统一接口**: 所有功能通过路由访问
3. **模块化设计**: 功能分离，易于维护
4. **配置驱动**: 所有参数可配置
5. **错误处理**: 完善的异常处理机制
6. **安全防护**: 多层安全保护
7. **性能优化**: 高效的资源使用

通过这个单一入口文件，您可以获得一个功能完整、易于维护的微信支付二维码生成系统。
