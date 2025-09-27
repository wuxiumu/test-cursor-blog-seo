# index.php API 接口文档

## 概述

`index.php` 提供统一的RESTful API接口，支持支付、二维码生成、管理等功能。

## 基础信息

- **基础URL**: `http://your-domain.com/index.php`
- **内容类型**: `application/json` / `application/xml`
- **字符编码**: `UTF-8`

## 路由参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| action | string | 是 | 操作类型 |
| sub | string | 否 | 子操作类型 |

## API 接口

### 1. 支付接口

#### 1.1 创建支付订单

**接口地址**: `POST /index.php?action=pay`

**请求参数**:
```json
{
    "body": "商品名称",
    "total_fee": 100,
    "product_id": "PRODUCT_123"
}
```

**请求示例**:
```bash
curl -X POST "http://localhost/index.php?action=pay" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "body=测试商品&total_fee=100&product_id=PRODUCT_123"
```

**响应格式**:
```json
{
    "success": true,
    "code_url": "weixin://wxpay/bizpayurl?pr=xxx",
    "prepay_id": "wx123456789",
    "order_no": "ORDER_1234567890_1234"
}
```

**错误响应**:
```json
{
    "success": false,
    "error": "错误信息"
}
```

#### 1.2 支付回调

**接口地址**: `POST /index.php?action=notify`

**请求格式**: XML
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

**响应格式**: XML
```xml
<xml>
    <return_code><![CDATA[SUCCESS]]></return_code>
    <return_msg><![CDATA[OK]]></return_msg>
</xml>
```

### 2. 二维码接口

#### 2.1 生成二维码

**接口地址**: `GET /index.php?action=qr&sub=generate`

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| text | string | 是 | 二维码内容 |
| size | int | 否 | 图片尺寸，默认200 |
| logo | string | 否 | logo图片路径 |

**请求示例**:
```bash
curl "http://localhost/index.php?action=qr&sub=generate&text=https://example.com&size=200&logo=./logo.png"
```

**响应格式**:
```json
{
    "success": true,
    "qr_code": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...",
    "text": "https://example.com",
    "size": 200
}
```

#### 2.2 检查资源

**接口地址**: `GET /index.php?action=qr&sub=check`

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| path | string | 是 | 资源路径 |

**请求示例**:
```bash
curl "http://localhost/index.php?action=qr&sub=check&path=./logo.png"
```

**响应格式**:
```json
{
    "success": true,
    "exists": true
}
```

#### 2.3 获取API状态

**接口地址**: `GET /index.php?action=qr&sub=status`

**请求示例**:
```bash
curl "http://localhost/index.php?action=qr&sub=status"
```

**响应格式**:
```json
{
    "success": true,
    "apis": [
        {
            "name": "QR Server",
            "enabled": true,
            "priority": 1,
            "url": "https://api.qrserver.com/v1/create-qr-code/"
        }
    ]
}
```

### 3. 管理接口

#### 3.1 仪表板

**接口地址**: `GET /index.php?action=admin&sub=dashboard`

**请求示例**:
```bash
curl "http://localhost/index.php?action=admin&sub=dashboard"
```

**响应格式**: HTML页面

#### 3.2 配置管理

**接口地址**: `GET /index.php?action=admin&sub=config`

**请求示例**:
```bash
curl "http://localhost/index.php?action=admin&sub=config"
```

**响应格式**: HTML页面，显示JSON配置

#### 3.3 日志查看

**接口地址**: `GET /index.php?action=admin&sub=logs`

**请求示例**:
```bash
curl "http://localhost/index.php?action=admin&sub=logs"
```

**响应格式**: HTML页面，显示日志内容

### 4. 测试接口

#### 4.1 系统测试

**接口地址**: `GET /index.php?action=test`

**请求示例**:
```bash
curl "http://localhost/index.php?action=test"
```

**响应格式**: HTML页面，包含测试功能

## 错误码说明

### HTTP状态码

| 状态码 | 说明 |
|--------|------|
| 200 | 请求成功 |
| 400 | 请求参数错误 |
| 401 | 未授权 |
| 403 | 禁止访问 |
| 404 | 资源不存在 |
| 500 | 服务器内部错误 |

### 业务错误码

| 错误码 | 说明 |
|--------|------|
| success: false | 操作失败 |
| success: true | 操作成功 |

### 常见错误

| 错误信息 | 说明 | 解决方案 |
|----------|------|----------|
| 二维码内容不能为空 | text参数为空 | 提供有效的text参数 |
| 支付金额不能小于1分 | total_fee参数无效 | 提供大于0的金额 |
| 无法从任何API获取二维码数据 | 二维码生成失败 | 检查网络连接和API配置 |
| 微信支付API返回错误 | 支付接口调用失败 | 检查微信支付配置 |

## 请求限制

### 频率限制
- 二维码生成: 每分钟最多100次
- 支付订单创建: 每分钟最多50次
- 资源检查: 每分钟最多200次

### 大小限制
- 二维码内容: 最大2048字符
- Logo文件: 最大2MB
- 请求体: 最大10MB

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

## 使用示例

### JavaScript示例

```javascript
// 创建支付订单
async function createPayment() {
    const formData = new FormData();
    formData.append('body', '测试商品');
    formData.append('total_fee', '100');
    formData.append('product_id', 'PRODUCT_123');

    const response = await fetch('/index.php?action=pay', {
        method: 'POST',
        body: formData
    });

    const result = await response.json();
    console.log(result);
}

// 生成二维码
async function generateQRCode(text) {
    const response = await fetch(`/index.php?action=qr&sub=generate&text=${encodeURIComponent(text)}&size=200`);
    const result = await response.json();

    if (result.success) {
        const img = document.createElement('img');
        img.src = result.qr_code;
        document.body.appendChild(img);
    }
}
```

### PHP示例

```php
// 创建支付订单
$data = [
    'body' => '测试商品',
    'total_fee' => 100,
    'product_id' => 'PRODUCT_123'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/index.php?action=pay');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
```

### Python示例

```python
import requests

# 创建支付订单
data = {
    'body': '测试商品',
    'total_fee': 100,
    'product_id': 'PRODUCT_123'
}

response = requests.post('http://localhost/index.php?action=pay', data=data)
result = response.json()
print(result)

# 生成二维码
response = requests.get('http://localhost/index.php?action=qr&sub=generate&text=https://example.com&size=200')
result = response.json()
print(result)
```

## 调试方法

### 启用调试模式
```php
// config/app.php
'debug' => [
    'enabled' => true,
    'show_errors' => true,
    'log_requests' => true
]
```

### 查看日志
```bash
# 实时查看日志
tail -f debug.log

# 搜索错误
grep "ERROR" debug.log

# 搜索特定请求
grep "action=pay" debug.log
```

### 测试接口
```bash
# 测试所有功能
curl "http://localhost/index.php?action=test"

# 测试特定接口
curl -X POST "http://localhost/index.php?action=pay" \
  -d "body=测试&total_fee=1&product_id=TEST"
```

## 总结

`index.php` 提供了完整的RESTful API接口，支持：

1. **支付功能**: 创建订单、处理回调
2. **二维码功能**: 生成、检查、状态查询
3. **管理功能**: 仪表板、配置、日志
4. **测试功能**: 系统测试、功能验证

通过统一的API接口，可以轻松集成到各种应用系统中。
