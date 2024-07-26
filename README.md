
### install 
在 宝塔中安装 docker 容器
#### 安装扩展包
docker run --rm \
-u "$(id -u):$(id -g)" \
-v "$(pwd):/var/www/html" \
-w /var/www/html \
laravelsail/php83-composer:latest \
composer install --ignore-platform-reqs

#### 修改配置 .env 文件

cp .env.example .env


1. 修改数据库配置

执行命令  ```vi .env``` 

修改 .env 文件中数据库配置，，APP_URL 设置为你的域名 APP_ENV 设置为 production
```bash
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=123456 
```

2. 修改 redis 配置

```bash
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```
3. 修改队列配置
```bash
QUEUE_CONNECTION=redis
```

### 配置域名

执行命令 cp nginx.conf.example nginx.conf
1. 将 server_name 修改为 项目解析域名, 
2. 修改 root 为容器中项目根目录（一般需要修改）
```bash
server {
    listen 80;
    server_name your_domain.com;
    root /var/www/html/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass laravel.test:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```
### 配置 supervisord
修改 supervisord.conf 文件, 将 horizon 设置为守护进程，并设置日志文件，一般不需要修改
```bash
[program:horizon]
command=php /var/www/html/artisan horizon
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/horizon.log
stopwaitsecs=3600
```

### 修改文件权限


### 启动项目
####  运行容器 docker-compose up -d --build 

#### 进入容器
docker-compose exec laravel.test bash 

#### 生成 key
php artisan key:generate
#### 创建数据库
php artisan migrate
#### 创建管理员账号
php artisan make:filament-user

请替换成你的项目地址

chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html/storage


#### 验证队列是否执行：
php artisan horizon:status

#### 后台地址 http://your_domain/admin/

请将 your_domain 替换成你的域名




