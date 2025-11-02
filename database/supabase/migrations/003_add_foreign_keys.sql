-- ============================================================================
-- Migration 003: 外部キー制約の追加
-- ============================================================================
-- 作成日: 2025-11-02
-- 説明: テーブル間の外部キー制約を追加
-- ============================================================================

BEGIN;

-- ----------------------------------------------------------------------------
-- 1. afad_postback_logs の外部キー
-- ----------------------------------------------------------------------------

-- access テーブルへの外部キー
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.table_constraints
    WHERE constraint_name = 'fk_logs_access'
  ) THEN
    ALTER TABLE afad_postback_logs
      ADD CONSTRAINT fk_logs_access
      FOREIGN KEY (access_id)
      REFERENCES access(id)
      ON DELETE CASCADE;
  END IF;
END $$;

-- afad_configs テーブルへの外部キー
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.table_constraints
    WHERE constraint_name = 'fk_logs_config'
  ) THEN
    ALTER TABLE afad_postback_logs
      ADD CONSTRAINT fk_logs_config
      FOREIGN KEY (afad_config_id)
      REFERENCES afad_configs(id)
      ON DELETE RESTRICT;
  END IF;
END $$;

-- ----------------------------------------------------------------------------
-- 2. afad_retry_queue の外部キー
-- ----------------------------------------------------------------------------

-- access テーブルへの外部キー
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.table_constraints
    WHERE constraint_name = 'fk_queue_access'
  ) THEN
    ALTER TABLE afad_retry_queue
      ADD CONSTRAINT fk_queue_access
      FOREIGN KEY (access_id)
      REFERENCES access(id)
      ON DELETE CASCADE;
  END IF;
END $$;

-- afad_configs テーブルへの外部キー
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.table_constraints
    WHERE constraint_name = 'fk_queue_config'
  ) THEN
    ALTER TABLE afad_retry_queue
      ADD CONSTRAINT fk_queue_config
      FOREIGN KEY (afad_config_id)
      REFERENCES afad_configs(id)
      ON DELETE RESTRICT;
  END IF;
END $$;

COMMIT;
