# AFADソケット連携機能 詳細設計書

## 目次

1. [概要](#1-概要)
2. [システム構成](#2-システム構成)
3. [連携フロー](#3-連携フロー)
4. [データベース設計](#4-データベース設計)
5. [API仕様](#5-api仕様)
6. [実装詳細](#6-実装詳細)
7. [セキュリティ要件](#7-セキュリティ要件)
8. [エラー処理](#8-エラー処理)
9. [ログ仕様](#9-ログ仕様)
10. [テスト仕様](#10-テスト仕様)
11. [運用要件](#11-運用要件)
12. [実装スケジュール](#12-実装スケジュール)

---

## 1. 概要

### 1.1 目的

AFAD(アフィリエイトアド)のポストバック連携(ソケット連携)機能を実装し、AFADメディア側システムとの成果データ連携を実現する。

### 1.2 背景

現在のorka-asp2システムは独自のトラッキングシステムを持つが、AFAD側のメディアシステムと連携するためには、AFADが発行するセッションIDを受け取り、成果発生時にAFAD側へポストバックする仕組みが必要。

### 1.3 連携概要

**クリック時の処理:**
- AFADメディアの広告URLから流入したユーザーがクリックする
- AFADが発行したセッションIDをURLパラメータから受け取る
- セッションIDを保存し、ユーザーを広告主サイトへリダイレクト

**成果発生時の処理:**
- ユーザーがコンバージョン(成果)を達成
- 保存していたAFADセッションIDを使用してAFADのポストバックURLへHTTPリクエストを送信
- 成果データ(セッションID、注文番号、金額など)を通知

---

## 2. システム構成

### 2.1 システム構成図

```
┌─────────────┐
│   ユーザー    │
└──────┬──────┘
       │ ①クリック
       ▼
┌─────────────┐      ②セッションID付与
│   メディア    │──────────────────┐
│  (AFAD側)   │                  │
└─────────────┘                  │
                                 ▼
                         ┌──────────────┐
                         │   link.php    │
                         │ (当システム)   │
                         └───────┬──────┘
                                 │ ③リダイレクト
                                 │   +セッションID保存
                                 ▼
                         ┌──────────────┐
                         │  広告主サイト  │
                         └───────┬──────┘
                                 │ ④成果発生
                                 ▼
                         ┌──────────────┐
                         │   add.php     │
                         │  (CV計測)     │
                         └───────┬──────┘
                                 │ ⑤ポストバック送信
                                 ▼
                         ┌──────────────┐
                         │ AFADシステム  │
                         │ (ポストバック │
                         │   受信)       │
                         └──────────────┘
```

### 2.2 関連ファイル

| ファイル名 | 役割 | 変更内容 |
|-----------|------|---------|
| `link.php` | クリック計測・リダイレクト | AFADセッションID受け取り機能追加 |
| `add.php` | 成果計測 | AFADポストバック送信機能追加 |
| `continue.php` | 継続課金成果計測 | AFADポストバック送信機能追加 |
| `module/afad_postback.inc` | AFADポストバック処理モジュール(新規) | ポストバック送信処理 |
| `tdb/afad_config.csv` | AFAD連携設定(新規) | 広告ごとのAFAD設定情報 |
| `custom/extends/afadConf.php` | AFAD設定ファイル(新規) | グローバル設定 |

---

## 3. 連携フロー

### 3.1 クリック発生時のフロー

```
[ユーザー] → [メディア(AFAD)] → [link.php(当システム)] → [広告主サイト]
```

**詳細手順:**

1. ユーザーがAFADメディアの広告をクリック
2. AFADがセッションIDを生成し、URLパラメータに付与
   ```
   https://example.com/link.php?adwares=123&id=456&afad_sid=ABC123XYZ
   ```
3. `link.php`でパラメータを受け取る
   - `afad_sid`: AFADセッションID(パラメータ名は設定可能)
4. データベースにアクセス情報とセッションIDを保存
5. セッションIDをCookieにも保存(バックアップ用)
6. 広告主サイトへリダイレクト(必要に応じてURLにセッションIDを付与)

**シーケンス図:**

```sequence
ユーザー->AFAD: 広告クリック
AFAD->link.php: GET /link.php?adwares=123&afad_sid=XXX
link.php->DB: セッションID保存
link.php->Cookie: セッションID保存
link.php->ユーザー: 302 Redirect + Set-Cookie
ユーザー->広告主: リダイレクト
```

### 3.2 成果発生時のフロー

```
[広告主サイト] → [add.php/continue.php] → [AFADポストバックURL]
```

**詳細手順:**

1. ユーザーが広告主サイトで成果を達成(購入、登録など)
2. 広告主サイトのサンクスページにトラッキングコードが設置されている
   ```html
   <img src="https://example.com/add.php?check=TOKEN&sales=10000" width="1" height="1">
   ```
3. `add.php`が実行される
4. アクセスログからAFADセッションIDを取得
5. AFADのポストバックURLを構築
   ```
   https://ac.afad-domain.jp/xxxxx/ac/?gid=GROUP_ID&af=SESSION_ID&uid=ORDER_ID&amount=10000
   ```
6. HTTPリクエストでポストバックURLへ送信
7. AFADから200 OKレスポンスを受信
8. 送信結果をログに記録

**シーケンス図:**

```sequence
広告主->add.php: GET /add.php?check=TOKEN&sales=10000
add.php->DB: AFADセッションID取得
add.php->AFAD: GET /ac/?gid=XX&af=YY&uid=ZZ&amount=10000
AFAD->add.php: 200 OK
add.php->Log: ポストバック送信ログ記録
add.php->広告主: 1x1 GIF画像
```

---

## 4. データベース設計

### 4.1 アクセステーブル拡張

既存の`access`テーブル(またはアクセスログ)にAFAD連携用のフィールドを追加。

**追加カラム:**

| カラム名 | 型 | NULL | 説明 |
|---------|-----|------|------|
| `afad_session_id` | VARCHAR(255) | YES | AFADセッションID |
| `afad_postback_sent` | TINYINT(1) | NO | ポストバック送信済みフラグ(0:未送信, 1:送信済み) |
| `afad_postback_status` | VARCHAR(50) | YES | ポストバック送信ステータス(success/failed/pending) |
| `afad_postback_time` | INT(11) | YES | ポストバック送信日時(UNIXタイムスタンプ) |
| `afad_postback_response` | TEXT | YES | AFADからのレスポンス内容 |
| `afad_postback_retry_count` | TINYINT(3) | NO | リトライ回数(デフォルト:0) |

**インデックス:**

```sql
CREATE INDEX idx_afad_session ON access(afad_session_id);
CREATE INDEX idx_afad_postback ON access(afad_postback_sent, afad_postback_status);
```

### 4.2 AFAD設定テーブル(CSV)

ファイル: `tdb/afad_config.csv`

広告(adwares)ごとのAFAD連携設定を管理。

**カラム構成:**

| カラム名 | 説明 | 例 |
|---------|------|-----|
| `adwares_id` | 広告ID | `123` |
| `afad_enable` | AFAD連携有効フラグ(0:無効, 1:有効) | `1` |
| `afad_parameter_name` | セッションID受け取り用パラメータ名 | `afad_sid` |
| `afad_postback_url` | AFADポストバックURL | `https://ac.afad.jp/12345/ac/` |
| `afad_group_id` | AFAD広告グループID | `ABC123` |
| `afad_send_uid` | uid(注文番号等)を送信するか(0:No, 1:Yes) | `1` |
| `afad_send_uid2` | uid2を送信するか(0:No, 1:Yes) | `0` |
| `afad_send_amount` | 成果金額を送信するか(0:No, 1:Yes) | `1` |
| `afad_approval_status` | 送信する承認ステータス(1:承認待ち, 2:承認, 3:否認) | `1` |
| `afad_timeout` | HTTPリクエストタイムアウト(秒) | `10` |
| `afad_retry_max` | リトライ最大回数 | `3` |
| `afad_url_passthrough` | リダイレクト先URLにもセッションIDを渡すか(0:No, 1:Yes) | `1` |
| `afad_cookie_expire` | Cookie有効期限(日数) | `30` |

**サンプルデータ:**

```csv
adwares_id,afad_enable,afad_parameter_name,afad_postback_url,afad_group_id,afad_send_uid,afad_send_uid2,afad_send_amount,afad_approval_status,afad_timeout,afad_retry_max,afad_url_passthrough,afad_cookie_expire
123,1,afad_sid,https://ac.afad.jp/12345/ac/,GRP001,1,0,1,1,10,3,1,30
456,1,asid,https://ac.afad.jp/67890/ac/,GRP002,1,1,1,2,15,5,0,60
```

### 4.3 AFAD送信ログテーブル(新規)

ファイル: `tdb/afad_postback_log.csv`

ポストバック送信履歴を記録。

**カラム構成:**

| カラム名 | 説明 |
|---------|------|
| `log_id` | ログID(ユニーク) |
| `access_id` | アクセスID(accessテーブルとの紐付け) |
| `adwares_id` | 広告ID |
| `afad_session_id` | AFADセッションID |
| `postback_url` | 送信したポストバックURL |
| `request_params` | リクエストパラメータ(JSON形式) |
| `response_code` | HTTPレスポンスコード |
| `response_body` | レスポンスボディ |
| `status` | 送信ステータス(success/failed/timeout) |
| `error_message` | エラーメッセージ |
| `retry_count` | リトライ回数 |
| `created_at` | 作成日時(UNIXタイムスタンプ) |
| `updated_at` | 更新日時(UNIXタイムスタンプ) |

---

## 5. API仕様

### 5.1 AFADセッションID受け取り(link.php)

**エンドポイント:**
```
GET /link.php
```

**リクエストパラメータ:**

| パラメータ名 | 必須 | 型 | 説明 | 例 |
|------------|------|-----|------|-----|
| `adwares` | ○ | string | 広告ID | `123` |
| `id` | ○ | string | アフィリエイターID | `456` |
| `afad_sid` | △ | string | AFADセッションID(パラメータ名は設定による) | `ABC123XYZ` |
| その他 | - | - | 既存パラメータ | - |

**処理フロー:**

1. 既存のlink.php処理を実行
2. AFAD設定を取得(`afad_config.csv`)
3. AFAD連携が有効な場合:
   - 設定されたパラメータ名からセッションIDを取得
   - アクセスレコードに保存
   - Cookieに保存(フォールバック用)
4. リダイレクト処理
   - `afad_url_passthrough`が有効な場合、リダイレクト先URLにもセッションIDを付与

**Cookie仕様:**

| 項目 | 値 |
|------|-----|
| Cookie名 | `afad_session_{adwares_id}` |
| 値 | AFADセッションID |
| 有効期限 | 設定値(デフォルト30日) |
| Path | `/` |
| Secure | HTTPS環境ではtrue |
| HttpOnly | true |
| SameSite | Lax |

### 5.2 AFADポストバック送信(add.php / continue.php)

**送信タイミング:**
- 成果が発生した際に自動的にポストバック送信

**ポストバックURL形式:**

```
https://ac.{AFADドメイン}/{識別子}/ac/?{パラメータ}
```

**送信パラメータ:**

| パラメータ名 | 必須 | 説明 | 取得元 | 例 |
|------------|------|------|--------|-----|
| `gid` | ○ | 広告グループID | AFAD設定 | `GRP001` |
| `af` | ○ | AFADセッションID | アクセスログ | `ABC123XYZ` |
| `uid` | △ | ユーザー識別ID(注文番号など) | 成果データ | `ORDER12345` |
| `uid2` | △ | サブユーザー識別ID | 成果データ | `MEMBER67890` |
| `amount` | △ | 成果金額 | 成果データ | `10000` |
| `status` | △ | 承認ステータス(1:承認待ち, 2:承認, 3:否認) | AFAD設定 | `1` |

**送信例:**

```
GET https://ac.afad.jp/12345/ac/?gid=GRP001&af=ABC123XYZ&uid=ORDER12345&amount=10000&status=1
```

**期待されるレスポンス:**

| HTTPステータス | 意味 | 処理 |
|--------------|------|------|
| 200 OK | 成功 | 送信完了として記録 |
| 400 Bad Request | パラメータエラー | エラーログ記録、リトライしない |
| 500 Internal Server Error | サーバーエラー | リトライ処理実行 |
| タイムアウト | 接続タイムアウト | リトライ処理実行 |

---

## 6. 実装詳細

### 6.1 link.php の変更

**追加処理:**

```php
/**
 * AFAD連携: セッションID受け取り処理
 * @param array $adwares 広告情報
 * @param array $access アクセス情報
 */
function HandleAFADSession($adwares, $access)
{
    // AFAD設定を読み込み
    $afadConfig = LoadAFADConfig($adwares['adwares_id']);

    if (!$afadConfig || !$afadConfig['afad_enable']) {
        return; // AFAD連携無効
    }

    // パラメータ名からセッションIDを取得
    $paramName = $afadConfig['afad_parameter_name'];
    $sessionId = isset($_GET[$paramName]) ? $_GET[$paramName] : null;

    if (empty($sessionId)) {
        // パラメータがない場合はスキップ
        return;
    }

    // セッションIDをバリデーション
    if (!ValidateAFADSessionId($sessionId)) {
        WriteAFADLog('Invalid AFAD session ID format', $sessionId);
        return;
    }

    // アクセスレコードに保存
    SaveAFADSessionToAccess($access['access_id'], $sessionId);

    // Cookieに保存(バックアップ用)
    SetAFADSessionCookie($adwares['adwares_id'], $sessionId, $afadConfig['afad_cookie_expire']);

    // ログ記録
    WriteAFADLog('AFAD session ID received', [
        'adwares_id' => $adwares['adwares_id'],
        'access_id' => $access['access_id'],
        'session_id' => $sessionId
    ]);
}
```

**変更箇所:**

`link.php` の `AddAccess()` 関数の後に上記処理を追加。

```php
// 既存処理
$access = AddAccess( $adwares );
$pay    = AddClickReward( $adwares , $access );

// AFAD連携処理を追加
HandleAFADSession($adwares, $access);
```

**リダイレクトURL生成の変更:**

```php
function DoRedirect( $adwares , $access )
{
    // 既存のリダイレクトURL生成処理
    $redirectUrl = BuildRedirectURL($adwares, $access);

    // AFAD連携: URLパススルー処理
    $afadConfig = LoadAFADConfig($adwares['adwares_id']);
    if ($afadConfig && $afadConfig['afad_url_passthrough']) {
        $sessionId = GetAFADSessionFromAccess($access['access_id']);
        if ($sessionId) {
            $paramName = $afadConfig['afad_parameter_name'];
            $redirectUrl = AppendURLParameter($redirectUrl, $paramName, $sessionId);
        }
    }

    // リダイレクト実行
    header("Location: " . $redirectUrl);
    exit;
}
```

### 6.2 add.php / continue.php の変更

**追加処理:**

```php
/**
 * AFAD連携: ポストバック送信処理
 * @param array $adwares 広告情報
 * @param array $access アクセス情報
 * @param array $conversion 成果情報
 */
function SendAFADPostback($adwares, $access, $conversion)
{
    // AFAD設定を読み込み
    $afadConfig = LoadAFADConfig($adwares['adwares_id']);

    if (!$afadConfig || !$afadConfig['afad_enable']) {
        return; // AFAD連携無効
    }

    // セッションIDを取得
    $sessionId = GetAFADSessionFromAccess($access['access_id']);

    if (empty($sessionId)) {
        // Cookieからフォールバック取得
        $sessionId = GetAFADSessionFromCookie($adwares['adwares_id']);
    }

    if (empty($sessionId)) {
        WriteAFADLog('AFAD session ID not found', [
            'adwares_id' => $adwares['adwares_id'],
            'access_id' => $access['access_id']
        ]);
        return;
    }

    // すでに送信済みかチェック
    if (IsAFADPostbackSent($access['access_id'])) {
        WriteAFADLog('AFAD postback already sent', [
            'access_id' => $access['access_id']
        ]);
        return;
    }

    // ポストバックURL構築
    $postbackUrl = BuildAFADPostbackURL($afadConfig, $sessionId, $conversion);

    // HTTPリクエスト送信
    $result = SendHTTPRequest($postbackUrl, $afadConfig['afad_timeout']);

    // 送信結果を記録
    RecordAFADPostback($access['access_id'], $sessionId, $postbackUrl, $result);

    // リトライ処理
    if (!$result['success'] && $result['retry_count'] < $afadConfig['afad_retry_max']) {
        ScheduleAFADRetry($access['access_id'], $result['retry_count']);
    }
}
```

**変更箇所:**

`add.php` の成果追加処理の後に上記を追加。

```php
// 既存の成果追加処理
$conversion = AddConversion($adwares, $access, $sales);

// AFAD連携: ポストバック送信
SendAFADPostback($adwares, $access, $conversion);
```

### 6.3 module/afad_postback.inc (新規作成)

AFADポストバック処理を集約したモジュール。

**主要関数:**

```php
<?php
/**
 * AFADポストバック連携モジュール
 */

/**
 * AFAD設定を読み込む
 * @param string $adwaresId 広告ID
 * @return array|null AFAD設定配列
 */
function LoadAFADConfig($adwaresId)
{
    // CSVから設定を読み込み
    // キャッシュ機能も実装
}

/**
 * AFADセッションIDのバリデーション
 * @param string $sessionId セッションID
 * @return bool 有効な場合true
 */
function ValidateAFADSessionId($sessionId)
{
    // 英数字とハイフン、アンダースコアのみ許可
    // 長さ制限: 1-255文字
    return preg_match('/^[a-zA-Z0-9_-]{1,255}$/', $sessionId);
}

/**
 * アクセスレコードにセッションIDを保存
 */
function SaveAFADSessionToAccess($accessId, $sessionId)
{
    // データベース更新処理
}

/**
 * Cookie にセッションIDを保存
 */
function SetAFADSessionCookie($adwaresId, $sessionId, $expireDays)
{
    $cookieName = "afad_session_{$adwaresId}";
    $expire = time() + ($expireDays * 86400);

    setcookie($cookieName, $sessionId, [
        'expires' => $expire,
        'path' => '/',
        'secure' => isHTTPS(),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

/**
 * アクセスレコードからセッションIDを取得
 */
function GetAFADSessionFromAccess($accessId)
{
    // データベースから取得
}

/**
 * Cookie からセッションIDを取得
 */
function GetAFADSessionFromCookie($adwaresId)
{
    $cookieName = "afad_session_{$adwaresId}";
    return isset($_COOKIE[$cookieName]) ? $_COOKIE[$cookieName] : null;
}

/**
 * ポストバックURL構築
 */
function BuildAFADPostbackURL($afadConfig, $sessionId, $conversion)
{
    $baseUrl = rtrim($afadConfig['afad_postback_url'], '/');

    $params = [
        'gid' => $afadConfig['afad_group_id'],
        'af' => $sessionId
    ];

    // オプションパラメータ
    if ($afadConfig['afad_send_uid'] && !empty($conversion['uid'])) {
        $params['uid'] = $conversion['uid'];
    }

    if ($afadConfig['afad_send_uid2'] && !empty($conversion['uid2'])) {
        $params['uid2'] = $conversion['uid2'];
    }

    if ($afadConfig['afad_send_amount'] && !empty($conversion['amount'])) {
        $params['amount'] = $conversion['amount'];
    }

    if (!empty($afadConfig['afad_approval_status'])) {
        $params['status'] = $afadConfig['afad_approval_status'];
    }

    return $baseUrl . '?' . http_build_query($params);
}

/**
 * HTTPリクエスト送信
 */
function SendHTTPRequest($url, $timeout = 10)
{
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'ORKA-ASP2-AFAD/1.0'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    return [
        'success' => ($httpCode == 200),
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}

/**
 * ポストバック送信結果を記録
 */
function RecordAFADPostback($accessId, $sessionId, $url, $result)
{
    // アクセステーブルを更新
    // ログテーブルに記録
}

/**
 * ポストバック送信済みチェック
 */
function IsAFADPostbackSent($accessId)
{
    // データベースから確認
}

/**
 * リトライスケジュール
 */
function ScheduleAFADRetry($accessId, $retryCount)
{
    // リトライキューに追加
    // または cron で定期実行する仕組みを用意
}

/**
 * AFADログ記録
 */
function WriteAFADLog($message, $data = [])
{
    $logFile = './logs/afad_postback.log';
    $timestamp = date('Y-m-d H:i:s');
    $logData = is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data;

    $logLine = "[{$timestamp}] {$message}: {$logData}\n";

    file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
}

/**
 * HTTPS判定
 */
function isHTTPS()
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || $_SERVER['SERVER_PORT'] == 443;
}
```

### 6.4 custom/extends/afadConf.php (新規作成)

グローバル設定ファイル。

```php
<?php
/**
 * AFAD連携グローバル設定
 */

// AFAD連携機能の有効化
$CONFIG_AFAD_ENABLE = true;

// デフォルトパラメータ名
$CONFIG_AFAD_DEFAULT_PARAM_NAME = 'afad_sid';

// デフォルトタイムアウト(秒)
$CONFIG_AFAD_DEFAULT_TIMEOUT = 10;

// デフォルトリトライ回数
$CONFIG_AFAD_DEFAULT_RETRY_MAX = 3;

// デフォルトCookie有効期限(日)
$CONFIG_AFAD_DEFAULT_COOKIE_EXPIRE = 30;

// ログファイルパス
$CONFIG_AFAD_LOG_FILE = './logs/afad_postback.log';

// ログレベル (0:無効, 1:エラーのみ, 2:全て)
$CONFIG_AFAD_LOG_LEVEL = 2;

// リトライ間隔(秒) - 指数バックオフ
$CONFIG_AFAD_RETRY_INTERVALS = [60, 300, 900]; // 1分, 5分, 15分

// ポストバック送信時のUser-Agent
$CONFIG_AFAD_USER_AGENT = 'ORKA-ASP2-AFAD/1.0';
```

---

## 7. セキュリティ要件

### 7.1 入力バリデーション

**AFADセッションID:**
- 許可文字: 英数字、ハイフン、アンダースコア
- 長さ: 1-255文字
- SQLインジェクション対策: プリペアドステートメント使用
- XSS対策: 出力時にエスケープ処理

**実装例:**

```php
function ValidateAFADSessionId($sessionId)
{
    // 型チェック
    if (!is_string($sessionId)) {
        return false;
    }

    // 長さチェック
    if (strlen($sessionId) < 1 || strlen($sessionId) > 255) {
        return false;
    }

    // 文字種チェック
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $sessionId)) {
        return false;
    }

    return true;
}
```

### 7.2 HTTPS通信

**要件:**
- AFADポストバックURLはHTTPSを推奨
- SSL証明書の検証を有効化(`CURLOPT_SSL_VERIFYPEER => true`)
- 自己署名証明書は許可しない

### 7.3 Cookie セキュリティ

**設定:**
- `HttpOnly`: true (JavaScriptからのアクセス不可)
- `Secure`: HTTPS環境ではtrue
- `SameSite`: Lax (CSRF対策)

### 7.4 ログセキュリティ

**個人情報保護:**
- ユーザー識別ID(uid)はハッシュ化してログ記録
- IPアドレスは下位バイトをマスク化
- クレジットカード番号等の機密情報は記録しない

### 7.5 アクセス制御

**設定ファイル保護:**
- `afadConf.php`はWeb経由でアクセス不可にする(.htaccess設定)
- ファイルパーミッション: 644 (所有者のみ書き込み可)

**.htaccess 例:**

```apache
<Files "afadConf.php">
    Order allow,deny
    Deny from all
</Files>
```

---

## 8. エラー処理

### 8.1 エラー分類

| エラー種別 | 説明 | 対処 |
|-----------|------|------|
| セッションID未取得 | パラメータにセッションIDがない | ログ記録のみ、処理継続 |
| セッションID不正 | バリデーションエラー | ログ記録、処理スキップ |
| AFAD設定なし | 広告に対するAFAD設定が存在しない | ログ記録、処理スキップ |
| ポストバック送信失敗 | HTTPエラー、タイムアウト | リトライ処理 |
| ポストバック重複送信 | すでに送信済み | ログ記録、処理スキップ |

### 8.2 リトライ処理

**リトライ対象:**
- HTTPステータス 500系エラー
- ネットワークタイムアウト
- 接続エラー

**リトライ非対象:**
- HTTPステータス 400系エラー(クライアントエラー)
- セッションID不正
- すでに最大リトライ回数に達している

**リトライロジック:**

```php
function RetryAFADPostback($accessId)
{
    global $CONFIG_AFAD_RETRY_INTERVALS;

    // 現在のリトライ回数を取得
    $retryCount = GetAFADRetryCount($accessId);

    // 最大回数チェック
    $afadConfig = LoadAFADConfigByAccessId($accessId);
    if ($retryCount >= $afadConfig['afad_retry_max']) {
        WriteAFADLog('Max retry count reached', ['access_id' => $accessId]);
        return false;
    }

    // リトライ間隔計算(指数バックオフ)
    $interval = isset($CONFIG_AFAD_RETRY_INTERVALS[$retryCount])
        ? $CONFIG_AFAD_RETRY_INTERVALS[$retryCount]
        : 900; // デフォルト15分

    // 次回実行時刻を計算
    $nextExecutionTime = time() + $interval;

    // リトライキューに追加
    AddToAFADRetryQueue($accessId, $nextExecutionTime, $retryCount + 1);

    return true;
}
```

### 8.3 エラーログフォーマット

**ログファイル:** `logs/afad_postback.log`

**フォーマット:**

```
[日時] レベル メッセージ: データ(JSON)
```

**例:**

```
[2025-11-02 10:30:45] INFO AFAD session ID received: {"adwares_id":"123","access_id":"456","session_id":"ABC123"}
[2025-11-02 10:31:00] ERROR AFAD postback failed: {"access_id":"456","url":"https://ac.afad.jp/...","http_code":"500","error":"Internal Server Error"}
[2025-11-02 10:32:00] INFO AFAD postback retry scheduled: {"access_id":"456","retry_count":"1","next_time":"2025-11-02 10:33:00"}
```

---

## 9. ログ仕様

### 9.1 ログ種別

| ログ種別 | ファイル名 | 内容 |
|---------|-----------|------|
| 処理ログ | `afad_postback.log` | セッションID受け取り、ポストバック送信処理 |
| エラーログ | `afad_error.log` | エラー詳細 |
| デバッグログ | `afad_debug.log` | 開発時のデバッグ情報 |

### 9.2 ログローテーション

**方式:** 日次ローテーション

**ファイル名規則:**
```
afad_postback_YYYYMMDD.log
```

**保存期間:** 90日

**実装:**

```php
function GetAFADLogFileName($logType = 'postback')
{
    $date = date('Ymd');
    return "./logs/afad_{$logType}_{$date}.log";
}
```

### 9.3 監視項目

**アラート条件:**
- エラー率が10%を超えた場合
- タイムアウト率が20%を超えた場合
- 24時間以上送信失敗が続く場合

**監視スクリプト例:**

```bash
#!/bin/bash
# afad_monitor.sh

LOG_FILE="/path/to/logs/afad_postback_$(date +%Y%m%d).log"
ERROR_THRESHOLD=10

# エラー数カウント
ERROR_COUNT=$(grep "ERROR" "$LOG_FILE" | wc -l)
TOTAL_COUNT=$(wc -l < "$LOG_FILE")

# エラー率計算
ERROR_RATE=$((ERROR_COUNT * 100 / TOTAL_COUNT))

if [ $ERROR_RATE -gt $ERROR_THRESHOLD ]; then
    echo "ALERT: AFAD postback error rate is ${ERROR_RATE}%"
    # メール送信やSlack通知など
fi
```

---

## 10. テスト仕様

### 10.1 単体テスト

**テスト対象関数:**

| 関数名 | テストケース |
|-------|------------|
| `ValidateAFADSessionId()` | 正常値、空文字、不正文字、長すぎる値、NULL |
| `BuildAFADPostbackURL()` | 各種パラメータ組み合わせ、特殊文字エンコード |
| `LoadAFADConfig()` | 設定あり、設定なし、不正なCSV |
| `SendHTTPRequest()` | 正常、タイムアウト、404エラー、500エラー |

**テストコード例:**

```php
// tests/AFADPostbackTest.php

class AFADPostbackTest extends PHPUnit_Framework_TestCase
{
    public function testValidateAFADSessionId_Valid()
    {
        $this->assertTrue(ValidateAFADSessionId('ABC123xyz-_'));
    }

    public function testValidateAFADSessionId_Empty()
    {
        $this->assertFalse(ValidateAFADSessionId(''));
    }

    public function testValidateAFADSessionId_InvalidChars()
    {
        $this->assertFalse(ValidateAFADSessionId('ABC<script>'));
    }

    public function testValidateAFADSessionId_TooLong()
    {
        $longString = str_repeat('A', 256);
        $this->assertFalse(ValidateAFADSessionId($longString));
    }

    public function testBuildAFADPostbackURL()
    {
        $config = [
            'afad_postback_url' => 'https://ac.afad.jp/12345/ac/',
            'afad_group_id' => 'GRP001',
            'afad_send_uid' => 1,
            'afad_send_amount' => 1
        ];

        $conversion = [
            'uid' => 'ORDER123',
            'amount' => 10000
        ];

        $url = BuildAFADPostbackURL($config, 'SESS123', $conversion);

        $this->assertStringContainsString('gid=GRP001', $url);
        $this->assertStringContainsString('af=SESS123', $url);
        $this->assertStringContainsString('uid=ORDER123', $url);
        $this->assertStringContainsString('amount=10000', $url);
    }
}
```

### 10.2 結合テスト

**テストシナリオ:**

#### シナリオ1: 正常系(クリック→成果→ポストバック)

1. AFADセッションID付きでlink.phpにアクセス
2. セッションIDがDBとCookieに保存されることを確認
3. 広告主サイトへリダイレクトされることを確認
4. add.phpで成果計測
5. AFADポストバックが送信されることを確認
6. ログに記録されることを確認

#### シナリオ2: セッションIDなしでクリック

1. AFADセッションIDなしでlink.phpにアクセス
2. 通常のクリック計測が動作することを確認
3. add.phpで成果計測
4. AFADポストバックが送信されないことを確認

#### シナリオ3: ポストバック送信失敗→リトライ

1. AFADポストバックURLをモックサーバー(500エラー返却)に設定
2. add.phpで成果計測
3. ポストバック送信が失敗することを確認
4. リトライキューに追加されることを確認
5. リトライ処理実行
6. 再送信されることを確認

#### シナリオ4: 重複送信防止

1. 同一アクセスIDで2回add.phpを実行
2. 1回目はポストバック送信される
3. 2回目は送信されない(重複チェック)ことを確認

### 10.3 負荷テスト

**目的:** 大量のポストバック送信時のパフォーマンス確認

**テスト条件:**
- 同時成果発生数: 100件/秒
- 継続時間: 10分
- AFADサーバーレスポンス: 平均200ms

**評価基準:**
- ポストバック送信成功率: 99%以上
- タイムアウト率: 1%以下
- システムリソース使用率: CPU 80%以下、メモリ 70%以下

**テストツール:** Apache JMeter または Locust

### 10.4 セキュリティテスト

**テスト項目:**

| 項目 | テスト内容 |
|------|-----------|
| SQLインジェクション | セッションIDに`' OR 1=1--`等を入力 |
| XSS | セッションIDに`<script>alert(1)</script>`を入力 |
| CSRF | 不正なRefererからのリクエスト |
| Cookie改ざん | Cookieのセッションidを改ざん |

---

## 11. 運用要件

### 11.1 設定手順

**新規広告にAFAD連携を設定する場合:**

1. AFADから以下の情報を入手:
   - ポストバックURL
   - 広告グループID (gid)
   - セッションIDパラメータ名(デフォルト: afad_sid)

2. `tdb/afad_config.csv`に設定を追加:
   ```csv
   123,1,afad_sid,https://ac.afad.jp/12345/ac/,GRP001,1,0,1,1,10,3,1,30
   ```

3. 広告URL発行時にセッションIDパラメータを含める:
   ```
   https://example.com/link.php?adwares=123&id=456&afad_sid=##sessionId##
   ```
   ※`##sessionId##`はAFAD側で置換される変数

4. テスト実行:
   - テストクリック→成果発生→AFADポストバック確認

### 11.2 監視項目

**日次監視:**

| 項目 | 確認方法 | アラート条件 |
|------|---------|------------|
| ポストバック送信成功率 | ログ解析 | 95%未満 |
| タイムアウト率 | ログ解析 | 5%超過 |
| エラーログ | `afad_error.log`確認 | ERROR件数が10件以上 |
| リトライキューサイズ | DB/CSV確認 | 100件以上 |

**監視スクリプト例:**

```bash
#!/bin/bash
# daily_afad_report.sh

DATE=$(date +%Y%m%d)
LOG_FILE="/path/to/logs/afad_postback_${DATE}.log"

echo "=== AFAD Postback Daily Report ${DATE} ==="

# 総送信数
TOTAL=$(grep "postback sent" "$LOG_FILE" | wc -l)
echo "Total: $TOTAL"

# 成功数
SUCCESS=$(grep "postback sent.*success" "$LOG_FILE" | wc -l)
echo "Success: $SUCCESS"

# 失敗数
FAILED=$(grep "postback failed" "$LOG_FILE" | wc -l)
echo "Failed: $FAILED"

# 成功率
if [ $TOTAL -gt 0 ]; then
    SUCCESS_RATE=$((SUCCESS * 100 / TOTAL))
    echo "Success Rate: ${SUCCESS_RATE}%"
fi
```

### 11.3 バックアップ

**対象ファイル:**
- `tdb/afad_config.csv`
- `tdb/afad_postback_log.csv`
- `logs/afad_*.log`

**バックアップ頻度:** 日次

**保存期間:** 90日

### 11.4 障害対応

**障害シナリオと対応:**

| 障害内容 | 対応手順 |
|---------|---------|
| AFADサーバーダウン | リトライ処理で自動復旧。24時間以上続く場合はAFADに連絡 |
| 設定ファイル破損 | バックアップから復元 |
| ログファイル肥大化 | ログローテーション設定確認、古いログ削除 |
| DB接続エラー | DB再起動、接続設定確認 |

---

## 12. 実装スケジュール

### 12.1 フェーズ1: 設計・準備(1週間)

| タスク | 担当 | 期間 |
|-------|------|------|
| 詳細設計レビュー | 全員 | 1日 |
| DB設計・テーブル作成 | DBエンジニア | 2日 |
| 設定ファイル仕様確定 | システムエンジニア | 1日 |
| 開発環境準備 | インフラエンジニア | 2日 |

### 12.2 フェーズ2: 実装(2週間)

| タスク | 担当 | 期間 |
|-------|------|------|
| `module/afad_postback.inc`実装 | 開発者A | 3日 |
| `link.php`改修 | 開発者B | 2日 |
| `add.php`改修 | 開発者B | 2日 |
| `continue.php`改修 | 開発者B | 1日 |
| 設定ファイル・CSV処理実装 | 開発者A | 2日 |
| リトライ処理実装 | 開発者A | 2日 |
| ログ処理実装 | 開発者C | 2日 |

### 12.3 フェーズ3: テスト(2週間)

| タスク | 担当 | 期間 |
|-------|------|------|
| 単体テスト | 各開発者 | 3日 |
| 結合テスト | QAエンジニア | 3日 |
| セキュリティテスト | セキュリティエンジニア | 2日 |
| 負荷テスト | インフラエンジニア | 2日 |
| バグ修正 | 開発者全員 | 3日 |

### 12.4 フェーズ4: リリース準備(1週間)

| タスク | 担当 | 期間 |
|-------|------|------|
| ステージング環境デプロイ | インフラエンジニア | 1日 |
| ステージング環境でAFADと接続テスト | システムエンジニア | 2日 |
| 運用マニュアル作成 | ドキュメント担当 | 2日 |
| リリース判定会議 | 全員 | 1日 |

### 12.5 フェーズ5: 本番リリース・運用開始(1週間)

| タスク | 担当 | 期間 |
|-------|------|------|
| 本番環境デプロイ | インフラエンジニア | 1日 |
| 本番環境でAFADと接続テスト | システムエンジニア | 1日 |
| 監視設定 | インフラエンジニア | 1日 |
| 運用開始 | 全員 | - |
| 初期監視期間(密なモニタリング) | 運用チーム | 3日 |

**総期間: 約7週間**

---

## 付録

### A. 用語集

| 用語 | 説明 |
|------|------|
| AFAD | アフィリエイトアド。メディア側のアフィリエイトシステム |
| ポストバック | 成果発生時にシステム間でデータを送信する仕組み |
| ソケット連携 | ポストバック連携の別称 |
| セッションID | AFADが発行する1クリックごとの識別ID |
| gid | 広告グループID(Group ID) |
| uid | ユーザー識別ID(User ID)。注文番号や会員IDなど |
| CV | コンバージョン。成果のこと |

### B. 参考資料

- AFAD仕様書.pdf
- orka-asp2システム仕様書
- PHP公式ドキュメント: https://www.php.net/
- cURL公式ドキュメント: https://curl.se/docs/

### C. 変更履歴

| 日付 | バージョン | 変更内容 | 作成者 |
|------|-----------|---------|-------|
| 2025-11-02 | 1.0 | 初版作成 | システム設計チーム |

---

**以上**
