# AFAD連携 デプロイメントチェックリスト

## 前提条件

- [ ] Supabaseプロジェクトが作成済み
- [ ] Supabase接続情報を入手済み
- [ ] AFADから連携情報を入手済み（ポストバックURL、グループID等）
- [ ] サーバーにPHP 7.4以上がインストール済み
- [ ] cURL拡張がインストール済み

---

## ステップ1: 環境変数の設定

### 1.1 環境変数ファイルの作成

```bash
cp .env.example .env
```

### 1.2 Supabase接続情報の設定

`.env`ファイルに以下を設定：

```env
SUPABASE_DB_HOST=your-project.supabase.co
SUPABASE_DB_PORT=5432
SUPABASE_DB_NAME=postgres
SUPABASE_DB_USER=postgres
SUPABASE_DB_PASSWORD=your-password-here
```

### 1.3 環境変数の検証

```bash
# 環境変数が正しく読み込まれるか確認
php -r "echo getenv('SUPABASE_DB_HOST');"
```

- [ ] 環境変数ファイル作成完了
- [ ] Supabase接続情報設定完了
- [ ] 環境変数読み込み確認完了

---

## ステップ2: データベーススキーマの適用

### 2.1 Supabaseダッシュボードにアクセス

https://app.supabase.com/

### 2.2 SQL Editorでマイグレーションを実行

以下のファイルを順番に実行：

1. `database/supabase/migrations/001_create_afad_tables.sql`
   - [ ] afad_configsテーブル作成
   - [ ] afad_postback_logsテーブル作成
   - [ ] afad_retry_queueテーブル作成

2. `database/supabase/migrations/002_add_afad_columns_to_access.sql`
   - [ ] accessテーブルにAFAD関連カラム追加

3. `database/supabase/migrations/003_add_foreign_keys.sql`
   - [ ] 外部キー制約の追加

4. `database/supabase/migrations/004_create_views_and_functions.sql`
   - [ ] ビューと関数の作成

5. `database/supabase/migrations/005_create_rls_policies.sql`
   - [ ] Row Level Security (RLS) ポリシーの設定

6. `database/supabase/migrations/006_add_conversion_columns.sql`
   - [ ] コンバージョン関連カラムの追加

### 2.3 スキーマ適用の確認

```sql
-- テーブルが作成されたか確認
SELECT table_name
FROM information_schema.tables
WHERE table_schema = 'public'
  AND table_name LIKE 'afad%';

-- 期待される結果:
-- afad_configs
-- afad_postback_logs
-- afad_retry_queue
```

- [ ] 全てのマイグレーション実行完了
- [ ] テーブル作成確認完了

---

## ステップ3: AFAD設定の登録

### 3.1 Supabaseダッシュボードで設定を登録

SQL Editorで以下を実行：

```sql
INSERT INTO afad_configs (
    adwares_id,
    enabled,
    parameter_name,
    postback_url,
    group_id,
    send_uid,
    send_uid2,
    send_amount,
    approval_status,
    timeout_seconds,
    retry_max,
    url_passthrough,
    cookie_expire_days
) VALUES (
    123,                                    -- 広告ID（実際のIDに置き換え）
    true,                                   -- 有効化
    'afad_sid',                            -- パラメータ名
    'https://ac.afad.jp/12345/ac/',       -- AFADポストバックURL
    'GRP001',                               -- AFADグループID
    true,                                   -- uid送信
    false,                                  -- uid2送信
    true,                                   -- amount送信
    1,                                      -- 承認ステータス（1=承認待ち）
    10,                                     -- タイムアウト（秒）
    3,                                      -- リトライ最大回数
    true,                                   -- URLパススルー
    30                                      -- Cookie有効期限（日）
);
```

### 3.2 設定の確認

```sql
SELECT * FROM afad_configs WHERE enabled = true;
```

- [ ] AFAD設定登録完了
- [ ] 設定内容確認完了

---

## ステップ4: ファイルとディレクトリの準備

### 4.1 ログディレクトリの作成と権限設定

```bash
mkdir -p logs
chmod 755 logs
chown www-data:www-data logs  # Webサーバーユーザーに合わせて変更
```

### 4.2 ログディレクトリの書き込み権限確認

```bash
sudo -u www-data touch logs/test.log
ls -la logs/test.log
rm logs/test.log
```

- [ ] ログディレクトリ作成完了
- [ ] 書き込み権限確認完了

---

## ステップ5: 設定ファイルの確認

### 5.1 AFAD設定ファイルの確認

`custom/extends/afadConf.php`を開き、以下を確認：

```php
// AFAD連携機能の有効化
$CONFIG_AFAD_ENABLE = true;  // ✓ true に設定

// ログファイルパス
$CONFIG_AFAD_LOG_FILE = __DIR__ . '/../../logs/afad_postback.log';  // ✓ パスが正しい

// 環境別設定
if (getenv('APP_ENV') === 'production') {
    $CONFIG_AFAD_DEBUG_MODE = false;
    $CONFIG_AFAD_TEST_MODE = false;
    $CONFIG_AFAD_LOG_LEVEL = 1; // エラーのみ
}
```

- [ ] `$CONFIG_AFAD_ENABLE = true` 確認
- [ ] ログファイルパス確認
- [ ] 環境別設定確認

---

## ステップ6: テストの実行

### 6.1 統合テストの実行

```bash
php tests/afad_integration_test.php
```

期待される出力:
```
✓ 全てのテストに成功しました
```

### 6.2 テスト結果の確認

- [ ] 設定ファイル読み込みテスト: PASS
- [ ] ログディレクトリ権限テスト: PASS
- [ ] セッションID検証テスト: PASS
- [ ] ポストバックURL構築テスト: PASS
- [ ] HTTPS検出テスト: PASS
- [ ] URLパラメータ追加テスト: PASS
- [ ] ログ書き込みテスト: PASS
- [ ] データベース接続テスト: PASS

---

## ステップ7: 動作確認（ステージング環境）

### 7.1 クリック計測テスト

ブラウザで以下にアクセス：

```
https://your-domain.com/link.php?adwares=123&id=456&afad_sid=TEST_SESSION_123
```

### 7.2 ログ確認

```bash
tail -f logs/afad_postback.log
```

期待されるログ:
```
[2025-11-02 12:34:56] [INFO] AFAD session ID received from URL parameter: {"param_name":"afad_sid","session_id":"TEST_SESSION_123"}
[2025-11-02 12:34:56] [INFO] AFAD session ID stored to cookie: {"cookie_name":"afad_session_afad_sid","expire_days":30}
```

### 7.3 データベース確認

```sql
-- セッションIDが保存されたか確認
SELECT id, afad_session_id, afad_config_id
FROM access
WHERE afad_session_id IS NOT NULL
ORDER BY created_at DESC
LIMIT 10;
```

### 7.4 コンバージョン計測テスト

ブラウザで以下にアクセス（上記のアクセスIDを使用）：

```
https://your-domain.com/add.php?check=ACCESS_ID&sales=10000&uid=ORDER123
```

### 7.5 ポストバックログ確認

```sql
SELECT * FROM afad_postback_logs ORDER BY created_at DESC LIMIT 10;
```

期待される結果:
- `status`: 'success'
- `response_code`: 200
- `afad_session_id`: 'TEST_SESSION_123'

- [ ] クリック計測テスト成功
- [ ] ログ出力確認
- [ ] データベースにセッションID保存確認
- [ ] コンバージョン計測テスト成功
- [ ] ポストバック送信確認

---

## ステップ8: 監視設定

### 8.1 ログローテーション設定

`/etc/logrotate.d/afad`を作成：

```
/path/to/orka-asp2/logs/afad_*.log {
    daily
    rotate 90
    compress
    delaycompress
    missingok
    notifempty
    create 0644 www-data www-data
}
```

### 8.2 エラー監視スクリプトの設定

```bash
# crontabに追加
# 毎時間エラー率をチェック
0 * * * * /path/to/scripts/check_afad_errors.sh
```

- [ ] ログローテーション設定完了
- [ ] エラー監視設定完了

---

## ステップ9: 本番デプロイ

### 9.1 本番環境への反映

```bash
# Gitで変更をプッシュ
git add .
git commit -m "Add AFAD integration"
git push origin main

# 本番サーバーで
git pull origin main
```

### 9.2 環境変数の設定（本番）

```bash
# 本番環境の .env ファイルを確認
APP_ENV=production
SUPABASE_DB_HOST=your-production-project.supabase.co
# ...
```

### 9.3 キャッシュクリア

```bash
# PHPキャッシュクリア（使用している場合）
php artisan cache:clear  # Laravelの場合
# または
apachectl graceful       # Apacheの場合
systemctl reload php-fpm # PHP-FPMの場合
```

- [ ] 本番環境へのデプロイ完了
- [ ] 本番環境変数設定完了
- [ ] キャッシュクリア完了

---

## ステップ10: 本番動作確認

### 10.1 実際のAFAD連携テスト

1. AFADの管理画面で広告URLを発行
2. 発行されたURLにアクセス
3. コンバージョンを発生させる
4. AFADの管理画面で成果が記録されているか確認

### 10.2 初期監視（24時間）

- [ ] エラーログに異常がないか確認
- [ ] ポストバック成功率が95%以上か確認
- [ ] レスポンスタイムが正常か確認

---

## トラブルシューティング

### 問題: ポストバックが送信されない

**確認事項:**
1. `$CONFIG_AFAD_ENABLE = true` になっているか
2. AFAD設定がデータベースに登録されているか
3. セッションIDがアクセスレコードに保存されているか
4. ログファイルにエラーが出力されていないか

**解決策:**
```bash
# ログ確認
tail -100 logs/afad_postback.log | grep ERROR

# データベース確認
psql -h your-project.supabase.co -U postgres -d postgres
SELECT * FROM afad_configs WHERE enabled = true;
SELECT * FROM access WHERE afad_session_id IS NOT NULL LIMIT 10;
```

### 問題: データベース接続エラー

**確認事項:**
1. 環境変数が正しく設定されているか
2. Supabaseの接続情報が正しいか
3. ファイアウォールでPostgreSQLポート(5432)が開いているか

**解決策:**
```bash
# 接続テスト
php -r "require 'module/afad_postback.inc'; try { GetAFADDatabaseConnection(); echo 'OK'; } catch (Exception \$e) { echo \$e->getMessage(); }"
```

### 問題: ログファイルに書き込めない

**確認事項:**
1. logsディレクトリが存在するか
2. 書き込み権限があるか

**解決策:**
```bash
mkdir -p logs
chmod 755 logs
chown www-data:www-data logs
```

---

## チェックリスト完了確認

- [ ] 環境変数設定完了
- [ ] データベーススキーマ適用完了
- [ ] AFAD設定登録完了
- [ ] ログディレクトリ準備完了
- [ ] テスト実行・成功
- [ ] ステージング環境での動作確認完了
- [ ] 監視設定完了
- [ ] 本番デプロイ完了
- [ ] 本番動作確認完了

---

**デプロイ責任者署名:**

氏名: ___________________
日付: ___________________
署名: ___________________

**レビュアー署名:**

氏名: ___________________
日付: ___________________
署名: ___________________
