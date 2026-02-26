# lsky-docker

基于 Lsky Pro 代码整理的 Docker 部署版本，适合在服务器上快速搭建图床服务。

## 环境要求
- Docker 24+
- Docker Compose（`docker compose` 可用）
- 建议服务器内存 2GB+

## 项目内的 Docker 文件
- `Dockerfile`
- `docker-compose.yml`
- `docker/app/entrypoint.sh`
- `docker/app/php.ini`
- `docker/nginx/Dockerfile`
- `docker/nginx/default.conf`
- `.env.docker.example`

## Docker 部署教程

### 1. 拉取代码并进入目录
```bash
git clone git@github.com:bearhero123/lsky-docker.git
cd lsky-docker
```

### 2. 初始化环境变量
```bash
cp .env.docker.example .env
```

编辑 `.env`，至少修改以下配置：
- `APP_URL`：你的域名或服务器 IP
- `DB_PASSWORD`：MySQL 普通用户密码
- `DB_ROOT_PASSWORD`：MySQL root 密码
- `HTTP_PORT`：对外访问端口（默认 `8080`）

### 3. 构建并启动容器
```bash
docker compose up -d --build
```

### 4. 首次初始化 Laravel
```bash
docker compose exec app php artisan key:generate --force
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize
```

### 5. 访问系统
- `http://服务器IP:HTTP_PORT`
- 示例：`http://1.2.3.4:8080`

## 常用运维命令

查看日志：
```bash
docker compose logs -f app
docker compose logs -f nginx
docker compose logs -f mysql
```

重启服务：
```bash
docker compose restart
```

停止服务：
```bash
docker compose down
```

更新代码后重建：
```bash
git pull
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize
```

## 数据持久化
`docker-compose.yml` 已配置以下持久化卷：
- `lsky_mysql`：MySQL 数据
- `lsky_storage`：Laravel `storage` 与上传文件
- `lsky_thumbnails`：缩略图目录
- `lsky_redis`：Redis 数据

只要不手动删除卷，重建容器不会丢数据。

## 生产建议
- 使用反向代理（Nginx Proxy Manager/Caddy/Nginx）配置 HTTPS。
- 配置服务器防火墙，只开放必要端口（如 `80/443`）。
- 定期备份 MySQL 与 `lsky_storage` 卷。

## Troubleshooting

### Where to check logs
```bash
# App/PHP-FPM + Laravel logs (recommended first)
docker compose logs -f app

# Nginx access/error output
docker compose logs -f nginx

# Laravel daily log file inside app container
docker compose exec app sh -lc "ls -lah storage/logs && tail -n 200 storage/logs/laravel-$(date +%F).log"
```

### "Upload failed, service error, try again later"
1. Open browser DevTools, check `POST /upload` response JSON and copy `message`.
2. Run:
```bash
docker compose logs --tail=200 app
docker compose exec app sh -lc "tail -n 200 storage/logs/laravel-$(date +%F).log"
```
3. Common causes:
- file exceeds `upload_max_filesize` / `post_max_size`
- upload suffix not allowed by current group
- no available storage strategy in admin settings
- storage path permission issue

## HTTPS with Caddy

This repository includes `caddy` service in `docker-compose.override.yml`.

1. Free ports `80/443` on host (stop system nginx/apache first).
2. Edit `docker/caddy/Caddyfile`:
- set your domain (example: `img.bearhero.shop`)
- set a valid email for ACME notifications
3. Start Caddy:
```bash
docker compose up -d caddy
docker compose logs -f caddy
```
4. After certificate is issued, set local strategy URL to:
- `https://img.bearhero.shop/i`
5. Update APP_URL and clear cache:
```bash
sed -i 's#^APP_URL=.*#APP_URL=https://img.bearhero.shop#' .env
docker compose exec app php artisan optimize:clear
docker compose restart app nginx
```
