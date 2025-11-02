-- ============================================================================
-- Migration 005: Row Level Security (RLS) ポリシーの作成
-- ============================================================================
-- 作成日: 2025-11-02
-- 説明: テーブルごとのアクセス制御ポリシーを設定
-- ============================================================================

BEGIN;

-- ----------------------------------------------------------------------------
-- 1. afad_configs テーブルのRLS
-- ----------------------------------------------------------------------------

-- RLS有効化
ALTER TABLE afad_configs ENABLE ROW LEVEL SECURITY;

-- 管理者は全てのレコードにアクセス可能
CREATE POLICY policy_afad_configs_admin ON afad_configs
  FOR ALL
  TO authenticated
  USING (
    (current_setting('request.jwt.claims', true)::json->>'role' = 'admin')
  );

-- 一般ユーザーは自分の広告の設定のみ参照可能
CREATE POLICY policy_afad_configs_user_read ON afad_configs
  FOR SELECT
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM user_adwares_permissions
      WHERE user_id = auth.uid()
        AND adwares_id = afad_configs.adwares_id
    )
  );

-- サービスロール（バックエンド）は全てアクセス可能
CREATE POLICY policy_afad_configs_service_role ON afad_configs
  FOR ALL
  TO service_role
  USING (true)
  WITH CHECK (true);

-- APIキーによるアクセス（読み取りのみ）
CREATE POLICY policy_afad_configs_api_key ON afad_configs
  FOR SELECT
  TO anon
  USING (
    EXISTS (
      SELECT 1 FROM api_keys
      WHERE key = current_setting('request.headers', true)::json->>'x-api-key'
        AND is_active = true
        AND expires_at > NOW()
        AND 'afad:read' = ANY(permissions)
    )
  );

-- ----------------------------------------------------------------------------
-- 2. afad_postback_logs テーブルのRLS
-- ----------------------------------------------------------------------------

-- RLS有効化
ALTER TABLE afad_postback_logs ENABLE ROW LEVEL SECURITY;

-- 管理者は全てのログにアクセス可能
CREATE POLICY policy_afad_logs_admin ON afad_postback_logs
  FOR ALL
  TO authenticated
  USING (
    (current_setting('request.jwt.claims', true)::json->>'role' = 'admin')
  );

-- 一般ユーザーは自分の広告のログのみ参照可能
CREATE POLICY policy_afad_logs_user_read ON afad_postback_logs
  FOR SELECT
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM user_adwares_permissions
      WHERE user_id = auth.uid()
        AND adwares_id = afad_postback_logs.adwares_id
    )
  );

-- サービスロール（バックエンド）は全てアクセス可能
CREATE POLICY policy_afad_logs_service_role ON afad_postback_logs
  FOR ALL
  TO service_role
  USING (true)
  WITH CHECK (true);

-- APIキーによるアクセス（読み取りのみ）
CREATE POLICY policy_afad_logs_api_key ON afad_postback_logs
  FOR SELECT
  TO anon
  USING (
    EXISTS (
      SELECT 1 FROM api_keys
      WHERE key = current_setting('request.headers', true)::json->>'x-api-key'
        AND is_active = true
        AND expires_at > NOW()
        AND 'afad:logs:read' = ANY(permissions)
    )
  );

-- ----------------------------------------------------------------------------
-- 3. afad_retry_queue テーブルのRLS
-- ----------------------------------------------------------------------------

-- RLS有効化
ALTER TABLE afad_retry_queue ENABLE ROW LEVEL SECURITY;

-- 管理者のみアクセス可能
CREATE POLICY policy_retry_queue_admin ON afad_retry_queue
  FOR ALL
  TO authenticated
  USING (
    (current_setting('request.jwt.claims', true)::json->>'role' = 'admin')
  );

-- サービスロール（バックエンド）は全てアクセス可能
CREATE POLICY policy_retry_queue_service_role ON afad_retry_queue
  FOR ALL
  TO service_role
  USING (true)
  WITH CHECK (true);

-- ----------------------------------------------------------------------------
-- 4. afad_statistics テーブルのRLS
-- ----------------------------------------------------------------------------

-- RLS有効化
ALTER TABLE afad_statistics ENABLE ROW LEVEL SECURITY;

-- 管理者は全ての統計にアクセス可能
CREATE POLICY policy_afad_stats_admin ON afad_statistics
  FOR ALL
  TO authenticated
  USING (
    (current_setting('request.jwt.claims', true)::json->>'role' = 'admin')
  );

-- 一般ユーザーは自分の広告の統計のみ参照可能
CREATE POLICY policy_afad_stats_user_read ON afad_statistics
  FOR SELECT
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM user_adwares_permissions
      WHERE user_id = auth.uid()
        AND adwares_id = afad_statistics.adwares_id
    )
  );

-- サービスロール（バックエンド）は全てアクセス可能
CREATE POLICY policy_afad_stats_service_role ON afad_statistics
  FOR ALL
  TO service_role
  USING (true)
  WITH CHECK (true);

-- APIキーによるアクセス（読み取りのみ）
CREATE POLICY policy_afad_stats_api_key ON afad_statistics
  FOR SELECT
  TO anon
  USING (
    EXISTS (
      SELECT 1 FROM api_keys
      WHERE key = current_setting('request.headers', true)::json->>'x-api-key'
        AND is_active = true
        AND expires_at > NOW()
        AND 'afad:stats:read' = ANY(permissions)
    )
  );

-- ----------------------------------------------------------------------------
-- 5. ビューに対するGRANT
-- ----------------------------------------------------------------------------

-- authenticated ユーザーにビューへのアクセス権を付与
GRANT SELECT ON v_afad_postback_summary TO authenticated;
GRANT SELECT ON v_afad_pending_retries TO authenticated;
GRANT SELECT ON v_afad_daily_stats TO authenticated;

-- service_role にビューへのアクセス権を付与
GRANT SELECT ON v_afad_postback_summary TO service_role;
GRANT SELECT ON v_afad_pending_retries TO service_role;
GRANT SELECT ON v_afad_daily_stats TO service_role;

-- ----------------------------------------------------------------------------
-- 6. 関数に対するGRANT
-- ----------------------------------------------------------------------------

-- authenticated ユーザーに統計関数の実行権を付与
GRANT EXECUTE ON FUNCTION get_afad_statistics TO authenticated;
GRANT EXECUTE ON FUNCTION calculate_postback_rate TO authenticated;
GRANT EXECUTE ON FUNCTION get_afad_table_sizes TO authenticated;

-- service_role に全ての関数の実行権を付与
GRANT EXECUTE ON ALL FUNCTIONS IN SCHEMA public TO service_role;

-- ----------------------------------------------------------------------------
-- 7. RLS ポリシーのテスト用コメント
-- ----------------------------------------------------------------------------

COMMENT ON POLICY policy_afad_configs_admin ON afad_configs IS '管理者は全てのAFAD設定にアクセス可能';
COMMENT ON POLICY policy_afad_configs_user_read ON afad_configs IS '一般ユーザーは自分の広告の設定のみ参照可能';
COMMENT ON POLICY policy_afad_configs_service_role ON afad_configs IS 'サービスロールは全てアクセス可能';

COMMENT ON POLICY policy_afad_logs_admin ON afad_postback_logs IS '管理者は全てのログにアクセス可能';
COMMENT ON POLICY policy_afad_logs_user_read ON afad_postback_logs IS '一般ユーザーは自分の広告のログのみ参照可能';
COMMENT ON POLICY policy_afad_logs_service_role ON afad_postback_logs IS 'サービスロールは全てアクセス可能';

COMMIT;

-- ----------------------------------------------------------------------------
-- RLS ポリシーの確認クエリ
-- ----------------------------------------------------------------------------

-- 以下のクエリで設定されたポリシーを確認できます
/*
SELECT
  schemaname,
  tablename,
  policyname,
  permissive,
  roles,
  cmd,
  qual,
  with_check
FROM pg_policies
WHERE tablename LIKE 'afad%'
ORDER BY tablename, policyname;
*/
