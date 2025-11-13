# Azure OAuth2 客户端 Bundle

[English](README.md) | [中文](README.zh-CN.md)

一个用于将 Azure OAuth2 身份验证集成到您的应用程序中的 Symfony Bundle。此包为 Microsoft Azure AD 提供完整的 OAuth2 客户端实现，包括用户身份验证、令牌管理和 管理界面。

## 功能特性

- **完整的 OAuth2 流程**: 完整实现 Azure OAuth2 授权码流程
- **PKCE 支持**: 支持证明密钥代码交换 (PKCE) 以增强安全性
- **令牌管理**: 自动令牌刷新和过期处理
- **用户管理**: 在数据库中存储和管理 Azure AD 用户
- **管理界面**: EasyAdmin 集成用于管理 OAuth2 配置和用户
- **安全性**: 状态参数验证和安全令牌存储
- **缓存**: 内置缓存支持以提升性能
- **日志记录**: 全面的日志记录用于调试和监控

## 安装

```bash
composer require tourze/azure-oauth2-client-bundle
```

## 配置

### 1. Bundle 注册

```php
// config/bundles.php
return [
    // ...
    Tourze\AzureOAuth2ClientBundle\AzureOAuth2ClientBundle::class => ['all' => true],
];
```

### 2. 数据库架构

Bundle 将自动创建所需的数据库表：

- `azure_oauth2_config` - 存储 Azure AD 应用程序配置
- `azure_oauth2_user` - 存储已认证用户信息
- `azure_oauth2_state` - 存储 OAuth2 状态参数

### 3. 路由配置

将 Bundle 路由添加到您的路由配置中：

```yaml
# config/routes.yaml
azure_oauth2_client:
    resource: "@AzureOAuth2ClientBundle/Resources/config/routing.yaml"
```

## 使用方法

### 基本身份验证流程

```php
use Tourze\AzureOAuth2ClientBundle\Service\AzureOAuth2Service;

class AuthController extends AbstractController
{
    public function login(AzureOAuth2Service $oauth2Service): Response
    {
        $authorizationUrl = $oauth2Service->generateAuthorizationUrl();
        return $this->redirect($authorizationUrl);
    }

    public function callback(Request $request, AzureOAuth2Service $oauth2Service): Response
    {
        $code = $request->query->get('code');
        $state = $request->query->get('state');

        try {
            $user = $oauth2Service->handleCallback($code, $state);
            // 在您的系统中认证用户
            // ...
        } catch (AzureOAuth2Exception $e) {
            // 处理身份验证错误
        }
    }
}
```

### 使用 PKCE 的高级用法

```php
// 使用 PKCE 生成授权 URL
$authorizationUrl = $oauth2Service->generateAuthorizationUrl(
    sessionId: $session->getId(),
    codeChallenge: $codeChallenge,
    codeChallengeMethod: 'S256'
);
```

### 令牌管理

```php
// 刷新过期的令牌
$refreshedCount = $oauth2Service->refreshExpiredTokens();

// 刷新特定用户令牌
$success = $oauth2Service->refreshToken($userObjectId);

// 获取用户信息
$userInfo = $oauth2Service->fetchUserInfo($userObjectId, $forceRefresh = true);
```

### 配置管理

通过管理界面或以编程方式创建 Azure AD 应用程序配置：

```php
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;

$config = new AzureOAuth2Config();
$config->setClientId('your-client-id');
$config->setClientSecret('your-client-secret');
$config->setTenantId('your-tenant-id');
$config->setName('我的 Azure 应用');
$config->setScope('openid profile email User.Read');
$config->setRedirectUri('https://your-app.com/callback');

$entityManager->persist($config);
$entityManager->flush();
```

## Azure AD 应用程序设置

1. **注册应用程序**: 转到 Azure Portal → 应用注册 → 新注册
2. **配置重定向 URI**: 添加您的回调 URL（例如 `https://your-app.com/oauth2/callback`）
3. **创建客户端密钥**: 生成客户端密钥并安全存储
4. **设置 API 权限**: 添加所需的 Microsoft Graph 权限（例如 `User.Read`）
5. **获取详细信息**: 记下应用程序（客户端）ID 和目录（租户）ID

## API 端点

### 身份验证
- `GET /azure/oauth2/login` - 启动 OAuth2 登录流程
- `GET /azure/oauth2/callback` - OAuth2 回调处理器

### 管理（EasyAdmin 集成）
- `/admin/azure/oauth2/config` - 管理 OAuth2 配置
- `/admin/azure/oauth2/user` - 管理已认证用户
- `/admin/azure/oauth2/state` - 管理 OAuth2 状态

## 服务

### AzureOAuth2Service

用于处理 OAuth2 操作的主要服务：

- `generateAuthorizationUrl()` - 生成授权 URL
- `handleCallback()` - 处理 OAuth2 回调
- `refreshToken()` - 刷新用户令牌
- `fetchUserInfo()` - 从 Azure 获取用户信息
- `refreshExpiredTokens()` - 批量刷新过期令牌
- `cleanupExpiredStates()` - 清理过期的 OAuth2 状态

## 控制台命令

```bash
# 刷新过期令牌
php bin/console azure:oauth2:refresh-tokens

# 清理过期状态
php bin/console azure:oauth2:cleanup-states

# 获取用户信息
php bin/console azure:oauth2:fetch-user-info <user-object-id>
```

## 配置选项

```yaml
# config/packages/azure_oauth2_client.yaml
azure_oauth2_client:
    # OAuth2 请求的默认范围
    default_scope: 'openid profile email User.Read'

    # 用户信息的缓存 TTL（秒）
    cache_ttl: 3600

    # 令牌过期缓冲时间（秒）
    token_expiration_buffer: 300

    # 是否自动刷新过期令牌
    auto_refresh_tokens: true
```

## 安全注意事项

- **状态验证**: 通过状态参数验证自动进行 CSRF 保护
- **安全存储**: 令牌被加密并安全地存储在数据库中
- **PKCE 支持**: 在公共客户端中使用 PKCE 以获得额外安全性
- **令牌过期**: 自动令牌刷新和清理
- **HTTPS 要求**: 在生产环境中始终使用 HTTPS

## 错误处理

Bundle 为不同的错误场景提供特定的异常：

- `AzureOAuth2ConfigurationException` - 配置错误
- `AzureOAuth2ApiException` - Azure API 错误
- `AzureOAuth2RuntimeException` - 身份验证期间的运行时错误

## 日志记录

所有操作都使用 `azure_oauth2_client` 通道进行日志记录。在您的 Monolog 设置中配置日志记录：

```yaml
# config/packages/monolog.yaml
monolog:
    channels: ['azure_oauth2_client']
    handlers:
        azure_oauth2:
            type: rotating_file
            path: '%kernel.logs_dir%/azure_oauth2.log'
            level: info
            channels: ['azure_oauth2_client']
```

## 依赖项

- Symfony 7.3+
- Doctrine ORM
- EasyAdmin Bundle（用于管理界面）
- HttpClient Bundle
- Monolog Bundle

## 贡献

1. Fork 仓库
2. 创建功能分支
3. 进行更改
4. 为新功能添加测试
5. 提交 Pull Request

## 许可证

此 Bundle 使用 MIT 许可证授权。详细信息请参见 LICENSE 文件。

## 支持

如需问题和疑问：
- 在 GitHub 上创建 issue
- 查看文档
- 查看测试用例以获取使用示例