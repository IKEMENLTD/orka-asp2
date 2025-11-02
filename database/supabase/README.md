# Supabase データベース設計

このディレクトリには、ORKA-ASP2システムのSupabase（PostgreSQL）用のデータベース設計が含まれています。

## ファイル構成

- `schema.sql` - 完全なスキーマ定義
- `migrations/` - マイグレーションスクリプト
- `seeds/` - テストデータ
- `functions/` - ストアドファンシ��ン・トリガー
- `policies/` - Row Level Security (RLS) ポリシー
- `indexes.sql` - インデックス定義
- `DESIGN.md` - データベース設計書

## セットアップ手順

### 1. 新規Supabaseプロジェクトでのセットアップ

```bash
# 1. スキーマ作成
psql -h your-project.supabase.co -U postgres -d postgres -f schema.sql

# 2. 関数・トリガー作成
psql -h your-project.supabase.co -U postgres -d postgres -f functions/all.sql

# 3. RLSポリシー設定
psql -h your-project.supabase.co -U postgres -d postgres -f policies/all.sql

# 4. インデックス作成
psql -h your-project.supabase.co -U postgres -d postgres -f indexes.sql

# 5. テストデータ投入（開発環境のみ）
psql -h your-project.supabase.co -U postgres -d postgres -f seeds/test_data.sql
```

### 2. マイグレーションを使ったセットアップ

```bash
# マイグレーションを順番に実行
psql -h your-project.supabase.co -U postgres -d postgres -f migrations/001_create_afad_tables.sql
psql -h your-project.supabase.co -U postgres -d postgres -f migrations/002_add_afad_columns_to_access.sql
psql -h your-project.supabase.co -U postgres -d postgres -f migrations/003_create_indexes.sql
psql -h your-project.supabase.co -U postgres -d postgres -f migrations/004_create_functions.sql
psql -h your-project.supabase.co -U postgres -d postgres -f migrations/005_create_rls_policies.sql
```

## テーブル一覧

| テーブル名 | 説明 |
|-----------|------|
| `access` | アクセスログ（AFAD連携カラム追加） |
| `afad_configs` | AFAD連携設定 |
| `afad_postback_logs` | ポストバック送信ログ |
| `afad_retry_queue` | リトライキュー |

## 主要機能

- **自動タイムスタンプ**: created_at, updated_at の自動更新
- **論理削除**: deleted_at カラムによるソフトデリート
- **リトライ管理**: 自動リトライキューイング
- **監査ログ**: 全ての変更を追跡
- **Row Level Security**: テーブル単位のアクセス制御
- **全文検索**: GINインデックスによる高速検索
- **パーティショニング**: 大量データ対応

## 注意事項

- 本番環境では必ずバックアップを取ってから実行してください
- RLSポリシーは本番環境で必ず有効化してください
- インデックスは運用開始後にパフォーマンスを見ながら追加・調整してください
