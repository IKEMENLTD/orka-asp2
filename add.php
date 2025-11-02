<?php
/**
 * ============================================================================
 * add.php - 成果計測トラッキングピクセル
 * ============================================================================
 *
 * 広告主サイトのサンクスページに設置される1x1ピクセル画像
 * コンバージョン（成果）が発生した際にAFADへポストバックを送信する
 *
 * 使用例:
 * <img src="https://example.com/add.php?check=ACCESS_ID&sales=10000&uid=ORDER123" width="1" height="1">
 *
 * パラメータ:
 * - check (必須): アクセスID（link.phpで発行されたaid）
 * - sales: 売上金額（AFAD amountパラメータに使用）
 * - uid: ユーザー識別ID、注文番号など（AFAD uidパラメータに使用）
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
    ValidateConversionRequest();

    // アクセスIDを取得
    $accessId = $_GET['check'] ?? null;

    if (empty($accessId)) {
        throw new InvalidQueryException('アクセスIDが指定されていません');
    }

    // アクセス情報を取得
    $access = GetAccessRecord($accessId);

    if (!$access) {
        LogAFADInfo('Access record not found', ['access_id' => $accessId]);
        OutputTrackingPixel();
        exit;
    }

    // 広告情報を取得
    $adwaresId = $access->getData('adwares');
    $adwaresType = $access->getData('adwares_type');

    if (empty($adwaresId)) {
        LogAFADInfo('Adwares ID not found in access record', ['access_id' => $accessId]);
        OutputTrackingPixel();
        exit;
    }

    // 広告レコードを取得
    $adwares = new RecordModel($adwaresType ?: 'adwares', $adwaresId);

    // AFAD連携処理
    if ($CONFIG_AFAD_ENABLE) {
        ProcessAFADConversion($adwares, $access);
    }

    // 1x1透明GIF画像を出力
    OutputTrackingPixel();

} catch (Exception $e) {
    ob_end_clean();

    // エラーログに記録
    LogAFADError('Conversion tracking error', $e->getMessage(), [
        'access_id' => $_GET['check'] ?? null,
        'sales' => $_GET['sales'] ?? null
    ]);

    // エラーでも1x1ピクセルを返す（ユーザーには影響させない）
    OutputTrackingPixel();
}

ob_end_flush();

/*******************************************************************************************************
 * 関数
 *******************************************************************************************************/

/**
 * コンバージョンリクエストのパラメータを検証
 */
function ValidateConversionRequest()
{
    ConceptCheck::IsScalar($_GET, ['check', 'sales', 'uid', 'uid2', 'status']);
}

/**
 * アクセスレコードを取得
 *
 * @param string $accessId アクセスID
 * @return RecordModel|null アクセスレコード
 */
function GetAccessRecord($accessId)
{
    try {
        $access = new RecordModel('access', $accessId);

        // レコードが存在するか確認
        if ($access && $access->getID()) {
            return $access;
        }

        return null;
    } catch (Exception $e) {
        LogAFADError('Failed to get access record', $e->getMessage(), [
            'access_id' => $accessId
        ]);
        return null;
    }
}

/**
 * AFAD連携: コンバージョンポストバック処理
 *
 * @param RecordModel $adwares 広告情報
 * @param RecordModel $access アクセス情報
 */
function ProcessAFADConversion($adwares, $access)
{
    global $CONFIG_AFAD_ENABLE;

    if (!$CONFIG_AFAD_ENABLE) {
        return;
    }

    try {
        // AFAD設定を取得
        $afadConfig = GetAFADConfig($adwares->getID());

        if (!$afadConfig || !$afadConfig['enabled']) {
            LogAFADInfo('AFAD not enabled for this adwares', [
                'adwares_id' => $adwares->getID()
            ]);
            return;
        }

        // AFADセッションIDを取得
        $afadSessionId = $access->getData('afad_session_id');

        if (empty($afadSessionId)) {
            // Cookieからフォールバック取得を試みる
            $afadSessionId = GetAFADSessionIdFromCookie();

            if (empty($afadSessionId)) {
                LogAFADInfo('AFAD session ID not found', [
                    'access_id' => $access->getID(),
                    'adwares_id' => $adwares->getID()
                ]);
                return;
            }
        }

        // すでに送信済みかチェック
        if ($access->getData('afad_postback_sent')) {
            LogAFADInfo('AFAD postback already sent', [
                'access_id' => $access->getID()
            ]);
            return;
        }

        // 成果データを準備
        $conversionData = [
            'uid' => $_GET['uid'] ?? null,
            'uid2' => $_GET['uid2'] ?? null,
            'amount' => isset($_GET['sales']) ? floatval($_GET['sales']) : null,
            'status' => isset($_GET['status']) ? intval($_GET['status']) : null
        ];

        // 承認ステータスによるフィルタリング
        if ($afadConfig['approval_status']) {
            $conversionStatus = $conversionData['status'] ?? 1; // デフォルトは承認待ち

            if ($conversionStatus != $afadConfig['approval_status']) {
                LogAFADInfo('Conversion status does not match config', [
                    'access_id' => $access->getID(),
                    'conversion_status' => $conversionStatus,
                    'required_status' => $afadConfig['approval_status']
                ]);
                return;
            }
        }

        // ポストバックを送信
        $result = SendAFADPostback(
            $afadConfig,
            $afadSessionId,
            $access->getID(),
            $conversionData
        );

        // アクセスレコードを更新
        UpdateAccessAfterPostback($access, $result);

        LogAFADInfo('AFAD postback sent successfully', [
            'access_id' => $access->getID(),
            'afad_session_id' => $afadSessionId,
            'status' => $result['status']
        ]);

    } catch (Exception $e) {
        LogAFADError('AFAD conversion processing failed', $e->getMessage(), [
            'adwares_id' => $adwares->getID(),
            'access_id' => $access->getID()
        ]);
    }
}

/**
 * ポストバック送信後にアクセスレコードを更新
 *
 * @param RecordModel $access アクセスレコード
 * @param array $result 送信結果
 */
function UpdateAccessAfterPostback($access, $result)
{
    try {
        $access->setData('afad_postback_sent', true);
        $access->setData('afad_postback_status', $result['status']);
        $access->setData('afad_postback_time', date('Y-m-d H:i:s'));
        $access->setData('afad_postback_response', $result['response_body'] ?? null);
        $access->setData('afad_postback_retry_count', $result['retry_count'] ?? 0);

        if (!empty($result['error'])) {
            $access->setData('afad_postback_error', $result['error']);
        }

        $access->update();

    } catch (Exception $e) {
        LogAFADError('Failed to update access record after postback', $e->getMessage(), [
            'access_id' => $access->getID()
        ]);
    }
}

/**
 * 1x1透明GIF画像を出力
 */
function OutputTrackingPixel()
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
