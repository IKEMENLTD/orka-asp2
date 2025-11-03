# 実装ガイド - orka-asp2 CSS実装

**バージョン:** 1.0.0
**作成日:** 2025-11-03

このガイドでは、CSS設計書とコンポーネントライブラリに基づいて、orka-asp2システムのUIを段階的に実装する方法を説明します。

---

## 目次

1. [実装の流れ](#1-実装の流れ)
2. [ステップ1: CSSファイルの作成](#2-ステップ1-cssファイルの作成)
3. [ステップ2: PHPクラスの更新](#3-ステップ2-phpクラスの更新)
4. [ステップ3: 各ページの実装](#4-ステップ3-各ページの実装)
5. [テスト方法](#5-テスト方法)
6. [トラブルシューティング](#6-トラブルシューティング)
7. [パフォーマンス最適化](#7-パフォーマンス最適化)

---

## 1. 実装の流れ

### 全体フロー

```
1. CSSファイルの作成
   ↓
2. 基盤クラス（System.php, Draw.php）の更新
   ↓
3. Template.phpの実装
   ↓
4. 各ページの順次実装
   - ログインページ
   - ダッシュボード
   - レポート
   - AFAD管理
   ↓
5. テスト・調整
   ↓
6. 本番環境へデプロイ
```

### 推奨実装順序

**フェーズ1: 基盤（1-2日）**
1. CSSファイル作成（main.css）
2. System.php更新（ヘッダー/フッター）
3. Draw.php更新（ユーティリティメソッド）

**フェーズ2: 認証（1日）**
4. ログインページ（login.php）
5. パスワードリセット（unlock.php, reminder.php）

**フェーズ3: コア機能（2-3日）**
6. ダッシュボード（index.php）
7. レポート（report.php）
8. 検索（search.php）

**フェーズ4: 管理機能（2-3日）**
9. 登録・編集フォーム（regist.php）
10. 管理ツール（tool.php）
11. AFAD管理ページ

**フェーズ5: 最終調整（1-2日）**
12. レスポンシブ対応の確認
13. アクセシビリティ確認
14. ブラウザ互換性テスト

---

## 2. ステップ1: CSSファイルの作成

### 2.1 ディレクトリ構造の作成

```bash
mkdir -p /home/user/orka-asp2/css
mkdir -p /home/user/orka-asp2/css/components
mkdir -p /home/user/orka-asp2/css/pages
```

### 2.2 main.cssの作成

`/css/main.css` に全てのスタイルを統合します。

**ファイル構成:**

```css
/* main.css */

/* ==========================================================================
   1. CSS Variables (カスタムプロパティ)
   ========================================================================== */

/* 2. Reset & Base Styles (リセット・基本スタイル)
   ========================================================================== */

/* 3. Layout System (レイアウトシステム)
   ========================================================================== */

/* 4. Components (コンポーネント)
   ========================================================================== */

/* 5. AFAD Specific (AFAD固有)
   ========================================================================== */

/* 6. Page Specific (ページ固有)
   ========================================================================== */

/* 7. Utilities (ユーティリティ)
   ========================================================================== */

/* 8. Responsive (レスポンシブ)
   ========================================================================== */
```

**注意点:**
- 一つのファイルに全て統合（HTTPリクエスト削減）
- 圧縮前のサイズ: 約50-70KB
- 圧縮後のサイズ: 約10-15KB

### 2.3 CSSファイルの読み込み

System.phpのgetHead()メソッドを更新:

```php
public static function getHead($gm, $loginUserType, $loginUserRank) {
    $title = $gm['system']['SITE_NAME'] ?? 'orka-asp2';
    $charset = 'UTF-8';

    return <<<HTML
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="{$charset}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <link rel="stylesheet" href="/css/main.css">
</head>
<body>
HTML;
}
```

---

## 3. ステップ2: PHPクラスの更新

### 3.1 System.phpの更新

**現在のファイル:** `/home/user/orka-asp2/include/base/System.php`

**更新内容:**

```php
<?php
/**
 * System Class - HTML generation and layout
 */
class System {
    public static $checkData = null;

    /**
     * ヘッダー生成（ナビゲーション付き）
     */
    public static function getHead($gm, $loginUserType, $loginUserRank) {
        $title = $gm['system']['SITE_NAME'] ?? 'orka-asp2';
        $isLoggedIn = ($loginUserType != 'NOT_LOGIN');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <link rel="stylesheet" href="/css/main.css">
</head>
<body>
HTML;

        // ログイン済みの場合はナビゲーション表示
        if ($isLoggedIn) {
            $html .= self::getNavbar($loginUserType);
            $html .= '<div class="app-layout">';
            $html .= self::getSidebar($loginUserType);
            $html .= '<main class="app-main">';
        }

        return $html;
    }

    /**
     * フッター生成
     */
    public static function getFoot($gm, $loginUserType, $loginUserRank) {
        $isLoggedIn = ($loginUserType != 'NOT_LOGIN');

        $html = '';

        if ($isLoggedIn) {
            $html .= '</main>'; // app-main
            $html .= '</div>'; // app-layout
        }

        $html .= <<<HTML
<footer class="app-footer">
    <p>&copy; 2025 orka-asp2. All rights reserved.</p>
</footer>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * ナビゲーションバー
     */
    private static function getNavbar($loginUserType) {
        return <<<HTML
<header class="app-header">
    <nav class="navbar">
        <a href="/index.php" class="navbar-brand">ORKA-ASP2</a>
        <ul class="navbar-menu">
            <li><a href="/index.php" class="navbar-link">ダッシュボード</a></li>
            <li><a href="/report.php" class="navbar-link">レポート</a></li>
            <li><a href="/search.php?type=adwares" class="navbar-link">検索</a></li>
            <li><a href="/login.php?logout=1" class="navbar-link">ログアウト</a></li>
        </ul>
    </nav>
</header>
HTML;
    }

    /**
     * サイドバーナビゲーション
     */
    private static function getSidebar($loginUserType) {
        return <<<HTML
<aside class="app-sidebar">
    <nav class="sidebar-nav">
        <div class="sidebar-nav-group-title">メニュー</div>
        <ul>
            <li class="sidebar-nav-item">
                <a href="/index.php" class="sidebar-nav-link">
                    <span class="sidebar-nav-icon">📊</span>
                    ダッシュボード
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="/report.php" class="sidebar-nav-link">
                    <span class="sidebar-nav-icon">📈</span>
                    レポート
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="/search.php?type=adwares" class="sidebar-nav-link">
                    <span class="sidebar-nav-icon">🔍</span>
                    検索
                </a>
            </li>
        </ul>

        <div class="sidebar-nav-group-title">管理</div>
        <ul>
            <li class="sidebar-nav-item">
                <a href="/tool.php" class="sidebar-nav-link">
                    <span class="sidebar-nav-icon">🛠️</span>
                    ツール
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="/regist.php?type=admin" class="sidebar-nav-link">
                    <span class="sidebar-nav-icon">👤</span>
                    ユーザー管理
                </a>
            </li>
        </ul>

        <div class="sidebar-nav-group-title">AFAD</div>
        <ul>
            <li class="sidebar-nav-item">
                <a href="/afad/config.php" class="sidebar-nav-link">
                    <span class="sidebar-nav-icon">⚙️</span>
                    AFAD設定
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="/afad/logs.php" class="sidebar-nav-link">
                    <span class="sidebar-nav-icon">📋</span>
                    ログ
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="/afad/stats.php" class="sidebar-nav-link">
                    <span class="sidebar-nav-icon">📊</span>
                    統計
                </a>
            </li>
        </ul>
    </nav>
</aside>
HTML;
    }
}
?>
```

### 3.2 Draw.phpの更新

**新しいユーティリティメソッドを追加:**

```php
<?php
/**
 * Draw Class - HTML component generation utilities
 */
class Draw {
    // 既存のメソッド
    public static function Head($sqlMaster) {
        echo '<!DOCTYPE html><html><head><title>Setup</title><link rel="stylesheet" href="/css/main.css"></head><body>';
    }

    public static function SQLConnectError() {
        echo '<div class="alert alert-error">';
        echo '<strong class="alert-title">データベース接続エラー</strong>';
        echo '<p>データベースに接続できませんでした。設定を確認してください。</p>';
        echo '</div>';
    }

    // 新規メソッド

    /**
     * ボタン生成
     */
    public static function button($text, $type = 'primary', $size = 'md', $attributes = []) {
        $class = "btn btn-{$type} btn-{$size}";
        $attr_str = '';

        foreach ($attributes as $key => $value) {
            $attr_str .= " {$key}=\"" . htmlspecialchars($value) . "\"";
        }

        return "<button class=\"{$class}\"{$attr_str}>" . htmlspecialchars($text) . "</button>";
    }

    /**
     * アラート生成
     */
    public static function alert($message, $type = 'info', $title = '', $dismissible = false) {
        $html = "<div class=\"alert alert-{$type}\">";

        if ($title) {
            $html .= "<strong class=\"alert-title\">" . htmlspecialchars($title) . "</strong>";
        }

        $html .= htmlspecialchars($message);

        if ($dismissible) {
            $html .= '<button class="alert-close" onclick="this.parentElement.remove()">×</button>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * カード生成
     */
    public static function card($title, $content, $footer = '') {
        $html = '<div class="card">';

        if ($title) {
            $html .= '<div class="card-header">';
            $html .= "<h3 class=\"card-title\">" . htmlspecialchars($title) . "</h3>";
            $html .= '</div>';
        }

        $html .= '<div class="card-body">';
        $html .= $content;
        $html .= '</div>';

        if ($footer) {
            $html .= '<div class="card-footer">';
            $html .= $footer;
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * 統計カード生成
     */
    public static function statCard($label, $value, $change = '', $changeType = '') {
        $html = '<div class="stat-card">';
        $html .= "<div class=\"stat-label\">" . htmlspecialchars($label) . "</div>";
        $html .= "<div class=\"stat-value\">" . htmlspecialchars($value) . "</div>";

        if ($change) {
            $html .= "<div class=\"stat-change {$changeType}\">" . htmlspecialchars($change) . "</div>";
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * テーブル生成
     */
    public static function table($headers, $rows, $options = []) {
        $striped = $options['striped'] ?? false;
        $bordered = $options['bordered'] ?? false;
        $compact = $options['compact'] ?? false;

        $tableClass = 'table';
        if ($striped) $tableClass .= ' table-striped';
        if ($bordered) $tableClass .= ' table-bordered';
        if ($compact) $tableClass .= ' table-compact';

        $html = '<div class="table-wrapper">';
        $html .= "<table class=\"{$tableClass}\">";

        // Header
        $html .= '<thead><tr>';
        foreach ($headers as $header) {
            $align = $header['align'] ?? 'left';
            $html .= "<th class=\"text-{$align}\">" . htmlspecialchars($header['label']) . "</th>";
        }
        $html .= '</tr></thead>';

        // Body
        $html .= '<tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $i => $cell) {
                $align = $headers[$i]['align'] ?? 'left';
                $html .= "<td class=\"text-{$align}\">" . htmlspecialchars($cell) . "</td>";
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';

        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * フォーム入力フィールド生成
     */
    public static function formInput($name, $label, $type = 'text', $options = []) {
        $required = $options['required'] ?? false;
        $value = $options['value'] ?? '';
        $placeholder = $options['placeholder'] ?? '';
        $help = $options['help'] ?? '';
        $error = $options['error'] ?? '';

        $labelClass = 'form-label' . ($required ? ' form-label-required' : '');
        $inputClass = 'form-input' . ($error ? ' is-invalid' : '');

        $html = '<div class="form-group">';
        $html .= "<label class=\"{$labelClass}\" for=\"{$name}\">" . htmlspecialchars($label) . "</label>";
        $html .= "<input type=\"{$type}\" id=\"{$name}\" name=\"{$name}\" class=\"{$inputClass}\" value=\"" . htmlspecialchars($value) . "\" placeholder=\"" . htmlspecialchars($placeholder) . "\"";
        if ($required) $html .= ' required';
        $html .= '>';

        if ($help) {
            $html .= "<span class=\"form-help\">" . htmlspecialchars($help) . "</span>";
        }

        if ($error) {
            $html .= "<span class=\"form-error\">" . htmlspecialchars($error) . "</span>";
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * フォームセレクト生成
     */
    public static function formSelect($name, $label, $options, $selected = '', $required = false) {
        $labelClass = 'form-label' . ($required ? ' form-label-required' : '');

        $html = '<div class="form-group">';
        $html .= "<label class=\"{$labelClass}\" for=\"{$name}\">" . htmlspecialchars($label) . "</label>";
        $html .= "<select id=\"{$name}\" name=\"{$name}\" class=\"form-select\"";
        if ($required) $html .= ' required';
        $html .= '>';

        foreach ($options as $value => $text) {
            $selectedAttr = ($value == $selected) ? ' selected' : '';
            $html .= "<option value=\"" . htmlspecialchars($value) . "\"{$selectedAttr}>" . htmlspecialchars($text) . "</option>";
        }

        $html .= '</select>';
        $html .= '</div>';

        return $html;
    }
}
?>
```

### 3.3 AFAD専用クラスの作成

**新規ファイル:** `/home/user/orka-asp2/include/base/AFADDraw.php`

```php
<?php
/**
 * AFADDraw Class - AFAD specific UI components
 */
class AFADDraw {
    /**
     * AFADステータスバッジ
     */
    public static function statusBadge($status) {
        $statusMap = [
            'pending' => 'Pending',
            'sent' => 'Sent',
            'failed' => 'Failed',
            'retry' => 'Retry',
            'timeout' => 'Timeout',
            'skip' => 'Skip',
        ];

        $text = $statusMap[$status] ?? $status;
        return "<span class=\"afad-status afad-status-{$status}\">" . htmlspecialchars($text) . "</span>";
    }

    /**
     * AFAD統計カード
     */
    public static function statsCard($stats) {
        $html = '<div class="afad-stats-card">';
        $html .= '<div class="afad-stats-header">';
        $html .= '<h3 class="afad-stats-title">AFAD統計</h3>';
        $html .= '<span class="afad-stats-period">' . htmlspecialchars($stats['period']) . '</span>';
        $html .= '</div>';

        $html .= '<div class="afad-stats-grid">';

        foreach ($stats['items'] as $item) {
            $html .= '<div class="afad-stat-item">';
            $html .= '<div class="afad-stat-label">' . htmlspecialchars($item['label']) . '</div>';
            $html .= '<div class="afad-stat-value">' . htmlspecialchars($item['value']) . '</div>';
            if (isset($item['rate'])) {
                $rateClass = $item['rate_type'] === 'success' ? 'afad-success-rate' : 'afad-failure-rate';
                $html .= '<div class="afad-stat-rate ' . $rateClass . '">' . htmlspecialchars($item['rate']) . '</div>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * AFADログアイテム
     */
    public static function logItem($log) {
        $levelClass = "afad-log-level-{$log['level']}";

        $html = '<div class="afad-log-item">';
        $html .= '<span class="afad-log-timestamp">' . htmlspecialchars($log['timestamp']) . '</span>';
        $html .= '<span class="afad-log-level ' . $levelClass . '">' . strtoupper(htmlspecialchars($log['level'])) . '</span>';
        $html .= '<span class="afad-log-message">' . htmlspecialchars($log['message']) . '</span>';

        if (isset($log['details'])) {
            $html .= '<div class="afad-log-details">' . htmlspecialchars($log['details']) . '</div>';
        }

        $html .= '</div>';

        return $html;
    }
}
?>
```

---

## 4. ステップ3: 各ページの実装

### 4.1 ログインページの実装

**ファイル:** `/home/user/orka-asp2/login.php`

**Template.phpにログインテンプレートを追加:**

```php
public static function drawTemplate($gm, $rec, $loginUserType, $loginUserRank, $param, $designType) {
    switch ($designType) {
        case 'LOGIN_PAGE_DESIGN':
            self::drawLoginPage($gm, $rec, $param);
            break;
        case 'LOGIN_FALED_DESIGN':
            self::drawLoginFailed($gm, $rec, $param);
            break;
        case 'LOGIN_LOCK_DESIGN':
            self::drawLoginLocked($gm, $rec, $param);
            break;
        case 'TOP_PAGE_DESIGN':
            self::drawDashboard($gm, $rec, $loginUserType, $loginUserRank);
            break;
        default:
            self::drawDefault();
    }
}

private static function drawLoginPage($gm, $rec, $param) {
    echo <<<HTML
<div class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <h1>ORKA-ASP2</h1>
        </div>
        <h2 class="login-title">ログイン</h2>

        <form method="POST" action="/login.php" class="login-form">
            <div class="form-group">
                <label class="form-label form-label-required" for="username">ユーザー名</label>
                <input type="text" id="username" name="username" class="form-input" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label form-label-required" for="password">パスワード</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>

            <div class="form-check">
                <input type="checkbox" id="remember" name="remember" class="form-check-input">
                <label class="form-check-label" for="remember">ログイン状態を保持</label>
            </div>

            <button type="submit" class="btn btn-primary btn-block login-submit">ログイン</button>
        </form>

        <div class="login-links">
            <a href="/unlock.php">パスワードを忘れた方</a>
        </div>
    </div>
</div>
HTML;
}

private static function drawLoginFailed($gm, $rec, $param) {
    echo '<div class="login-page">';
    echo '<div class="login-card">';
    echo '<div class="login-logo"><h1>ORKA-ASP2</h1></div>';
    echo '<h2 class="login-title">ログイン</h2>';
    echo Draw::alert('ユーザー名またはパスワードが正しくありません', 'error', 'ログイン失敗');
    echo '<form method="POST" action="/login.php" class="login-form">';
    echo Draw::formInput('username', 'ユーザー名', 'text', ['required' => true]);
    echo Draw::formInput('password', 'パスワード', 'password', ['required' => true]);
    echo '<button type="submit" class="btn btn-primary btn-block">ログイン</button>';
    echo '</form>';
    echo '</div>';
    echo '</div>';
}

private static function drawLoginLocked($gm, $rec, $param) {
    echo '<div class="login-page">';
    echo '<div class="login-card">';
    echo '<div class="login-logo"><h1>ORKA-ASP2</h1></div>';
    echo '<h2 class="login-title">アカウントロック</h2>';
    echo Draw::alert('ログイン試行回数が上限を超えました。しばらく待ってから再度お試しください。', 'warning', 'アカウントがロックされています');
    echo '<div class="login-links"><a href="/index.php">トップページに戻る</a></div>';
    echo '</div>';
    echo '</div>';
}
```

### 4.2 ダッシュボードの実装

**Template.phpにダッシュボードテンプレートを追加:**

```php
private static function drawDashboard($gm, $rec, $loginUserType, $loginUserRank) {
    echo '<div class="page-header">';
    echo '<h1 class="page-title">ダッシュボード</h1>';
    echo '<p class="page-subtitle">システム全体の概要</p>';
    echo '</div>';

    // 統計カード
    echo '<div class="dashboard-stats">';
    echo Draw::statCard('総コンバージョン数', '1,234', '↑ 12.5% (前月比)', 'positive');
    echo Draw::statCard('今日のCV', '45', '↑ 8.3%', 'positive');
    echo Draw::statCard('成功率', '98.5%', '↑ 0.5%', 'positive');
    echo Draw::statCard('リトライ中', '3', '変動なし', '');
    echo '</div>';

    // メインコンテンツ
    echo '<div class="dashboard-grid">';

    // 最近の活動カード
    echo Draw::card(
        '最近の活動',
        '<p>最近の活動がここに表示されます</p>',
        '<a href="/report.php" class="btn btn-outline">詳細を見る</a>'
    );

    // AFAD統計カード
    echo AFADDraw::statsCard([
        'period' => '過去30日間',
        'items' => [
            ['label' => '送信成功', 'value' => '1,234', 'rate' => '98.5%', 'rate_type' => 'success'],
            ['label' => '送信失敗', 'value' => '19', 'rate' => '1.5%', 'rate_type' => 'failure'],
            ['label' => 'リトライ中', 'value' => '3'],
        ]
    ]);

    echo '</div>';
}
```

---

## 5. テスト方法

### 5.1 視覚的テスト

```bash
# 各ページにアクセスして確認
http://localhost/login.php
http://localhost/index.php
http://localhost/report.php
http://localhost/search.php?type=adwares
```

### 5.2 レスポンシブテスト

**ブラウザの開発者ツールで確認:**
1. Chrome DevTools（F12）を開く
2. デバイスツールバーをクリック（Ctrl+Shift+M）
3. 各デバイスサイズで確認:
   - iPhone SE (375px)
   - iPad (768px)
   - Desktop (1280px)

### 5.3 ブラウザ互換性テスト

**確認ブラウザ:**
- Chrome (最新版)
- Firefox (最新版)
- Safari (最新版)
- Edge (最新版)

### 5.4 アクセシビリティテスト

```bash
# WAVEツールでチェック
https://wave.webaim.org/

# Lighthouse（Chrome DevTools）でスコア確認
1. Chrome DevTools を開く
2. Lighthouse タブを選択
3. Accessibility をチェックして Generate report
```

---

## 6. トラブルシューティング

### 6.1 CSSが読み込まれない

**症状:** ページにスタイルが適用されない

**原因:**
1. CSSファイルのパスが間違っている
2. ファイルのパーミッションが正しくない

**解決方法:**

```bash
# パーミッション確認
ls -la /home/user/orka-asp2/css/main.css

# パーミッション変更
chmod 644 /home/user/orka-asp2/css/main.css

# パスの確認（ブラウザのコンソールでエラーを確認）
```

### 6.2 レイアウトが崩れる

**症状:** 要素が重なる、位置がずれる

**原因:**
1. CSS変数が定義されていない
2. クラス名のスペルミス
3. 親要素のdisplayプロパティが間違っている

**解決方法:**

```css
/* CSS変数が定義されているか確認 */
:root {
  --spacing-4: 1rem;
  --color-primary-500: #2196F3;
}

/* ブラウザの開発者ツールで要素を検査 */
/* Computed スタイルを確認 */
```

### 6.3 モバイルで表示が崩れる

**症状:** スマートフォンで見ると要素がはみ出る

**原因:**
1. viewport メタタグが設定されていない
2. レスポンシブのメディアクエリが機能していない

**解決方法:**

```php
// System.phpのgetHead()に以下が含まれているか確認
<meta name="viewport" content="width=device-width, initial-scale=1.0">
```

---

## 7. パフォーマンス最適化

### 7.1 CSS圧縮

**ツール:** cssnano, clean-css

```bash
# npmを使用する場合
npm install -g cssnano-cli
cssnano main.css main.min.css

# または、オンラインツール
https://cssnano.co/playground/
```

### 7.2 Critical CSS

**クリティカルCSS（初回表示に必要なCSS）をインライン化:**

```php
// System.phpのgetHead()に追加
public static function getHead($gm, $loginUserType, $loginUserRank) {
    // ... existing code ...

    $criticalCSS = <<<CSS
    <style>
        body{margin:0;font-family:-apple-system,BlinkMacSystemFont,sans-serif}
        .login-page{display:flex;align-items:center;justify-content:center;min-height:100vh}
        /* 最小限のスタイルのみ */
    </style>
CSS;

    // headタグ内に挿入
}
```

### 7.3 遅延読み込み

```php
// 非クリティカルなCSSを遅延読み込み
<link rel="preload" href="/css/main.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="/css/main.css"></noscript>
```

---

## 8. デプロイチェックリスト

実装完了後、本番環境へデプロイする前に以下を確認:

- [ ] 全ページで視覚的なバグがない
- [ ] レスポンシブデザインが正しく動作
- [ ] 全てのリンクが正しく機能
- [ ] フォームのバリデーションが動作
- [ ] CSSファイルが圧縮されている
- [ ] ブラウザ互換性テスト完了
- [ ] アクセシビリティスコアが80以上
- [ ] ページ読み込み速度が3秒以内

---

## 9. 次のステップ

CSS実装完了後の追加機能:

1. **JavaScriptの追加**
   - モーダルの開閉
   - アラートの自動消去
   - フォームのリアルタイムバリデーション

2. **アニメーション強化**
   - ページ遷移アニメーション
   - ローディングアニメーション
   - ホバーエフェクト

3. **ダークモード対応**
   - ダークモードの実装
   - ユーザー設定の保存

4. **国際化（i18n）**
   - 多言語対応
   - 日本語/英語の切り替え

---

## まとめ

この実装ガイドに従うことで、orka-asp2システムの完全なUI実装が可能です。

**重要なポイント:**
- 段階的に実装（一度に全部やらない）
- 各段階でテストを実施
- 問題が発生したらトラブルシューティングを参照
- パフォーマンスを常に意識

次は、実際のmain.cssファイルを生成し、実装を開始します。
