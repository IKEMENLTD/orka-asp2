<?php
/**
 * ============================================================================
 * AFAD連携グローバル設定
 * ============================================================================
 *
 * AFAD(アフィリエイトアド)ポストバック連携の全体設定
 *
 * @version 1.0.0
 * @date 2025-11-02
 * ============================================================================
 */

// ============================================================================
// 基本設定
// ============================================================================

/**
 * AFAD連携機能の有効化
 * true: 有効 / false: 無効
 */
$CONFIG_AFAD_ENABLE = true;

/**
 * デフォルトパラメータ名
 * URLからセッションIDを取得する際のデフォルトパラメータ名
 */
$CONFIG_AFAD_DEFAULT_PARAM_NAME = 'afad_sid';

// ============================================================================
// HTTPリクエスト設定
// ============================================================================

/**
 * デフォルトタイムアウト(秒)
 * ポストバック送信時のHTTPリクエストタイムアウト
 */
$CONFIG_AFAD_DEFAULT_TIMEOUT = 10;

/**
 * デフォルトリトライ回数
 * 送信失敗時の最大リトライ回数
 */
$CONFIG_AFAD_DEFAULT_RETRY_MAX = 3;

/**
 * リトライ間隔(秒) - 指数バックオフ
 * [1回目, 2回目, 3回目, ...]
 * 実際の間隔は 基準間隔 * 2^リトライ回数 で計算されます
 */
$CONFIG_AFAD_RETRY_BASE_INTERVAL = 60; // 60秒基準

$CONFIG_AFAD_RETRY_INTERVALS = [
    60,   // 1回目: 1分後
    300,  // 2回目: 5分後
    900,  // 3回目: 15分後
    1800, // 4回目: 30分後
    3600  // 5回目: 60分後
];

/**
 * ポストバック送信時のUser-Agent
 */
$CONFIG_AFAD_USER_AGENT = 'ORKA-ASP2-AFAD/1.0';

// ============================================================================
// Cookie設定
// ============================================================================

/**
 * デフォルトCookie有効期限(日)
 * セッションIDをCookieに保存する際の有効期限
 */
$CONFIG_AFAD_DEFAULT_COOKIE_EXPIRE = 30;

/**
 * Cookieドメイン
 * 空文字の場合は現在のドメインを使用
 */
$CONFIG_AFAD_COOKIE_DOMAIN = '';

/**
 * Cookie パス
 */
$CONFIG_AFAD_COOKIE_PATH = '/';

// ============================================================================
// ログ設定
// ============================================================================

/**
 * ログファイルパス
 * ログファイルの保存先
 */
$CONFIG_AFAD_LOG_FILE = __DIR__ . '/../../logs/afad_postback.log';

/**
 * ログレベル
 * 0: 無効（ログを記録しない）
 * 1: エラーのみ
 * 2: 全て（INFO + ERROR）
 */
$CONFIG_AFAD_LOG_LEVEL = 2;

/**
 * ログローテーション設定
 * ログファイルの最大サイズ（バイト）
 * この���イズを超えたらローテーションする
 */
$CONFIG_AFAD_LOG_MAX_SIZE = 10 * 1024 * 1024; // 10MB

/**
 * ログファイルの保存世代数
 */
$CONFIG_AFAD_LOG_MAX_FILES = 10;

// ============================================================================
// データベース設定（Supabase）
// ============================================================================

/**
 * Supabaseデータベース接続設定
 * 環境変数から取得、設定されていない場合はこちらの値を使用
 */

// データベースホスト
if (!defined('SUPABASE_DB_HOST')) {
    define('SUPABASE_DB_HOST', getenv('SUPABASE_DB_HOST') ?: 'your-project.supabase.co');
}

// データベースポート
if (!defined('SUPABASE_DB_PORT')) {
    define('SUPABASE_DB_PORT', getenv('SUPABASE_DB_PORT') ?: '5432');
}

// データベース名
if (!defined('SUPABASE_DB_NAME')) {
    define('SUPABASE_DB_NAME', getenv('SUPABASE_DB_NAME') ?: 'postgres');
}

// データベースユーザー
if (!defined('SUPABASE_DB_USER')) {
    define('SUPABASE_DB_USER', getenv('SUPABASE_DB_USER') ?: 'postgres');
}

// データベースパスワード
if (!defined('SUPABASE_DB_PASSWORD')) {
    define('SUPABASE_DB_PASSWORD', getenv('SUPABASE_DB_PASSWORD') ?: '');
}

/**
 * データベース接続プール設定
 */
$CONFIG_AFAD_DB_PERSISTENT = false; // 持続的接続を使用するか

// ============================================================================
// パフォーマンス設定
// ============================================================================

/**
 * AFAD設定のキャッシュ時間(秒)
 * 0: キャッシュしない
 */
$CONFIG_AFAD_CONFIG_CACHE_TTL = 300; // 5分

/**
 * 非同期送信モード
 * true: バックグラウンドで非同期送信
 * false: 同期送信（レスポンスを待つ）
 */
$CONFIG_AFAD_ASYNC_MODE = false;

// ============================================================================
// セキュリティ設定
// ============================================================================

/**
 * セッションIDの最大長
 */
$CONFIG_AFAD_SESSION_ID_MAX_LENGTH = 255;

/**
 * セッションIDの許可文字パターン
 * 正規表現
 */
$CONFIG_AFAD_SESSION_ID_PATTERN = '/^[a-zA-Z0-9_-]+$/';

/**
 * HTTPSリクエストの検証
 * true: SSL証明書を検証する
 * false: 検証しない（開発環境のみ）
 */
$CONFIG_AFAD_VERIFY_SSL = true;

// ============================================================================
// デバッグ設定
// ============================================================================

/**
 * デバッグモード
 * true: 詳細なログを出力
 * false: 通常ログのみ
 */
$CONFIG_AFAD_DEBUG_MODE = false;

/**
 * テストモード
 * true: 実際のHTTPリクエストを送信せず、ログのみ記録
 * false: 通常動作
 */
$CONFIG_AFAD_TEST_MODE = false;

/**
 * テストモード時のダミーレスポンス
 */
$CONFIG_AFAD_TEST_RESPONSE = [
    'success' => true,
    'http_code' => 200,
    'response' => 'OK',
    'error' => '',
    'time_ms' => 100
];

// ============================================================================
// 統計・監視設定
// ============================================================================

/**
 * 統計情報の自動集計
 * true: 有効 / false: 無効
 */
$CONFIG_AFAD_AUTO_STATS = true;

/**
 * 統計情報の集計間隔(秒)
 * 0: 毎回集計
 */
$CONFIG_AFAD_STATS_INTERVAL = 3600; // 1時間

/**
 * アラート通知設定
 * エラー率がこの値を超えたらアラート
 */
$CONFIG_AFAD_ALERT_ERROR_RATE = 10; // 10%

/**
 * アラート通知先メールアドレス
 * 空の場合は通知しない
 */
$CONFIG_AFAD_ALERT_EMAIL = '';

// ============================================================================
// 高度な設定
// ============================================================================

/**
 * ポストバック送信の優先度
 * 1-1000（低い値ほど高優先）
 */
$CONFIG_AFAD_DEFAULT_PRIORITY = 100;

/**
 * 古いログの自動削除
 * true: 有効 / false: 無効
 */
$CONFIG_AFAD_AUTO_CLEANUP_LOGS = true;

/**
 * ログの保存期間(日)
 */
$CONFIG_AFAD_LOG_RETENTION_DAYS = 90;

/**
 * リトライキューの最大保持件数
 * この件数を超えたら古いものから削除
 */
$CONFIG_AFAD_RETRY_QUEUE_MAX_SIZE = 10000;

/**
 * バッチ処理のバッチサイズ
 * リトライ処理などのバッチ処理で一度に処理する件数
 */
$CONFIG_AFAD_BATCH_SIZE = 100;

// ============================================================================
// カスタムフック設定
// ============================================================================

/**
 * ポストバック送信前のカスタムフック
 * 関数名を指定すると送信前に実行される
 * 例: 'MyCustomPreSendHook'
 */
$CONFIG_AFAD_PRE_SEND_HOOK = '';

/**
 * ポストバック送信後のカスタムフック
 * 関数名を指定すると送信後に実行される
 * 例: 'MyCustomPostSendHook'
 */
$CONFIG_AFAD_POST_SEND_HOOK = '';

// ============================================================================
// 環境別設定の上書き
// ============================================================================

/**
 * 環境判定
 * 本番環境の場合はより厳格な設定に上書き
 */
if (getenv('APP_ENV') === 'production') {
    // 本番環境設定
    $CONFIG_AFAD_DEBUG_MODE = false;
    $CONFIG_AFAD_TEST_MODE = false;
    $CONFIG_AFAD_LOG_LEVEL = 1; // エラーのみ
    $CONFIG_AFAD_VERIFY_SSL = true;
}

if (getenv('APP_ENV') === 'development' || getenv('APP_ENV') === 'local') {
    // 開発環境設定
    $CONFIG_AFAD_DEBUG_MODE = true;
    $CONFIG_AFAD_LOG_LEVEL = 2; // 全て
}

// ============================================================================
// 設定の検証
// ============================================================================

/**
 * 設定の妥当性を検証
 */
function ValidateAFADConfig()
{
    global $CONFIG_AFAD_LOG_FILE;

    $errors = [];

    // ログディレクトリの書き込み権限チェック
    $logDir = dirname($CONFIG_AFAD_LOG_FILE);
    if (!is_dir($logDir)) {
        if (!@mkdir($logDir, 0755, true)) {
            $errors[] = "ログディレクトリを作成できません: {$logDir}";
        }
    } elseif (!is_writable($logDir)) {
        $errors[] = "ログディレクトリに書き込み権限がありません: {$logDir}";
    }

    // データベース接続情報チェック
    if (empty(SUPABASE_DB_HOST) || SUPABASE_DB_HOST === 'your-project.supabase.co') {
        $errors[] = "Supabaseホストが設定されていません";
    }

    if (empty(SUPABASE_DB_PASSWORD)) {
        $errors[] = "データベースパスワードが設定されていません";
    }

    if (!empty($errors)) {
        error_log('[AFAD Config] Validation errors: ' . implode(', ', $errors));
        return false;
    }

    return true;
}

// 設定の自動検証（オプション）
if ($CONFIG_AFAD_ENABLE) {
    ValidateAFADConfig();
}

?>
