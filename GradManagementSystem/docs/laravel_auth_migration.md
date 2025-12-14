# Laravel 注册/登录（迁移建议）

当前项目后端是“原生 PHP + 多个 api/*.php”。如果课程/大纲要求“必须用 Laravel 做注册与登录”，建议采用“渐进迁移”：

1) 保留现有 Vue 前端不动  
2) 新建一个 Laravel API（专门负责注册/登录/鉴权与角色）  
3) 逐步把现有 PHP API 迁移成 Laravel Controller

## 1. 安装前置（Windows）

- 安装 PHP 8.2+（建议用 XAMPP/WAMP 自带版本也行，但 Laravel 更推荐独立 PHP）
- 安装 Composer
-（可选）安装 Node.js（你已有前端）

## 2. 创建 Laravel 项目（建议在仓库根目录）

在 `GradManagementSystem` 目录下执行：

```bash
composer create-project laravel/laravel backend-laravel
```

## 3. 配置数据库

编辑 `backend-laravel/.env`：

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=grad_system
DB_USERNAME=root
DB_PASSWORD=Lqw84441669.
```

执行迁移：

```bash
php artisan migrate
```

## 4. 角色设计（建议）

最简单：在 `users` 表保留 `role` 字段：

- `student`
- `faculty`（包含 GPD / Major Professor）
- `registrar` / `admin`

然后在 Laravel 中用 middleware 做路由级权限控制。

## 5. API 端点（最小集合）

- `POST /api/register`
- `POST /api/login`
- `GET /api/me`
- `POST /api/logout`

前端用 cookie session 或 Laravel Sanctum 都可以（SPA 推荐 Sanctum）。

