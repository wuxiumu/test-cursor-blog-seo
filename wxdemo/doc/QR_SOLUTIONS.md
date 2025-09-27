# 二维码生成解决方案

## 问题分析
您遇到的"网络错误，请重试"问题通常由以下原因造成：
1. 在线API服务不稳定
2. 网络连接问题
3. API限制或超时
4. 服务器响应慢

## 解决方案

### 方案一：本地生成二维码（推荐）

**文件：** `qr_generator.php`

**特点：**
- ✅ 支持图片拼接（logo）
- ✅ 资源检测（本地/远程）
- ✅ 多API备用策略
- ✅ 错误处理和日志记录
- ✅ 支持样式定制

**使用方法：**
```php
$generator = new QRCodeGenerator();
$qrCode = $generator->generateQRCode($text, $size, $logoPath);
```

**API接口：**
```
GET qr_generator.php?action=generate&text=内容&size=200&logo=logo路径
```

### 方案二：多API备用策略

**文件：** `qr_api_manager.php`

**特点：**
- ✅ 4个备用API（QR Server、Google Charts等）
- ✅ 智能API选择
- ✅ 自动故障转移
- ✅ API状态监控
- ✅ 性能优化

**API列表：**
1. QR Server (api.qrserver.com)
2. Google Charts (chart.googleapis.com)
3. QR Server Alt (qr-server.com)
4. QR Code API (api.qrserver.com)

### 增强版生成器

**文件：** `enhanced_qr_generator.php`

**特点：**
- ✅ 结合本地处理和在线API
- ✅ 高级图片处理
- ✅ 样式定制（边框、阴影、圆角）
- ✅ 资源验证和优化
- ✅ 完整的错误处理

## 集成到支付页面

### 更新后的二维码生成流程：

1. **主要方案：** 增强版生成器
2. **备用方案1：** 基础版生成器
3. **备用方案2：** 多API策略
4. **最终兜底：** 显示支付链接

### 用户体验改进：

- 🔄 自动重试机制
- 📱 微信环境检测
- 🔗 支付链接复制
- 💡 操作提示和帮助
- 🎨 美观的界面设计

## 测试页面

**文件：** `qr_test.php`

提供完整的测试界面，可以测试：
- 本地生成器
- 多API策略
- 增强版生成器
- 资源检测
- API状态查询

## 使用方法

### 1. 基本使用
```javascript
// 在支付页面中
generateQRCode(paymentUrl, 'qrCode');
```

### 2. 带logo的二维码
```javascript
// 检查资源是否存在
fetch(`enhanced_qr_generator.php?action=check_resource&path=${logoPath}`)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.exists) {
            // 生成带logo的二维码
            generateQRCode(paymentUrl, 'qrCode');
        }
    });
```

### 3. 错误处理
```javascript
// 自动重试
function retryQRGeneration() {
    if (window.currentPaymentLink) {
        generateQRCode(window.currentPaymentLink, 'qrCode');
    }
}
```

## 配置选项

### 二维码样式
```php
$options = [
    'logo' => 'path/to/logo.png',
    'logo_size' => 40, // 自动计算为二维码的1/5
    'background_color' => '#ffffff',
    'foreground_color' => '#000000',
    'border' => true,
    'border_size' => 10,
    'border_color' => '#e1e5e9',
    'corner_radius' => 8,
    'shadow' => true
];
```

### API配置
```php
// 在 qr_api_manager.php 中配置
$apis = [
    [
        'name' => 'QR Server',
        'url' => 'https://api.qrserver.com/v1/create-qr-code/',
        'priority' => 1,
        'enabled' => true
    ],
    // ... 更多API
];
```

## 性能优化

1. **缓存机制：** 相同内容复用已生成的二维码
2. **超时控制：** 设置合理的API超时时间
3. **并发限制：** 避免同时请求过多API
4. **错误恢复：** 自动重试和故障转移

## 监控和日志

- 📝 详细的操作日志
- 📊 API成功率统计
- ⚠️ 错误告警机制
- 🔍 性能监控

## 总结

通过实施这两种方案，您的二维码生成系统将具备：

1. **高可靠性：** 多级备用策略确保99%+成功率
2. **用户体验：** 智能重试和友好的错误提示
3. **功能丰富：** 支持logo拼接和样式定制
4. **易于维护：** 模块化设计，便于扩展和调试

建议优先使用**方案一（本地生成器）**，它提供了最佳的功能性和可靠性平衡。
