# API 文档

## 二维码生成 API

### 1. 生成二维码

**接口地址：** `GET /qr_generator.php`

**请求参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| action | string | 是 | 固定值：generate |
| text | string | 是 | 二维码内容 |
| size | int | 否 | 图片尺寸，默认200 |
| logo | string | 否 | logo图片路径或URL |

**请求示例：**
```
GET /qr_generator.php?action=generate&text=https://example.com&size=200&logo=./logo.png
```

**响应格式：**
```json
{
    "success": true,
    "qr_code": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...",
    "text": "https://example.com",
    "size": 200,
    "has_logo": true
}
```

**错误响应：**
```json
{
    "success": false,
    "error": "错误信息"
}
```

### 2. 检查资源

**接口地址：** `GET /qr_generator.php`

**请求参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| action | string | 是 | 固定值：check_resource |
| path | string | 是 | 资源路径或URL |

**请求示例：**
```
GET /qr_generator.php?action=check_resource&path=./logo.png
```

**响应格式：**
```json
{
    "success": true,
    "exists": true,
    "info": {
        "size": 1024,
        "type": "image/png"
    }
}
```

## 微信支付 API

### 1. 创建支付订单

**接口地址：** `POST /pay.php`

**请求参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| body | string | 是 | 商品名称 |
| total_fee | int | 是 | 支付金额（分） |
| product_id | string | 是 | 商品ID |

**请求示例：**
```javascript
fetch('/pay.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'body=测试商品&total_fee=100&product_id=PRODUCT_123'
})
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

**错误响应：**
```json
{
    "success": false,
    "error": "错误信息"
}
```

### 2. 支付回调

**接口地址：** `POST /notify.php`

**说明：** 微信支付成功后的回调接口，由微信服务器调用。

**回调数据格式：**
```xml
<xml>
    <appid>wx930c3d66db6cb49d</appid>
    <bank_type>CFT</bank_type>
    <cash_fee>1</cash_fee>
    <fee_type>CNY</fee_type>
    <is_subscribe>Y</is_subscribe>
    <mch_id>1710407126</mch_id>
    <nonce_str>random_string</nonce_str>
    <openid>oUpF8uMuAJO_M2pxb1Q9zNjWeS6o</openid>
    <out_trade_no>ORDER_1234567890_1234</out_trade_no>
    <result_code>SUCCESS</result_code>
    <return_code>SUCCESS</return_code>
    <sign>signature</sign>
    <time_end>20141030133540</time_end>
    <total_fee>1</total_fee>
    <trade_type>NATIVE</trade_type>
    <transaction_id>1004400740201410300005762</transaction_id>
</xml>
```

**响应格式：**
```xml
<xml>
    <return_code><![CDATA[SUCCESS]]></return_code>
    <return_msg><![CDATA[OK]]></return_msg>
</xml>
```

## 工具类 API

### ConfigManager

配置管理器，提供统一的配置访问接口。

**方法：**

#### get($key, $default = null)
获取配置值。

**参数：**
- `$key`: 配置键，支持点号分隔的嵌套键
- `$default`: 默认值

**示例：**
```php
$config = ConfigManager::getInstance();
$appId = $config->get('wechat.app_id');
$timeout = $config->get('qr_code.apis.0.timeout', 10);
```

#### set($key, $value)
设置配置值。

**参数：**
- `$key`: 配置键
- `$value`: 配置值

**示例：**
```php
$config = ConfigManager::getInstance();
$config->set('wechat.app_id', 'new_app_id');
```

#### has($key)
检查配置是否存在。

**参数：**
- `$key`: 配置键

**返回值：** boolean

**示例：**
```php
if ($config->has('wechat.app_id')) {
    // 配置存在
}
```

### Logger

日志工具，支持分级日志记录。

**方法：**

#### log($level, $message, $context = [])
记录日志。

**参数：**
- `$level`: 日志级别（DEBUG, INFO, WARNING, ERROR）
- `$message`: 日志消息
- `$context`: 上下文信息

**示例：**
```php
$logger = Logger::getInstance();
$logger->log(Logger::INFO, '用户登录', ['user_id' => 123]);
```

#### debug($message, $context = [])
记录调试信息。

**示例：**
```php
$logger->debug('调试信息', ['data' => $data]);
```

#### info($message, $context = [])
记录信息。

**示例：**
```php
$logger->info('操作成功', ['action' => 'create_order']);
```

#### error($message, $context = [])
记录错误。

**示例：**
```php
$logger->error('操作失败', ['error' => $exception->getMessage()]);
```

### QRCodeGenerator

二维码生成器，支持多API备用策略。

**方法：**

#### generate($text, $size = null, $options = [])
生成二维码。

**参数：**
- `$text`: 二维码内容
- `$size`: 图片尺寸
- `$options`: 选项数组

**返回值：** string|false

**示例：**
```php
$generator = new QRCodeGenerator();
$qrCode = $generator->generate('https://example.com', 200, [
    'logo' => './logo.png'
]);
```

#### checkResource($path)
检查资源是否存在。

**参数：**
- `$path`: 资源路径

**返回值：** boolean

**示例：**
```php
if ($generator->checkResource('./logo.png')) {
    // 资源存在
}
```

#### getAPIStatus()
获取API状态。

**返回值：** array

**示例：**
```php
$status = $generator->getAPIStatus();
foreach ($status as $api) {
    echo $api['name'] . ': ' . ($api['enabled'] ? '启用' : '禁用');
}
```

### WeChatPay

微信支付工具，处理支付相关功能。

**方法：**

#### createOrder($orderData)
创建支付订单。

**参数：**
- `$orderData`: 订单数据数组

**返回值：** array

**示例：**
```php
$wechatPay = new WeChatPay();
$result = $wechatPay->createOrder([
    'body' => '商品名称',
    'total_fee' => 100,
    'product_id' => 'PRODUCT_123'
]);
```

#### verifyNotify($data)
验证支付回调。

**参数：**
- `$data`: 回调数据

**返回值：** array

**示例：**
```php
$result = $wechatPay->verifyNotify($callbackData);
if ($result['success']) {
    // 验证成功
}
```

#### queryOrder($orderNo)
查询订单状态。

**参数：**
- `$orderNo`: 订单号

**返回值：** array

**示例：**
```php
$result = $wechatPay->queryOrder('ORDER_1234567890_1234');
if ($result['success']) {
    echo '订单状态: ' . $result['status'];
}
```

## 错误码说明

### 通用错误码

| 错误码 | 说明 |
|--------|------|
| 1001 | 参数错误 |
| 1002 | 配置错误 |
| 1003 | 网络错误 |
| 1004 | 服务器错误 |

### 二维码生成错误码

| 错误码 | 说明 |
|--------|------|
| 2001 | 二维码内容不能为空 |
| 2002 | 无法从任何API获取二维码数据 |
| 2003 | 图片处理失败 |
| 2004 | Logo加载失败 |

### 微信支付错误码

| 错误码 | 说明 |
|--------|------|
| 3001 | 支付金额不能小于1分 |
| 3002 | 微信支付API返回错误 |
| 3003 | HTTP请求失败 |
| 3004 | 订单创建失败 |

## 响应状态码

### HTTP状态码

| 状态码 | 说明 |
|--------|------|
| 200 | 请求成功 |
| 400 | 请求参数错误 |
| 401 | 未授权 |
| 403 | 禁止访问 |
| 404 | 资源不存在 |
| 500 | 服务器内部错误 |

### 业务状态码

| 状态码 | 说明 |
|--------|------|
| success: true | 操作成功 |
| success: false | 操作失败 |

## 请求限制

### 频率限制
- 二维码生成：每分钟最多100次
- 支付订单创建：每分钟最多50次
- 资源检查：每分钟最多200次

### 大小限制
- 二维码内容：最大2048字符
- Logo文件：最大2MB
- 请求体：最大10MB

## 安全说明

### 认证
- 所有API都需要在HTTPS环境下使用
- 支付回调需要验证签名
- 敏感配置信息需要加密存储

### 防护
- 防止SQL注入
- 防止XSS攻击
- 防止CSRF攻击
- 限制请求频率

### 日志
- 记录所有API调用
- 记录错误信息
- 定期清理日志文件
