-- ============================================================================
-- Migration 002: accessテーブルにAFAD連携カラムを追加
-- ============================================================================
-- 作成日: 2025-11-02
-- 説明: 既存のaccessテーブルにAFAD連携用のカラムを追加
-- ============================================================================

BEGIN;

-- ----------------------------------------------------------------------------
-- 1. カラム追加
-- ----------------------------------------------------------------------------

-- afad_session_id カラム
ALTER TABLE access ADD COLUMN IF NOT EXISTS afad_session_id VARCHAR(255);
ALTER TABLE access ADD CONSTRAINT check_afad_session_id_format
  CHECK (afad_session_id IS NULL OR afad_session_id ~ '^[a-zA-Z0-9_-]{1,255}$');

-- afad_postback_sent カラム
ALTER TABLE access ADD COLUMN IF NOT EXISTS afad_postback_sent BOOLEAN NOT NULL DEFAULT false;

-- afad_postback_status カラム
ALTER TABLE access ADD COLUMN IF NOT EXISTS afad_postback_status VARCHAR(50);
ALTER TABLE access ADD CONSTRAINT check_afad_postback_status
  CHECK (afad_postback_status IS NULL OR afad_postback_status IN ('success', 'failed', 'pending', 'timeout'));

-- afad_postback_time カラム
ALTER TABLE access ADD COLUMN IF NOT EXISTS afad_postback_time TIMESTAMPTZ;

-- afad_postback_response カラム
ALTER TABLE access ADD COLUMN IF NOT EXISTS afad_postback_response TEXT;

-- afad_postback_retry_count カラム
ALTER TABLE access ADD COLUMN IF NOT EXISTS afad_postback_retry_count SMALLINT NOT NULL DEFAULT 0;
ALTER TABLE access ADD CONSTRAINT check_afad_retry_count
  CHECK (afad_postback_retry_count >= 0 AND afad_postback_retry_count <= 100);

-- afad_postback_error カラム
ALTER TABLE access ADD COLUMN IF NOT EXISTS afad_postback_error TEXT;

-- afad_config_id カラム（外部キー）
ALTER TABLE access ADD COLUMN IF NOT EXISTS afad_config_id BIGINT;

-- ----------------------------------------------------------------------------
-- 2. インデックス作成
-- ----------------------------------------------------------------------------

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

-- ----------------------------------------------------------------------------
-- 3. 外部キー制約
-- ----------------------------------------------------------------------------

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

-- ----------------------------------------------------------------------------
-- 4. 既存データの初期化（オプション）
-- ----------------------------------------------------------------------------

-- 既存レコードのAFAD関連カラムを初期化
-- UPDATE access SET
--   afad_postback_sent = false,
--   afad_postback_retry_count = 0
-- WHERE afad_session_id IS NOT NULL
--   AND afad_postback_sent IS NULL;

COMMIT;
