-- ============================================================================
-- Migration 006: 成果情報カラムの追加（パフォーマンス最適化）
-- ============================================================================
-- 作成日: 2025-11-02
-- 説明: afad_postback_logsに成果情報の個別カラムを追加し、検索・集計性能を向上
-- 理由: AFAD仕様のuid, uid2, amount, Statusを個別カラム化して高速検索を実現
-- ============================================================================

BEGIN;

-- ----------------------------------------------------------------------------
-- 1. afad_postback_logs テーブルにカラム追加
-- ----------------------------------------------------------------------------

-- conversion_uid カラム（ユーザー識別ID）
ALTER TABLE afad_postback_logs
  ADD COLUMN IF NOT EXISTS conversion_uid VARCHAR(255);

COMMENT ON COLUMN afad_postback_logs.conversion_uid IS 'AFAD uid パラメータ：注文番号、申込み番号、会員IDなど';

-- conversion_uid2 カラム（サブユーザー識別ID）
ALTER TABLE afad_postback_logs
  ADD COLUMN IF NOT EXISTS conversion_uid2 VARCHAR(255);

COMMENT ON COLUMN afad_postback_logs.conversion_uid2 IS 'AFAD uid2 パラメータ：サブユーザー識別ID';

-- conversion_amount カラム（成果金額）
ALTER TABLE afad_postback_logs
  ADD COLUMN IF NOT EXISTS conversion_amount DECIMAL(15,2);

ALTER TABLE afad_postback_logs
  ADD CONSTRAINT check_conversion_amount_positive
  CHECK (conversion_amount IS NULL OR conversion_amount >= 0);

COMMENT ON COLUMN afad_postback_logs.conversion_amount IS 'AFAD amount パラメータ：成果金額または売上合計金額';

-- conversion_status カラム（承認ステータス）
ALTER TABLE afad_postback_logs
  ADD COLUMN IF NOT EXISTS conversion_status SMALLINT;

ALTER TABLE afad_postback_logs
  ADD CONSTRAINT check_conversion_status_range
  CHECK (conversion_status IS NULL OR (conversion_status >= 1 AND conversion_status <= 3));

COMMENT ON COLUMN afad_postback_logs.conversion_status IS 'AFAD Status パラメータ：1=承認待ち、2=承認、3=否認';

-- ----------------------------------------------------------------------------
-- 2. インデックス作成（検索性能向上）
-- ----------------------------------------------------------------------------

-- ユーザーIDでの検索用
CREATE INDEX IF NOT EXISTS idx_afad_logs_conversion_uid
  ON afad_postback_logs(conversion_uid)
  WHERE conversion_uid IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_afad_logs_conversion_uid2
  ON afad_postback_logs(conversion_uid2)
  WHERE conversion_uid2 IS NOT NULL;

-- 金額での範囲検索・集計用
CREATE INDEX IF NOT EXISTS idx_afad_logs_conversion_amount
  ON afad_postback_logs(conversion_amount)
  WHERE conversion_amount IS NOT NULL;

-- 承認ステータス別集計用
CREATE INDEX IF NOT EXISTS idx_afad_logs_conversion_status
  ON afad_postback_logs(conversion_status)
  WHERE conversion_status IS NOT NULL;

-- 複合インデックス：広告ID + 金額（レポート用）
CREATE INDEX IF NOT EXISTS idx_afad_logs_adwares_amount
  ON afad_postback_logs(adwares_id, conversion_amount DESC)
  WHERE conversion_amount IS NOT NULL;

-- 複合インデックス：日付 + 金額（時系列レポート用）
CREATE INDEX IF NOT EXISTS idx_afad_logs_date_amount
  ON afad_postback_logs(created_at DESC, conversion_amount DESC);

-- 複合インデックス：ステータス + 金額（承認管理用）
CREATE INDEX IF NOT EXISTS idx_afad_logs_status_amount
  ON afad_postback_logs(conversion_status, conversion_amount DESC)
  WHERE conversion_status IS NOT NULL;

-- ----------------------------------------------------------------------------
-- 3. 既存データの移行（JSONBから個別カラムへ）
-- ----------------------------------------------------------------------------

-- 既存レコードのrequest_paramsからデータを抽出して個別カラムに設定
UPDATE afad_postback_logs
SET
  conversion_uid = request_params->>'uid',
  conversion_uid2 = request_params->>'uid2',
  conversion_amount = CASE
    WHEN request_params->>'amount' ~ '^[0-9]+\.?[0-9]*$'
    THEN (request_params->>'amount')::DECIMAL(15,2)
    ELSE NULL
  END,
  conversion_status = CASE
    WHEN request_params->>'status' ~ '^[123]$'
    THEN (request_params->>'status')::SMALLINT
    ELSE NULL
  END
WHERE request_params IS NOT NULL
  AND (conversion_uid IS NULL OR conversion_uid2 IS NULL OR conversion_amount IS NULL OR conversion_status IS NULL);

-- ----------------------------------------------------------------------------
-- 4. 統計テーブルにもカラム追加（オプション）
-- ----------------------------------------------------------------------------

-- afad_statisticsテーブルに承認ステータス別の統計を追加
ALTER TABLE afad_statistics
  ADD COLUMN IF NOT EXISTS afad_postback_pending INTEGER NOT NULL DEFAULT 0 CHECK (afad_postback_pending >= 0);

ALTER TABLE afad_statistics
  ADD COLUMN IF NOT EXISTS afad_postback_approved INTEGER NOT NULL DEFAULT 0 CHECK (afad_postback_approved >= 0);

ALTER TABLE afad_statistics
  ADD COLUMN IF NOT EXISTS afad_postback_rejected INTEGER NOT NULL DEFAULT 0 CHECK (afad_postback_rejected >= 0);

COMMENT ON COLUMN afad_statistics.afad_postback_pending IS 'ステータス1（承認待ち）の件数';
COMMENT ON COLUMN afad_statistics.afad_postback_approved IS 'ステータス2（承認）の件数';
COMMENT ON COLUMN afad_statistics.afad_postback_rejected IS 'ステータス3（否認）の件数';

-- ----------------------------------------------------------------------------
-- 5. ビューの更新
-- ----------------------------------------------------------------------------

-- v_afad_postback_summary ビューを再作成（成果情報含む）
DROP VIEW IF EXISTS v_afad_postback_summary;

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
  AVG(apl.execution_time_ms) as avg_execution_time_ms,
  -- 成果情報の集計
  SUM(apl.conversion_amount) as total_conversion_amount,
  COUNT(CASE WHEN apl.conversion_status = 1 THEN 1 END) as pending_count,
  COUNT(CASE WHEN apl.conversion_status = 2 THEN 1 END) as approved_count,
  COUNT(CASE WHEN apl.conversion_status = 3 THEN 1 END) as rejected_count
FROM afad_configs ac
LEFT JOIN afad_postback_logs apl ON ac.id = apl.afad_config_id
WHERE ac.deleted_at IS NULL
GROUP BY ac.id, ac.adwares_id, ac.group_id, ac.enabled;

COMMENT ON VIEW v_afad_postback_summary IS 'AFAD連携設定ごとのポストバック送信サマリー（成果情報含む）';

-- v_afad_daily_stats ビューを再作成（成果情報含む）
DROP VIEW IF EXISTS v_afad_daily_stats;

CREATE OR REPLACE VIEW v_afad_daily_stats AS
SELECT
  DATE(apl.created_at) as date,
  ac.adwares_id,
  ac.group_id as afad_group_id,
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
  AVG(apl.execution_time_ms) as avg_execution_time_ms,
  PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY apl.execution_time_ms) as median_execution_time_ms,
  PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY apl.execution_time_ms) as p95_execution_time_ms,
  -- 成果情報の集計
  COUNT(CASE WHEN apl.conversion_uid IS NOT NULL THEN 1 END) as conversion_count,
  SUM(apl.conversion_amount) as total_conversion_amount,
  AVG(apl.conversion_amount) as avg_conversion_amount,
  COUNT(CASE WHEN apl.conversion_status = 1 THEN 1 END) as pending_count,
  COUNT(CASE WHEN apl.conversion_status = 2 THEN 1 END) as approved_count,
  COUNT(CASE WHEN apl.conversion_status = 3 THEN 1 END) as rejected_count
FROM afad_postback_logs apl
JOIN afad_configs ac ON apl.afad_config_id = ac.id
WHERE ac.deleted_at IS NULL
GROUP BY DATE(apl.created_at), ac.adwares_id, ac.group_id
ORDER BY date DESC, ac.adwares_id;

COMMENT ON VIEW v_afad_daily_stats IS 'AFAD連携の日次統計情報（成果情報含む）';

-- ----------------------------------------------------------------------------
-- 6. 成果情報検索関数の追加
-- ----------------------------------------------------------------------------

-- ユーザーIDで成果を検索する関数
CREATE OR REPLACE FUNCTION find_conversions_by_uid(
  p_uid VARCHAR,
  p_limit INTEGER DEFAULT 100
)
RETURNS TABLE (
  id BIGINT,
  afad_session_id VARCHAR,
  conversion_uid VARCHAR,
  conversion_uid2 VARCHAR,
  conversion_amount DECIMAL,
  conversion_status SMALLINT,
  created_at TIMESTAMPTZ,
  status VARCHAR
) AS $$
BEGIN
  RETURN QUERY
  SELECT
    apl.id,
    apl.afad_session_id,
    apl.conversion_uid,
    apl.conversion_uid2,
    apl.conversion_amount,
    apl.conversion_status,
    apl.created_at,
    apl.status
  FROM afad_postback_logs apl
  WHERE apl.conversion_uid = p_uid
     OR apl.conversion_uid2 = p_uid
  ORDER BY apl.created_at DESC
  LIMIT p_limit;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION find_conversions_by_uid IS 'ユーザーIDで成果を検索';

-- 金額範囲で成果を検索する関数
CREATE OR REPLACE FUNCTION find_conversions_by_amount_range(
  p_min_amount DECIMAL,
  p_max_amount DECIMAL,
  p_limit INTEGER DEFAULT 100
)
RETURNS TABLE (
  id BIGINT,
  afad_session_id VARCHAR,
  adwares_id BIGINT,
  conversion_amount DECIMAL,
  conversion_status SMALLINT,
  created_at TIMESTAMPTZ,
  status VARCHAR
) AS $$
BEGIN
  RETURN QUERY
  SELECT
    apl.id,
    apl.afad_session_id,
    apl.adwares_id,
    apl.conversion_amount,
    apl.conversion_status,
    apl.created_at,
    apl.status
  FROM afad_postback_logs apl
  WHERE apl.conversion_amount BETWEEN p_min_amount AND p_max_amount
  ORDER BY apl.conversion_amount DESC, apl.created_at DESC
  LIMIT p_limit;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION find_conversions_by_amount_range IS '金額範囲で成果を検索';

-- 承認ステータス別の集計関数
CREATE OR REPLACE FUNCTION get_conversion_stats_by_status(
  p_adwares_id BIGINT,
  p_start_date DATE,
  p_end_date DATE
)
RETURNS TABLE (
  status_name TEXT,
  count BIGINT,
  total_amount NUMERIC,
  avg_amount NUMERIC
) AS $$
BEGIN
  RETURN QUERY
  SELECT
    CASE apl.conversion_status
      WHEN 1 THEN '承認待ち'
      WHEN 2 THEN '承認'
      WHEN 3 THEN '否認'
      ELSE '未設定'
    END as status_name,
    COUNT(apl.id) as count,
    COALESCE(SUM(apl.conversion_amount), 0) as total_amount,
    COALESCE(ROUND(AVG(apl.conversion_amount), 2), 0) as avg_amount
  FROM afad_postback_logs apl
  WHERE (p_adwares_id IS NULL OR apl.adwares_id = p_adwares_id)
    AND DATE(apl.created_at) BETWEEN p_start_date AND p_end_date
  GROUP BY apl.conversion_status
  ORDER BY apl.conversion_status;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION get_conversion_stats_by_status IS '承認ステータス別の成果統計を取得';

COMMIT;

-- ----------------------------------------------------------------------------
-- マイグレーション完了メッセージ
-- ----------------------------------------------------------------------------

DO $$
BEGIN
  RAISE NOTICE '================================================================';
  RAISE NOTICE 'Migration 006 completed successfully!';
  RAISE NOTICE '================================================================';
  RAISE NOTICE 'Added columns:';
  RAISE NOTICE '  - conversion_uid (VARCHAR)';
  RAISE NOTICE '  - conversion_uid2 (VARCHAR)';
  RAISE NOTICE '  - conversion_amount (DECIMAL)';
  RAISE NOTICE '  - conversion_status (SMALLINT)';
  RAISE NOTICE '';
  RAISE NOTICE 'Added indexes: 7 indexes for fast search';
  RAISE NOTICE 'Added functions: 3 new functions for conversion search';
  RAISE NOTICE 'Updated views: v_afad_postback_summary, v_afad_daily_stats';
  RAISE NOTICE '================================================================';
END $$;
