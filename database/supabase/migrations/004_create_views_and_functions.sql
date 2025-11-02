-- ============================================================================
-- Migration 004: ビューとユーティリティ関数の作成
-- ============================================================================
-- 作成日: 2025-11-02
-- 説明: データ参照用のビューと運用用のユーティリティ関数を作成
-- ============================================================================

BEGIN;

-- ----------------------------------------------------------------------------
-- 1. ビュー作成
-- ----------------------------------------------------------------------------

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

-- 日次統計ビュー
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
  PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY apl.execution_time_ms) as p95_execution_time_ms
FROM afad_postback_logs apl
JOIN afad_configs ac ON apl.afad_config_id = ac.id
WHERE ac.deleted_at IS NULL
GROUP BY DATE(apl.created_at), ac.adwares_id, ac.group_id
ORDER BY date DESC, ac.adwares_id;

COMMENT ON VIEW v_afad_daily_stats IS 'AFAD連携の日次統計情報';

-- ----------------------------------------------------------------------------
-- 2. ユーティリティ関数
-- ----------------------------------------------------------------------------

-- 古いログを削除する関数
CREATE OR REPLACE FUNCTION cleanup_old_logs(days_to_keep INTEGER DEFAULT 90)
RETURNS INTEGER AS $$
DECLARE
  deleted_count INTEGER;
BEGIN
  DELETE FROM afad_postback_logs
  WHERE created_at < NOW() - (days_to_keep || ' days')::INTERVAL;

  GET DIAGNOSTICS deleted_count = ROW_COUNT;

  -- 削除ログを記録
  RAISE NOTICE 'Deleted % old log records (older than % days)', deleted_count, days_to_keep;

  RETURN deleted_count;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION cleanup_old_logs IS '指定日数より古いログを削除';

-- 完了したリトライキューをクリーンアップする関数
CREATE OR REPLACE FUNCTION cleanup_completed_retry_queue(days_to_keep INTEGER DEFAULT 7)
RETURNS INTEGER AS $$
DECLARE
  deleted_count INTEGER;
BEGIN
  DELETE FROM afad_retry_queue
  WHERE status IN ('completed', 'failed', 'cancelled')
    AND processed_at < NOW() - (days_to_keep || ' days')::INTERVAL;

  GET DIAGNOSTICS deleted_count = ROW_COUNT;

  RAISE NOTICE 'Deleted % completed retry queue records (older than % days)', deleted_count, days_to_keep;

  RETURN deleted_count;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION cleanup_completed_retry_queue IS '完了したリトライキューを削除';

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

-- リトライ対象のレコードを取得する関数
CREATE OR REPLACE FUNCTION get_retry_targets(
  batch_size INTEGER DEFAULT 100
)
RETURNS TABLE (
  id BIGINT,
  access_id BIGINT,
  afad_config_id BIGINT,
  afad_session_id VARCHAR,
  retry_count SMALLINT
) AS $$
BEGIN
  RETURN QUERY
  SELECT
    arq.id,
    arq.access_id,
    arq.afad_config_id,
    arq.afad_session_id,
    arq.retry_count
  FROM afad_retry_queue arq
  WHERE arq.status = 'pending'
    AND arq.next_retry_at <= NOW()
    AND arq.retry_count < arq.max_retry_count
  ORDER BY arq.next_retry_at, arq.priority, arq.id
  LIMIT batch_size
  FOR UPDATE SKIP LOCKED;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION get_retry_targets IS 'リトライ対象のキューをバッチ取得';

-- ポストバック送信レートを計算する関数
CREATE OR REPLACE FUNCTION calculate_postback_rate(
  p_adwares_id BIGINT,
  p_hours INTEGER DEFAULT 24
)
RETURNS TABLE (
  total_attempts BIGINT,
  success_count BIGINT,
  failed_count BIGINT,
  timeout_count BIGINT,
  success_rate NUMERIC(5,2),
  avg_response_time_ms NUMERIC(10,2)
) AS $$
BEGIN
  RETURN QUERY
  SELECT
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
    ROUND(AVG(apl.execution_time_ms), 2) as avg_response_time_ms
  FROM afad_postback_logs apl
  WHERE (p_adwares_id IS NULL OR apl.adwares_id = p_adwares_id)
    AND apl.created_at > NOW() - (p_hours || ' hours')::INTERVAL;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION calculate_postback_rate IS '指定時間内のポストバック成功率を計算';

-- AFAD設定の検証関数
CREATE OR REPLACE FUNCTION validate_afad_config(
  p_config_id BIGINT
)
RETURNS TABLE (
  is_valid BOOLEAN,
  error_messages TEXT[]
) AS $$
DECLARE
  config_record RECORD;
  errors TEXT[] := ARRAY[]::TEXT[];
BEGIN
  SELECT * INTO config_record
  FROM afad_configs
  WHERE id = p_config_id AND deleted_at IS NULL;

  IF NOT FOUND THEN
    RETURN QUERY SELECT false, ARRAY['設定が見つかりません']::TEXT[];
    RETURN;
  END IF;

  -- ポストバックURLの検証
  IF config_record.postback_url NOT LIKE 'https://%' THEN
    errors := array_append(errors, 'ポストバックURLはHTTPSである必要があります');
  END IF;

  -- グループIDの検証
  IF LENGTH(config_record.group_id) = 0 THEN
    errors := array_append(errors, 'グループIDが設定されていません');
  END IF;

  -- パラメータ名の検証
  IF config_record.parameter_name !~ '^[a-zA-Z][a-zA-Z0-9_]{0,99}$' THEN
    errors := array_append(errors, 'パラメータ名の形式が不正です');
  END IF;

  RETURN QUERY SELECT (array_length(errors, 1) IS NULL), errors;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION validate_afad_config IS 'AFAD設定の妥当性を検証';

-- テーブルサイズ監視関数
CREATE OR REPLACE FUNCTION get_afad_table_sizes()
RETURNS TABLE (
  table_name TEXT,
  row_count BIGINT,
  total_size TEXT,
  table_size TEXT,
  indexes_size TEXT
) AS $$
BEGIN
  RETURN QUERY
  SELECT
    t.tablename::TEXT,
    c.reltuples::BIGINT as row_count,
    pg_size_pretty(pg_total_relation_size(c.oid)) as total_size,
    pg_size_pretty(pg_table_size(c.oid)) as table_size,
    pg_size_pretty(pg_indexes_size(c.oid)) as indexes_size
  FROM pg_tables t
  JOIN pg_class c ON t.tablename = c.relname
  WHERE t.schemaname = 'public'
    AND t.tablename LIKE 'afad%'
  ORDER BY pg_total_relation_size(c.oid) DESC;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION get_afad_table_sizes IS 'AFADテーブルのサイズ情報を取得';

COMMIT;
