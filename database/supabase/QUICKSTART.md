# Supabase セットアップ クイックスタートガイド

このガイドに従って、AFAD連携機能のデータベースを素早くセットアップできます。

## 🚀 セットアップ手順（5ステップ）

### ステップ 1: Supabase プロジェクトの作成

1. [Supabase](https://supabase.com/)にアクセス
2. 新規プロジェクトを作成
3. データベース接続情報を取得:
   - Host: `your-project.supabase.co`
   - Database: `postgres`
   - User: `postgres`
   - Password: プロジェクト作成時に設定したパスワード

### ステップ 2: データベース接続確認

```bash
# PostgreSQLクライアントで接続テスト
psql "postgresql://postgres:[PASSWORD]@[HOST]:5432/postgres"

# または環境変数を設定
export PGHOST=your-project.supabase.co
export PGDATABASE=postgres
export PGUSER=postgres
export PGPASSWORD=your-password
export PGPORT=5432

psql
```

### ステップ 3: スキーマの作成（一括セットアップ）

**オプションA: 完全スキーマを一括実行（推奨 - 新規プロジェクト）**

```bash
cd database/supabase
psql -f schema.sql
```

このコマンドで以下が一括作成されます：
- 全テーブル
- インデックス
- トリガー
- 関数
- ビュー

**オプションB: マイグレーションを順番に実行（既存プロジェクト）**

```bash
cd database/supabase/migrations

# 各マイグレーションを順番に実行
psql -f 001_create_afad_tables.sql
psql -f 002_add_afad_columns_to_access.sql
psql -f 003_add_foreign_keys.sql
psql -f 004_create_views_and_functions.sql
psql -f 005_create_rls_policies.sql
```

### ステップ 4: テストデータの投入（開発環境のみ）

```bash
# テストデータを投入
psql -f seeds/test_data.sql
```

⚠️ **注意**: 本番環境では実行しないでください。

### ステップ 5: セットアップの確認

```bash
# psqlで以下のクエリを実行
psql
```

```sql
-- テーブルが作成されているか確認
SELECT table_name
FROM information_schema.tables
WHERE table_schema = 'public'
  AND table_name LIKE 'afad%'
ORDER BY table_name;

-- テーブルサイズを確認
SELECT * FROM get_afad_table_sizes();

-- テストデータが投入されているか確認（開発環境のみ）
SELECT COUNT(*) as config_count FROM afad_configs;
SELECT COUNT(*) as stats_count FROM afad_statistics;

-- ビューが動作するか確認
SELECT * FROM v_afad_postback_summary LIMIT 5;
```

期待される結果：
```
afad_configs
afad_postback_logs
afad_retry_queue
afad_statistics
```

## ✅ セットアップ完了チェックリスト

- [ ] Supabase プロジェクトが作成されている
- [ ] データベースに接続できる
- [ ] テーブルが全て作成されている（4テーブル）
- [ ] インデックスが作成されている
- [ ] 関数が作成されている（8関数以上）
- [ ] ビューが作成されている（3ビュー）
- [ ] RLSポリシーが設定されている
- [ ] テストデータが投入されている（開発環境のみ）

## 🔧 よくある問題と解決方法

### 問題1: 接続エラー

```
psql: error: connection to server at "xxx.supabase.co" failed
```

**解決方法:**
- ホスト名が正しいか確認
- パスワードが正しいか確認
- ファイアウォール設定を確認
- Supabaseプロジェクトが起動しているか確認

### 問題2: 権限エラー

```
ERROR: permission denied for table xxx
```

**解決方法:**
```sql
-- postgres ユーザーで接続していることを確認
SELECT current_user;

-- service_role キーを使用している場合は、Supabaseダッシュボードから直接実行
```

### 問題3: テーブルが既に存在する

```
ERROR: relation "afad_configs" already exists
```

**解決方法:**
```sql
-- 既存のテーブルを削除（データが失われます！）
DROP TABLE IF EXISTS afad_retry_queue CASCADE;
DROP TABLE IF EXISTS afad_postback_logs CASCADE;
DROP TABLE IF EXISTS afad_statistics CASCADE;
DROP TABLE IF EXISTS afad_configs CASCADE;

-- スキーマを再実行
\i schema.sql
```

### 問題4: access テーブルが存在しない

```
ERROR: relation "access" does not exist
```

**解決方法:**

accessテーブルは既存システムのテーブルです。以下のいずれかを実施：

1. **既存データベースから移行する場合:**
   ```bash
   # 既存DBからダンプ
   pg_dump -h old-host -U user -t access > access_dump.sql

   # Supabaseにリストア
   psql -f access_dump.sql
   ```

2. **新規にaccessテーブルを作成する場合:**
   ```sql
   -- 最小限のaccessテーブルを作成
   CREATE TABLE access (
     id BIGSERIAL PRIMARY KEY,
     adwares_id BIGINT NOT NULL,
     created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
     updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
   );
   ```

   その後、002のマイグレーションを実行してAFADカラムを追加。

## 📊 Supabase ダッシュボードでの確認

1. [Supabaseダッシュボード](https://app.supabase.com/)にログイン
2. プロジェクトを選択
3. **Table Editor**で確認:
   - `afad_configs`
   - `afad_postback_logs`
   - `afad_retry_queue`
   - `afad_statistics`

4. **Database** → **Policies**で確認:
   - RLSポリシーが設定されているか

5. **Database** → **Functions**で確認:
   - カスタム関数が作成されているか

## 🔐 RLSポリシーのテスト

### テスト用ユーザーの作成

```sql
-- テスト用ユーザーを作成（Supabase Auth経由）
-- ダッシュボードの Authentication → Users から作成

-- または、開発環境でのテスト用にダミーユーザーを作成
INSERT INTO auth.users (id, email)
VALUES ('test-user-uuid-1', 'test@example.com');

-- user_adwares_permissions テーブル（別途作成が必要）
CREATE TABLE IF NOT EXISTS user_adwares_permissions (
  user_id UUID NOT NULL,
  adwares_id BIGINT NOT NULL,
  PRIMARY KEY (user_id, adwares_id)
);

INSERT INTO user_adwares_permissions (user_id, adwares_id)
VALUES ('test-user-uuid-1', 1);
```

### RLSポリシーのテスト

```sql
-- 管理者として全データが見えるか確認
SET request.jwt.claims = '{"role": "admin"}';
SELECT COUNT(*) FROM afad_configs;

-- 一般ユーザーとして自分の広告のみ見えるか確認
SET request.jwt.claims = '{"role": "user", "sub": "test-user-uuid-1"}';
SELECT COUNT(*) FROM afad_configs;
-- 期待: 権限のある広告のみ表示
```

## 🎯 次のステップ

セットアップが完了したら：

1. **アプリケーションコードの実装**
   - `/docs/AFAD_SOCKET_INTEGRATION_DESIGN.md`を参照
   - `link.php` の改修
   - `add.php` の改修
   - `module/afad_postback.inc` の実装

2. **Supabase クライアントの設定**
   ```javascript
   // JavaScript/TypeScript の場合
   import { createClient } from '@supabase/supabase-js'

   const supabaseUrl = 'https://your-project.supabase.co'
   const supabaseKey = 'your-anon-key'
   const supabase = createClient(supabaseUrl, supabaseKey)
   ```

3. **環境変数の設定**
   ```bash
   # .env ファイル
   SUPABASE_URL=https://your-project.supabase.co
   SUPABASE_ANON_KEY=your-anon-key
   SUPABASE_SERVICE_ROLE_KEY=your-service-role-key
   ```

4. **運用設定**
   - バックアップスケジュールの設定
   - 監視アラートの設定
   - ログローテーションの設定（cron設定）

## 📚 参考資料

- [Supabase 公式ドキュメント](https://supabase.com/docs)
- [PostgreSQL 公式ドキュメント](https://www.postgresql.org/docs/)
- `DESIGN.md`: 完全な設計書
- `CHECKLIST.md`: 完全性チェックリスト
- `README.md`: 詳細なセットアップ手順

## 💡 ヒント

### パフォーマンス最適化

```sql
-- VACUUM ANALYZE を定期実行（週次推奨）
VACUUM ANALYZE afad_configs;
VACUUM ANALYZE afad_postback_logs;
VACUUM ANALYZE afad_retry_queue;
VACUUM ANALYZE afad_statistics;
```

### メンテナンス

```sql
-- 古いログを削除（90日以上前）
SELECT cleanup_old_logs(90);

-- 完了したリトライキューを削除（7日以上前）
SELECT cleanup_completed_retry_queue(7);
```

### 監視クエリ

```sql
-- テーブルサイズ確認
SELECT * FROM get_afad_table_sizes();

-- 最近のポストバック成功率
SELECT * FROM calculate_postback_rate(NULL, 24);

-- ペンディング中のリトライ
SELECT * FROM v_afad_pending_retries;
```

---

**トラブルシューティング**: 問題が解決しない場合は、`DESIGN.md`の「トラブルシューティング」セクションを参照してください。

**サポート**: チームメンバーまたはデータベース管理者に連絡してください。
