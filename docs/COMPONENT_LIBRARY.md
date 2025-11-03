# コンポーネントライブラリ - orka-asp2

**バージョン:** 1.0.0
**作成日:** 2025-11-03

このドキュメントでは、orka-asp2で使用する全てのUIコンポーネントのHTML実装例とCSS使用方法を示します。

---

## 目次

1. [ボタン](#1-ボタン)
2. [フォーム](#2-フォーム)
3. [テーブル](#3-テーブル)
4. [カード](#4-カード)
5. [ナビゲーション](#5-ナビゲーション)
6. [アラート・通知](#6-アラート通知)
7. [モーダル](#7-モーダル)
8. [AFAD専用コンポーネント](#8-afad専用コンポーネント)

---

## 1. ボタン

### 1.1 基本ボタン

```html
<!-- Primary Button -->
<button class="btn btn-primary">保存</button>

<!-- Secondary Button -->
<button class="btn btn-secondary">キャンセル</button>

<!-- Success Button -->
<button class="btn btn-success">承認</button>

<!-- Danger Button -->
<button class="btn btn-danger">削除</button>

<!-- Outline Button -->
<button class="btn btn-outline">編集</button>

<!-- Ghost Button -->
<button class="btn btn-ghost">詳細</button>
```

### 1.2 ボタンサイズ

```html
<!-- Small Button -->
<button class="btn btn-primary btn-sm">小</button>

<!-- Medium Button (デフォルト) -->
<button class="btn btn-primary btn-md">中</button>

<!-- Large Button -->
<button class="btn btn-primary btn-lg">大</button>

<!-- Block Button (全幅) -->
<button class="btn btn-primary btn-block">ログイン</button>
```

### 1.3 ボタン状態

```html
<!-- Disabled Button -->
<button class="btn btn-primary" disabled>無効</button>

<!-- Loading Button -->
<button class="btn btn-primary">
  <span class="spinner spinner-sm"></span>
  処理中...
</button>
```

### 1.4 PHPでの実装例

```php
// System.phpまたはDraw.phpに追加
class Draw {
    public static function button($text, $type = 'primary', $size = 'md', $attributes = []) {
        $class = "btn btn-{$type} btn-{$size}";
        $attr_str = '';

        foreach ($attributes as $key => $value) {
            $attr_str .= " {$key}=\"{$value}\"";
        }

        return "<button class=\"{$class}\"{$attr_str}>{$text}</button>";
    }
}

// 使用例
echo Draw::button('保存', 'primary', 'md', ['type' => 'submit']);
echo Draw::button('削除', 'danger', 'sm', ['onclick' => 'confirmDelete()']);
```

---

## 2. フォーム

### 2.1 基本的なフォーム

```html
<form class="form">
  <!-- Text Input -->
  <div class="form-group">
    <label class="form-label form-label-required" for="username">ユーザー名</label>
    <input type="text" id="username" class="form-input" placeholder="ユーザー名を入力" required>
    <span class="form-help">4文字以上で入力してください</span>
  </div>

  <!-- Password Input -->
  <div class="form-group">
    <label class="form-label form-label-required" for="password">パスワード</label>
    <input type="password" id="password" class="form-input" placeholder="パスワードを入力" required>
  </div>

  <!-- Select -->
  <div class="form-group">
    <label class="form-label" for="category">カテゴリ</label>
    <select id="category" class="form-select">
      <option value="">選択してください</option>
      <option value="1">カテゴリ1</option>
      <option value="2">カテゴリ2</option>
    </select>
  </div>

  <!-- Textarea -->
  <div class="form-group">
    <label class="form-label" for="description">説明</label>
    <textarea id="description" class="form-textarea" placeholder="説明を入力"></textarea>
  </div>

  <!-- Checkbox -->
  <div class="form-check">
    <input type="checkbox" id="agree" class="form-check-input">
    <label class="form-check-label" for="agree">利用規約に同意する</label>
  </div>

  <!-- Radio Buttons -->
  <div class="form-group">
    <label class="form-label">性別</label>
    <div class="form-check">
      <input type="radio" id="male" name="gender" class="form-check-input" value="male">
      <label class="form-check-label" for="male">男性</label>
    </div>
    <div class="form-check">
      <input type="radio" id="female" name="gender" class="form-check-input" value="female">
      <label class="form-check-label" for="female">女性</label>
    </div>
  </div>

  <!-- Submit Button -->
  <button type="submit" class="btn btn-primary btn-block">送信</button>
</form>
```

### 2.2 バリデーション状態

```html
<!-- Valid Input -->
<div class="form-group">
  <label class="form-label" for="email">メールアドレス</label>
  <input type="email" id="email" class="form-input is-valid" value="user@example.com">
  <span class="form-help" style="color: var(--color-success);">有効なメールアドレスです</span>
</div>

<!-- Invalid Input -->
<div class="form-group">
  <label class="form-label" for="phone">電話番号</label>
  <input type="tel" id="phone" class="form-input is-invalid" value="123">
  <span class="form-error">正しい電話番号を入力してください</span>
</div>
```

### 2.3 インプットグループ

```html
<!-- Prepend -->
<div class="form-group">
  <label class="form-label" for="price">価格</label>
  <div class="input-group">
    <span class="input-group-prepend">¥</span>
    <input type="number" id="price" class="form-input" placeholder="0">
  </div>
</div>

<!-- Append -->
<div class="form-group">
  <label class="form-label" for="commission">手数料率</label>
  <div class="input-group">
    <input type="number" id="commission" class="form-input" placeholder="0">
    <span class="input-group-append">%</span>
  </div>
</div>

<!-- Both -->
<div class="form-group">
  <label class="form-label" for="amount">金額</label>
  <div class="input-group">
    <span class="input-group-prepend">$</span>
    <input type="number" id="amount" class="form-input" placeholder="0.00">
    <span class="input-group-append">USD</span>
  </div>
</div>
```

### 2.4 PHPでの実装例

```php
class Draw {
    public static function formInput($name, $label, $type = 'text', $options = []) {
        $required = $options['required'] ?? false;
        $value = $options['value'] ?? '';
        $placeholder = $options['placeholder'] ?? '';
        $help = $options['help'] ?? '';
        $error = $options['error'] ?? '';

        $labelClass = 'form-label' . ($required ? ' form-label-required' : '');
        $inputClass = 'form-input' . ($error ? ' is-invalid' : '');

        $html = '<div class="form-group">';
        $html .= "<label class=\"{$labelClass}\" for=\"{$name}\">{$label}</label>";
        $html .= "<input type=\"{$type}\" id=\"{$name}\" name=\"{$name}\" class=\"{$inputClass}\" value=\"{$value}\" placeholder=\"{$placeholder}\"";
        if ($required) $html .= ' required';
        $html .= '>';

        if ($help) {
            $html .= "<span class=\"form-help\">{$help}</span>";
        }

        if ($error) {
            $html .= "<span class=\"form-error\">{$error}</span>";
        }

        $html .= '</div>';

        return $html;
    }

    public static function formSelect($name, $label, $options, $selected = '', $required = false) {
        $labelClass = 'form-label' . ($required ? ' form-label-required' : '');

        $html = '<div class="form-group">';
        $html .= "<label class=\"{$labelClass}\" for=\"{$name}\">{$label}</label>";
        $html .= "<select id=\"{$name}\" name=\"{$name}\" class=\"form-select\"";
        if ($required) $html .= ' required';
        $html .= '>';

        foreach ($options as $value => $text) {
            $selectedAttr = ($value == $selected) ? ' selected' : '';
            $html .= "<option value=\"{$value}\"{$selectedAttr}>{$text}</option>";
        }

        $html .= '</select>';
        $html .= '</div>';

        return $html;
    }
}

// 使用例
echo Draw::formInput('username', 'ユーザー名', 'text', [
    'required' => true,
    'placeholder' => 'ユーザー名を入力',
    'help' => '4文字以上で入力してください'
]);

echo Draw::formSelect('category', 'カテゴリ', [
    '' => '選択してください',
    '1' => 'カテゴリ1',
    '2' => 'カテゴリ2'
], '', true);
```

---

## 3. テーブル

### 3.1 基本テーブル

```html
<div class="table-wrapper">
  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>名前</th>
        <th>メール</th>
        <th>登録日</th>
        <th class="text-right">操作</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>1</td>
        <td>山田太郎</td>
        <td>yamada@example.com</td>
        <td>2025-11-01</td>
        <td class="text-right">
          <button class="btn btn-sm btn-outline">編集</button>
          <button class="btn btn-sm btn-danger">削除</button>
        </td>
      </tr>
      <tr>
        <td>2</td>
        <td>佐藤花子</td>
        <td>sato@example.com</td>
        <td>2025-11-02</td>
        <td class="text-right">
          <button class="btn btn-sm btn-outline">編集</button>
          <button class="btn btn-sm btn-danger">削除</button>
        </td>
      </tr>
    </tbody>
  </table>
</div>
```

### 3.2 ストライプテーブル

```html
<div class="table-wrapper">
  <table class="table table-striped">
    <thead>
      <tr>
        <th>項目</th>
        <th class="text-right">値</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>総コンバージョン数</td>
        <td class="text-right">1,234</td>
      </tr>
      <tr>
        <td>成功率</td>
        <td class="text-right">98.5%</td>
      </tr>
    </tbody>
  </table>
</div>
```

### 3.3 PHPでの実装例

```php
class Draw {
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
            $html .= "<th class=\"text-{$align}\">{$header['label']}</th>";
        }
        $html .= '</tr></thead>';

        // Body
        $html .= '<tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $i => $cell) {
                $align = $headers[$i]['align'] ?? 'left';
                $html .= "<td class=\"text-{$align}\">{$cell}</td>";
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';

        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }
}

// 使用例
$headers = [
    ['label' => 'ID', 'align' => 'left'],
    ['label' => '名前', 'align' => 'left'],
    ['label' => '金額', 'align' => 'right'],
];

$rows = [
    [1, '山田太郎', '¥10,000'],
    [2, '佐藤花子', '¥20,000'],
];

echo Draw::table($headers, $rows, ['striped' => true]);
```

---

## 4. カード

### 4.1 基本カード

```html
<div class="card">
  <div class="card-header">
    <h3 class="card-title">カードタイトル</h3>
  </div>
  <div class="card-body">
    <p>カードの内容がここに入ります。</p>
  </div>
  <div class="card-footer">
    <button class="btn btn-primary">アクション</button>
  </div>
</div>
```

### 4.2 統計カード

```html
<div class="stat-card">
  <div class="stat-label">総コンバージョン数</div>
  <div class="stat-value">1,234</div>
  <div class="stat-change positive">↑ 12.5% (前月比)</div>
</div>
```

### 4.3 PHPでの実装例

```php
class Draw {
    public static function card($title, $content, $footer = '', $options = []) {
        $html = '<div class="card">';

        if ($title) {
            $html .= '<div class="card-header">';
            $html .= "<h3 class=\"card-title\">{$title}</h3>";
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

    public static function statCard($label, $value, $change = '', $changeType = '') {
        $html = '<div class="stat-card">';
        $html .= "<div class=\"stat-label\">{$label}</div>";
        $html .= "<div class=\"stat-value\">{$value}</div>";

        if ($change) {
            $html .= "<div class=\"stat-change {$changeType}\">{$change}</div>";
        }

        $html .= '</div>';

        return $html;
    }
}

// 使用例
echo Draw::card(
    'お知らせ',
    '<p>新しいアップデートがあります。</p>',
    '<button class="btn btn-primary">詳細を見る</button>'
);

echo Draw::statCard('総CV数', '1,234', '↑ 12.5%', 'positive');
```

---

## 5. ナビゲーション

### 5.1 ヘッダーナビゲーション

```html
<nav class="navbar">
  <a href="/" class="navbar-brand">ORKA-ASP2</a>
  <ul class="navbar-menu">
    <li><a href="/dashboard" class="navbar-link is-active">ダッシュボード</a></li>
    <li><a href="/reports" class="navbar-link">レポート</a></li>
    <li><a href="/settings" class="navbar-link">設定</a></li>
    <li><a href="/logout" class="navbar-link">ログアウト</a></li>
  </ul>
</nav>
```

### 5.2 サイドバーナビゲーション

```html
<nav class="sidebar-nav">
  <div class="sidebar-nav-group-title">メニュー</div>
  <ul>
    <li class="sidebar-nav-item">
      <a href="/dashboard" class="sidebar-nav-link is-active">
        <span class="sidebar-nav-icon">📊</span>
        ダッシュボード
      </a>
    </li>
    <li class="sidebar-nav-item">
      <a href="/reports" class="sidebar-nav-link">
        <span class="sidebar-nav-icon">📈</span>
        レポート
      </a>
    </li>
  </ul>

  <div class="sidebar-nav-group-title">AFAD管理</div>
  <ul>
    <li class="sidebar-nav-item">
      <a href="/afad/config" class="sidebar-nav-link">
        <span class="sidebar-nav-icon">⚙️</span>
        AFAD設定
      </a>
    </li>
    <li class="sidebar-nav-item">
      <a href="/afad/logs" class="sidebar-nav-link">
        <span class="sidebar-nav-icon">📋</span>
        ログ
      </a>
    </li>
  </ul>
</nav>
```

---

## 6. アラート・通知

### 6.1 基本アラート

```html
<!-- Success Alert -->
<div class="alert alert-success">
  <strong class="alert-title">成功！</strong>
  データが正常に保存されました。
  <button class="alert-close">×</button>
</div>

<!-- Warning Alert -->
<div class="alert alert-warning">
  <strong class="alert-title">警告</strong>
  この操作は元に戻せません。
</div>

<!-- Error Alert -->
<div class="alert alert-error">
  <strong class="alert-title">エラー</strong>
  入力内容に誤りがあります。
</div>

<!-- Info Alert -->
<div class="alert alert-info">
  <strong class="alert-title">お知らせ</strong>
  メンテナンスのお知らせです。
</div>
```

### 6.2 PHPでの実装例

```php
class Draw {
    public static function alert($message, $type = 'info', $title = '', $dismissible = false) {
        $html = "<div class=\"alert alert-{$type}\">";

        if ($title) {
            $html .= "<strong class=\"alert-title\">{$title}</strong>";
        }

        $html .= $message;

        if ($dismissible) {
            $html .= '<button class="alert-close" onclick="this.parentElement.remove()">×</button>';
        }

        $html .= '</div>';

        return $html;
    }
}

// 使用例
echo Draw::alert('保存が完了しました', 'success', '成功！', true);
echo Draw::alert('入力内容を確認してください', 'error', 'エラー');
```

---

## 7. モーダル

### 7.1 基本モーダル

```html
<div class="modal-backdrop" id="exampleModal" style="display: none;">
  <div class="modal">
    <div class="modal-header">
      <h2 class="modal-title">モーダルタイトル</h2>
      <button class="modal-close" onclick="closeModal('exampleModal')">×</button>
    </div>
    <div class="modal-body">
      <p>モーダルの内容がここに入ります。</p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('exampleModal')">キャンセル</button>
      <button class="btn btn-primary">確認</button>
    </div>
  </div>
</div>

<script>
function openModal(id) {
  document.getElementById(id).style.display = 'flex';
}

function closeModal(id) {
  document.getElementById(id).style.display = 'none';
}

// Backdrop クリックで閉じる
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-backdrop')) {
    closeModal(e.target.id);
  }
});
</script>

<!-- モーダルを開くボタン -->
<button class="btn btn-primary" onclick="openModal('exampleModal')">モーダルを開く</button>
```

---

## 8. AFAD専用コンポーネント

### 8.1 AFADステータスバッジ

```html
<!-- 保留中 -->
<span class="afad-status afad-status-pending">Pending</span>

<!-- 送信成功 -->
<span class="afad-status afad-status-sent">Sent</span>

<!-- 送信失敗 -->
<span class="afad-status afad-status-failed">Failed</span>

<!-- リトライ中 -->
<span class="afad-status afad-status-retry">Retry</span>
```

### 8.2 AFAD統計カード

```html
<div class="afad-stats-card">
  <div class="afad-stats-header">
    <h3 class="afad-stats-title">AFAD統計</h3>
    <span class="afad-stats-period">過去30日間</span>
  </div>
  <div class="afad-stats-grid">
    <div class="afad-stat-item">
      <div class="afad-stat-label">送信成功</div>
      <div class="afad-stat-value">1,234</div>
      <div class="afad-stat-rate afad-success-rate">98.5%</div>
    </div>
    <div class="afad-stat-item">
      <div class="afad-stat-label">送信失敗</div>
      <div class="afad-stat-value">19</div>
      <div class="afad-stat-rate afad-failure-rate">1.5%</div>
    </div>
    <div class="afad-stat-item">
      <div class="afad-stat-label">リトライ中</div>
      <div class="afad-stat-value">3</div>
    </div>
  </div>
</div>
```

### 8.3 AFADリトライキュー

```html
<div class="afad-retry-queue">
  <div class="afad-retry-header">
    <span class="afad-retry-title">リトライキュー</span>
    <span class="afad-retry-count">3</span>
  </div>

  <div class="afad-retry-item">
    <div class="afad-retry-info">
      <div class="afad-retry-id">Session ID: abc123def456</div>
      <div class="afad-retry-attempts">試行回数: 2/5</div>
    </div>
    <div class="afad-retry-next">次回: 5分後</div>
    <div class="afad-retry-actions">
      <button class="btn btn-sm btn-outline">今すぐ送信</button>
      <button class="btn btn-sm btn-danger">スキップ</button>
    </div>
  </div>

  <div class="afad-retry-item">
    <div class="afad-retry-info">
      <div class="afad-retry-id">Session ID: xyz789ghi012</div>
      <div class="afad-retry-attempts">試行回数: 1/5</div>
    </div>
    <div class="afad-retry-next">次回: 1分後</div>
    <div class="afad-retry-actions">
      <button class="btn btn-sm btn-outline">今すぐ送信</button>
      <button class="btn btn-sm btn-danger">スキップ</button>
    </div>
  </div>
</div>
```

### 8.4 AFADログビューア

```html
<div class="afad-log-viewer">
  <div class="afad-log-filters">
    <select class="form-select" style="width: auto;">
      <option>全てのレベル</option>
      <option>エラーのみ</option>
      <option>警告のみ</option>
    </select>
    <input type="date" class="form-input" style="width: auto;">
    <button class="btn btn-sm btn-primary">フィルター</button>
  </div>

  <div class="afad-log-item">
    <span class="afad-log-timestamp">2025-11-03 10:23:45</span>
    <span class="afad-log-level afad-log-level-success">SUCCESS</span>
    <span class="afad-log-message">Postback sent successfully to https://afad.example.com</span>
    <div class="afad-log-details">
      HTTP 200 OK | Response time: 234ms | Session ID: abc123def456
    </div>
  </div>

  <div class="afad-log-item">
    <span class="afad-log-timestamp">2025-11-03 10:22:30</span>
    <span class="afad-log-level afad-log-level-error">ERROR</span>
    <span class="afad-log-message">Postback failed: Connection timeout</span>
    <div class="afad-log-details">
      HTTP 408 Timeout | Session ID: xyz789ghi012 | Added to retry queue
    </div>
  </div>
</div>
```

### 8.5 PHPでの実装例

```php
class AFADDraw {
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
        return "<span class=\"afad-status afad-status-{$status}\">{$text}</span>";
    }

    public static function statsCard($stats) {
        $html = '<div class="afad-stats-card">';
        $html .= '<div class="afad-stats-header">';
        $html .= '<h3 class="afad-stats-title">AFAD統計</h3>';
        $html .= '<span class="afad-stats-period">' . $stats['period'] . '</span>';
        $html .= '</div>';

        $html .= '<div class="afad-stats-grid">';

        foreach ($stats['items'] as $item) {
            $html .= '<div class="afad-stat-item">';
            $html .= '<div class="afad-stat-label">' . $item['label'] . '</div>';
            $html .= '<div class="afad-stat-value">' . $item['value'] . '</div>';
            if (isset($item['rate'])) {
                $rateClass = $item['rate_type'] === 'success' ? 'afad-success-rate' : 'afad-failure-rate';
                $html .= '<div class="afad-stat-rate ' . $rateClass . '">' . $item['rate'] . '</div>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public static function logItem($log) {
        $levelClass = "afad-log-level-{$log['level']}";

        $html = '<div class="afad-log-item">';
        $html .= '<span class="afad-log-timestamp">' . $log['timestamp'] . '</span>';
        $html .= '<span class="afad-log-level ' . $levelClass . '">' . strtoupper($log['level']) . '</span>';
        $html .= '<span class="afad-log-message">' . htmlspecialchars($log['message']) . '</span>';

        if (isset($log['details'])) {
            $html .= '<div class="afad-log-details">' . htmlspecialchars($log['details']) . '</div>';
        }

        $html .= '</div>';

        return $html;
    }
}

// 使用例
echo AFADDraw::statusBadge('sent');

echo AFADDraw::statsCard([
    'period' => '過去30日間',
    'items' => [
        [
            'label' => '送信成功',
            'value' => '1,234',
            'rate' => '98.5%',
            'rate_type' => 'success'
        ],
        [
            'label' => '送信失敗',
            'value' => '19',
            'rate' => '1.5%',
            'rate_type' => 'failure'
        ],
    ]
]);

echo AFADDraw::logItem([
    'timestamp' => '2025-11-03 10:23:45',
    'level' => 'success',
    'message' => 'Postback sent successfully',
    'details' => 'HTTP 200 OK | Response time: 234ms'
]);
```

---

## 9. ページレイアウト例

### 9.1 ログインページ

```php
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン - ORKA-ASP2</title>
    <link rel="stylesheet" href="/css/main.css">
</head>
<body>
    <div class="login-page">
        <div class="login-card">
            <div class="login-logo">
                <h1>ORKA-ASP2</h1>
            </div>
            <h2 class="login-title">ログイン</h2>

            <?php if (isset($error)): ?>
                <div class="alert alert-error login-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/login.php" class="login-form">
                <div class="form-group">
                    <label class="form-label form-label-required" for="username">ユーザー名</label>
                    <input type="text" id="username" name="username" class="form-input" required>
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
                <a href="/password-reset.php">パスワードを忘れた方</a>
            </div>
        </div>
    </div>
</body>
</html>
```

### 9.2 ダッシュボードページ

```php
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ダッシュボード - ORKA-ASP2</title>
    <link rel="stylesheet" href="/css/main.css">
</head>
<body>
    <div class="app-layout">
        <header class="app-header">
            <nav class="navbar">
                <a href="/" class="navbar-brand">ORKA-ASP2</a>
                <ul class="navbar-menu">
                    <li><a href="/dashboard" class="navbar-link is-active">ダッシュボード</a></li>
                    <li><a href="/reports" class="navbar-link">レポート</a></li>
                    <li><a href="/settings" class="navbar-link">設定</a></li>
                    <li><a href="/logout" class="navbar-link">ログアウト</a></li>
                </ul>
            </nav>
        </header>

        <aside class="app-sidebar">
            <nav class="sidebar-nav">
                <div class="sidebar-nav-group-title">メニュー</div>
                <ul>
                    <li class="sidebar-nav-item">
                        <a href="/dashboard" class="sidebar-nav-link is-active">
                            <span class="sidebar-nav-icon">📊</span>
                            ダッシュボード
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="/reports" class="sidebar-nav-link">
                            <span class="sidebar-nav-icon">📈</span>
                            レポート
                        </a>
                    </li>
                </ul>

                <div class="sidebar-nav-group-title">AFAD管理</div>
                <ul>
                    <li class="sidebar-nav-item">
                        <a href="/afad/config" class="sidebar-nav-link">
                            <span class="sidebar-nav-icon">⚙️</span>
                            AFAD設定
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="/afad/logs" class="sidebar-nav-link">
                            <span class="sidebar-nav-icon">📋</span>
                            ログ
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="app-main">
            <div class="page-header">
                <h1 class="page-title">ダッシュボード</h1>
                <p class="page-subtitle">システム全体の概要</p>
            </div>

            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-label">総コンバージョン数</div>
                    <div class="stat-value">1,234</div>
                    <div class="stat-change positive">↑ 12.5% (前月比)</div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">今日のCV</div>
                    <div class="stat-value">45</div>
                    <div class="stat-change positive">↑ 8.3%</div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">成功率</div>
                    <div class="stat-value">98.5%</div>
                    <div class="stat-change positive">↑ 0.5%</div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">リトライ中</div>
                    <div class="stat-value">3</div>
                    <div class="stat-change">変動なし</div>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">最近の活動</h3>
                    </div>
                    <div class="card-body">
                        <p>最近の活動がここに表示されます</p>
                    </div>
                </div>

                <div class="afad-stats-card">
                    <div class="afad-stats-header">
                        <h3 class="afad-stats-title">AFAD統計</h3>
                        <span class="afad-stats-period">過去30日間</span>
                    </div>
                    <div class="afad-stats-grid">
                        <div class="afad-stat-item">
                            <div class="afad-stat-label">送信成功</div>
                            <div class="afad-stat-value">1,234</div>
                            <div class="afad-stat-rate afad-success-rate">98.5%</div>
                        </div>
                        <div class="afad-stat-item">
                            <div class="afad-stat-label">送信失敗</div>
                            <div class="afad-stat-value">19</div>
                            <div class="afad-stat-rate afad-failure-rate">1.5%</div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer class="app-footer">
            <p>&copy; 2025 ORKA-ASP2. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
```

---

## まとめ

このコンポーネントライブラリを使用することで、orka-asp2システムの全てのUIを統一的に実装できます。

**重要なポイント:**
1. すべてのコンポーネントはレスポンシブ対応
2. アクセシビリティを考慮した設計
3. PHPでの実装例を提供
4. AFAD固有のコンポーネントも完備
5. モダンなデザインパターンを採用

次のステップとして、実際のCSSファイル（main.css）の実装に進むことができます。
