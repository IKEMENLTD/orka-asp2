-- ============================================================================
-- テストデータ投入スクリプト
-- ============================================================================
-- 作成日: 2025-11-02
-- 説明: 開発・テスト環境用のサンプルデータ
-- 注意: 本番環境では実行しないでください
-- ============================================================================

BEGIN;

-- ----------------------------------------------------------------------------
-- 1. afad_configs テーブルのテストデータ
-- ----------------------------------------------------------------------------

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
  cookie_expire_days,
  priority,
  notes
) VALUES
  (
    1,
    true,
    'afad_sid',
    'https://ac.test-afad.example.jp/12345/ac/',
    'GRP001',
    true,
    false,
    true,
    1, -- 承認待ち
    10,
    3,
    true,
    30,
    100,
    'テスト広告1 - 化粧品キャンペーン'
  ),
  (
    2,
    true,
    'afad_sid',
    'https://ac.test-afad.example.jp/67890/ac/',
    'GRP002',
    true,
    true,
    true,
    2, -- 承認
    15,
    5,
    true,
    60,
    50,
    'テスト広告2 - サブスクリプションサービス'
  ),
  (
    3,
    false,
    'asid',
    'https://ac.test-afad.example.jp/11111/ac/',
    'GRP003',
    true,
    false,
    false,
    1,
    10,
    3,
    false,
    30,
    200,
    'テスト広告3 - 無効化されている広告'
  ),
  (
    4,
    true,
    'afad_session',
    'https://ac.test-afad.example.jp/22222/ac/',
    'GRP004',
    true,
    true,
    true,
    1,
    20,
    10,
    true,
    90,
    10,
    'テスト広告4 - 高優先度広告'
  );

-- ----------------------------------------------------------------------------
-- 2. afad_statistics テーブルのテストデータ
-- ----------------------------------------------------------------------------

-- 過去7日分の統計データを生成
INSERT INTO afad_statistics (
  adwares_id,
  date,
  total_clicks,
  afad_session_received,
  total_conversions,
  afad_postback_attempted,
  afad_postback_success,
  afad_postback_failed,
  afad_postback_timeout,
  total_amount,
  avg_response_time_ms
)
SELECT
  adwares_id,
  CURRENT_DATE - (d || ' days')::INTERVAL,
  FLOOR(RANDOM() * 1000 + 100)::INTEGER,
  FLOOR(RANDOM() * 800 + 80)::INTEGER,
  FLOOR(RANDOM() * 100 + 10)::INTEGER,
  FLOOR(RANDOM() * 100 + 10)::INTEGER,
  FLOOR(RANDOM() * 90 + 8)::INTEGER,
  FLOOR(RANDOM() * 8 + 1)::INTEGER,
  FLOOR(RANDOM() * 2)::INTEGER,
  ROUND((RANDOM() * 100000 + 10000)::NUMERIC, 2),
  FLOOR(RANDOM() * 300 + 100)::INTEGER
FROM
  (SELECT unnest(ARRAY[1, 2, 4]) as adwares_id) ads
CROSS JOIN
  generate_series(0, 6) as d;

-- ----------------------------------------------------------------------------
-- 3. サンプルポストバックログ（最近のデータ）
-- ----------------------------------------------------------------------------

-- 注: このデータを投入するには、事前にaccessテーブルにデータが必要です
-- 実際の環境では、accessテーブルへのデータ投入後に実行してください

/*
INSERT INTO afad_postback_logs (
  access_id,
  afad_config_id,
  adwares_id,
  afad_session_id,
  postback_url,
  request_params,
  response_code,
  response_body,
  status,
  execution_time_ms,
  ip_address,
  user_agent
) VALUES
  (
    1,
    1,
    1,
    'TEST_SESSION_001',
    'https://ac.test-afad.example.jp/12345/ac/?gid=GRP001&af=TEST_SESSION_001&uid=ORDER001&amount=5000',
    '{"gid":"GRP001","af":"TEST_SESSION_001","uid":"ORDER001","amount":5000}'::jsonb,
    200,
    'OK',
    'success',
    145,
    '192.168.1.100'::inet,
    'ORKA-ASP2-AFAD/1.0'
  ),
  (
    2,
    1,
    1,
    'TEST_SESSION_002',
    'https://ac.test-afad.example.jp/12345/ac/?gid=GRP001&af=TEST_SESSION_002&uid=ORDER002&amount=8000',
    '{"gid":"GRP001","af":"TEST_SESSION_002","uid":"ORDER002","amount":8000}'::jsonb,
    500,
    'Internal Server Error',
    'failed',
    3520,
    '192.168.1.101'::inet,
    'ORKA-ASP2-AFAD/1.0'
  ),
  (
    3,
    2,
    2,
    'TEST_SESSION_003',
    'https://ac.test-afad.example.jp/67890/ac/?gid=GRP002&af=TEST_SESSION_003&uid=ORDER003&uid2=MEMBER123&amount=12000',
    '{"gid":"GRP002","af":"TEST_SESSION_003","uid":"ORDER003","uid2":"MEMBER123","amount":12000}'::jsonb,
    200,
    'OK',
    'success',
    98,
    '192.168.1.102'::inet,
    'ORKA-ASP2-AFAD/1.0'
  );
*/

-- ----------------------------------------------------------------------------
-- 4. サンプルリトライキュー
-- ----------------------------------------------------------------------------

/*
INSERT INTO afad_retry_queue (
  access_id,
  afad_config_id,
  afad_session_id,
  retry_count,
  max_retry_count,
  next_retry_at,
  last_error,
  status,
  priority
) VALUES
  (
    2,
    1,
    'TEST_SESSION_002',
    1,
    3,
    NOW() + INTERVAL '5 minutes',
    'HTTP 500 Internal Server Error',
    'pending',
    100
  );
*/

-- ----------------------------------------------------------------------------
-- 5. データ投入確認
-- ----------------------------------------------------------------------------

-- 投入されたデータの確認
SELECT 'afad_configs' as table_name, COUNT(*) as record_count FROM afad_configs
UNION ALL
SELECT 'afad_statistics', COUNT(*) FROM afad_statistics
UNION ALL
SELECT 'afad_postback_logs', COUNT(*) FROM afad_postback_logs
UNION ALL
SELECT 'afad_retry_queue', COUNT(*) FROM afad_retry_queue;

-- 設定の確認
SELECT
  id,
  adwares_id,
  enabled,
  group_id,
  postback_url,
  notes
FROM afad_configs
ORDER BY priority, id;

-- 統計データの確認
SELECT
  adwares_id,
  date,
  total_clicks,
  afad_session_received,
  total_conversions,
  afad_postback_success,
  ROUND((afad_postback_success::NUMERIC / NULLIF(afad_postback_attempted, 0)::NUMERIC * 100), 2) as success_rate
FROM afad_statistics
WHERE date >= CURRENT_DATE - INTERVAL '7 days'
ORDER BY date DESC, adwares_id
LIMIT 20;

COMMIT;

-- ----------------------------------------------------------------------------
-- テストデータ削除用クエリ（必要な場合）
-- ----------------------------------------------------------------------------

/*
BEGIN;

-- リトライキューを削除
DELETE FROM afad_retry_queue;

-- ポストバックログを削除
DELETE FROM afad_postback_logs;

-- 統計データを削除
DELETE FROM afad_statistics;

-- AFAD設定を削除
DELETE FROM afad_configs;

-- シーケンスをリセット
ALTER SEQUENCE afad_configs_id_seq RESTART WITH 1;
ALTER SEQUENCE afad_postback_logs_id_seq RESTART WITH 1;
ALTER SEQUENCE afad_retry_queue_id_seq RESTART WITH 1;
ALTER SEQUENCE afad_statistics_id_seq RESTART WITH 1;

COMMIT;
*/
