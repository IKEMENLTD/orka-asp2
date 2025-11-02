# Supabase データベース設計書 - AFAD連携機能

## 設計方針

### 1. PostgreSQL ベストプラクティス準拠
- 正規化第3正常形（3NF）を基本とする
- 適切なデータ型の選択（BIGINT, UUID, TIMESTAMPTZ等）
- NOT NULL制約の積極的活用
- CHECK制約によるデータ整合性保証

### 2. Supabase 最適化
- Row Level Security (RLS) による細かいアクセス制御
- リアルタイム機能への対応
- PostgRESTによるAPIアクセス最適化
- pg_stat_statements による性能監視

### 3. 高可用性・高パフォーマンス
- 適切なインデックス設計
- パーティショニング対応（将来的な大量データ対応）
- 接続プーリング考慮
- クエリ最適化

### 4. 運用性
- 自動バックアップ対応
- マイグレーション管理
- 監査ログ
- 論理削除（ソフトデリート）

---

## テーブル設計

### 1. access テーブル（既存テーブル拡張）

**説明**: アクセスログテーブル。AFAD連携用のカラムを追加。

**拡張カラム:**

| カラム名 | データ型 | NULL | デフォルト | 説明 |
|---------|---------|------|-----------|------|
| afad_session_id | VARCHAR(255) | YES | NULL | AFADセッションID |
| afad_postback_sent | BOOLEAN | NO | false | ポストバック送信済みフラグ |
| afad_postback_status | VARCHAR(50) | YES | NULL | 送信ステータス(success/failed/pending/timeout) |
| afad_postback_time | TIMESTAMPTZ | YES | NULL | ポストバック送信日時 |
| afad_postback_response | TEXT | YES | NULL | AFADからのレスポンス内容 |
| afad_postback_retry_count | SMALLINT | NO | 0 | リトライ回数 |
| afad_postback_error | TEXT | YES | NULL | エラーメッセージ |
| afad_config_id | BIGINT | YES | NULL | AFAD設定ID（外部キー） |

**制約:**
```sql
-- セッションIDの形式チェック
ALTER TABLE access ADD CONSTRAINT check_afad_session_id_format
  CHECK (afad_session_id IS NULL OR afad_session_id ~ '^[a-zA-Z0-9_-]{1,255}$');

-- ステータス値の制限
ALTER TABLE access ADD CONSTRAINT check_afad_postback_status
  CHECK (afad_postback_status IN ('success', 'failed', 'pending', 'timeout'));

-- リトライ回数の上限
ALTER TABLE access ADD CONSTRAINT check_afad_retry_count
  CHECK (afad_postback_retry_count >= 0 AND afad_postback_retry_count <= 100);
```

**インデックス:**
```sql
-- セッションID検索用
CREATE INDEX idx_access_afad_session_id ON access(afad_session_id) WHERE afad_session_id IS NOT NULL;

-- 未送信・失敗レコード検索用（リトライ処理）
CREATE INDEX idx_access_afad_postback_pending ON access(afad_postback_sent, afad_postback_status, afad_postback_time)
  WHERE afad_postback_sent = false OR afad_postback_status IN ('failed', 'timeout');

-- 設定IDによる検索用
CREATE INDEX idx_access_afad_config_id ON access(afad_config_id) WHERE afad_config_id IS NOT NULL;

-- 複合インデックス（日付範囲 + ステータス）
CREATE INDEX idx_access_afad_date_status ON access(afad_postback_time, afad_postback_status);
```

---

### 2. afad_configs テーブル

**説明**: 広告ごとのAFAD連携設定を管理。

**スキーマ:**

| カラム名 | データ型 | NULL | デフォルト | 説明 |
|---------|---------|------|-----------|------|
| id | BIGSERIAL | NO | - | 主キー |
| adwares_id | BIGINT | NO | - | 広告ID（既存システムとの連携） |
| enabled | BOOLEAN | NO | true | AFAD連携有効フラグ |
| parameter_name | VARCHAR(100) | NO | 'afad_sid' | セッションID受け取り用パラメータ名 |
| postback_url | VARCHAR(2048) | NO | - | AFADポストバックURL（HTTPS必須） |
| group_id | VARCHAR(100) | NO | - | AFAD広告グループID |
| send_uid | BOOLEAN | NO | true | uid送信フラグ |
| send_uid2 | BOOLEAN | NO | false | uid2送信フラグ |
| send_amount | BOOLEAN | NO | true | 成果金額送信フラグ |
| approval_status | SMALLINT | YES | 1 | 承認ステータス(1:承認待ち, 2:承認, 3:否認) |
| timeout_seconds | SMALLINT | NO | 10 | HTTPタイムアウト(秒) |
| retry_max | SMALLINT | NO | 3 | 最大リトライ回数 |
| url_passthrough | BOOLEAN | NO | true | リダイレクトURLへのセッションID付与フラグ |
| cookie_expire_days | SMALLINT | NO | 30 | Cookie有効期限(日) |
| priority | SMALLINT | NO | 100 | 優先度（低い値が高優先） |
| notes | TEXT | YES | NULL | メモ・備考 |
| created_at | TIMESTAMPTZ | NO | NOW() | 作成日時 |
| updated_at | TIMESTAMPTZ | NO | NOW() | 更新日時 |
| deleted_at | TIMESTAMPTZ | YES | NULL | 削除日時（論理削除） |

**制約:**
```sql
-- 広告IDユニーク制約（論理削除を除く）
CREATE UNIQUE INDEX idx_afad_configs_adwares_id_unique ON afad_configs(adwares_id)
  WHERE deleted_at IS NULL;

-- ポストバックURLはHTTPS必須
ALTER TABLE afad_configs ADD CONSTRAINT check_postback_url_https
  CHECK (postback_url LIKE 'https://%');

-- パラメータ名の形式チェック
ALTER TABLE afad_configs ADD CONSTRAINT check_parameter_name_format
  CHECK (parameter_name ~ '^[a-zA-Z][a-zA-Z0-9_]{0,99}$');

-- 承認ステータスの範囲
ALTER TABLE afad_configs ADD CONSTRAINT check_approval_status
  CHECK (approval_status IS NULL OR (approval_status >= 1 AND approval_status <= 3));

-- タイムアウトの範囲（1秒〜60秒）
ALTER TABLE afad_configs ADD CONSTRAINT check_timeout_range
  CHECK (timeout_seconds >= 1 AND timeout_seconds <= 60);

-- リトライ回数の範囲（0〜10回）
ALTER TABLE afad_configs ADD CONSTRAINT check_retry_max_range
  CHECK (retry_max >= 0 AND retry_max <= 10);

-- Cookie有効期限の範囲（1日〜365日）
ALTER TABLE afad_configs ADD CONSTRAINT check_cookie_expire_range
  CHECK (cookie_expire_days >= 1 AND cookie_expire_days <= 365);

-- 優先度の範囲（1〜1000）
ALTER TABLE afad_configs ADD CONSTRAINT check_priority_range
  CHECK (priority >= 1 AND priority <= 1000);
```

**インデックス:**
```sql
-- 主キー
CREATE INDEX idx_afad_configs_id ON afad_configs(id);

-- 広告ID検索用（論理削除除外）
CREATE INDEX idx_afad_configs_adwares_id ON afad_configs(adwares_id)
  WHERE deleted_at IS NULL;

-- 有効な設定のみ検索用
CREATE INDEX idx_afad_configs_enabled ON afad_configs(enabled, deleted_at)
  WHERE enabled = true AND deleted_at IS NULL;

-- 優先度順ソート用
CREATE INDEX idx_afad_configs_priority ON afad_configs(priority, id)
  WHERE deleted_at IS NULL;
```

**トリガー:**
```sql
-- updated_at自動更新
CREATE TRIGGER trigger_afad_configs_updated_at
  BEFORE UPDATE ON afad_configs
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();
```

---

### 3. afad_postback_logs テーブル

**説明**: ポストバック送信履歴を記録。監査ログとしても機能。

**スキーマ:**

| カラム名 | データ型 | NULL | デフォルト | 説明 |
|---------|---------|------|-----------|------|
| id | BIGSERIAL | NO | - | 主キー |
| access_id | BIGINT | NO | - | アクセスID（外部キー） |
| afad_config_id | BIGINT | NO | - | AFAD設定ID（外部キー） |
| adwares_id | BIGINT | NO | - | 広告ID（非正規化・高速検索用） |
| afad_session_id | VARCHAR(255) | NO | - | AFADセッションID |
| postback_url | VARCHAR(2048) | NO | - | 送信したポストバックURL |
| request_params | JSONB | NO | '{}' | リクエストパラメータ（JSON形式） |
| request_headers | JSONB | YES | NULL | リクエストヘッダー（JSON形式） |
| response_code | SMALLINT | YES | NULL | HTTPレスポンスコード |
| response_body | TEXT | YES | NULL | レスポンスボディ |
| response_headers | JSONB | YES | NULL | レスポンスヘッダー（JSON形式） |
| status | VARCHAR(50) | NO | 'pending' | 送信ステータス |
| error_message | TEXT | YES | NULL | エラーメッセージ |
| retry_count | SMALLINT | NO | 0 | リトライ回数 |
| execution_time_ms | INTEGER | YES | NULL | 実行時間（ミリ秒） |
| ip_address | INET | YES | NULL | 送信元IPアドレス |
| user_agent | VARCHAR(500) | YES | NULL | User-Agent |
| created_at | TIMESTAMPTZ | NO | NOW() | 作成日時 |
| updated_at | TIMESTAMPTZ | NO | NOW() | 更新日時 |

**制約:**
```sql
-- ステータス値の制限
ALTER TABLE afad_postback_logs ADD CONSTRAINT check_status
  CHECK (status IN ('success', 'failed', 'pending', 'timeout', 'cancelled'));

-- レスポンスコードの範囲（100〜599）
ALTER TABLE afad_postback_logs ADD CONSTRAINT check_response_code
  CHECK (response_code IS NULL OR (response_code >= 100 AND response_code < 600));

-- リトライ回数の上限
ALTER TABLE afad_postback_logs ADD CONSTRAINT check_retry_count
  CHECK (retry_count >= 0 AND retry_count <= 100);

-- 実行時間は正の数
ALTER TABLE afad_postback_logs ADD CONSTRAINT check_execution_time
  CHECK (execution_time_ms IS NULL OR execution_time_ms >= 0);
```

**インデックス:**
```sql
-- アクセスID検索用
CREATE INDEX idx_afad_logs_access_id ON afad_postback_logs(access_id);

-- セッションID検索用
CREATE INDEX idx_afad_logs_session_id ON afad_postback_logs(afad_session_id);

-- 広告ID + 日付範囲検索用
CREATE INDEX idx_afad_logs_adwares_date ON afad_postback_logs(adwares_id, created_at DESC);

-- ステータス別検索用
CREATE INDEX idx_afad_logs_status ON afad_postback_logs(status, created_at DESC);

-- AFAD設定ID検索用
CREATE INDEX idx_afad_logs_config_id ON afad_postback_logs(afad_config_id);

-- JSONBカラムのGINインデックス（パラメータ検索用）
CREATE INDEX idx_afad_logs_request_params ON afad_postback_logs USING GIN(request_params);

-- パーティションキー用（将来的なテーブルパーティショニング対応）
CREATE INDEX idx_afad_logs_created_at ON afad_postback_logs(created_at DESC);
```

**トリガー:**
```sql
-- updated_at自動更新
CREATE TRIGGER trigger_afad_logs_updated_at
  BEFORE UPDATE ON afad_postback_logs
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();
```

**パーティショニング（オプション・大量データ対応）:**

```sql
-- 月次パーティショニング例
-- ログが大量になる場合に有効化

-- 1. 既存テーブルをパーティションテーブルに変換
-- 2. 月ごとのパーティションを作成
-- 例: afad_postback_logs_2025_01, afad_postback_logs_2025_02, ...

-- パーティション作成関数
CREATE OR REPLACE FUNCTION create_afad_log_partition()
RETURNS void AS $$
DECLARE
  partition_date DATE;
  partition_name TEXT;
  start_date DATE;
  end_date DATE;
BEGIN
  -- 来月のパーティションを作成
  partition_date := DATE_TRUNC('month', CURRENT_DATE + INTERVAL '1 month');
  partition_name := 'afad_postback_logs_' || TO_CHAR(partition_date, 'YYYY_MM');
  start_date := partition_date;
  end_date := partition_date + INTERVAL '1 month';

  EXECUTE format(
    'CREATE TABLE IF NOT EXISTS %I PARTITION OF afad_postback_logs
     FOR VALUES FROM (%L) TO (%L)',
    partition_name, start_date, end_date
  );
END;
$$ LANGUAGE plpgsql;
```

---

### 4. afad_retry_queue テーブル

**説明**: ポストバック送信のリトライキュー。失敗した送信を管理。

**スキーマ:**

| カラム名 | データ型 | NULL | デフォルト | 説明 |
|---------|---------|------|-----------|------|
| id | BIGSERIAL | NO | - | 主キー |
| access_id | BIGINT | NO | - | アクセスID（外部キー） |
| afad_config_id | BIGINT | NO | - | AFAD設定ID（外部キー） |
| afad_session_id | VARCHAR(255) | NO | - | AFADセッションID |
| retry_count | SMALLINT | NO | 0 | 現在のリトライ回数 |
| max_retry_count | SMALLINT | NO | 3 | 最大リトライ回数 |
| next_retry_at | TIMESTAMPTZ | NO | - | 次回リトライ予定日時 |
| last_error | TEXT | YES | NULL | 最後のエラーメッセージ |
| status | VARCHAR(50) | NO | 'pending' | ステータス(pending/processing/completed/failed) |
| priority | SMALLINT | NO | 100 | 優先度（低い値が高優先） |
| created_at | TIMESTAMPTZ | NO | NOW() | 作成日時 |
| updated_at | TIMESTAMPTZ | NO | NOW() | 更新日時 |
| processed_at | TIMESTAMPTZ | YES | NULL | 処理完了日時 |

**制約:**
```sql
-- ステータス値の制限
ALTER TABLE afad_retry_queue ADD CONSTRAINT check_queue_status
  CHECK (status IN ('pending', 'processing', 'completed', 'failed', 'cancelled'));

-- リトライ回数は最大値以下
ALTER TABLE afad_retry_queue ADD CONSTRAINT check_retry_counts
  CHECK (retry_count <= max_retry_count);

-- 次回リトライ日時は未来
ALTER TABLE afad_retry_queue ADD CONSTRAINT check_next_retry_future
  CHECK (next_retry_at > created_at);

-- 優先度の範囲（1〜1000）
ALTER TABLE afad_retry_queue ADD CONSTRAINT check_queue_priority_range
  CHECK (priority >= 1 AND priority <= 1000);
```

**インデックス:**
```sql
-- アクセスID検索用（重複防止にも使用）
CREATE UNIQUE INDEX idx_retry_queue_access_id ON afad_retry_queue(access_id)
  WHERE status IN ('pending', 'processing');

-- 実行待ちキュー取得用（最重要インデックス）
CREATE INDEX idx_retry_queue_next_retry ON afad_retry_queue(next_retry_at, priority, id)
  WHERE status = 'pending' AND next_retry_at <= NOW();

-- ステータス別検索用
CREATE INDEX idx_retry_queue_status ON afad_retry_queue(status, created_at);

-- セッションID検索用
CREATE INDEX idx_retry_queue_session_id ON afad_retry_queue(afad_session_id);
```

**トリガー:**
```sql
-- updated_at自動更新
CREATE TRIGGER trigger_retry_queue_updated_at
  BEFORE UPDATE ON afad_retry_queue
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();

-- 処理完了時に processed_at を自動設定
CREATE TRIGGER trigger_retry_queue_processed_at
  BEFORE UPDATE ON afad_retry_queue
  FOR EACH ROW
  WHEN (NEW.status IN ('completed', 'failed', 'cancelled') AND OLD.status NOT IN ('completed', 'failed', 'cancelled'))
  EXECUTE FUNCTION set_processed_at();
```

---

### 5. afad_statistics テーブル（統計情報・オプション）

**説明**: AFAD連携の統計情報を集計。ダッシュボード表示用。

**スキーマ:**

| カラム名 | データ型 | NULL | デフォルト | 説明 |
|---------|---------|------|-----------|------|
| id | BIGSERIAL | NO | - | 主キー |
| adwares_id | BIGINT | NO | - | 広告ID |
| date | DATE | NO | - | 集計日 |
| total_clicks | INTEGER | NO | 0 | 総クリック数 |
| afad_session_received | INTEGER | NO | 0 | AFADセッションID受信数 |
| total_conversions | INTEGER | NO | 0 | 総コンバージョン数 |
| afad_postback_attempted | INTEGER | NO | 0 | ポストバック送信試行数 |
| afad_postback_success | INTEGER | NO | 0 | ポストバック送信成功数 |
| afad_postback_failed | INTEGER | NO | 0 | ポストバック送信失敗数 |
| afad_postback_timeout | INTEGER | NO | 0 | タイムアウト数 |
| total_amount | DECIMAL(15,2) | NO | 0.00 | 総成果金額 |
| avg_response_time_ms | INTEGER | YES | NULL | 平均レスポンス時間（ミリ秒） |
| created_at | TIMESTAMPTZ | NO | NOW() | 作成日時 |
| updated_at | TIMESTAMPTZ | NO | NOW() | 更新日時 |

**制約:**
```sql
-- 広告ID + 日付のユニーク制約
CREATE UNIQUE INDEX idx_afad_stats_unique ON afad_statistics(adwares_id, date);

-- 数値は全て0以上
ALTER TABLE afad_statistics ADD CONSTRAINT check_stats_positive
  CHECK (
    total_clicks >= 0 AND
    afad_session_received >= 0 AND
    total_conversions >= 0 AND
    afad_postback_attempted >= 0 AND
    afad_postback_success >= 0 AND
    afad_postback_failed >= 0 AND
    afad_postback_timeout >= 0 AND
    total_amount >= 0
  );

-- 論理チェック: 試行数 = 成功 + 失敗 + タイムアウト
ALTER TABLE afad_statistics ADD CONSTRAINT check_stats_logic
  CHECK (afad_postback_attempted >= (afad_postback_success + afad_postback_failed + afad_postback_timeout));
```

**インデックス:**
```sql
-- 広告ID + 日付範囲検索用
CREATE INDEX idx_afad_stats_adwares_date ON afad_statistics(adwares_id, date DESC);

-- 日付範囲検索用
CREATE INDEX idx_afad_stats_date ON afad_statistics(date DESC);
```

---

## 外部キー制約

```sql
-- access テーブル
ALTER TABLE access
  ADD CONSTRAINT fk_access_afad_config
  FOREIGN KEY (afad_config_id)
  REFERENCES afad_configs(id)
  ON DELETE SET NULL;

-- afad_postback_logs テーブル
ALTER TABLE afad_postback_logs
  ADD CONSTRAINT fk_logs_access
  FOREIGN KEY (access_id)
  REFERENCES access(id)
  ON DELETE CASCADE;

ALTER TABLE afad_postback_logs
  ADD CONSTRAINT fk_logs_config
  FOREIGN KEY (afad_config_id)
  REFERENCES afad_configs(id)
  ON DELETE RESTRICT;

-- afad_retry_queue テーブル
ALTER TABLE afad_retry_queue
  ADD CONSTRAINT fk_queue_access
  FOREIGN KEY (access_id)
  REFERENCES access(id)
  ON DELETE CASCADE;

ALTER TABLE afad_retry_queue
  ADD CONSTRAINT fk_queue_config
  FOREIGN KEY (afad_config_id)
  REFERENCES afad_configs(id)
  ON DELETE RESTRICT;
```

---

## ストアドファンクション

### 1. update_updated_at_column()

```sql
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;
```

### 2. set_processed_at()

```sql
CREATE OR REPLACE FUNCTION set_processed_at()
RETURNS TRIGGER AS $$
BEGIN
  IF NEW.processed_at IS NULL THEN
    NEW.processed_at = NOW();
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;
```

### 3. get_next_retry_time()

指数バックオフによる次回リトライ時刻を計算。

```sql
CREATE OR REPLACE FUNCTION get_next_retry_time(
  retry_count INTEGER,
  base_interval INTEGER DEFAULT 60
)
RETURNS TIMESTAMPTZ AS $$
BEGIN
  -- 指数バックオフ: 1分, 5分, 15分, 30分, 60分, ...
  RETURN NOW() + (base_interval * POWER(2, LEAST(retry_count, 5))) * INTERVAL '1 second';
END;
$$ LANGUAGE plpgsql IMMUTABLE;
```

### 4. cleanup_old_logs()

古いログを削除する関数（定期実行用）。

```sql
CREATE OR REPLACE FUNCTION cleanup_old_logs(days_to_keep INTEGER DEFAULT 90)
RETURNS INTEGER AS $$
DECLARE
  deleted_count INTEGER;
BEGIN
  DELETE FROM afad_postback_logs
  WHERE created_at < NOW() - (days_to_keep || ' days')::INTERVAL;

  GET DIAGNOSTICS deleted_count = ROW_COUNT;
  RETURN deleted_count;
END;
$$ LANGUAGE plpgsql;
```

### 5. get_afad_statistics()

統計情報を取得するビュー関数。

```sql
CREATE OR REPLACE FUNCTION get_afad_statistics(
  p_adwares_id BIGINT,
  p_start_date DATE,
  p_end_date DATE
)
RETURNS TABLE (
  date DATE,
  total_clicks BIGINT,
  afad_sessions BIGINT,
  conversions BIGINT,
  postback_success BIGINT,
  postback_failed BIGINT,
  success_rate NUMERIC(5,2)
) AS $$
BEGIN
  RETURN QUERY
  SELECT
    DATE(apl.created_at) as date,
    COUNT(DISTINCT a.id) as total_clicks,
    COUNT(DISTINCT CASE WHEN a.afad_session_id IS NOT NULL THEN a.id END) as afad_sessions,
    COUNT(DISTINCT CASE WHEN a.afad_postback_sent = true THEN a.id END) as conversions,
    COUNT(CASE WHEN apl.status = 'success' THEN 1 END) as postback_success,
    COUNT(CASE WHEN apl.status IN ('failed', 'timeout') THEN 1 END) as postback_failed,
    ROUND(
      CASE
        WHEN COUNT(apl.id) > 0 THEN
          (COUNT(CASE WHEN apl.status = 'success' THEN 1 END)::NUMERIC / COUNT(apl.id)::NUMERIC) * 100
        ELSE 0
      END,
      2
    ) as success_rate
  FROM access a
  LEFT JOIN afad_postback_logs apl ON a.id = apl.access_id
  WHERE a.adwares_id = p_adwares_id
    AND DATE(a.created_at) BETWEEN p_start_date AND p_end_date
  GROUP BY DATE(apl.created_at)
  ORDER BY date DESC;
END;
$$ LANGUAGE plpgsql;
```

---

## ビュー

### 1. v_afad_postback_summary

ポストバック送信状況のサマリービュー。

```sql
CREATE OR REPLACE VIEW v_afad_postback_summary AS
SELECT
  ac.id as config_id,
  ac.adwares_id,
  ac.group_id as afad_group_id,
  ac.enabled,
  COUNT(apl.id) as total_attempts,
  COUNT(CASE WHEN apl.status = 'success' THEN 1 END) as success_count,
  COUNT(CASE WHEN apl.status = 'failed' THEN 1 END) as failed_count,
  COUNT(CASE WHEN apl.status = 'timeout' THEN 1 END) as timeout_count,
  ROUND(
    CASE
      WHEN COUNT(apl.id) > 0 THEN
        (COUNT(CASE WHEN apl.status = 'success' THEN 1 END)::NUMERIC / COUNT(apl.id)::NUMERIC) * 100
      ELSE 0
    END,
    2
  ) as success_rate,
  MAX(apl.created_at) as last_attempt_at,
  AVG(apl.execution_time_ms) as avg_execution_time_ms
FROM afad_configs ac
LEFT JOIN afad_postback_logs apl ON ac.id = apl.afad_config_id
WHERE ac.deleted_at IS NULL
GROUP BY ac.id, ac.adwares_id, ac.group_id, ac.enabled;
```

### 2. v_afad_pending_retries

リトライ待ちのキューを表示するビュー。

```sql
CREATE OR REPLACE VIEW v_afad_pending_retries AS
SELECT
  arq.id,
  arq.access_id,
  arq.afad_session_id,
  arq.retry_count,
  arq.max_retry_count,
  arq.next_retry_at,
  arq.last_error,
  arq.priority,
  ac.adwares_id,
  ac.group_id as afad_group_id,
  EXTRACT(EPOCH FROM (arq.next_retry_at - NOW())) as seconds_until_retry
FROM afad_retry_queue arq
JOIN afad_configs ac ON arq.afad_config_id = ac.id
WHERE arq.status = 'pending'
  AND arq.retry_count < arq.max_retry_count
ORDER BY arq.next_retry_at, arq.priority, arq.id;
```

---

## Row Level Security (RLS) ポリシー

### 基本方針
- 管理者は全てのデータにアクセス可能
- 一般ユーザーは自分が管理する広告のデータのみアクセス可能
- APIキーによるアクセス制御

### 1. afad_configs テーブル

```sql
-- RLS有効化
ALTER TABLE afad_configs ENABLE ROW LEVEL SECURITY;

-- 管理者は全てのレコードにアクセス可能
CREATE POLICY policy_afad_configs_admin ON afad_configs
  FOR ALL
  TO authenticated
  USING (auth.jwt() ->> 'role' = 'admin');

-- 一般ユーザーは自分の広告の設定のみ参照可能
CREATE POLICY policy_afad_configs_user_read ON afad_configs
  FOR SELECT
  TO authenticated
  USING (
    adwares_id IN (
      SELECT adwares_id FROM user_adwares_permissions
      WHERE user_id = auth.uid()
    )
  );

-- APIキーによるアクセス
CREATE POLICY policy_afad_configs_api_key ON afad_configs
  FOR SELECT
  TO anon
  USING (
    EXISTS (
      SELECT 1 FROM api_keys
      WHERE key = current_setting('request.headers')::json->>'x-api-key'
        AND is_active = true
        AND expires_at > NOW()
    )
  );
```

### 2. afad_postback_logs テーブル

```sql
-- RLS有効化
ALTER TABLE afad_postback_logs ENABLE ROW LEVEL SECURITY;

-- 管理者は全てのログにアクセス可能
CREATE POLICY policy_afad_logs_admin ON afad_postback_logs
  FOR ALL
  TO authenticated
  USING (auth.jwt() ->> 'role' = 'admin');

-- 一般ユーザーは自分の広告のログのみ参照可能
CREATE POLICY policy_afad_logs_user_read ON afad_postback_logs
  FOR SELECT
  TO authenticated
  USING (
    adwares_id IN (
      SELECT adwares_id FROM user_adwares_permissions
      WHERE user_id = auth.uid()
    )
  );

-- システムからの書き込み専用ポリシー
CREATE POLICY policy_afad_logs_system_write ON afad_postback_logs
  FOR INSERT
  TO service_role
  WITH CHECK (true);
```

### 3. afad_retry_queue テーブル

```sql
-- RLS有効化
ALTER TABLE afad_retry_queue ENABLE ROW LEVEL SECURITY;

-- 管理者のみアクセス可能
CREATE POLICY policy_retry_queue_admin ON afad_retry_queue
  FOR ALL
  TO authenticated
  USING (auth.jwt() ->> 'role' = 'admin');

-- システムからの読み書き
CREATE POLICY policy_retry_queue_system ON afad_retry_queue
  FOR ALL
  TO service_role
  USING (true)
  WITH CHECK (true);
```

---

## パフォーマンス最適化

### 1. 部分インデックス
- NULL値を除外した部分インデックスを活用
- 論理削除されたレコードを除外

### 2. JSONB インデックス
- GINインデックスによる高速なJSON検索
- よく検索されるJSON���ーには専用のインデックス

### 3. マテリアライズドビュー（オプション）

大量データの集計用。

```sql
CREATE MATERIALIZED VIEW mv_afad_daily_stats AS
SELECT
  DATE(apl.created_at) as date,
  ac.adwares_id,
  ac.group_id as afad_group_id,
  COUNT(apl.id) as total_attempts,
  COUNT(CASE WHEN apl.status = 'success' THEN 1 END) as success_count,
  COUNT(CASE WHEN apl.status = 'failed' THEN 1 END) as failed_count,
  COUNT(CASE WHEN apl.status = 'timeout' THEN 1 END) as timeout_count,
  AVG(apl.execution_time_ms) as avg_execution_time_ms,
  PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY apl.execution_time_ms) as median_execution_time_ms,
  PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY apl.execution_time_ms) as p95_execution_time_ms
FROM afad_postback_logs apl
JOIN afad_configs ac ON apl.afad_config_id = ac.id
GROUP BY DATE(apl.created_at), ac.adwares_id, ac.group_id;

-- インデックス作成
CREATE UNIQUE INDEX idx_mv_afad_daily_stats ON mv_afad_daily_stats(date, adwares_id);

-- 日次更新（cron等で実行）
REFRESH MATERIALIZED VIEW CONCURRENTLY mv_afad_daily_stats;
```

### 4. パーティショニング戦略

ログテーブルが大量になる場合の対応。

```sql
-- 月次パーティショニング
-- afad_postback_logs を created_at でパーティション分割

-- 自動パーティション作成（pg_cron使用）
SELECT cron.schedule('create-afad-log-partition', '0 0 1 * *', 'SELECT create_afad_log_partition()');

-- 古いパーティションの削除（90日以上前）
SELECT cron.schedule('drop-old-afad-log-partition', '0 1 1 * *', $$
  SELECT drop_old_partitions('afad_postback_logs', 90)
$$);
```

---

## 監視・メンテナンス

### 1. 監視すべきメトリクス

```sql
-- テーブルサイズ監視
SELECT
  schemaname,
  tablename,
  pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size
FROM pg_tables
WHERE tablename LIKE 'afad%'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;

-- インデックス使用状況
SELECT
  schemaname,
  tablename,
  indexname,
  idx_scan as index_scans,
  pg_size_pretty(pg_relation_size(indexrelid)) as index_size
FROM pg_stat_user_indexes
WHERE tablename LIKE 'afad%'
ORDER BY idx_scan ASC;

-- スロークエリ検出
SELECT
  query,
  calls,
  total_time,
  mean_time,
  max_time
FROM pg_stat_statements
WHERE query LIKE '%afad%'
ORDER BY mean_time DESC
LIMIT 10;
```

### 2. 定期メンテナンススクリプト

```sql
-- VACUUM ANALYZE（週次実行推奨）
VACUUM ANALYZE afad_configs;
VACUUM ANALYZE afad_postback_logs;
VACUUM ANALYZE afad_retry_queue;

-- 統計情報更新
ANALYZE afad_configs;
ANALYZE afad_postback_logs;
ANALYZE afad_retry_queue;

-- 古いログ削除（90日以上前）
SELECT cleanup_old_logs(90);

-- 完了したリトライキューのクリーンアップ
DELETE FROM afad_retry_queue
WHERE status IN ('completed', 'failed')
  AND processed_at < NOW() - INTERVAL '7 days';
```

---

## バックアップ戦略

### 1. Supabase 自動バックアップ
- デフォルトで有効（過去7日分）
- Point-in-Time Recovery (PITR) 対応

### 2. 手動バックアップ

```bash
# 全テーブルのバックアップ
pg_dump -h your-project.supabase.co -U postgres -d postgres \
  -t afad_configs \
  -t afad_postback_logs \
  -t afad_retry_queue \
  -t afad_statistics \
  > afad_backup_$(date +%Y%m%d).sql

# 設定テーブルのみバックアップ
pg_dump -h your-project.supabase.co -U postgres -d postgres \
  -t afad_configs \
  > afad_configs_backup_$(date +%Y%m%d).sql
```

### 3. リストア

```bash
# バックアップからリストア
psql -h your-project.supabase.co -U postgres -d postgres \
  < afad_backup_20250102.sql
```

---

## セキュリティチェックリスト

- [ ] RLS ポリシーが全てのテーブルで有効化されている
- [ ] 外部キー制約が適切に設定されている
- [ ] CHECK 制約でデータ整合性が保証されている
- [ ] 機密情報（エラーメッセージ等）が適切にマスキングされている
- [ ] APIキーによるアクセス制御が実装されている
- [ ] HTTPS通信が強制されている（postback_urlの制約）
- [ ] SQLインジェクション対策（プリペアドステートメント使用）
- [ ] 定期的なバックアップが設定されている
- [ ] ログの保存期間が適切に設定されている
- [ ] 個人情報保護法への対応（必要に応じて暗号化）

---

## パフォーマンスチェックリスト

- [ ] 全ての外部キーにインデックスが作成されている
- [ ] よく検索されるカラムにインデックスが作成されている
- [ ] 部分インデックスで不要なデータを除外している
- [ ] JSONBカラムにGINインデックスが作成されている
- [ ] VACUUM ANALYZE が定期実行されている
- [ ] 統計情報が最新の状態に保たれている
- [ ] スロークエリが監視されている
- [ ] 大量データ時のパーティショニング戦略が検討されている
- [ ] 接続プーリングが適切に設定されている
- [ ] クエリプランが最適化されている

---

## データ整合性チェックリスト

- [ ] NOT NULL 制約が適切に設定されている
- [ ] UNIQUE 制約で重複データを防止している
- [ ] CHECK 制約で不正な値を防止している
- [ ] 外部キー制約で参照整合性が保証されている
- [ ] トリガーで自動更新が実装されている
- [ ] 論理削除が適切に実装されている
- [ ] タイムスタンプが自動管理されている
- [ ] トランザクション分離レベルが適切に設定されている
- [ ] デッドロック対策が実装されている
- [ ] データ型が適切に選択されている

---

**以上**
