# サーバーセットアップガイド

## orka-asp2 AFAD連携システム完全セットアップ手順

---

## 目次

1. [推奨サーバースペック](#1-推奨サーバースペック)
2. [サーバーセットアップ（Ubuntu 22.04）](#2-サーバーセットアップubuntu-2204)
3. [Supabaseセットアップ](#3-supabaseセットアップ)
4. [アプリケーションデプロイ](#4-アプリケーションデプロイ)
5. [SSL証明書設定](#5-ssl証明書設定)
6. [監視・運用設定](#6-監視運用設定)
7. [トラブルシューティング](#7-トラブルシューティング)

---

## 1. 推奨サーバースペック

### 最小要件

```
CPU: 2コア
RAM: 2GB
ストレージ: 30GB SSD
帯域: 100Mbps
OS: Ubuntu 22.04 LTS
```

### 推奨要件（本番環境）

```
CPU: 4コア
RAM: 4GB
ストレージ: 50GB SSD
帯域: 1Gbps
OS: Ubuntu 22.04 LTS
```

### トラフィック別推奨

| 成果/日 | CPU | RAM | 月額コスト |
|--------|-----|-----|----------|
| ～1,000 | 2コア | 2GB | 約1,700円 |
| 1,000～5,000 | 3コア | 4GB | 約3,600円 |
| 5,000～10,000 | 4コア | 8GB | 約7,200円 |
| 10,000～ | 8コア以上 | 16GB以上 | 要相談 |

---

## 2. サーバーセットアップ（Ubuntu 22.04）

### 2.1 初期セットアップ

```bash
# システムアップデート
sudo apt update && sudo apt upgrade -y

# 基本パッケージインストール
sudo apt install -y build-essential software-properties-common \
    curl wget git vim ufw htop

# タイムゾーン設定
sudo timedatectl set-timezone Asia/Tokyo

# ホスト名設定
sudo hostnamectl set-hostname orka-asp2
```

### 2.2 ファイアウォール設定

```bash
# UFWセットアップ
sudo ufw default deny incoming
sudo ufw default allow outgoing

# 必要なポートを開放
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS

# ファイアウォール有効化
sudo ufw enable
sudo ufw status
```

### 2.3 Nginxインストール

```bash
# Nginxインストール
sudo apt install -y nginx

# Nginx起動・自動起動設定
sudo systemctl start nginx
sudo systemctl enable nginx

# 動作確認
curl http://localhost
```

### 2.4 PHP 8.1インストール

```bash
# PHP 8.1とFPM、必須拡張をインストール
sudo apt install -y php8.1 php8.1-fpm php8.1-cli \
    php8.1-curl php8.1-pgsql php8.1-mbstring php8.1-json \
    php8.1-xml php8.1-zip php8.1-gd php8.1-opcache

# PHP-FPM起動・自動起動設定
sudo systemctl start php8.1-fpm
sudo systemctl enable php8.1-fpm

# PHPバージョン確認
php -v
```

### 2.5 PHP設定最適化

```bash
# php.ini編集
sudo nano /etc/php/8.1/fpm/php.ini
```

以下を設定:

```ini
; タイムゾーン
date.timezone = Asia/Tokyo

; メモリ制限
memory_limit = 256M

; 実行時間
max_execution_time = 60

; アップロード設定
upload_max_filesize = 10M
post_max_size = 12M

; エラー表示（本番環境ではOFF）
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log

; セッション設定
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
```

PHPログディレクトリ作成:

```bash
sudo mkdir -p /var/log/php
sudo chown www-data:www-data /var/log/php
```

PHP-FPM再起動:

```bash
sudo systemctl restart php8.1-fpm
```

### 2.6 Composer インストール（必要に応じて）

```bash
# Composer（PHPパッケージマネージャ）インストール
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

---

## 3. Supabaseセットアップ

### 3.1 Supabaseプロジェクト作成

1. https://supabase.com にアクセス
2. 新規プロジェクトを作成
3. プロジェクト名: `orka-asp2`
4. リージョン: `Northeast Asia (Tokyo)` を選択（推奨）
5. データベースパスワードを設定（強力なパスワードを使用）

### 3.2 データベース接続情報の取得

Supabaseダッシュボードから取得:

```
Settings → Database → Connection Info

Host: db.xxxxxxxxxxxxxx.supabase.co
Port: 5432
Database: postgres
User: postgres
Password: [作成時に設定したパスワード]
```

### 3.3 PostgreSQLクライアントインストール

```bash
# PostgreSQLクライアントツールインストール
sudo apt install -y postgresql-client

# 接続テスト
psql -h db.xxxxxxxxxxxxxx.supabase.co -U postgres -d postgres
```

### 3.4 データベーススキーマ作成

```bash
# データベース設計を適用
cd /path/to/orka-asp2

# スキーマ作成
psql -h db.xxxxxxxxxxxxxx.supabase.co -U postgres -d postgres \
    -f database/supabase/schema.sql

# 関数・トリガー作成
psql -h db.xxxxxxxxxxxxxx.supabase.co -U postgres -d postgres \
    -f database/supabase/functions/all.sql

# インデックス作成
psql -h db.xxxxxxxxxxxxxx.supabase.co -U postgres -d postgres \
    -f database/supabase/indexes.sql

# RLSポリシー設定
psql -h db.xxxxxxxxxxxxxx.supabase.co -U postgres -d postgres \
    -f database/supabase/policies/all.sql
```

---

## 4. アプリケーションデプロイ

### 4.1 アプリケーションディレクトリ作成

```bash
# Webルートディレクトリ作成
sudo mkdir -p /var/www/orka-asp2

# 所有者変更
sudo chown -R $USER:www-data /var/www/orka-asp2
```

### 4.2 ソースコードデプロイ

**方法1: Gitクローン**

```bash
cd /var/www
git clone https://github.com/IKEMENLTD/orka-asp2.git
cd orka-asp2
```

**方法2: ファイルアップロード**

```bash
# ローカルからサーバーへアップロード
scp -r /path/to/orka-asp2/* user@server:/var/www/orka-asp2/
```

### 4.3 ディレクトリパーミッション設定

```bash
cd /var/www/orka-asp2

# 書き込み可能ディレクトリ
sudo chmod 755 file/ logs/ tdb/ tdb/module/ report/
sudo chmod 666 file/* logs/*.log tdb/*.csv tdb/module/*.csv
sudo chown -R www-data:www-data file/ logs/ tdb/ report/

# セキュリティ: 設定ファイル保護
sudo chmod 640 custom/extends/*.php
sudo chown root:www-data custom/extends/*.php
```

### 4.4 環境変数設定

```bash
# .env ファイル作成
sudo nano /var/www/orka-asp2/.env
```

内容:

```bash
# 環境設定
APP_ENV=production

# Supabase接続情報
SUPABASE_DB_HOST=db.xxxxxxxxxxxxxx.supabase.co
SUPABASE_DB_PORT=5432
SUPABASE_DB_NAME=postgres
SUPABASE_DB_USER=postgres
SUPABASE_DB_PASSWORD=your_strong_password_here

# AFAD連携設定
CONFIG_AFAD_ENABLE=true
CONFIG_AFAD_LOG_LEVEL=1
CONFIG_AFAD_DEBUG_MODE=false
CONFIG_AFAD_TEST_MODE=false
```

パーミッション設定:

```bash
sudo chmod 600 /var/www/orka-asp2/.env
sudo chown www-data:www-data /var/www/orka-asp2/.env
```

### 4.5 Nginx設定

```bash
# Nginx設定ファイル作成
sudo nano /etc/nginx/sites-available/orka-asp2
```

`docs/RECOMMENDED_NGINX_CONFIG.md` の内容を参照してコピー

```bash
# シンボリックリンク作成
sudo ln -s /etc/nginx/sites-available/orka-asp2 /etc/nginx/sites-enabled/

# デフォルト設定を無効化
sudo rm /etc/nginx/sites-enabled/default

# 設定テスト
sudo nginx -t

# Nginx再起動
sudo systemctl restart nginx
```

---

## 5. SSL証明書設定

### 5.1 Let's Encryptインストール

```bash
# Certbotインストール
sudo apt install -y certbot python3-certbot-nginx
```

### 5.2 SSL証明書取得

```bash
# SSL証明書取得・Nginx自動設定
sudo certbot --nginx -d example.com -d www.example.com

# メールアドレス入力
# 利用規約に同意
# HTTPS リダイレクトを有効化
```

### 5.3 自動更新設定

```bash
# 自動更新テスト
sudo certbot renew --dry-run

# cronで自動更新（既に設定済みの場合が多い）
sudo systemctl status certbot.timer
```

---

## 6. 監視・運用設定

### 6.1 ログローテーション設定

```bash
# ログローテーション設定作成
sudo nano /etc/logrotate.d/orka-asp2
```

内容:

```
/var/www/orka-asp2/logs/*.log {
    daily
    missingok
    rotate 90
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
    sharedscripts
    postrotate
        systemctl reload php8.1-fpm > /dev/null 2>&1 || true
    endscript
}
```

### 6.2 監視スクリプト設定

```bash
# 監視スクリプト作成
sudo nano /usr/local/bin/orka-asp2-monitor.sh
```

内容:

```bash
#!/bin/bash

LOG_FILE="/var/www/orka-asp2/logs/afad_postback_$(date +%Y%m%d).log"
ALERT_EMAIL="admin@example.com"
ERROR_THRESHOLD=10

# エラー数カウント
if [ -f "$LOG_FILE" ]; then
    ERROR_COUNT=$(grep -c "ERROR" "$LOG_FILE")

    if [ $ERROR_COUNT -gt $ERROR_THRESHOLD ]; then
        echo "ALERT: AFAD postback error count: $ERROR_COUNT" | \
            mail -s "[ORKA-ASP2] Error Alert" $ALERT_EMAIL
    fi
fi
```

実行権限付与:

```bash
sudo chmod +x /usr/local/bin/orka-asp2-monitor.sh
```

cronで定期実行:

```bash
# crontab編集
crontab -e
```

追加:

```
# 毎時間エラーチェック
0 * * * * /usr/local/bin/orka-asp2-monitor.sh

# 毎日午前4時にログローテーション確認
0 4 * * * /usr/sbin/logrotate /etc/logrotate.conf
```

### 6.3 システムリソース監視

```bash
# htopインストール
sudo apt install -y htop

# ディスク使用量確認
df -h

# メモリ使用量確認
free -h

# 継続監視
htop
```

---

## 7. トラブルシューティング

### 7.1 PHP-FPMエラー

**問題: 502 Bad Gateway**

```bash
# PHP-FPMステータス確認
sudo systemctl status php8.1-fpm

# エラーログ確認
sudo tail -f /var/log/php8.1-fpm.log

# PHP-FPM再起動
sudo systemctl restart php8.1-fpm
```

### 7.2 データベース接続エラー

**問題: Could not connect to database**

```bash
# PostgreSQL接続テスト
psql -h db.xxxxxxxxxxxxxx.supabase.co -U postgres -d postgres

# PHP PostgreSQL拡張確認
php -m | grep pgsql

# 環境変数確認
cat /var/www/orka-asp2/.env
```

### 7.3 パーミッションエラー

**問題: Permission denied**

```bash
# ログディレクトリパーミッション修正
sudo chown -R www-data:www-data /var/www/orka-asp2/logs
sudo chmod 755 /var/www/orka-asp2/logs
sudo chmod 666 /var/www/orka-asp2/logs/*.log

# tdbディレクトリパーミッション修正
sudo chown -R www-data:www-data /var/www/orka-asp2/tdb
sudo chmod 755 /var/www/orka-asp2/tdb
sudo chmod 666 /var/www/orka-asp2/tdb/*.csv
```

### 7.4 Nginxエラー

**問題: Nginx起動失敗**

```bash
# 設定ファイル検証
sudo nginx -t

# エラーログ確認
sudo tail -f /var/log/nginx/error.log

# Nginx再起動
sudo systemctl restart nginx
```

### 7.5 AFADポストバック失敗

**問題: AFADポストバックが送信されない**

```bash
# AFADログ確認
sudo tail -f /var/www/orka-asp2/logs/afad_postback.log

# cURL動作確認
php -r "var_dump(function_exists('curl_init'));"

# ファイアウォール確認（外部HTTP接続許可）
sudo ufw status
```

---

## 8. パフォーマンス最適化

### 8.1 PHP OPcache有効化

```bash
# OPcache設定
sudo nano /etc/php/8.1/fpm/conf.d/10-opcache.ini
```

内容:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

再起動:

```bash
sudo systemctl restart php8.1-fpm
```

### 8.2 Nginx キャッシュ設定

```bash
# Nginx設定にキャッシュ追加
sudo nano /etc/nginx/nginx.conf
```

追加:

```nginx
http {
    # FastCGI キャッシュ
    fastcgi_cache_path /var/cache/nginx levels=1:2
        keys_zone=PHPCACHE:100m inactive=60m;
    fastcgi_cache_key "$scheme$request_method$host$request_uri";
}
```

キャッシュディレクトリ作成:

```bash
sudo mkdir -p /var/cache/nginx
sudo chown www-data:www-data /var/cache/nginx
```

---

## 9. セキュリティ強化

### 9.1 SSH鍵認証設定

```bash
# SSH鍵生成（ローカル）
ssh-keygen -t rsa -b 4096

# 公開鍵をサーバーにコピー
ssh-copy-id user@server

# パスワード認証無効化
sudo nano /etc/ssh/sshd_config
```

変更:

```
PasswordAuthentication no
PubkeyAuthentication yes
```

SSH再起動:

```bash
sudo systemctl restart sshd
```

### 9.2 Fail2Ban設定

```bash
# Fail2Banインストール
sudo apt install -y fail2ban

# 設定ファイル作成
sudo nano /etc/fail2ban/jail.local
```

内容:

```ini
[sshd]
enabled = true
port = 22
maxretry = 3
bantime = 3600

[nginx-http-auth]
enabled = true
port = http,https
maxretry = 5
```

起動:

```bash
sudo systemctl start fail2ban
sudo systemctl enable fail2ban
```

---

## 10. バックアップ設定

### 10.1 データベースバックアップ

```bash
# バックアップスクリプト作成
sudo nano /usr/local/bin/backup-database.sh
```

内容:

```bash
#!/bin/bash

BACKUP_DIR="/var/backups/orka-asp2"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Supabaseデータベースバックアップ
PGPASSWORD="your_password" pg_dump \
    -h db.xxxxxxxxxxxxxx.supabase.co \
    -U postgres -d postgres \
    -f $BACKUP_DIR/database_$DATE.sql

# 7日以上古いバックアップ削除
find $BACKUP_DIR -name "database_*.sql" -mtime +7 -delete
```

実行権限とcron設定:

```bash
sudo chmod +x /usr/local/bin/backup-database.sh

# 毎日午前3時にバックアップ
crontab -e
```

追加:

```
0 3 * * * /usr/local/bin/backup-database.sh
```

---

## まとめ

これでorka-asp2システムのサーバーセットアップが完了です！

**チェックリスト:**

- [ ] Ubuntu 22.04セットアップ完了
- [ ] Nginx + PHP 8.1インストール完了
- [ ] Supabaseデータベース作成・接続確認
- [ ] アプリケーションデプロイ完了
- [ ] SSL証明書取得完了
- [ ] ログローテーション設定完了
- [ ] 監視スクリプト設定完了
- [ ] バックアップ設定完了
- [ ] セキュリティ設定完了

**動作確認:**

```bash
# Webサイトアクセス
https://example.com

# AFAD連携テスト
https://example.com/link.php?adwares=123&id=456&afad_sid=TEST123
```

問題が発生した場合は、各セクションのトラブルシューティングを参照してください。
