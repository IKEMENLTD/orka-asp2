-- ============================================================================
-- ORKA-ASP2 AFAD連携機能 - Supabase スキーマ定義
-- ============================================================================
--
-- このファイルには全テーブルの完全なスキーマ定義が含まれています。
-- 新規Supabaseプロジェクトで一括実行する場合に使用してください。
--
-- 実行方法:
--   psql -h your-project.supabase.co -U postgres -d postgres -f schema.sql
--
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. 拡張機能の有効化
-- ----------------------------------------------------------------------------

-- UUID生成
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- pgcrypto（暗号化機能）
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- pg_stat_statements（性能監視）
CREATE EXTENSION IF NOT EXISTS "pg_stat_statements";

-- pg_trgm（全文検索）
CREATE EXTENSION IF NOT EXISTS "pg_trgm";

-- ----------------------------------------------------------------------------
-- 2. カスタム型定義
-- ----------------------------------------------------------------------------

-- ポストバックステータス型
CREATE TYPE afad_postback_status AS ENUM (
  'pending',
  'success',
  'failed',
  'timeout',
  'cancelled'
);

-- リトライキューステータス型
CREATE TYPE afad_queue_status AS ENUM (
  'pending',
  'processing',
  'completed',
  'failed',
  'cancelled'
);

-- 承認ステータス型（AFAD仕様）
CREATE TYPE afad_approval_status AS ENUM (
  'pending',    -- 1: 承認待ち
  'approved',   -- 2: 承認
  'rejected'    -- 3: 否認
);

-- ----------------------------------------------------------------------------
-- 3. 共通関数定義
-- ----------------------------------------------------------------------------

-- updated_at 自動更新関数
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- processed_at 自動設定関数
CREATE OR REPLACE FUNCTION set_processed_at()
RETURNS TRIGGER AS $$
BEGIN
  IF NEW.processed_at IS NULL THEN
    NEW.processed_at = NOW();
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- 指数バックオフによる次回リトライ時刻計算関数
CREATE OR REPLACE FUNCTION get_next_retry_time(
  retry_count INTEGER,
  base_interval INTEGER DEFAULT 60
)
RETURNS TIMESTAMPTZ AS $$
BEGIN
  -- 指数バックオフ: 60秒, 120秒, 240秒, 480秒, 960秒, 1920秒
  RETURN NOW() + (base_interval * POWER(2, LEAST(retry_count, 5))) * INTERVAL '1 second';
END;
$$ LANGUAGE plpgsql IMMUTABLE;

-- ----------------------------------------------------------------------------
-- 4. テーブル作成
-- ----------------------------------------------------------------------------

-- ============================================================================
-- 4.1 afad_configs テーブル（AFAD連携設定）
-- ============================================================================

CREATE TABLE IF NOT EXISTS afad_configs (
  -- 主キー
  id BIGSERIAL PRIMARY KEY,

  -- 基本情報
  adwares_id BIGINT NOT NULL,
  enabled BOOLEAN NOT NULL DEFAULT true,

  -- セッションID受け取り設定
  parameter_name VARCHAR(100) NOT NULL DEFAULT 'afad_sid',

  -- ポストバック送信設定
  postback_url VARCHAR(2048) NOT NULL,
  group_id VARCHAR(100) NOT NULL,

  -- 送信パラメータ設定
  send_uid BOOLEAN NOT NULL DEFAULT true,
  send_uid2 BOOLEAN NOT NULL DEFAULT false,
  send_amount BOOLEAN NOT NULL DEFAULT true,
  approval_status SMALLINT CHECK (approval_status IS NULL OR (approval_status >= 1 AND approval_status <= 3)),

  -- タイムアウト・リトライ設定
  timeout_seconds SMALLINT NOT NULL DEFAULT 10
    CHECK (timeout_seconds >= 1 AND timeout_seconds <= 60),
  retry_max SMALLINT NOT NULL DEFAULT 3
    CHECK (retry_max >= 0 AND retry_max <= 10),

  -- その他設定
  url_passthrough BOOLEAN NOT NULL DEFAULT true,
  cookie_expire_days SMALLINT NOT NULL DEFAULT 30
    CHECK (cookie_expire_days >= 1 AND cookie_expire_days <= 365),
  priority SMALLINT NOT NULL DEFAULT 100
    CHECK (priority >= 1 AND priority <= 1000),

  -- メモ
  notes TEXT,

  -- タイムスタンプ
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  deleted_at TIMESTAMPTZ,

  -- 制約
  CONSTRAINT check_postback_url_https CHECK (postback_url LIKE 'https://%'),
  CONSTRAINT check_parameter_name_format CHECK (parameter_name ~ '^[a-zA-Z][a-zA-Z0-9_]{0,99}$')
);

-- インデックス
CREATE UNIQUE INDEX idx_afad_configs_adwares_id_unique
  ON afad_configs(adwares_id)
  WHERE deleted_at IS NULL;

CREATE INDEX idx_afad_configs_enabled
  ON afad_configs(enabled, deleted_at)
  WHERE enabled = true AND deleted_at IS NULL;

CREATE INDEX idx_afad_configs_priority
  ON afad_configs(priority, id)
  WHERE deleted_at IS NULL;

-- トリガー
CREATE TRIGGER trigger_afad_configs_updated_at
  BEFORE UPDATE ON afad_configs
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();

-- コメント
COMMENT ON TABLE afad_configs IS 'AFAD連携設定テーブル。広告ごとのAFAD連携パラメータを管理。';
COMMENT ON COLUMN afad_configs.adwares_id IS '広告ID（既存システムとの連携キー）';
COMMENT ON COLUMN afad_configs.parameter_name IS 'URLからセッションIDを取得するパラメータ名';
COMMENT ON COLUMN afad_configs.postback_url IS 'AFADポストバックURL（HTTPS必須）';
COMMENT ON COLUMN afad_configs.group_id IS 'AFAD広告グループID（gidパラメータ）';

-- ============================================================================
-- 4.2 access テーブル拡張（AFAD連携カラム追加）
-- ============================================================================
-- 注: 既存のaccessテーブルにカラムを追加する想定
-- 新規テーブルの場合は、既存のカラム定義も含める必要があります

-- 既存テーブルへのカラム追加（マイグレーション用）
DO $$
BEGIN
  -- afad_session_id カラム
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'access' AND column_name = 'afad_session_id'
  ) THEN
    ALTER TABLE access ADD COLUMN afad_session_id VARCHAR(255);
    ALTER TABLE access ADD CONSTRAINT check_afad_session_id_format
      CHECK (afad_session_id IS NULL OR afad_session_id ~ '^[a-zA-Z0-9_-]{1,255}$');
  END IF;

  -- afad_postback_sent カラム
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'access' AND column_name = 'afad_postback_sent'
  ) THEN
    ALTER TABLE access ADD COLUMN afad_postback_sent BOOLEAN NOT NULL DEFAULT false;
  END IF;

  -- afad_postback_status カラム
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'access' AND column_name = 'afad_postback_status'
  ) THEN
    ALTER TABLE access ADD COLUMN afad_postback_status VARCHAR(50);
    ALTER TABLE access ADD CONSTRAINT check_afad_postback_status
      CHECK (afad_postback_status IS NULL OR afad_postback_status IN ('success', 'failed', 'pending', 'timeout'));
  END IF;

  -- afad_postback_time カラム
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'access' AND column_name = 'afad_postback_time'
  ) THEN
    ALTER TABLE access ADD COLUMN afad_postback_time TIMESTAMPTZ;
  END IF;

  -- afad_postback_response カラム
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'access' AND column_name = 'afad_postback_response'
  ) THEN
    ALTER TABLE access ADD COLUMN afad_postback_response TEXT;
  END IF;

  -- afad_postback_retry_count カラム
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'access' AND column_name = 'afad_postback_retry_count'
  ) THEN
    ALTER TABLE access ADD COLUMN afad_postback_retry_count SMALLINT NOT NULL DEFAULT 0;
    ALTER TABLE access ADD CONSTRAINT check_afad_retry_count
      CHECK (afad_postback_retry_count >= 0 AND afad_postback_retry_count <= 100);
  END IF;

  -- afad_postback_error カラム
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'access' AND column_name = 'afad_postback_error'
  ) THEN
    ALTER TABLE access ADD COLUMN afad_postback_error TEXT;
  END IF;

  -- afad_config_id カラム（外部キー）
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'access' AND column_name = 'afad_config_id'
  ) THEN
    ALTER TABLE access ADD COLUMN afad_config_id BIGINT;
  END IF;
END $$;

-- インデックス（既存の場合はスキップ）
CREATE INDEX IF NOT EXISTS idx_access_afad_session_id
  ON access(afad_session_id)
  WHERE afad_session_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_access_afad_postback_pending
  ON access(afad_postback_sent, afad_postback_status, afad_postback_time)
  WHERE afad_postback_sent = false OR afad_postback_status IN ('failed', 'timeout');

CREATE INDEX IF NOT EXISTS idx_access_afad_config_id
  ON access(afad_config_id)
  WHERE afad_config_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_access_afad_date_status
  ON access(afad_postback_time, afad_postback_status);

-- 外部キー制約
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.table_constraints
    WHERE constraint_name = 'fk_access_afad_config'
  ) THEN
    ALTER TABLE access
      ADD CONSTRAINT fk_access_afad_config
      FOREIGN KEY (afad_config_id)
      REFERENCES afad_configs(id)
      ON DELETE SET NULL;
  END IF;
END $$;

-- ============================================================================
-- 4.3 afad_postback_logs テーブル（ポストバック送信ログ）
-- ============================================================================

CREATE TABLE IF NOT EXISTS afad_postback_logs (
  -- 主キー
  id BIGSERIAL PRIMARY KEY,

  -- 関連ID
  access_id BIGINT NOT NULL,
  afad_config_id BIGINT NOT NULL,
  adwares_id BIGINT NOT NULL, -- 非正規化（検索性能向上）
  afad_session_id VARCHAR(255) NOT NULL,

  -- リクエスト情報
  postback_url VARCHAR(2048) NOT NULL,
  request_params JSONB NOT NULL DEFAULT '{}'::jsonb,
  request_headers JSONB,

  -- レスポンス情報
  response_code SMALLINT CHECK (response_code IS NULL OR (response_code >= 100 AND response_code < 600)),
  response_body TEXT,
  response_headers JSONB,

  -- ステータス
  status VARCHAR(50) NOT NULL DEFAULT 'pending'
    CHECK (status IN ('success', 'failed', 'pending', 'timeout', 'cancelled')),
  error_message TEXT,
  retry_count SMALLINT NOT NULL DEFAULT 0
    CHECK (retry_count >= 0 AND retry_count <= 100),

  -- パフォーマンス情報
  execution_time_ms INTEGER CHECK (execution_time_ms IS NULL OR execution_time_ms >= 0),

  -- 追加情報
  ip_address INET,
  user_agent VARCHAR(500),

  -- 成果情報（AFAD仕様パラメータ）
  conversion_uid VARCHAR(255),
  conversion_uid2 VARCHAR(255),
  conversion_amount DECIMAL(15,2) CHECK (conversion_amount IS NULL OR conversion_amount >= 0),
  conversion_status SMALLINT CHECK (conversion_status IS NULL OR (conversion_status >= 1 AND conversion_status <= 3)),

  -- タイムスタンプ
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- インデックス
CREATE INDEX idx_afad_logs_access_id ON afad_postback_logs(access_id);
CREATE INDEX idx_afad_logs_session_id ON afad_postback_logs(afad_session_id);
CREATE INDEX idx_afad_logs_adwares_date ON afad_postback_logs(adwares_id, created_at DESC);
CREATE INDEX idx_afad_logs_status ON afad_postback_logs(status, created_at DESC);
CREATE INDEX idx_afad_logs_config_id ON afad_postback_logs(afad_config_id);
CREATE INDEX idx_afad_logs_created_at ON afad_postback_logs(created_at DESC);

-- JSONBインデックス
CREATE INDEX idx_afad_logs_request_params ON afad_postback_logs USING GIN(request_params);

-- 成果情報インデックス（検索性能向上）
CREATE INDEX idx_afad_logs_conversion_uid ON afad_postback_logs(conversion_uid) WHERE conversion_uid IS NOT NULL;
CREATE INDEX idx_afad_logs_conversion_uid2 ON afad_postback_logs(conversion_uid2) WHERE conversion_uid2 IS NOT NULL;
CREATE INDEX idx_afad_logs_conversion_amount ON afad_postback_logs(conversion_amount) WHERE conversion_amount IS NOT NULL;
CREATE INDEX idx_afad_logs_conversion_status ON afad_postback_logs(conversion_status) WHERE conversion_status IS NOT NULL;
CREATE INDEX idx_afad_logs_adwares_amount ON afad_postback_logs(adwares_id, conversion_amount DESC) WHERE conversion_amount IS NOT NULL;
CREATE INDEX idx_afad_logs_date_amount ON afad_postback_logs(created_at DESC, conversion_amount DESC);
CREATE INDEX idx_afad_logs_status_amount ON afad_postback_logs(conversion_status, conversion_amount DESC) WHERE conversion_status IS NOT NULL;

-- トリガー
CREATE TRIGGER trigger_afad_logs_updated_at
  BEFORE UPDATE ON afad_postback_logs
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();

-- 外部キー
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

-- コメント
COMMENT ON TABLE afad_postback_logs IS 'AFADポストバック送信ログテーブル。全ての送信履歴を記録。';
COMMENT ON COLUMN afad_postback_logs.request_params IS 'リクエストパラメータ（JSON形式）';
COMMENT ON COLUMN afad_postback_logs.execution_time_ms IS 'HTTPリクエスト実行時間（ミリ秒）';
COMMENT ON COLUMN afad_postback_logs.conversion_uid IS 'AFAD uid パラメータ：注文番号、申込み番号、会員IDなど';
COMMENT ON COLUMN afad_postback_logs.conversion_uid2 IS 'AFAD uid2 パラメータ：サブユーザー識別ID';
COMMENT ON COLUMN afad_postback_logs.conversion_amount IS 'AFAD amount パラメータ：成果金額または売上合計金額';
COMMENT ON COLUMN afad_postback_logs.conversion_status IS 'AFAD Status パラメータ：1=承認待ち、2=承認、3=否認';

-- ============================================================================
-- 4.4 afad_retry_queue テーブル（リトライキュー）
-- ============================================================================

CREATE TABLE IF NOT EXISTS afad_retry_queue (
  -- 主キー
  id BIGSERIAL PRIMARY KEY,

  -- 関連ID
  access_id BIGINT NOT NULL,
  afad_config_id BIGINT NOT NULL,
  afad_session_id VARCHAR(255) NOT NULL,

  -- リトライ制御
  retry_count SMALLINT NOT NULL DEFAULT 0,
  max_retry_count SMALLINT NOT NULL DEFAULT 3,
  next_retry_at TIMESTAMPTZ NOT NULL,
  last_error TEXT,

  -- ステータス
  status VARCHAR(50) NOT NULL DEFAULT 'pending'
    CHECK (status IN ('pending', 'processing', 'completed', 'failed', 'cancelled')),

  -- 優先度
  priority SMALLINT NOT NULL DEFAULT 100
    CHECK (priority >= 1 AND priority <= 1000),

  -- タイムスタンプ
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  processed_at TIMESTAMPTZ,

  -- 制約
  CONSTRAINT check_retry_counts CHECK (retry_count <= max_retry_count),
  CONSTRAINT check_next_retry_future CHECK (next_retry_at > created_at)
);

-- インデックス
CREATE UNIQUE INDEX idx_retry_queue_access_id
  ON afad_retry_queue(access_id)
  WHERE status IN ('pending', 'processing');

CREATE INDEX idx_retry_queue_next_retry
  ON afad_retry_queue(next_retry_at, priority, id)
  WHERE status = 'pending';

CREATE INDEX idx_retry_queue_status
  ON afad_retry_queue(status, created_at);

CREATE INDEX idx_retry_queue_session_id
  ON afad_retry_queue(afad_session_id);

-- トリガー
CREATE TRIGGER trigger_retry_queue_updated_at
  BEFORE UPDATE ON afad_retry_queue
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER trigger_retry_queue_processed_at
  BEFORE UPDATE ON afad_retry_queue
  FOR EACH ROW
  WHEN (NEW.status IN ('completed', 'failed', 'cancelled')
    AND OLD.status NOT IN ('completed', 'failed', 'cancelled'))
  EXECUTE FUNCTION set_processed_at();

-- 外部キー
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

-- コメント
COMMENT ON TABLE afad_retry_queue IS 'AFADポストバック��トライキュー。送信失敗時の再送管理。';
COMMENT ON COLUMN afad_retry_queue.next_retry_at IS '次回リトライ予定日時（指数バックオフ）';

-- ============================================================================
-- 4.5 afad_statistics テーブル（統計情報）
-- ============================================================================

CREATE TABLE IF NOT EXISTS afad_statistics (
  -- 主キー
  id BIGSERIAL PRIMARY KEY,

  -- 集計キー
  adwares_id BIGINT NOT NULL,
  date DATE NOT NULL,

  -- 統計データ
  total_clicks INTEGER NOT NULL DEFAULT 0 CHECK (total_clicks >= 0),
  afad_session_received INTEGER NOT NULL DEFAULT 0 CHECK (afad_session_received >= 0),
  total_conversions INTEGER NOT NULL DEFAULT 0 CHECK (total_conversions >= 0),
  afad_postback_attempted INTEGER NOT NULL DEFAULT 0 CHECK (afad_postback_attempted >= 0),
  afad_postback_success INTEGER NOT NULL DEFAULT 0 CHECK (afad_postback_success >= 0),
  afad_postback_failed INTEGER NOT NULL DEFAULT 0 CHECK (afad_postback_failed >= 0),
  afad_postback_timeout INTEGER NOT NULL DEFAULT 0 CHECK (afad_postback_timeout >= 0),
  total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00 CHECK (total_amount >= 0),
  avg_response_time_ms INTEGER,

  -- タイムスタンプ
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),

  -- 制約
  CONSTRAINT check_stats_logic CHECK (
    afad_postback_attempted >= (afad_postback_success + afad_postback_failed + afad_postback_timeout)
  )
);

-- インデックス
CREATE UNIQUE INDEX idx_afad_stats_unique ON afad_statistics(adwares_id, date);
CREATE INDEX idx_afad_stats_adwares_date ON afad_statistics(adwares_id, date DESC);
CREATE INDEX idx_afad_stats_date ON afad_statistics(date DESC);

-- トリガー
CREATE TRIGGER trigger_afad_stats_updated_at
  BEFORE UPDATE ON afad_statistics
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();

-- コメント
COMMENT ON TABLE afad_statistics IS 'AFAD連携の日次統計情報。ダッシュボード表示用。';

-- ============================================================================
-- 5. ビュー作成
-- ============================================================================

-- ポストバック送信サマリービュー
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

COMMENT ON VIEW v_afad_postback_summary IS 'AFAD連携設定ごとのポストバック送信サマリー';

-- リトライ待ちキュービュー
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

COMMENT ON VIEW v_afad_pending_retries IS 'リトライ待ちのキュー一覧';

-- ============================================================================
-- 6. ユーティリティ関数
-- ============================================================================

-- 古いログを削除する関数
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

COMMENT ON FUNCTION cleanup_old_logs IS '指定日数より古いログを削除';

-- 統計情報取得関数
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
  WHERE (p_adwares_id IS NULL OR a.adwares_id = p_adwares_id)
    AND DATE(a.created_at) BETWEEN p_start_date AND p_end_date
  GROUP BY DATE(apl.created_at)
  ORDER BY date DESC;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION get_afad_statistics IS 'AFAD連携の統計情報を期間指定で取得';

-- ============================================================================
-- 7. 初期データ投入（オプション）
-- ============================================================================

-- サンプル設定データ（開発環境用）
-- 本番環境では削除またはコメントアウトしてください

/*
INSERT INTO afad_configs (
  adwares_id,
  enabled,
  parameter_name,
  postback_url,
  group_id,
  send_uid,
  send_amount,
  approval_status,
  timeout_seconds,
  retry_max,
  notes
) VALUES
  (1, true, 'afad_sid', 'https://ac.example-afad.jp/12345/ac/', 'GRP001', true, true, 1, 10, 3, 'サンプル広告1'),
  (2, true, 'asid', 'https://ac.example-afad.jp/67890/ac/', 'GRP002', true, true, 1, 15, 5, 'サンプル広告2');
*/

-- ============================================================================
-- スキーマ作成完了
-- ============================================================================

-- 作成されたテーブル一覧を表示
SELECT
  table_name,
  pg_size_pretty(pg_total_relation_size(quote_ident(table_name))) as size
FROM information_schema.tables
WHERE table_schema = 'public'
  AND table_name LIKE 'afad%'
ORDER BY table_name;
