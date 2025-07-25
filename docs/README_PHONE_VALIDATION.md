# 手机号验证系统

## 功能特性

-   ✨ **并发验证**: 使用 Saloon 并发池，速度提升显著（从 60 秒降至 2.5 秒）
-   🔄 **自动定时**: 每小时自动执行验证任务
-   🎯 **智能过滤**: 默认只验证正常状态，可强制验证所有状态
-   📊 **实时统计**: 显示验证进度和详细结果

## 命令用法

```bash
# 基本验证（仅正常状态）
./vendor/bin/sail artisan phone:validate

# 强制验证所有状态
./vendor/bin/sail artisan phone:validate --force

# 自定义并发数
./vendor/bin/sail artisan phone:validate --concurrency=20
```

## 定时任务

系统已配置每小时自动执行验证：

-   时间：每小时整点执行
-   防重复：`withoutOverlapping()` 防止重复执行
-   后台运行：`runInBackground()` 不阻塞其他任务
-   日志记录：输出保存到 `storage/logs/phone-validation.log`

## 性能优化

### 并发处理

-   使用 Saloon 的 pool 功能实现并发请求
-   默认并发数：10（可配置）
-   批次处理：每批 500 条记录，避免内存过载

### 架构组件

-   `PhoneConnector`: Saloon 连接器，支持池功能
-   `PoolablePhoneRequest`: 可池化的验证请求
-   `PhoneValidationService`: 核心业务逻辑
-   `ValidatePhoneNumbersCommand`: 命令行接口

## 验证逻辑

1. 获取需要验证的手机号（必须有`phone_address`）
2. 创建并发池，批量发送 HTTP 请求
3. 检查响应：
    - 错误码 10022 → 标记为失效
    - 成功响应 → 保持有效
    - 网络异常 → 记录错误，不更改状态
4. 更新数据库状态和记录日志

## 性能对比

| 方式     | 1000 个请求耗时 | 性能提升  |
| -------- | --------------- | --------- |
| 串行验证 | ~60 秒          | 基准      |
| 并发验证 | ~2.5 秒         | **24 倍** |

## 安全特性

-   防止任务重叠执行
-   完整的错误处理和日志记录
-   内存优化，支持大数据量处理
-   可配置的并发控制
