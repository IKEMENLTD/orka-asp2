<?php
/**
 * ============================================================================
 * AFAD ポストバック連携モジュール
 * ============================================================================
 *
 * AFAD(アフィリエイトアド)のソケット連携(ポストバック連携)機能を提供
 *
 * 主な機能:
 * - セッションIDの受け取りと保存
 * - ポストバックURLの構築と送信
 * - リトライ処理
 * - ログ記録
 *
 * @version 1.0.0
 * @date 2025-11-02
 * ============================================================================
 */

// グローバル設定ファイルの読み込み
require_once __DIR__ . '/../custom/extends/afadConf.php';

/**
 * ============================================================================
 * データベース接続
 * ============================================================================
 */

/**
 * Supabase(PostgreSQL)データベース接続を取得
 *
 * @return PDO データベース接続
 * @throws Exception 接続失敗時
 */
function GetAFADDatabaseConnection()
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    // 環境変数または設定ファイルから接続情報を取得
    $host = getenv('SUPABASE_DB_HOST') ?: 'your-project.supabase.co';
    $port = getenv('SUPABASE_DB_PORT') ?: '5432';
    $dbname = getenv('SUPABASE_DB_NAME') ?: 'postgres';
    $user = getenv('SUPABASE_DB_USER') ?: 'postgres';
    $password = getenv('SUPABASE_DB_PASSWORD') ?: '';

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";

    try {
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $pdo;
    } catch (PDOException $e) {
        WriteAFADLog('Database connection failed', $e->getMessage());
        throw new Exception('Database connection failed: ' . $e->getMessage());
    }
}

/**
 * ============================================================================
 * AFAD設定管理
 * ============================================================================
 */

/**
 * AFAD設定を読み込む
 *
 * @param int $adwaresId 広告ID
 * @return array|null AFAD設定配列、設定がない場合はnull
 */
function LoadAFADConfig($adwaresId)
{
    static $cache = [];

    // キャッシュチェック
    if (isset($cache[$adwaresId])) {
        return $cache[$adwaresId];
    }

    try {
        $pdo = GetAFADDatabaseConnection();

        $sql = "
            SELECT * FROM afad_configs
            WHERE adwares_id = :adwares_id
              AND enabled = true
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['adwares_id' => $adwaresId]);
        $config = $stmt->fetch();

        if (!$config) {
            $cache[$adwaresId] = null;
            return null;
        }

        $cache[$adwaresId] = $config;
        return $config;

    } catch (Exception $e) {
        WriteAFADLog('Failed to load AFAD config', [
            'adwares_id' => $adwaresId,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}

/**
 * AFAD設定を取得（LoadAFADConfigのエイリアス）
 *
 * @param int $adwaresId 広告ID
 * @return array|null AFAD設定配列
 */
function GetAFADConfig($adwaresId)
{
    return LoadAFADConfig($adwaresId);
}

/**
 * AFAD設定のキャッシュをクリア
 */
function ClearAFADConfigCache()
{
    static $cache;
    $cache = [];
}

/**
 * ============================================================================
 * セッションID管理
 * ============================================================================
 */

/**
 * AFADセッションIDのバリデーション
 *
 * @param string $sessionId セッションID
 * @return bool 有効な場合true
 */
function ValidateAFADSessionId($sessionId)
{
    // 型チェック
    if (!is_string($sessionId)) {
        return false;
    }

    // 長さチェック (1-255文字)
    $length = strlen($sessionId);
    if ($length < 1 || $length > 255) {
        return false;
    }

    // 文字種チェック (英数字、ハイフン、アンダースコアのみ)
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $sessionId)) {
        return false;
    }

    return true;
}

/**
 * アクセスレコードにAFADセッションIDを保存
 *
 * @param int $accessId アクセスID
 * @param string $sessionId AFADセッションID
 * @param int $afadConfigId AFAD設定ID
 * @return bool 成功時true
 */
function SaveAFADSessionToAccess($accessId, $sessionId, $afadConfigId)
{
    try {
        $pdo = GetAFADDatabaseConnection();

        $sql = "
            UPDATE access
            SET afad_session_id = :session_id,
                afad_config_id = :config_id,
                updated_at = NOW()
            WHERE id = :access_id
        ";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            'session_id' => $sessionId,
            'config_id' => $afadConfigId,
            'access_id' => $accessId
        ]);

        if ($result) {
            WriteAFADLog('AFAD session saved to access', [
                'access_id' => $accessId,
                'session_id' => $sessionId
            ]);
        }

        return $result;

    } catch (Exception $e) {
        WriteAFADLog('Failed to save AFAD session', [
            'access_id' => $accessId,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * CookieにAFADセッションIDを保存
 *
 * @param int $adwaresId 広告ID
 * @param string $sessionId AFADセッションID
 * @param int $expireDays Cookie有効期限(日数)
 * @return bool 成功時true
 */
function SetAFADSessionCookie($adwaresId, $sessionId, $expireDays = 30)
{
    $cookieName = "afad_session_{$adwaresId}";
    $expire = time() + ($expireDays * 86400);
    $isSecure = IsHTTPS();

    $options = [
        'expires' => $expire,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ];

    $result = setcookie($cookieName, $sessionId, $options);

    if ($result) {
        WriteAFADLog('AFAD session cookie set', [
            'adwares_id' => $adwaresId,
            'expire_days' => $expireDays
        ]);
    }

    return $result;
}

/**
 * URLパラメータまたはCookieからAFADセッションIDを取得
 *
 * @param string $paramName パラメータ名（例: 'afad_sid'）
 * @return string|null セッションID、見つからない場合はnull
 */
function GetAFADSessionId($paramName)
{
    // まずURLパラメータから取得を試みる
    if (isset($_GET[$paramName]) && !empty($_GET[$paramName])) {
        $sessionId = (string)$_GET[$paramName];

        if (ValidateAFADSessionId($sessionId)) {
            LogAFADInfo('AFAD session ID received from URL parameter', [
                'param_name' => $paramName,
                'session_id' => $sessionId
            ]);
            return $sessionId;
        } else {
            LogAFADError('Invalid AFAD session ID in URL parameter', 'Validation failed', [
                'param_name' => $paramName,
                'session_id' => $sessionId
            ]);
            return null;
        }
    }

    // URLパラメータになければCookieから取得を試みる
    $cookieName = "afad_session_{$paramName}";
    if (isset($_COOKIE[$cookieName]) && !empty($_COOKIE[$cookieName])) {
        $sessionId = (string)$_COOKIE[$cookieName];

        if (ValidateAFADSessionId($sessionId)) {
            LogAFADInfo('AFAD session ID retrieved from cookie', [
                'cookie_name' => $cookieName,
                'session_id' => $sessionId
            ]);
            return $sessionId;
        }
    }

    return null;
}

/**
 * AFADセッションIDをCookieに保存（簡易版）
 *
 * @param string $sessionId AFADセッションID
 * @param int $expireDays Cookie有効期限(日数)
 * @param string $paramName パラメータ名（Cookie名の一部として使用）
 * @return bool 成功時true
 */
function StoreAFADSessionIdToCookie($sessionId, $expireDays = 30, $paramName = 'default')
{
    if (!ValidateAFADSessionId($sessionId)) {
        LogAFADError('Cannot store invalid AFAD session ID to cookie', 'Validation failed', [
            'session_id' => $sessionId
        ]);
        return false;
    }

    $cookieName = "afad_session_{$paramName}";
    $expire = time() + ($expireDays * 86400);
    $isSecure = IsHTTPS();

    $options = [
        'expires' => $expire,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ];

    $result = setcookie($cookieName, $sessionId, $options);

    if ($result) {
        LogAFADInfo('AFAD session ID stored to cookie', [
            'cookie_name' => $cookieName,
            'expire_days' => $expireDays
        ]);
    }

    return $result;
}

/**
 * CookieからAFADセッションIDを取得（汎用版）
 *
 * @param string $paramName パラメータ名（省略時は全てのCookieを検索）
 * @return string|null セッションID、見つからない場合はnull
 */
function GetAFADSessionIdFromCookie($paramName = null)
{
    // 特定のパラメータ名が指定されている場合
    if ($paramName !== null) {
        $cookieName = "afad_session_{$paramName}";
        if (isset($_COOKIE[$cookieName]) && !empty($_COOKIE[$cookieName])) {
            $sessionId = (string)$_COOKIE[$cookieName];
            if (ValidateAFADSessionId($sessionId)) {
                return $sessionId;
            }
        }
        return null;
    }

    // パラメータ名が指定されていない場合は、全てのAFAD Cookieを検索
    foreach ($_COOKIE as $name => $value) {
        if (strpos($name, 'afad_session_') === 0) {
            if (ValidateAFADSessionId($value)) {
                LogAFADInfo('AFAD session ID found in cookie', [
                    'cookie_name' => $name
                ]);
                return (string)$value;
            }
        }
    }

    return null;
}

/**
 * アクセスレコードからAFADセッションIDを取得
 *
 * @param int $accessId アクセスID
 * @return string|null セッションID、見つからない場合はnull
 */
function GetAFADSessionFromAccess($accessId)
{
    try {
        $pdo = GetAFADDatabaseConnection();

        $sql = "
            SELECT afad_session_id, afad_config_id
            FROM access
            WHERE id = :access_id
              AND afad_session_id IS NOT NULL
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['access_id' => $accessId]);
        $result = $stmt->fetch();

        return $result ? $result['afad_session_id'] : null;

    } catch (Exception $e) {
        WriteAFADLog('Failed to get AFAD session from access', [
            'access_id' => $accessId,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}

/**
 * AFAD設定IDをアクセスレコードから取得
 *
 * @param int $accessId アクセスID
 * @return int|null AFAD設定ID、見つからない場合はnull
 */
function GetAFADConfigIdFromAccess($accessId)
{
    try {
        $pdo = GetAFADDatabaseConnection();

        $sql = "
            SELECT afad_config_id
            FROM access
            WHERE id = :access_id
              AND afad_config_id IS NOT NULL
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['access_id' => $accessId]);
        $result = $stmt->fetch();

        return $result ? (int)$result['afad_config_id'] : null;

    } catch (Exception $e) {
        WriteAFADLog('Failed to get AFAD config ID from access', [
            'access_id' => $accessId,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}

/**
 * CookieからAFADセッションIDを取得
 *
 * @param int $adwaresId 広告ID
 * @return string|null セッションID、見つからない場合はnull
 */
function GetAFADSessionFromCookie($adwaresId)
{
    $cookieName = "afad_session_{$adwaresId}";

    if (isset($_COOKIE[$cookieName])) {
        $sessionId = $_COOKIE[$cookieName];

        // バリデーション
        if (ValidateAFADSessionId($sessionId)) {
            WriteAFADLog('AFAD session retrieved from cookie', [
                'adwares_id' => $adwaresId
            ]);
            return $sessionId;
        } else {
            WriteAFADLog('Invalid AFAD session in cookie', [
                'adwares_id' => $adwaresId,
                'session_id' => $sessionId
            ]);
        }
    }

    return null;
}

/**
 * ============================================================================
 * ポストバック送信
 * ============================================================================
 */

/**
 * AFADポストバックURLを構築
 *
 * @param array $afadConfig AFAD設定
 * @param string $sessionId AFADセッションID
 * @param array $conversionData 成果データ
 * @return string ポストバックURL
 */
function BuildAFADPostbackURL($afadConfig, $sessionId, $conversionData = [])
{
    $baseUrl = rtrim($afadConfig['postback_url'], '/');

    // 必須パラメータ
    $params = [
        'gid' => $afadConfig['group_id'],
        'af' => $sessionId
    ];

    // オプションパラメータ: uid
    if ($afadConfig['send_uid'] && !empty($conversionData['uid'])) {
        $params['uid'] = $conversionData['uid'];
    }

    // オプションパラメータ: uid2
    if ($afadConfig['send_uid2'] && !empty($conversionData['uid2'])) {
        $params['uid2'] = $conversionData['uid2'];
    }

    // オプションパラメータ: amount
    if ($afadConfig['send_amount'] && !empty($conversionData['amount'])) {
        $params['amount'] = $conversionData['amount'];
    }

    // オプションパラメータ: Status (大文字Sに注意)
    // AFAD仕様: 1=承認待ち、2=承認、3=否認
    if (!empty($conversionData['status'])) {
        $params['Status'] = $conversionData['status'];
    }

    $url = $baseUrl . '?' . http_build_query($params);

    WriteAFADLog('AFAD postback URL built', [
        'url' => $url,
        'params' => $params
    ]);

    return $url;
}

/**
 * HTTPリクエストを送信
 *
 * @param string $url リクエストURL
 * @param int $timeout タイムアウト(秒)
 * @return array ['success' => bool, 'http_code' => int, 'response' => string, 'error' => string, 'time_ms' => int]
 */
function SendHTTPRequest($url, $timeout = 10)
{
    global $CONFIG_AFAD_USER_AGENT;

    $startTime = microtime(true);

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => $CONFIG_AFAD_USER_AGENT ?: 'ORKA-ASP2-AFAD/1.0',
        CURLOPT_HEADER => false,
        CURLOPT_NOBODY => false,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $curlErrno = curl_errno($ch);

    curl_close($ch);

    $endTime = microtime(true);
    $timeMs = (int)(($endTime - $startTime) * 1000);

    $success = ($httpCode == 200 && $curlErrno == 0);

    $result = [
        'success' => $success,
        'http_code' => $httpCode,
        'response' => $response ?: '',
        'error' => $error ?: '',
        'time_ms' => $timeMs,
        'curl_errno' => $curlErrno
    ];

    WriteAFADLog('HTTP request completed', [
        'url' => $url,
        'http_code' => $httpCode,
        'time_ms' => $timeMs,
        'success' => $success
    ]);

    return $result;
}

/**
 * AFADポストバックを送信
 *
 * @param array $afadConfig AFAD設定配列
 * @param string $sessionId AFADセッションID
 * @param string $accessId アクセスID
 * @param array $conversionData 成果データ ['uid' => '', 'uid2' => '', 'amount' => 0, 'status' => 1]
 * @return array ['success' => bool, 'status' => int, 'response_body' => string, 'error' => string, 'retry_count' => int]
 */
function SendAFADPostback($afadConfig, $sessionId, $accessId, $conversionData = [])
{
    // バリデーション
    if (!$afadConfig || !isset($afadConfig['postback_url'])) {
        LogAFADError('Invalid AFAD config', 'Config is null or missing postback_url', [
            'access_id' => $accessId
        ]);
        return [
            'success' => false,
            'status' => 0,
            'response_body' => null,
            'error' => 'AFAD連携設定が無効です',
            'retry_count' => 0
        ];
    }

    if (!ValidateAFADSessionId($sessionId)) {
        LogAFADError('Invalid AFAD session ID', 'Validation failed', [
            'access_id' => $accessId,
            'session_id' => $sessionId
        ]);
        return [
            'success' => false,
            'status' => 0,
            'response_body' => null,
            'error' => 'AFADセッションIDが無効です',
            'retry_count' => 0
        ];
    }

    // ポストバックURLを構築
    $postbackUrl = BuildAFADPostbackURL($afadConfig, $sessionId, $conversionData);

    // HTTPリクエスト送信
    $timeout = $afadConfig['timeout_seconds'] ?? 10;
    $httpResult = SendHTTPRequest($postbackUrl, $timeout);

    // 送信結果を記録
    $adwaresId = $afadConfig['adwares_id'] ?? null;
    $logId = RecordAFADPostback(
        $accessId,
        $afadConfig['id'],
        $adwaresId,
        $sessionId,
        $postbackUrl,
        $conversionData,
        $httpResult
    );

    // リトライ処理
    $retryCount = 0;
    if (!$httpResult['success']) {
        $retryMax = $afadConfig['retry_max'] ?? 3;
        $retryCount = GetAFADRetryCount($accessId);

        if ($retryCount < $retryMax) {
            ScheduleAFADRetry(
                $accessId,
                $afadConfig['id'],
                $sessionId,
                $retryCount,
                $retryMax,
                $httpResult['error'] ?? 'Unknown error'
            );
        }
    }

    return [
        'success' => $httpResult['success'],
        'status' => $httpResult['http_code'] ?? 0,
        'response_body' => $httpResult['response'] ?? null,
        'error' => $httpResult['error'] ?? null,
        'retry_count' => $retryCount
    ];
}

/**
 * ============================================================================
 * ポストバック記録
 * ============================================================================
 */

/**
 * AFADポストバック送信結果を記録
 *
 * @param int $accessId アクセスID
 * @param int $afadConfigId AFAD設定ID
 * @param int $adwaresId 広告ID
 * @param string $sessionId AFADセッションID
 * @param string $postbackUrl ポストバックURL
 * @param array $conversionData 成果データ
 * @param array $result HTTPレスポンス結果
 * @return int|null ログID
 */
function RecordAFADPostback($accessId, $afadConfigId, $adwaresId, $sessionId, $postbackUrl, $conversionData, $result)
{
    try {
        $pdo = GetAFADDatabaseConnection();

        // ステータス判定
        $status = 'pending';
        if ($result['success']) {
            $status = 'success';
        } elseif ($result['curl_errno'] == CURLE_OPERATION_TIMEOUTED || $result['curl_errno'] == 28) {
            // CURLE_OPERATION_TIMEOUTED = 28 (タイムアウト)
            $status = 'timeout';
        } else {
            $status = 'failed';
        }

        // リクエストパラメータをJSONB形式で準備
        $requestParams = [
            'gid' => $afadConfigId,
            'af' => $sessionId
        ];
        if (!empty($conversionData['uid'])) $requestParams['uid'] = $conversionData['uid'];
        if (!empty($conversionData['uid2'])) $requestParams['uid2'] = $conversionData['uid2'];
        if (!empty($conversionData['amount'])) $requestParams['amount'] = $conversionData['amount'];

        $sql = "
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
                error_message,
                retry_count,
                execution_time_ms,
                ip_address,
                user_agent,
                conversion_uid,
                conversion_uid2,
                conversion_amount,
                conversion_status
            ) VALUES (
                :access_id,
                :afad_config_id,
                :adwares_id,
                :afad_session_id,
                :postback_url,
                :request_params::jsonb,
                :response_code,
                :response_body,
                :status,
                :error_message,
                0,
                :execution_time_ms,
                :ip_address,
                :user_agent,
                :conversion_uid,
                :conversion_uid2,
                :conversion_amount,
                :conversion_status
            )
            RETURNING id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'access_id' => $accessId,
            'afad_config_id' => $afadConfigId,
            'adwares_id' => $adwaresId,
            'afad_session_id' => $sessionId,
            'postback_url' => $postbackUrl,
            'request_params' => json_encode($requestParams),
            'response_code' => $result['http_code'] ?: null,
            'response_body' => substr($result['response'], 0, 10000), // 最大10KB
            'status' => $status,
            'error_message' => $result['error'] ?: null,
            'execution_time_ms' => $result['time_ms'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'conversion_uid' => $conversionData['uid'] ?? null,
            'conversion_uid2' => $conversionData['uid2'] ?? null,
            'conversion_amount' => isset($conversionData['amount']) ? (float)$conversionData['amount'] : null,
            'conversion_status' => $conversionData['status'] ?? null
        ]);

        $logId = $stmt->fetchColumn();

        WriteAFADLog('AFAD postback recorded', [
            'log_id' => $logId,
            'access_id' => $accessId,
            'status' => $status
        ]);

        return $logId;

    } catch (Exception $e) {
        WriteAFADLog('Failed to record AFAD postback', [
            'access_id' => $accessId,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}

/**
 * アクセステーブルのAFAD送信ステータスを更新
 *
 * @param int $accessId アクセスID
 * @param bool $success 成功フラグ
 * @param array $result HTTPレスポンス結果
 * @return bool 成功時true
 */
function UpdateAccessAFADStatus($accessId, $success, $result)
{
    try {
        $pdo = GetAFADDatabaseConnection();

        $status = $success ? 'success' : ($result['curl_errno'] == 28 ? 'timeout' : 'failed');

        $sql = "
            UPDATE access
            SET afad_postback_sent = :sent,
                afad_postback_status = :status,
                afad_postback_time = NOW(),
                afad_postback_response = :response,
                afad_postback_error = :error,
                updated_at = NOW()
            WHERE id = :access_id
        ";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'sent' => $success,
            'status' => $status,
            'response' => substr($result['response'], 0, 1000),
            'error' => $result['error'] ?: null,
            'access_id' => $accessId
        ]);

    } catch (Exception $e) {
        WriteAFADLog('Failed to update access AFAD status', [
            'access_id' => $accessId,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * AFADポストバック送信済みかチェック
 *
 * @param int $accessId アクセスID
 * @return bool 送信済みの場合true
 */
function IsAFADPostbackSent($accessId)
{
    try {
        $pdo = GetAFADDatabaseConnection();

        $sql = "
            SELECT afad_postback_sent
            FROM access
            WHERE id = :access_id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['access_id' => $accessId]);
        $result = $stmt->fetch();

        return $result && $result['afad_postback_sent'];

    } catch (Exception $e) {
        WriteAFADLog('Failed to check AFAD postback sent', [
            'access_id' => $accessId,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * ============================================================================
 * リトライ管理
 * ============================================================================
 */

/**
 * AFADポストバックのリトライをスケジュール
 *
 * @param int $accessId アクセスID
 * @param int $afadConfigId AFAD設定ID
 * @param string $sessionId AFADセッションID
 * @param int $currentRetryCount 現在のリトライ回数
 * @param int $maxRetryCount 最大リトライ回数
 * @param string $lastError 最後のエラーメッセージ
 * @return bool 成功時true
 */
function ScheduleAFADRetry($accessId, $afadConfigId, $sessionId, $currentRetryCount, $maxRetryCount, $lastError = '')
{
    try {
        $pdo = GetAFADDatabaseConnection();

        // 指数バックオフで次回リトライ時刻を計算
        $baseInterval = 60; // 60秒
        $nextRetryDelay = $baseInterval * pow(2, min($currentRetryCount, 5)); // 最大32倍
        $nextRetryAt = date('Y-m-d H:i:s', time() + $nextRetryDelay);

        // 既存のリトライキューをチェック
        $checkSql = "
            SELECT id FROM afad_retry_queue
            WHERE access_id = :access_id
              AND status IN ('pending', 'processing')
        ";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['access_id' => $accessId]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            // 既存のキューを更新
            $sql = "
                UPDATE afad_retry_queue
                SET retry_count = :retry_count,
                    next_retry_at = :next_retry_at,
                    last_error = :last_error,
                    updated_at = NOW()
                WHERE id = :id
            ";

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                'retry_count' => $currentRetryCount + 1,
                'next_retry_at' => $nextRetryAt,
                'last_error' => $lastError,
                'id' => $existing['id']
            ]);
        } else {
            // 新規キューを作成
            $sql = "
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
                ) VALUES (
                    :access_id,
                    :afad_config_id,
                    :afad_session_id,
                    :retry_count,
                    :max_retry_count,
                    :next_retry_at,
                    :last_error,
                    'pending',
                    100
                )
            ";

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                'access_id' => $accessId,
                'afad_config_id' => $afadConfigId,
                'afad_session_id' => $sessionId,
                'retry_count' => $currentRetryCount + 1,
                'max_retry_count' => $maxRetryCount,
                'next_retry_at' => $nextRetryAt,
                'last_error' => $lastError
            ]);
        }

        if ($result) {
            WriteAFADLog('AFAD retry scheduled', [
                'access_id' => $accessId,
                'retry_count' => $currentRetryCount + 1,
                'next_retry_at' => $nextRetryAt
            ]);
        }

        return $result;

    } catch (Exception $e) {
        WriteAFADLog('Failed to schedule AFAD retry', [
            'access_id' => $accessId,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * AFADリトライ回数を取得
 *
 * @param int $accessId アクセスID
 * @return int リトライ回数
 */
function GetAFADRetryCount($accessId)
{
    try {
        $pdo = GetAFADDatabaseConnection();

        $sql = "
            SELECT afad_postback_retry_count
            FROM access
            WHERE id = :access_id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['access_id' => $accessId]);
        $result = $stmt->fetch();

        return $result ? (int)$result['afad_postback_retry_count'] : 0;

    } catch (Exception $e) {
        WriteAFADLog('Failed to get AFAD retry count', [
            'access_id' => $accessId,
            'error' => $e->getMessage()
        ]);
        return 0;
    }
}

/**
 * ============================================================================
 * ユーティリティ関数
 * ============================================================================
 */

/**
 * HTTPS接続かどうか判定
 *
 * @return bool HTTPS接続の場合true
 */
function IsHTTPS()
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['SERVER_PORT'] ?? 0) == 443
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
}

/**
 * AFADログを記録
 *
 * @param string $message ログメッセージ
 * @param mixed $data ログデータ（配列または文字列）
 * @return bool 成功時true
 */
function WriteAFADLog($message, $data = null)
{
    global $CONFIG_AFAD_LOG_FILE, $CONFIG_AFAD_LOG_LEVEL;

    // ログレベルチェック
    if (!isset($CONFIG_AFAD_LOG_LEVEL) || $CONFIG_AFAD_LOG_LEVEL == 0) {
        return false;
    }

    $logFile = $CONFIG_AFAD_LOG_FILE ?? __DIR__ . '/../logs/afad_postback.log';

    // ログディレクトリの存在確認
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $logData = is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $data;

    $logLine = "[{$timestamp}] {$message}";
    if ($logData) {
        $logLine .= ": {$logData}";
    }
    $logLine .= "\n";

    return @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX) !== false;
}

/**
 * INFOレベルのログを出力
 *
 * @param string $message ログメッセージ
 * @param array|null $data 追加データ
 * @return bool 成功時true
 */
function LogAFADInfo($message, $data = null)
{
    global $CONFIG_AFAD_LOG_LEVEL;

    // ログレベル1以上で出力
    if (isset($CONFIG_AFAD_LOG_LEVEL) && $CONFIG_AFAD_LOG_LEVEL >= 1) {
        return WriteAFADLog("[INFO] " . $message, $data);
    }

    return false;
}

/**
 * ERRORレベルのログを出力
 *
 * @param string $message ログメッセージ
 * @param string|null $error エラー詳細
 * @param array|null $data 追加データ
 * @return bool 成功時true
 */
function LogAFADError($message, $error = null, $data = null)
{
    global $CONFIG_AFAD_LOG_LEVEL;

    // ログレベル0以上（常に出力）
    if (!isset($CONFIG_AFAD_LOG_LEVEL) || $CONFIG_AFAD_LOG_LEVEL >= 0) {
        $errorData = $data ?? [];
        if ($error !== null) {
            $errorData['error'] = $error;
        }
        return WriteAFADLog("[ERROR] " . $message, $errorData);
    }

    return false;
}

/**
 * URLにパラメータを追加
 *
 * @param string $url ベースURL
 * @param string $paramName パラメータ名
 * @param string $paramValue パラメータ値
 * @return string パラメータ追加後のURL
 */
function AppendURLParameter($url, $paramName, $paramValue)
{
    $separator = (strpos($url, '?') !== false) ? '&' : '?';
    return $url . $separator . urlencode($paramName) . '=' . urlencode($paramValue);
}

?>
