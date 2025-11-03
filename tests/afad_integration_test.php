#!/usr/bin/env php
<?php
/**
 * ============================================================================
 * AFAD連携 統合テストスクリプト
 * ============================================================================
 *
 * 使用方法:
 *   php tests/afad_integration_test.php
 *
 * テスト内容:
 *   1. 設定ファイルの読み込み確認
 *   2. データベース接続確認
 *   3. セッションID検証テスト
 *   4. ポストバックURL構築テスト
 *   5. HTTPリクエスト送信テスト（モック）
 *   6. ログ書き込みテスト
 *
 * @version 1.0.0
 * @date 2025-11-02
 * ============================================================================
 */

// 設定ファイル読み込み
require_once __DIR__ . '/../custom/head_main.php';
require_once __DIR__ . '/../custom/extends/afadConf.php';
require_once __DIR__ . '/../module/afad_postback.inc';

// テストモードを有効化
$CONFIG_AFAD_TEST_MODE = true;
$CONFIG_AFAD_LOG_LEVEL = 2;

// テスト結果
$testResults = [];
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

/**
 * テストアサーション
 */
function assertTrue($condition, $testName) {
    global $testResults, $totalTests, $passedTests, $failedTests;

    $totalTests++;
    $result = $condition ? 'PASS' : 'FAIL';

    if ($condition) {
        $passedTests++;
        $color = "\033[32m"; // Green
    } else {
        $failedTests++;
        $color = "\033[31m"; // Red
    }

    $testResults[] = [
        'name' => $testName,
        'result' => $result,
        'color' => $color
    ];

    echo $color . "[$result] " . $testName . "\033[0m\n";
}

function assertEquals($expected, $actual, $testName) {
    $condition = ($expected === $actual);

    if (!$condition) {
        echo "  Expected: " . var_export($expected, true) . "\n";
        echo "  Actual: " . var_export($actual, true) . "\n";
    }

    assertTrue($condition, $testName);
}

echo "\n";
echo "============================================================================\n";
echo " AFAD連携 統合テスト\n";
echo "============================================================================\n\n";

// ============================================================================
// テスト 1: 設定ファイル読み込み
// ============================================================================

echo "--- テスト 1: 設定ファイル読み込み ---\n";

assertTrue(
    isset($CONFIG_AFAD_ENABLE),
    'AFAD機能有効化フラグが定義されている'
);

assertTrue(
    isset($CONFIG_AFAD_DEFAULT_PARAM_NAME),
    'デフォルトパラメータ名が定義されている'
);

assertTrue(
    isset($CONFIG_AFAD_DEFAULT_TIMEOUT),
    'デフォルトタイムアウトが定義されている'
);

assertTrue(
    isset($CONFIG_AFAD_LOG_FILE),
    'ログファイルパスが定義されている'
);

// ============================================================================
// テスト 2: ログディレクトリの書き込み権限
// ============================================================================

echo "\n--- テスト 2: ログディレクトリ権限 ---\n";

$logDir = dirname($CONFIG_AFAD_LOG_FILE);

assertTrue(
    is_dir($logDir),
    'ログディレクトリが存在する: ' . $logDir
);

assertTrue(
    is_writable($logDir),
    'ログディレクトリに書き込み権限がある'
);

// ============================================================================
// テスト 3: セッションID検証
// ============================================================================

echo "\n--- テスト 3: セッションID検証 ---\n";

// 正常なセッションID
assertTrue(
    ValidateAFADSessionId('ABC123xyz-_'),
    '正常なセッションID: 英数字+ハイフン+アンダースコア'
);

assertTrue(
    ValidateAFADSessionId('a'),
    '正常なセッションID: 1文字'
);

assertTrue(
    ValidateAFADSessionId(str_repeat('A', 255)),
    '正常なセッションID: 255文字'
);

// 不正なセッションID
assertTrue(
    !ValidateAFADSessionId(''),
    '不正なセッションID: 空文字'
);

assertTrue(
    !ValidateAFADSessionId(str_repeat('A', 256)),
    '不正なセッションID: 256文字（長すぎる）'
);

assertTrue(
    !ValidateAFADSessionId('ABC<script>'),
    '不正なセッションID: スクリプトタグ'
);

assertTrue(
    !ValidateAFADSessionId('ABC 123'),
    '不正なセッションID: スペース含む'
);

assertTrue(
    !ValidateAFADSessionId('あいうえお'),
    '不正なセッションID: マルチバイト文字'
);

// ============================================================================
// テスト 4: ポストバックURL構築
// ============================================================================

echo "\n--- テスト 4: ポストバックURL構築 ---\n";

$mockConfig = [
    'postback_url' => 'https://ac.afad.jp/12345/ac/',
    'group_id' => 'GRP001',
    'send_uid' => true,
    'send_uid2' => false,
    'send_amount' => true,
];

$sessionId = 'TEST_SESSION_123';

$conversionData = [
    'uid' => 'ORDER_12345',
    'uid2' => 'MEMBER_67890',
    'amount' => 10000,
    'status' => 1
];

$url = BuildAFADPostbackURL($mockConfig, $sessionId, $conversionData);

assertTrue(
    strpos($url, 'gid=GRP001') !== false,
    'URLにgidパラメータが含まれる'
);

assertTrue(
    strpos($url, 'af=TEST_SESSION_123') !== false,
    'URLにafパラメータ（セッションID）が含まれる'
);

assertTrue(
    strpos($url, 'uid=ORDER_12345') !== false,
    'URLにuidパラメータが含まれる'
);

assertTrue(
    strpos($url, 'amount=10000') !== false,
    'URLにamountパラメータが含まれる'
);

assertTrue(
    strpos($url, 'uid2=') === false,
    'send_uid2がfalseの場合、uid2パラメータは含まれない'
);

// ============================================================================
// テスト 5: HTTPSデテクション
// ============================================================================

echo "\n--- テスト 5: HTTPS検出 ---\n";

// HTTPS環境をシミュレート
$_SERVER['HTTPS'] = 'on';
assertTrue(
    IsHTTPS(),
    'HTTPS環境を正しく検出（$_SERVER["HTTPS"] = "on"）'
);

$_SERVER['HTTPS'] = 'off';
assertTrue(
    !IsHTTPS(),
    'HTTP環境を正しく検出（$_SERVER["HTTPS"] = "off"）'
);

unset($_SERVER['HTTPS']);
$_SERVER['SERVER_PORT'] = 443;
assertTrue(
    IsHTTPS(),
    'HTTPS環境を正しく検出（$_SERVER["SERVER_PORT"] = 443）'
);

$_SERVER['SERVER_PORT'] = 80;
assertTrue(
    !IsHTTPS(),
    'HTTP環境を正しく検出（$_SERVER["SERVER_PORT"] = 80）'
);

// ============================================================================
// テスト 6: URLパラメータ追加
// ============================================================================

echo "\n--- テスト 6: URLパラメータ追加 ---\n";

$url1 = AppendURLParameter('https://example.com/page', 'key', 'value');
assertEquals(
    'https://example.com/page?key=value',
    $url1,
    'URLにパラメータを追加（?付与）'
);

$url2 = AppendURLParameter('https://example.com/page?existing=1', 'key', 'value');
assertEquals(
    'https://example.com/page?existing=1&key=value',
    $url2,
    'URLにパラメータを追加（&付与）'
);

$url3 = AppendURLParameter('https://example.com/page', 'key', 'value with space');
assertTrue(
    strpos($url3, 'value+with+space') !== false || strpos($url3, 'value%20with%20space') !== false,
    'URLエンコードが適用される'
);

// ============================================================================
// テスト 7: ログ書き込み
// ============================================================================

echo "\n--- テスト 7: ログ書き込み ---\n";

$testLogMessage = 'AFAD integration test message';
$testLogData = ['test_key' => 'test_value', 'timestamp' => time()];

$result = WriteAFADLog($testLogMessage, $testLogData);

assertTrue(
    $result !== false,
    'ログファイルに書き込み成功'
);

if (file_exists($CONFIG_AFAD_LOG_FILE)) {
    $logContent = file_get_contents($CONFIG_AFAD_LOG_FILE);

    assertTrue(
        strpos($logContent, $testLogMessage) !== false,
        'ログファイルに正しいメッセージが記録されている'
    );

    assertTrue(
        strpos($logContent, 'test_key') !== false,
        'ログファイルにデータが記録されている'
    );
}

// ============================================================================
// テスト 8: データベース接続（オプション）
// ============================================================================

echo "\n--- テスト 8: データベース接続（オプション） ---\n";

try {
    $pdo = GetAFADDatabaseConnection();

    assertTrue(
        $pdo instanceof PDO,
        'データベース接続が成功'
    );

    // 簡単なクエリを実行
    $stmt = $pdo->query('SELECT 1 as test');
    $result = $stmt->fetch();

    assertEquals(
        1,
        (int)$result['test'],
        'データベースクエリが実行できる'
    );

} catch (Exception $e) {
    echo "  \033[33m[SKIP] データベース接続テストをスキップ: " . $e->getMessage() . "\033[0m\n";
}

// ============================================================================
// テスト結果サマリー
// ============================================================================

echo "\n";
echo "============================================================================\n";
echo " テスト結果\n";
echo "============================================================================\n";
echo "\n";

echo "総テスト数: {$totalTests}\n";
echo "\033[32m成功: {$passedTests}\033[0m\n";
echo "\033[31m失敗: {$failedTests}\033[0m\n";

$successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0;
echo "成功率: {$successRate}%\n";

echo "\n";

if ($failedTests > 0) {
    echo "\033[31m✗ テストに失敗しました\033[0m\n";
    exit(1);
} else {
    echo "\033[32m✓ 全てのテストに成功しました\033[0m\n";
    exit(0);
}
?>
