# Nginx推奨設定

## orka-asp2システム用Nginx設定例

```nginx
# /etc/nginx/sites-available/orka-asp2

server {
    listen 80;
    listen [::]:80;
    server_name example.com www.example.com;

    # HTTPSにリダイレクト
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name example.com www.example.com;

    root /var/www/orka-asp2;
    index index.php index.html;

    # SSL証明書（Let's Encrypt）
    ssl_certificate /etc/letsencrypt/live/example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/example.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # セキュリティヘッダー
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # アクセスログ
    access_log /var/log/nginx/orka-asp2_access.log;
    error_log /var/log/nginx/orka-asp2_error.log;

    # PHP処理
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;

        # タイムアウト設定（AFAD連携考慮）
        fastcgi_read_timeout 60s;
        fastcgi_send_timeout 60s;
    }

    # 静的ファイル
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # セキュリティ: 設定ファイルへのアクセス拒否
    location ~ ^/(custom|tdb|logs|database)/ {
        deny all;
        return 403;
    }

    location ~ /\.(?!well-known).* {
        deny all;
        return 403;
    }

    # AFAD連携: link.php と add.php の最適化
    location ~ ^/(link|add|continue)\.php$ {
        try_files $uri =404;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;

        # キャッシュ無効化（トラッキングのため）
        add_header Cache-Control "no-cache, no-store, must-revalidate";
        add_header Pragma "no-cache";
        add_header Expires "0";

        # タイムアウト延長
        fastcgi_read_timeout 120s;
    }
}
```

## PHP-FPM設定

```ini
; /etc/php/8.1/fpm/pool.d/www.conf

[www]
user = www-data
group = www-data
listen = /run/php/php8.1-fpm.sock
listen.owner = www-data
listen.group = www-data

pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 10
pm.max_requests = 500

; タイムアウト設定
request_terminate_timeout = 60s

; メモリ制限
php_admin_value[memory_limit] = 256M
```

## セットアップコマンド

```bash
# Nginxインストール
sudo apt install nginx

# 設定ファイル配置
sudo nano /etc/nginx/sites-available/orka-asp2

# シンボリックリンク作成
sudo ln -s /etc/nginx/sites-available/orka-asp2 /etc/nginx/sites-enabled/

# 設定テスト
sudo nginx -t

# Nginx再起動
sudo systemctl restart nginx

# SSL証明書取得（Let's Encrypt）
sudo certbot --nginx -d example.com -d www.example.com
```

## パフォーマンスチューニング

### Nginx全体設定 (/etc/nginx/nginx.conf)

```nginx
user www-data;
worker_processes auto;
worker_rlimit_nofile 65535;

events {
    worker_connections 4096;
    use epoll;
    multi_accept on;
}

http {
    # 基本設定
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;

    # gzip圧縮
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;

    # ファイルディスクリプタキャッシュ
    open_file_cache max=10000 inactive=30s;
    open_file_cache_valid 60s;
    open_file_cache_min_uses 2;
    open_file_cache_errors on;

    include /etc/nginx/conf.d/*.conf;
    include /etc/nginx/sites-enabled/*;
}
```

## 監視設定

### Nginx ステータス有効化

```nginx
# /etc/nginx/sites-available/status

server {
    listen 127.0.0.1:8080;
    server_name localhost;

    location /nginx_status {
        stub_status on;
        access_log off;
        allow 127.0.0.1;
        deny all;
    }
}
```

確認コマンド:
```bash
curl http://127.0.0.1:8080/nginx_status
```
