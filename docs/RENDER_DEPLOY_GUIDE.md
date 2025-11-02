# Renderデプロイガイド

## orka-asp2 を Render にデプロイする完全ガイド

---

## 目次

1. [事前準備](#1-事前準備)
2. [Supabaseデータベースのセットアップ](#2-supabaseデータベースのセットアップ)
3. [Renderアカウント作成](#3-renderアカウント作成)
4. [Renderへのデプロイ](#4-renderへのデプロイ)
5. [環境変数の設定](#5-環境変数の設定)
6. [デプロイ完了後の確認](#6-デプロイ完了後の確認)
7. [トラブルシューティング](#7-トラブルシューティング)

---

## 1. 事前準備

### 必要なもの

- GitHubアカウント
- Supabaseアカウント（無料）
- Renderアカウント（無料）

### コスト

| サービス | プラン | 月額 |
|---------|-------|------|
| Render Web Service | Starter | $7/月 (無料プランもあり) |
| Supabase | Free Tier | $0 (PostgreSQL含む) |
| **合計** | | **約$7/月〜** |

**無料プランでも動作可能**（テスト・開発環境向け）

---

## 2. Supabaseデータベースのセットアップ

### 2.1 Supabaseプロジェクト作成

1. https://supabase.com にアクセス
2. 「Start your project」をクリック
3. GitHubでサインイン
4. 「New Project」をクリック
5. プロジェクト情報を入力:
   ```
   Name: orka-asp2
   Database Password: [強力なパスワードを設定]
   Region: Northeast Asia (Tokyo) ← 推奨
   Pricing Plan: Free
   ```
6. 「Create new project」をクリック（約2分で完了）

### 2.2 データベーススキーマの適用

1. Supabaseダッシュボードで「SQL Editor」を開く
2. 「New query」をクリック
3. プロジェクトの`database/supabase/schema.sql`の内容をコピー&ペースト
4. 「Run」をクリックしてスキーマを適用

または、マイグレーションファイルを順番に実行:

```sql
-- database/supabase/migrations/001_create_afad_tables.sql
-- database/supabase/migrations/002_add_afad_columns_to_access.sql
-- database/supabase/migrations/003_add_foreign_keys.sql
-- database/supabase/migrations/004_create_views_and_functions.sql
-- database/supabase/migrations/005_create_rls_policies.sql
-- database/supabase/migrations/006_add_conversion_columns.sql
```

### 2.3 データベース接続情報の取得

1. Supabaseダッシュボードで「Settings」→「Database」を開く
2. 「Connection Info」セクションで以下の情報を確認:

```
Host: db.xxxxxxxxxxxxxx.supabase.co
Port: 5432
Database: postgres
User: postgres
Password: [設定したパスワード]
```

**これらの情報は後で使用するので、メモしておいてください。**

### 2.4 接続プールの設定（推奨）

Supabaseの「Database Settings」で:

```
Connection pooling: Enabled
Pool Mode: Transaction
Pool Size: 15
```

---

## 3. Renderアカウント作成

1. https://render.com にアクセス
2. 「Get Started」をクリック
3. GitHubでサインアップ
4. メール認証を完了

---

## 4. Renderへのデプロイ

### 4.1 リポジトリのプッシュ

```bash
# GitHubリポジトリにコードがプッシュされていることを確認
git add .
git commit -m "Render deployment configuration"
git push origin claude/afad-socket-integration-design-011CUiqQgSf1NTHhTHEVekon
```

### 4.2 Render Web Serviceの作成

1. Renderダッシュボードで「New +」→「Web Service」をクリック
2. GitHubリポジトリを選択:
   - 「Connect a repository」でGitHubと連携
   - `IKEMENLTD/orka-asp2`を選択
3. 基本設定:
   ```
   Name: orka-asp2
   Region: Singapore (東京リージョンがないため最も近い)
   Branch: claude/afad-socket-integration-design-011CUiqQgSf1NTHhTHEVekon
   Runtime: PHP
   ```

### 4.3 ビルド&スタートコマンド

Renderが自動的に`render.yaml`を検出しますが、手動設定する場合:

**Build Command:**
```bash
if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader; fi && mkdir -p custom tdb lst/indexs w3c lst/afad_logs && chmod -R 755 .
```

**Start Command:**
```bash
php -S 0.0.0.0:${PORT:-10000} -t .
```

---

## 5. 環境変数の設定

Renderダッシュボードで「Environment」タブを開き、以下の環境変数を追加:

### 必須の環境変数

| キー | 値 | 説明 |
|-----|---|------|
| `PHP_VERSION` | `8.1` | PHPバージョン |
| `SQL_MASTER` | `PostgreSQLDatabase` | データベースタイプ |
| `SQL_SERVER` | `db.xxx.supabase.co` | Supabaseのホスト |
| `SQL_PORT` | `5432` | PostgreSQLポート |
| `DB_NAME` | `postgres` | データベース名 |
| `SQL_ID` | `postgres` | データベースユーザー |
| `SQL_PASS` | `[Supabaseのパスワード]` | データベースパスワード |
| `TZ` | `Asia/Tokyo` | タイムゾーン |

### AFAD関連の環境変数

| キー | 値 | 説明 |
|-----|---|------|
| `AFAD_POSTBACK_ENABLED` | `true` | AFADポストバック機能 |
| `AFAD_LOG_ENABLED` | `true` | AFADログ機能 |
| `AFAD_LOG_PATH` | `./lst/afad_logs` | ログ保存パス |

### セキュリティ関連（本番環境）

| キー | 値 | 説明 |
|-----|---|------|
| `DISPLAY_ERRORS` | `0` | エラー表示OFF |
| `LOG_ERRORS` | `1` | エラーログON |
| `SESSION_COOKIE_SECURE` | `1` | セキュアクッキー |
| `SESSION_COOKIE_HTTPONLY` | `1` | HTTPOnlyクッキー |

設定後、「Save Changes」をクリック。

---

## 6. デプロイ完了後の確認

### 6.1 デプロイステータス確認

1. Renderダッシュボードの「Logs」タブでデプロイログを確認
2. 緑色の「Live」表示が出たらデプロイ成功
3. 自動的にSSL証明書が発行されます（数分かかる場合があります）

### 6.2 動作確認

デプロイされたURL（例: `https://orka-asp2.onrender.com`）にアクセス:

```bash
# ブラウザでアクセス
https://orka-asp2.onrender.com/

# または curlで確認
curl https://orka-asp2.onrender.com/
```

### 6.3 データベース接続の確認

1. `/info.php` にアクセスしてPHP設定を確認
2. `/tool.php` にアクセスして管理画面を確認

### 6.4 AFADログの確認

```bash
# Renderダッシュボードの「Shell」タブで
ls -la lst/afad_logs/
```

---

## 7. トラブルシューティング

### 問題1: デプロイが失敗する

**症状:** ビルドコマンドでエラー

**解決策:**
```bash
# Renderダッシュボードで「Manual Deploy」→「Clear build cache & deploy」を実行
```

### 問題2: データベース接続エラー

**症状:** `Could not connect to database`

**確認事項:**
1. Supabaseのデータベースが稼働中か確認
2. Render環境変数の`SQL_SERVER`、`SQL_PASS`が正しいか確認
3. Supabaseの接続制限を確認（IP制限がある場合は解除）

**解決策:**
```bash
# Supabaseダッシュボード → Settings → Database
# Connection Pooling を有効化
# Pooler connection string を使用（6543ポート）
```

環境変数を更新:
```
SQL_PORT=6543
SQL_SERVER=db.xxx.supabase.co (pooler URL)
```

### 問題3: PHPエラーが表示される

**症状:** `Parse error` や `Fatal error`

**確認事項:**
1. PHPバージョンが8.1か確認
2. 必要なPHP拡張がインストールされているか確認

**解決策:**
```bash
# render.yaml に以下を追加
buildCommand: |
  apt-get update
  apt-get install -y php8.1-pgsql php8.1-mbstring php8.1-curl
  composer install
```

### 問題4: ファイルパーミッションエラー

**症状:** `Permission denied` エラー

**解決策:**
```bash
# ビルドコマンドに追加
chmod -R 755 lst/afad_logs
chmod -R 755 tdb
```

### 問題5: セッションが保持されない

**解決策:**

Renderの環境変数に追加:
```
SESSION_SAVE_PATH=/tmp
```

### 問題6: AFADポストバックが動作しない

**確認事項:**
1. `AFAD_POSTBACK_ENABLED=true` が設定されているか
2. `module/afad_postback.inc` が存在するか
3. ログファイルにエラーが記録されていないか

**ログ確認:**
```bash
# Renderダッシュボードの「Logs」タブで確認
# または Shell で
tail -f lst/afad_logs/*.log
```

---

## 8. 本番環境への移行

### 8.1 独自ドメインの設定

1. Renderダッシュボードで「Settings」→「Custom Domain」
2. 「Add Custom Domain」をクリック
3. ドメインを入力（例: `tracking.yourdomain.com`）
4. DNS設定画面でCNAMEレコードを追加:
   ```
   Type: CNAME
   Name: tracking
   Value: orka-asp2.onrender.com
   ```
5. SSL証明書が自動発行されます（Let's Encrypt）

### 8.2 パフォーマンス最適化

**Renderプランのアップグレード:**
- Starter Plan: $7/月 - 本番環境推奨
- Standard Plan: $25/月 - 高トラフィック向け

**Supabaseプランのアップグレード:**
- Pro Plan: $25/月 - より多くの接続、バックアップ

### 8.3 監視設定

1. Renderの「Monitoring」タブで以下を確認:
   - CPU使用率
   - メモリ使用率
   - レスポンスタイム
   - エラー率

2. アラート設定:
   - 「Alerts」タブで通知を設定
   - メールまたはSlack連携

### 8.4 バックアップ設定

**Supabaseバックアップ:**
- 無料プランでも日次バックアップあり（7日間保持）
- Pro Planで30日間保持

**コードバックアップ:**
- GitHubリポジトリが自動バックアップ
- タグを作成してバージョン管理

---

## 9. デプロイフロー（継続的デプロイ）

### 自動デプロイ

`claude/afad-socket-integration-design-011CUiqQgSf1NTHhTHEVekon` ブランチにプッシュすると自動的にデプロイされます:

```bash
git add .
git commit -m "Update feature"
git push origin claude/afad-socket-integration-design-011CUiqQgSf1NTHhTHEVekon
```

### 手動デプロイ

Renderダッシュボードで「Manual Deploy」→「Deploy latest commit」

---

## 10. コスト最適化

### 無料プランで運用する場合

**制限事項:**
- 15分間アクセスがないとスリープモード（初回アクセス時に起動に数秒）
- 月間750時間まで（約31日）

**対策:**
- Pingサービスを使用（UptimeRobotなど）で5分ごとにアクセス
- ただし、連続稼働は有料プラン推奨

### 推奨構成（本番環境）

```
Render Starter: $7/月
Supabase Free: $0/月
合計: $7/月
```

高トラフィック時:
```
Render Standard: $25/月
Supabase Pro: $25/月
合計: $50/月
```

---

## 11. サポート&リソース

### 公式ドキュメント
- Render PHP Guide: https://render.com/docs/deploy-php
- Supabase Docs: https://supabase.com/docs
- PostgreSQL Docs: https://www.postgresql.org/docs/

### コミュニティ
- Render Community: https://community.render.com/
- Supabase Discord: https://discord.supabase.com/

---

## まとめ

✅ **簡単**: GitHubから数クリックでデプロイ
✅ **安価**: 月額$7〜で本番運用可能
✅ **スケーラブル**: トラフィック増加に応じて柔軟にスケール
✅ **セキュア**: SSL自動発行、PostgreSQL暗号化
✅ **高可用性**: 99.9%のアップタイム保証

**これでorka-asp2のRenderデプロイは完了です！🎉**
