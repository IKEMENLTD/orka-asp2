-- ============================================================================
-- Migration 001: AFAD連携テーブル作成
-- ============================================================================
-- 作成日: 2025-11-02
-- 説明: AFAD連携機能の基本テーブルを作成
-- ============================================================================

BEGIN;

-- ----------------------------------------------------------------------------
-- 1. 拡張機能の有効化
-- ----------------------------------------------------------------------------

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";
CREATE EXTENSION IF NOT EXISTS "pg_stat_statements";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";

-- ----------------------------------------------------------------------------
-- 2. カスタム型定義
-- ----------------------------------------------------------------------------

CREATE TYPE afad_postback_status AS ENUM (
  'pending',
  'success',
  'failed',
  'timeout',
  'cancelled'
);

CREATE TYPE afad_queue_status AS ENUM (
  'pending',
  'processing',
  'completed',
  'failed',
  'cancelled'
);

-- ----------------------------------------------------------------------------
-- 3. 共通関数
-- ----------------------------------------------------------------------------

CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION set_processed_at()
RETURNS TRIGGER AS $$
BEGIN
  IF NEW.processed_at IS NULL THEN
    NEW.processed_at = NOW();
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION get_next_retry_time(
  retry_count INTEGER,
  base_interval INTEGER DEFAULT 60
)
RETURNS TIMESTAMPTZ AS $$
BEGIN
  RETURN NOW() + (base_interval * POWER(2, LEAST(retry_count, 5))) * INTERVAL '1 second';
END;
$$ LANGUAGE plpgsql IMMUTABLE;

-- ----------------------------------------------------------------------------
-- 4. afad_configs テーブル作成
-- ----------------------------------------------------------------------------

CREATE TABLE afad_configs (
  id BIGSERIAL PRIMARY KEY,
  adwares_id BIGINT NOT NULL,
  enabled BOOLEAN NOT NULL DEFAULT true,
  parameter_name VARCHAR(100) NOT NULL DEFAULT 'afad_sid',
  postback_url VARCHAR(2048) NOT NULL,
  group_id VARCHAR(100) NOT NULL,
  send_uid BOOLEAN NOT NULL DEFAULT true,
  send_uid2 BOOLEAN NOT NULL DEFAULT false,
  send_amount BOOLEAN NOT NULL DEFAULT true,
  approval_status SMALLINT CHECK (approval_status IS NULL OR (approval_status >= 1 AND approval_status <= 3)),
  timeout_seconds SMALLINT NOT NULL DEFAULT 10 CHECK (timeout_seconds >= 1 AND timeout_seconds <= 60),
  retry_max SMALLINT NOT NULL DEFAULT 3 CHECK (retry_max >= 0 AND retry_max <= 10),
  url_passthrough BOOLEAN NOT NULL DEFAULT true,
  cookie_expire_days SMALLINT NOT NULL DEFAULT 30 CHECK (cookie_expire_days >= 1 AND cookie_expire_days <= 365),
  priority SMALLINT NOT NULL DEFAULT 100 CHECK (priority >= 1 AND priority <= 1000),
  notes TEXT,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  deleted_at TIMESTAMPTZ,
  CONSTRAINT check_postback_url_https CHECK (postback_url LIKE 'https://%'),
  CONSTRAINT check_parameter_name_format CHECK (parameter_name ~ '^[a-zA-Z][a-zA-Z0-9_]{0,99}$')
);

CREATE UNIQUE INDEX idx_afad_configs_adwares_id_unique ON afad_configs(adwares_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_afad_configs_enabled ON afad_configs(enabled, deleted_at) WHERE enabled = true AND deleted_at IS NULL;
CREATE INDEX idx_afad_configs_priority ON afad_configs(priority, id) WHERE deleted_at IS NULL;

CREATE TRIGGER trigger_afad_configs_updated_at
  BEFORE UPDATE ON afad_configs
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();

-- ----------------------------------------------------------------------------
-- 5. afad_postback_logs テーブル作成
-- ----------------------------------------------------------------------------

CREATE TABLE afad_postback_logs (
  id BIGSERIAL PRIMARY KEY,
  access_id BIGINT NOT NULL,
  afad_config_id BIGINT NOT NULL,
  adwares_id BIGINT NOT NULL,
  afad_session_id VARCHAR(255) NOT NULL,
  postback_url VARCHAR(2048) NOT NULL,
  request_params JSONB NOT NULL DEFAULT '{}'::jsonb,
  request_headers JSONB,
  response_code SMALLINT CHECK (response_code IS NULL OR (response_code >= 100 AND response_code < 600)),
  response_body TEXT,
  response_headers JSONB,
  status VARCHAR(50) NOT NULL DEFAULT 'pending' CHECK (status IN ('success', 'failed', 'pending', 'timeout', 'cancelled')),
  error_message TEXT,
  retry_count SMALLINT NOT NULL DEFAULT 0 CHECK (retry_count >= 0 AND retry_count <= 100),
  execution_time_ms INTEGER CHECK (execution_time_ms IS NULL OR execution_time_ms >= 0),
  ip_address INET,
  user_agent VARCHAR(500),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_afad_logs_access_id ON afad_postback_logs(access_id);
CREATE INDEX idx_afad_logs_session_id ON afad_postback_logs(afad_session_id);
CREATE INDEX idx_afad_logs_adwares_date ON afad_postback_logs(adwares_id, created_at DESC);
CREATE INDEX idx_afad_logs_status ON afad_postback_logs(status, created_at DESC);
CREATE INDEX idx_afad_logs_config_id ON afad_postback_logs(afad_config_id);
CREATE INDEX idx_afad_logs_created_at ON afad_postback_logs(created_at DESC);
CREATE INDEX idx_afad_logs_request_params ON afad_postback_logs USING GIN(request_params);

CREATE TRIGGER trigger_afad_logs_updated_at
  BEFORE UPDATE ON afad_postback_logs
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();

-- ----------------------------------------------------------------------------
-- 6. afad_retry_queue テーブル作成
-- ----------------------------------------------------------------------------

CREATE TABLE afad_retry_queue (
  id BIGSERIAL PRIMARY KEY,
  access_id BIGINT NOT NULL,
  afad_config_id BIGINT NOT NULL,
  afad_session_id VARCHAR(255) NOT NULL,
  retry_count SMALLINT NOT NULL DEFAULT 0,
  max_retry_count SMALLINT NOT NULL DEFAULT 3,
  next_retry_at TIMESTAMPTZ NOT NULL,
  last_error TEXT,
  status VARCHAR(50) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed', 'cancelled')),
  priority SMALLINT NOT NULL DEFAULT 100 CHECK (priority >= 1 AND priority <= 1000),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  processed_at TIMESTAMPTZ,
  CONSTRAINT check_retry_counts CHECK (retry_count <= max_retry_count),
  CONSTRAINT check_next_retry_future CHECK (next_retry_at > created_at)
);

CREATE UNIQUE INDEX idx_retry_queue_access_id ON afad_retry_queue(access_id) WHERE status IN ('pending', 'processing');
CREATE INDEX idx_retry_queue_next_retry ON afad_retry_queue(next_retry_at, priority, id) WHERE status = 'pending';
CREATE INDEX idx_retry_queue_status ON afad_retry_queue(status, created_at);
CREATE INDEX idx_retry_queue_session_id ON afad_retry_queue(afad_session_id);

CREATE TRIGGER trigger_retry_queue_updated_at
  BEFORE UPDATE ON afad_retry_queue
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER trigger_retry_queue_processed_at
  BEFORE UPDATE ON afad_retry_queue
  FOR EACH ROW
  WHEN (NEW.status IN ('completed', 'failed', 'cancelled') AND OLD.status NOT IN ('completed', 'failed', 'cancelled'))
  EXECUTE FUNCTION set_processed_at();

-- ----------------------------------------------------------------------------
-- 7. afad_statistics テーブル作成
-- ----------------------------------------------------------------------------

CREATE TABLE afad_statistics (
  id BIGSERIAL PRIMARY KEY,
  adwares_id BIGINT NOT NULL,
  date DATE NOT NULL,
  total_clicks INTEGER NOT NULL DEFAULT 0 CHECK (total_clicks >= 0),
  afad_session_received INTEGER NOT NULL DEFAULT 0 CHECK (afad_session_received >= 0),
  total_conversions INTEGER NOT NULL DEFAULT 0 CHECK (total_conversions >= 0),
  afad_postback_attempted INTEGER NOT NULL DEFAULT 0 CHECK (afad_postback_attempted >= 0),
  afad_postback_success INTEGER NOT NULL DEFAULT 0 CHECK (afad_postback_success >= 0),
  afad_postback_failed INTEGER NOT NULL DEFAULT 0 CHECK (afad_postback_failed >= 0),
  afad_postback_timeout INTEGER NOT NULL DEFAULT 0 CHECK (afad_postback_timeout >= 0),
  total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00 CHECK (total_amount >= 0),
  avg_response_time_ms INTEGER,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT check_stats_logic CHECK (afad_postback_attempted >= (afad_postback_success + afad_postback_failed + afad_postback_timeout))
);

CREATE UNIQUE INDEX idx_afad_stats_unique ON afad_statistics(adwares_id, date);
CREATE INDEX idx_afad_stats_adwares_date ON afad_statistics(adwares_id, date DESC);
CREATE INDEX idx_afad_stats_date ON afad_statistics(date DESC);

CREATE TRIGGER trigger_afad_stats_updated_at
  BEFORE UPDATE ON afad_statistics
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();

COMMIT;
