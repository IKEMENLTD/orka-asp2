<?php
/**
 * ============================================================================
 * continue.php - 継続課金成果計測トラッキングピクセル
 * ============================================================================
 *
 * 広告主サイトの継続課金（サブスクリプション）成果発生時に呼び出される1x1ピクセル画像
 * 月額課金、年額課金などの継続成果が発生した際にAFADへポストバックを送信する
 *
 * 使用例:
 * <img src="https://example.com/continue.php?check=ACCESS_ID&sales=3000&uid=SUBSCRIPTION_ID" width="1" height="1">
 *
 * パラメータ:
 * - check (必須): アクセスID（link.phpで発行されたaid）
 * - sales: 継続課金金額（AFAD amountパラメータに使用）
 * - uid: サブスクリプションID、継続課金IDなど（AFAD uidパラメータに使用）
 * - uid2: サブユーザーID（AFAD uid2パラメータに使用）
 * - status: 承認ステータス 1=承認待ち, 2=承認, 3=否認（AFAD Statusパラメータに使用）
 *
 * @version 1.0.0
 * @date 2025-11-02
 * ============================================================================
 */

ob_start();

try {
    // 設定ファイル読み込み
    include_once 'custom/head_main.php';
    include_once 'custom/extends/afadConf.php';
    include_once 'module/afad_postback.inc';

    // パラメータ検証
    ValidateContinueRequest();

    // アクセスIDを取得
    $accessId = $_GET['check'] ?? null;

    if (empty($accessId)) {
        throw new InvalidQueryException('アクセスIDが指定されていません');
    }

    // アクセス情報を取得
    $access = GetContinueAccessRecord($accessId);

    if (!$access) {
        LogAFADInfo('Access record not found for continue conversion', ['access_id' => $accessId]);
        OutputContinueTrackingPixel();
        exit;
    }

    // 広告情報を取得
    $adwaresId = $access->getData('adwares');
    $adwaresType = $access->getData('adwares_type');

    if (empty($adwaresId)) {
        LogAFADInfo('Adwares ID not found in access record', ['access_id' => $accessId]);
        OutputContinueTrackingPixel();
        exit;
    }

    // 広告レコードを取得
    $adwares = new RecordModel($adwaresType ?: 'adwares', $adwaresId);

    // AFAD連携処理（設計書6.2節準拠、継続課金対応）
    if ($CONFIG_AFAD_ENABLE) {
        // 継続課金の場合は月次重複チェック
        $currentMonth = date('Y-m');
        $lastPostbackTime = $access->getData('afad_postback_time');
        $shouldSendPostback = true;

        if ($lastPostbackTime) {
            $lastMonth = date('Y-m', strtotime($lastPostbackTime));
            if ($currentMonth === $lastMonth) {
                LogAFADInfo('AFAD continue postback already sent this month', [
                    'access_id' => $access->getID(),
                    'current_month' => $currentMonth,
                    'last_month' => $lastMonth
                ]);
                $shouldSendPostback = false;
            }
        }

        // 月次チェックをパスした場合のみ送信
        if ($shouldSendPostback) {
            $conversion = [
                'uid' => $_GET['uid'] ?? null,
                'uid2' => $_GET['uid2'] ?? null,
                'amount' => isset($_GET['sales']) ? floatval($_GET['sales']) : null,
                'status' => isset($_GET['status']) ? intval($_GET['status']) : null
            ];
            SendAFADPostback($adwares, $access, $conversion);
        }
    }

    // 1x1透明GIF画像を出力
    OutputContinueTrackingPixel();

} catch (Exception $e) {
    ob_end_clean();

    // エラーログに記録
    LogAFADError('Continue conversion tracking error', $e->getMessage(), [
        'access_id' => $_GET['check'] ?? null,
        'sales' => $_GET['sales'] ?? null
    ]);

    // エラーでも1x1ピクセルを返す（ユーザーには影響させない）
    OutputContinueTrackingPixel();
}

ob_end_flush();

/*******************************************************************************************************
 * 関数
 *******************************************************************************************************/

/**
 * 継続課金リクエストのパラメータを検証
 */
function ValidateContinueRequest()
{
    ConceptCheck::IsScalar($_GET, ['check', 'sales', 'uid', 'uid2', 'status']);
}

/**
 * アクセスレコードを取得
 *
 * @param string $accessId アクセスID
 * @return RecordModel|null アクセスレコード
 */
function GetContinueAccessRecord($accessId)
{
    try {
        $access = new RecordModel('access', $accessId);

        // レコードが存在するか確認
        if ($access && $access->getID()) {
            return $access;
        }

        return null;
    } catch (Exception $e) {
        LogAFADError('Failed to get access record for continue', $e->getMessage(), [
            'access_id' => $accessId
        ]);
        return null;
    }
}

// ProcessAFADContinueConversion()関数は削除されました。
// 設計書準拠のSendAFADPostback()を使用してください（module/afad_postback.inc）
// 継続課金の月次重複チェックは呼び出し元で実施します

/**
 * 継続課金ポストバック送信後にアクセスレコードを更新
 *
 * @param RecordModel $access アクセスレコード
 * @param array $result 送信結果
 */
function UpdateAccessAfterContinuePostback($access, $result)
{
    try {
        // 継続課金の場合、送信フラグは毎回trueに更新
        // 最終送信時刻を更新することで月次重複を防ぐ
        $access->setData('afad_postback_sent', true);
        $access->setData('afad_postback_status', $result['status']);
        $access->setData('afad_postback_time', date('Y-m-d H:i:s'));
        $access->setData('afad_postback_response', $result['response_body'] ?? null);

        // リトライカウントは継続課金でもインクリメント
        $currentRetryCount = intval($access->getData('afad_postback_retry_count') ?? 0);
        $access->setData('afad_postback_retry_count', $currentRetryCount + ($result['retry_count'] ?? 0));

        if (!empty($result['error'])) {
            $access->setData('afad_postback_error', $result['error']);
        }

        $access->update();

    } catch (Exception $e) {
        LogAFADError('Failed to update access record after continue postback', $e->getMessage(), [
            'access_id' => $access->getID()
        ]);
    }
}

/**
 * 1x1透明GIF画像を出力
 */
function OutputContinueTrackingPixel()
{
    // 1x1透明GIF画像データ（Base64デコード）
    $gifData = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

    header('Content-Type: image/gif');
    header('Content-Length: ' . strlen($gifData));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo $gifData;
    exit;
}

?>
