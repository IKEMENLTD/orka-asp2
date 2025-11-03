# CSS設計書 - orka-asp2アフィリエイトシステム

**バージョン:** 1.0.0
**作成日:** 2025-11-03
**対象システム:** orka-asp2 (AFAD連携アフィリエイト管理システム)

---

## 目次

1. [概要](#1-概要)
2. [デザインシステム基盤](#2-デザインシステム基盤)
3. [レイアウトシステム](#3-レイアウトシステム)
4. [コンポーネントライブラリ](#4-コンポーネントライブラリ)
5. [ページ別デザイン仕様](#5-ページ別デザイン仕様)
6. [AFAD固有のUI要素](#6-afad固有のui要素)
7. [レスポンシブデザイン](#7-レスポンシブデザイン)
8. [実装ガイドライン](#8-実装ガイドライン)

---

## 1. 概要

### 1.1 システム構成

orka-asp2は以下の機能を持つアフィリエイト管理システムです：

**主要機能（19ページ）:**
- **認証・ユーザー管理**: login.php, regist.php, unlock.php, reminder.php
- **ダッシュボード**: index.php
- **AFAD連携**: link.php（クリック追跡）, add.php（CV追跡）, continue.php（継続課金）
- **レポート**: report.php, report_api.php, search.php
- **管理ツール**: tool.php（DB操作）, page.php, info.php
- **課金・返金**: return.php, quick.php, other.php
- **メール**: multimail_send.php

**データベーステーブル:**
- AFAD関連: afad_configs, afad_postback_logs, afad_retry_queue, afad_statistics
- ユーザー: admin, nuser, tier
- 広告: adwares, category, area, prefectures
- 課金: pay, click_pay, continue_pay, sales
- その他: blacklist, invitation, template, access

### 1.2 デザイン目標

1. **直感的な操作性** - アフィリエイト業務に必要な情報へ素早くアクセス
2. **データの可視性** - 統計、ログ、レポートを分かりやすく表示
3. **プロフェッショナル** - 信頼感のあるビジネスツールの外観
4. **レスポンシブ** - デスクトップ、タブレット、スマートフォン対応
5. **高速表示** - 最小限のCSS、最適化されたレンダリング

### 1.3 技術要件

- **CSS3** - モダンブラウザ対応（Flexbox, Grid, Custom Properties）
- **プレーンCSS** - フレームワーク不使用（軽量化のため）
- **プログレッシブエンハンスメント** - 古いブラウザでも基本機能が動作
- **アクセシビリティ** - WCAG 2.1 AA準拠

---

## 2. デザインシステム基盤

### 2.1 カラーパレット

#### プライマリーカラー（メインブランドカラー）

```css
:root {
  /* Primary - 青系（信頼感、プロフェッショナル） */
  --color-primary-50:  #E3F2FD;
  --color-primary-100: #BBDEFB;
  --color-primary-200: #90CAF9;
  --color-primary-300: #64B5F6;
  --color-primary-400: #42A5F5;
  --color-primary-500: #2196F3; /* メインカラー */
  --color-primary-600: #1E88E5;
  --color-primary-700: #1976D2;
  --color-primary-800: #1565C0;
  --color-primary-900: #0D47A1;
}
```

#### セカンダリーカラー（アクセントカラー）

```css
:root {
  /* Secondary - 緑系（成功、ポジティブ） */
  --color-secondary-50:  #E8F5E9;
  --color-secondary-100: #C8E6C9;
  --color-secondary-200: #A5D6A7;
  --color-secondary-300: #81C784;
  --color-secondary-400: #66BB6A;
  --color-secondary-500: #4CAF50; /* メインカラー */
  --color-secondary-600: #43A047;
  --color-secondary-700: #388E3C;
  --color-secondary-800: #2E7D32;
  --color-secondary-900: #1B5E20;
}
```

#### セマンティックカラー（意味を持つ色）

```css
:root {
  /* Success - 成功（緑） */
  --color-success-light: #D4EDDA;
  --color-success:       #28A745;
  --color-success-dark:  #155724;

  /* Warning - 警告（黄） */
  --color-warning-light: #FFF3CD;
  --color-warning:       #FFC107;
  --color-warning-dark:  #856404;

  /* Error - エラー（赤） */
  --color-error-light: #F8D7DA;
  --color-error:       #DC3545;
  --color-error-dark:  #721C24;

  /* Info - 情報（青） */
  --color-info-light: #D1ECF1;
  --color-info:       #17A2B8;
  --color-info-dark:  #0C5460;
}
```

#### ニュートラルカラー（グレースケール）

```css
:root {
  /* Neutral - グレー系 */
  --color-white:    #FFFFFF;
  --color-gray-50:  #FAFAFA;
  --color-gray-100: #F5F5F5;
  --color-gray-200: #EEEEEE;
  --color-gray-300: #E0E0E0;
  --color-gray-400: #BDBDBD;
  --color-gray-500: #9E9E9E;
  --color-gray-600: #757575;
  --color-gray-700: #616161;
  --color-gray-800: #424242;
  --color-gray-900: #212121;
  --color-black:    #000000;
}
```

#### AFADステータスカラー（AFAD固有）

```css
:root {
  /* AFAD Postback Status */
  --color-afad-pending:    #FFC107; /* 保留中 - 黄色 */
  --color-afad-sent:       #4CAF50; /* 送信成功 - 緑 */
  --color-afad-failed:     #DC3545; /* 送信失敗 - 赤 */
  --color-afad-retry:      #FF9800; /* リトライ中 - オレンジ */
  --color-afad-timeout:    #9C27B0; /* タイムアウト - 紫 */
  --color-afad-skip:       #9E9E9E; /* スキップ - グレー */
}
```

### 2.2 タイポグラフィ

#### フォントファミリー

```css
:root {
  /* 日本語 + 欧文の最適なフォントスタック */
  --font-family-base:
    -apple-system, BlinkMacSystemFont,
    "Segoe UI", "Hiragino Sans", "Hiragino Kaku Gothic ProN",
    "Noto Sans JP", Meiryo, sans-serif;

  /* コード・数字用（等幅フォント） */
  --font-family-mono:
    "SF Mono", "Monaco", "Inconsolata", "Roboto Mono",
    "Consolas", "Courier New", monospace;
}
```

#### フォントサイズ

```css
:root {
  /* Type Scale - 1.25倍（Major Third） */
  --font-size-xs:   0.75rem;  /* 12px */
  --font-size-sm:   0.875rem; /* 14px */
  --font-size-base: 1rem;     /* 16px - ベース */
  --font-size-lg:   1.125rem; /* 18px */
  --font-size-xl:   1.25rem;  /* 20px */
  --font-size-2xl:  1.5rem;   /* 24px */
  --font-size-3xl:  1.875rem; /* 30px */
  --font-size-4xl:  2.25rem;  /* 36px */
  --font-size-5xl:  3rem;     /* 48px */
}
```

#### フォントウェイト

```css
:root {
  --font-weight-light:   300;
  --font-weight-normal:  400;
  --font-weight-medium:  500;
  --font-weight-semibold: 600;
  --font-weight-bold:    700;
}
```

#### ラインハイト

```css
:root {
  --line-height-tight:  1.25;  /* 見出し用 */
  --line-height-normal: 1.5;   /* 本文用 */
  --line-height-relaxed: 1.75; /* 読みやすさ重視 */
}
```

### 2.3 スペーシング

```css
:root {
  /* 8pxベースのスケール */
  --spacing-0:  0;
  --spacing-1:  0.25rem;  /* 4px */
  --spacing-2:  0.5rem;   /* 8px */
  --spacing-3:  0.75rem;  /* 12px */
  --spacing-4:  1rem;     /* 16px */
  --spacing-5:  1.25rem;  /* 20px */
  --spacing-6:  1.5rem;   /* 24px */
  --spacing-8:  2rem;     /* 32px */
  --spacing-10: 2.5rem;   /* 40px */
  --spacing-12: 3rem;     /* 48px */
  --spacing-16: 4rem;     /* 64px */
  --spacing-20: 5rem;     /* 80px */
  --spacing-24: 6rem;     /* 96px */
}
```

### 2.4 ボーダー・角丸

```css
:root {
  /* Border Radius */
  --radius-none: 0;
  --radius-sm:   0.125rem; /* 2px */
  --radius-base: 0.25rem;  /* 4px */
  --radius-md:   0.375rem; /* 6px */
  --radius-lg:   0.5rem;   /* 8px */
  --radius-xl:   0.75rem;  /* 12px */
  --radius-2xl:  1rem;     /* 16px */
  --radius-full: 9999px;   /* 完全な円 */

  /* Border Width */
  --border-width-0: 0;
  --border-width-1: 1px;
  --border-width-2: 2px;
  --border-width-4: 4px;

  /* Border Color */
  --border-color: var(--color-gray-300);
  --border-color-dark: var(--color-gray-400);
}
```

### 2.5 シャドウ

```css
:root {
  /* Elevation Shadows */
  --shadow-sm:  0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow-base: 0 1px 3px 0 rgba(0, 0, 0, 0.1),
                 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  --shadow-md:  0 4px 6px -1px rgba(0, 0, 0, 0.1),
                0 2px 4px -1px rgba(0, 0, 0, 0.06);
  --shadow-lg:  0 10px 15px -3px rgba(0, 0, 0, 0.1),
                0 4px 6px -2px rgba(0, 0, 0, 0.05);
  --shadow-xl:  0 20px 25px -5px rgba(0, 0, 0, 0.1),
                0 10px 10px -5px rgba(0, 0, 0, 0.04);
  --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);

  /* Focus Ring */
  --shadow-focus: 0 0 0 3px rgba(33, 150, 243, 0.3);
}
```

### 2.6 アニメーション

```css
:root {
  /* Duration */
  --duration-fast: 150ms;
  --duration-base: 250ms;
  --duration-slow: 350ms;

  /* Easing */
  --ease-in:     cubic-bezier(0.4, 0, 1, 1);
  --ease-out:    cubic-bezier(0, 0, 0.2, 1);
  --ease-in-out: cubic-bezier(0.4, 0, 0.2, 1);
}
```

### 2.7 Z-Index階層

```css
:root {
  --z-index-dropdown:  1000;
  --z-index-sticky:    1020;
  --z-index-fixed:     1030;
  --z-index-backdrop:  1040;
  --z-index-modal:     1050;
  --z-index-popover:   1060;
  --z-index-tooltip:   1070;
  --z-index-toast:     1080;
}
```

---

## 3. レイアウトシステム

### 3.1 グリッドシステム

#### コンテナ

```css
.container {
  width: 100%;
  max-width: 1200px;
  margin-left: auto;
  margin-right: auto;
  padding-left: var(--spacing-4);
  padding-right: var(--spacing-4);
}

.container-fluid {
  width: 100%;
  padding-left: var(--spacing-4);
  padding-right: var(--spacing-4);
}

.container-narrow {
  max-width: 960px;
}

.container-wide {
  max-width: 1400px;
}
```

#### グリッド（CSS Grid）

```css
.grid {
  display: grid;
  gap: var(--spacing-4);
}

.grid-cols-1  { grid-template-columns: repeat(1, 1fr); }
.grid-cols-2  { grid-template-columns: repeat(2, 1fr); }
.grid-cols-3  { grid-template-columns: repeat(3, 1fr); }
.grid-cols-4  { grid-template-columns: repeat(4, 1fr); }
.grid-cols-6  { grid-template-columns: repeat(6, 1fr); }
.grid-cols-12 { grid-template-columns: repeat(12, 1fr); }

.col-span-1  { grid-column: span 1; }
.col-span-2  { grid-column: span 2; }
.col-span-3  { grid-column: span 3; }
.col-span-4  { grid-column: span 4; }
.col-span-6  { grid-column: span 6; }
.col-span-12 { grid-column: span 12; }
```

#### フレックスボックス

```css
.flex {
  display: flex;
}

.flex-row     { flex-direction: row; }
.flex-col     { flex-direction: column; }
.flex-wrap    { flex-wrap: wrap; }
.flex-nowrap  { flex-wrap: nowrap; }

.items-start   { align-items: flex-start; }
.items-center  { align-items: center; }
.items-end     { align-items: flex-end; }
.items-stretch { align-items: stretch; }

.justify-start   { justify-content: flex-start; }
.justify-center  { justify-content: center; }
.justify-end     { justify-content: flex-end; }
.justify-between { justify-content: space-between; }
.justify-around  { justify-content: space-around; }

.gap-1 { gap: var(--spacing-1); }
.gap-2 { gap: var(--spacing-2); }
.gap-3 { gap: var(--spacing-3); }
.gap-4 { gap: var(--spacing-4); }
.gap-6 { gap: var(--spacing-6); }
.gap-8 { gap: var(--spacing-8); }
```

### 3.2 全体レイアウト構造

#### ダッシュボードレイアウト

```
┌─────────────────────────────────────┐
│ Header (ヘッダー・ナビゲーション)   │
├──────┬──────────────────────────────┤
│      │                              │
│ Side │  Main Content Area           │
│ Nav  │  (メインコンテンツ)          │
│      │                              │
│      │                              │
├──────┴──────────────────────────────┤
│ Footer (フッター)                   │
└─────────────────────────────────────┘
```

```css
.app-layout {
  display: grid;
  grid-template-areas:
    "header header"
    "sidebar main"
    "footer footer";
  grid-template-columns: 250px 1fr;
  grid-template-rows: auto 1fr auto;
  min-height: 100vh;
}

.app-header {
  grid-area: header;
  background: var(--color-white);
  border-bottom: 1px solid var(--border-color);
  padding: var(--spacing-4);
}

.app-sidebar {
  grid-area: sidebar;
  background: var(--color-gray-50);
  border-right: 1px solid var(--border-color);
  padding: var(--spacing-4);
}

.app-main {
  grid-area: main;
  padding: var(--spacing-6);
  background: var(--color-gray-100);
}

.app-footer {
  grid-area: footer;
  background: var(--color-gray-800);
  color: var(--color-white);
  padding: var(--spacing-4);
  text-align: center;
}
```

### 3.3 ページセクション

```css
.page-header {
  margin-bottom: var(--spacing-6);
  padding-bottom: var(--spacing-4);
  border-bottom: 2px solid var(--border-color);
}

.page-title {
  font-size: var(--font-size-3xl);
  font-weight: var(--font-weight-bold);
  color: var(--color-gray-900);
  margin: 0;
}

.page-subtitle {
  font-size: var(--font-size-lg);
  color: var(--color-gray-600);
  margin-top: var(--spacing-2);
}

.page-actions {
  margin-top: var(--spacing-4);
}

.section {
  background: var(--color-white);
  border-radius: var(--radius-lg);
  padding: var(--spacing-6);
  margin-bottom: var(--spacing-6);
  box-shadow: var(--shadow-sm);
}

.section-header {
  margin-bottom: var(--spacing-4);
  padding-bottom: var(--spacing-3);
  border-bottom: 1px solid var(--border-color);
}

.section-title {
  font-size: var(--font-size-xl);
  font-weight: var(--font-weight-semibold);
  color: var(--color-gray-900);
  margin: 0;
}
```

---

## 4. コンポーネントライブラリ

### 4.1 ボタン

#### 基本ボタン

```css
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-2) var(--spacing-4);
  font-size: var(--font-size-base);
  font-weight: var(--font-weight-medium);
  line-height: var(--line-height-tight);
  border-radius: var(--radius-md);
  border: var(--border-width-1) solid transparent;
  cursor: pointer;
  transition: all var(--duration-fast) var(--ease-in-out);
  text-decoration: none;
  white-space: nowrap;
}

.btn:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn:active {
  transform: translateY(0);
}

.btn:focus {
  outline: none;
  box-shadow: var(--shadow-focus);
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  pointer-events: none;
}
```

#### ボタンバリエーション

```css
/* Primary Button */
.btn-primary {
  background-color: var(--color-primary-500);
  color: var(--color-white);
}

.btn-primary:hover {
  background-color: var(--color-primary-600);
}

/* Secondary Button */
.btn-secondary {
  background-color: var(--color-secondary-500);
  color: var(--color-white);
}

.btn-secondary:hover {
  background-color: var(--color-secondary-600);
}

/* Success Button */
.btn-success {
  background-color: var(--color-success);
  color: var(--color-white);
}

/* Danger Button */
.btn-danger {
  background-color: var(--color-error);
  color: var(--color-white);
}

/* Outline Button */
.btn-outline {
  background-color: transparent;
  border-color: var(--color-primary-500);
  color: var(--color-primary-500);
}

.btn-outline:hover {
  background-color: var(--color-primary-50);
}

/* Ghost Button */
.btn-ghost {
  background-color: transparent;
  color: var(--color-gray-700);
}

.btn-ghost:hover {
  background-color: var(--color-gray-100);
}
```

#### ボタンサイズ

```css
.btn-sm {
  padding: var(--spacing-1) var(--spacing-3);
  font-size: var(--font-size-sm);
}

.btn-md {
  padding: var(--spacing-2) var(--spacing-4);
  font-size: var(--font-size-base);
}

.btn-lg {
  padding: var(--spacing-3) var(--spacing-6);
  font-size: var(--font-size-lg);
}

.btn-block {
  width: 100%;
  display: flex;
}
```

### 4.2 フォーム

#### インプットフィールド

```css
.form-group {
  margin-bottom: var(--spacing-4);
}

.form-label {
  display: block;
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  color: var(--color-gray-700);
  margin-bottom: var(--spacing-2);
}

.form-label-required::after {
  content: " *";
  color: var(--color-error);
}

.form-input,
.form-select,
.form-textarea {
  width: 100%;
  padding: var(--spacing-2) var(--spacing-3);
  font-size: var(--font-size-base);
  font-family: var(--font-family-base);
  color: var(--color-gray-900);
  background-color: var(--color-white);
  border: var(--border-width-1) solid var(--border-color);
  border-radius: var(--radius-md);
  transition: border-color var(--duration-fast), box-shadow var(--duration-fast);
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: var(--color-primary-500);
  box-shadow: var(--shadow-focus);
}

.form-input:disabled,
.form-select:disabled,
.form-textarea:disabled {
  background-color: var(--color-gray-100);
  cursor: not-allowed;
}

.form-input.is-invalid {
  border-color: var(--color-error);
}

.form-input.is-valid {
  border-color: var(--color-success);
}

.form-textarea {
  min-height: 120px;
  resize: vertical;
}

.form-help {
  display: block;
  margin-top: var(--spacing-2);
  font-size: var(--font-size-sm);
  color: var(--color-gray-600);
}

.form-error {
  display: block;
  margin-top: var(--spacing-2);
  font-size: var(--font-size-sm);
  color: var(--color-error);
}
```

#### チェックボックス・ラジオボタン

```css
.form-check {
  display: flex;
  align-items: center;
  margin-bottom: var(--spacing-3);
}

.form-check-input {
  width: 1.25rem;
  height: 1.25rem;
  margin-right: var(--spacing-2);
  cursor: pointer;
}

.form-check-label {
  font-size: var(--font-size-base);
  color: var(--color-gray-700);
  cursor: pointer;
  user-select: none;
}
```

#### インプットグループ

```css
.input-group {
  display: flex;
  width: 100%;
}

.input-group .form-input {
  flex: 1;
  border-radius: 0;
}

.input-group .form-input:first-child {
  border-top-left-radius: var(--radius-md);
  border-bottom-left-radius: var(--radius-md);
}

.input-group .form-input:last-child {
  border-top-right-radius: var(--radius-md);
  border-bottom-right-radius: var(--radius-md);
}

.input-group-prepend,
.input-group-append {
  display: flex;
  align-items: center;
  padding: var(--spacing-2) var(--spacing-3);
  background-color: var(--color-gray-100);
  border: var(--border-width-1) solid var(--border-color);
  font-size: var(--font-size-sm);
  color: var(--color-gray-600);
  white-space: nowrap;
}

.input-group-prepend {
  border-right: 0;
  border-top-left-radius: var(--radius-md);
  border-bottom-left-radius: var(--radius-md);
}

.input-group-append {
  border-left: 0;
  border-top-right-radius: var(--radius-md);
  border-bottom-right-radius: var(--radius-md);
}
```

### 4.3 テーブル

```css
.table-wrapper {
  overflow-x: auto;
  background: var(--color-white);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
}

.table {
  width: 100%;
  border-collapse: collapse;
  font-size: var(--font-size-sm);
}

.table thead {
  background-color: var(--color-gray-50);
  border-bottom: 2px solid var(--border-color);
}

.table th {
  padding: var(--spacing-3) var(--spacing-4);
  text-align: left;
  font-weight: var(--font-weight-semibold);
  color: var(--color-gray-700);
  white-space: nowrap;
}

.table td {
  padding: var(--spacing-3) var(--spacing-4);
  border-bottom: 1px solid var(--border-color);
  color: var(--color-gray-900);
}

.table tbody tr:hover {
  background-color: var(--color-gray-50);
}

.table tbody tr:last-child td {
  border-bottom: none;
}

/* Striped Table */
.table-striped tbody tr:nth-child(odd) {
  background-color: var(--color-gray-50);
}

/* Bordered Table */
.table-bordered {
  border: 1px solid var(--border-color);
}

.table-bordered th,
.table-bordered td {
  border: 1px solid var(--border-color);
}

/* Compact Table */
.table-compact th,
.table-compact td {
  padding: var(--spacing-2) var(--spacing-3);
}

/* Cell Alignment */
.text-left   { text-align: left; }
.text-center { text-align: center; }
.text-right  { text-align: right; }
```

### 4.4 カード

```css
.card {
  background: var(--color-white);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-base);
  overflow: hidden;
  transition: box-shadow var(--duration-base);
}

.card:hover {
  box-shadow: var(--shadow-lg);
}

.card-header {
  padding: var(--spacing-4) var(--spacing-6);
  background-color: var(--color-gray-50);
  border-bottom: 1px solid var(--border-color);
}

.card-title {
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-semibold);
  color: var(--color-gray-900);
  margin: 0;
}

.card-body {
  padding: var(--spacing-6);
}

.card-footer {
  padding: var(--spacing-4) var(--spacing-6);
  background-color: var(--color-gray-50);
  border-top: 1px solid var(--border-color);
}
```

### 4.5 バッジ

```css
.badge {
  display: inline-flex;
  align-items: center;
  padding: var(--spacing-1) var(--spacing-2);
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-medium);
  line-height: 1;
  border-radius: var(--radius-full);
  white-space: nowrap;
}

.badge-primary {
  background-color: var(--color-primary-100);
  color: var(--color-primary-700);
}

.badge-success {
  background-color: var(--color-success-light);
  color: var(--color-success-dark);
}

.badge-warning {
  background-color: var(--color-warning-light);
  color: var(--color-warning-dark);
}

.badge-danger {
  background-color: var(--color-error-light);
  color: var(--color-error-dark);
}

.badge-info {
  background-color: var(--color-info-light);
  color: var(--color-info-dark);
}

.badge-gray {
  background-color: var(--color-gray-200);
  color: var(--color-gray-700);
}
```

### 4.6 アラート・通知

```css
.alert {
  padding: var(--spacing-4);
  border-radius: var(--radius-md);
  border-left: 4px solid;
  margin-bottom: var(--spacing-4);
}

.alert-success {
  background-color: var(--color-success-light);
  border-color: var(--color-success);
  color: var(--color-success-dark);
}

.alert-warning {
  background-color: var(--color-warning-light);
  border-color: var(--color-warning);
  color: var(--color-warning-dark);
}

.alert-error {
  background-color: var(--color-error-light);
  border-color: var(--color-error);
  color: var(--color-error-dark);
}

.alert-info {
  background-color: var(--color-info-light);
  border-color: var(--color-info);
  color: var(--color-info-dark);
}

.alert-title {
  font-weight: var(--font-weight-semibold);
  margin-bottom: var(--spacing-1);
}

.alert-close {
  float: right;
  cursor: pointer;
  opacity: 0.7;
}

.alert-close:hover {
  opacity: 1;
}
```

### 4.7 モーダル

```css
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: var(--z-index-backdrop);
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal {
  position: relative;
  background: var(--color-white);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-2xl);
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow: hidden;
  z-index: var(--z-index-modal);
}

.modal-header {
  padding: var(--spacing-6);
  border-bottom: 1px solid var(--border-color);
}

.modal-title {
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-semibold);
  color: var(--color-gray-900);
  margin: 0;
}

.modal-close {
  position: absolute;
  top: var(--spacing-4);
  right: var(--spacing-4);
  background: none;
  border: none;
  font-size: var(--font-size-2xl);
  cursor: pointer;
  color: var(--color-gray-600);
}

.modal-close:hover {
  color: var(--color-gray-900);
}

.modal-body {
  padding: var(--spacing-6);
  overflow-y: auto;
  max-height: calc(90vh - 160px);
}

.modal-footer {
  padding: var(--spacing-6);
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-3);
}
```

### 4.8 ナビゲーション

#### ヘッダーナビゲーション

```css
.navbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--color-white);
  border-bottom: 1px solid var(--border-color);
  padding: var(--spacing-4) var(--spacing-6);
}

.navbar-brand {
  font-size: var(--font-size-xl);
  font-weight: var(--font-weight-bold);
  color: var(--color-primary-700);
  text-decoration: none;
}

.navbar-menu {
  display: flex;
  align-items: center;
  gap: var(--spacing-6);
  list-style: none;
  margin: 0;
  padding: 0;
}

.navbar-link {
  color: var(--color-gray-700);
  text-decoration: none;
  font-weight: var(--font-weight-medium);
  transition: color var(--duration-fast);
}

.navbar-link:hover {
  color: var(--color-primary-600);
}

.navbar-link.is-active {
  color: var(--color-primary-600);
  font-weight: var(--font-weight-semibold);
}
```

#### サイドバーナビゲーション

```css
.sidebar-nav {
  list-style: none;
  margin: 0;
  padding: 0;
}

.sidebar-nav-item {
  margin-bottom: var(--spacing-1);
}

.sidebar-nav-link {
  display: flex;
  align-items: center;
  padding: var(--spacing-3) var(--spacing-4);
  color: var(--color-gray-700);
  text-decoration: none;
  border-radius: var(--radius-md);
  transition: background-color var(--duration-fast);
}

.sidebar-nav-link:hover {
  background-color: var(--color-gray-200);
}

.sidebar-nav-link.is-active {
  background-color: var(--color-primary-100);
  color: var(--color-primary-700);
  font-weight: var(--font-weight-semibold);
}

.sidebar-nav-icon {
  margin-right: var(--spacing-3);
  font-size: var(--font-size-lg);
}

.sidebar-nav-group-title {
  padding: var(--spacing-3) var(--spacing-4);
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-semibold);
  color: var(--color-gray-600);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-top: var(--spacing-4);
}
```

### 4.9 ページネーション

```css
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-2);
  margin: var(--spacing-6) 0;
}

.pagination-item {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 2.5rem;
  height: 2.5rem;
  padding: var(--spacing-2);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  background: var(--color-white);
  color: var(--color-gray-700);
  text-decoration: none;
  transition: all var(--duration-fast);
  cursor: pointer;
}

.pagination-item:hover {
  background-color: var(--color-gray-100);
}

.pagination-item.is-active {
  background-color: var(--color-primary-500);
  color: var(--color-white);
  border-color: var(--color-primary-500);
}

.pagination-item:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
```

### 4.10 ローディング・スピナー

```css
.spinner {
  display: inline-block;
  width: 2rem;
  height: 2rem;
  border: 3px solid var(--color-gray-300);
  border-top-color: var(--color-primary-500);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.spinner-sm {
  width: 1rem;
  height: 1rem;
  border-width: 2px;
}

.spinner-lg {
  width: 3rem;
  height: 3rem;
  border-width: 4px;
}

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(255, 255, 255, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: var(--z-index-modal);
}
```

---

## 5. ページ別デザイン仕様

### 5.1 ログインページ（login.php）

#### レイアウト

```
┌────────────────────────────────┐
│                                │
│        ┌──────────┐             │
│        │   Logo   │             │
│        └──────────┘             │
│                                │
│    ┌────────────────────┐      │
│    │  Login Form        │      │
│    │  - Username        │      │
│    │  - Password        │      │
│    │  - Remember Me     │      │
│    │  [ログインボタン]  │      │
│    └────────────────────┘      │
│                                │
│    パスワードを忘れた方         │
│                                │
└────────────────────────────────┘
```

#### CSS

```css
.login-page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background: linear-gradient(135deg, var(--color-primary-600), var(--color-secondary-600));
}

.login-card {
  width: 100%;
  max-width: 420px;
  background: var(--color-white);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-2xl);
  padding: var(--spacing-8);
}

.login-logo {
  text-align: center;
  margin-bottom: var(--spacing-6);
}

.login-logo img {
  max-width: 200px;
}

.login-title {
  text-align: center;
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-bold);
  color: var(--color-gray-900);
  margin-bottom: var(--spacing-6);
}

.login-form .form-group {
  margin-bottom: var(--spacing-4);
}

.login-submit {
  width: 100%;
  margin-top: var(--spacing-4);
}

.login-links {
  text-align: center;
  margin-top: var(--spacing-4);
}

.login-links a {
  color: var(--color-primary-600);
  text-decoration: none;
  font-size: var(--font-size-sm);
}

.login-error {
  margin-bottom: var(--spacing-4);
}
```

### 5.2 ダッシュボード（index.php）

#### レイアウト

```
┌────────────────────────────────────────┐
│  Dashboard                             │
├────────┬─────────┬─────────┬───────────┤
│ Card 1 │ Card 2  │ Card 3  │  Card 4   │
│ 総CV数 │ 今日CV  │ 成功率  │ リトライ数│
└────────┴─────────┴─────────┴───────────┘
┌──────────────────┬─────────────────────┐
│                  │                     │
│  Recent Activity │  AFAD Statistics    │
│  (最近の活動)    │  (統計グラフ)       │
│                  │                     │
└──────────────────┴─────────────────────┘
```

#### CSS

```css
.dashboard {
  padding: var(--spacing-6);
}

.dashboard-header {
  margin-bottom: var(--spacing-6);
}

.dashboard-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: var(--spacing-4);
  margin-bottom: var(--spacing-6);
}

.stat-card {
  background: var(--color-white);
  border-radius: var(--radius-lg);
  padding: var(--spacing-6);
  box-shadow: var(--shadow-base);
  border-left: 4px solid var(--color-primary-500);
}

.stat-label {
  font-size: var(--font-size-sm);
  color: var(--color-gray-600);
  margin-bottom: var(--spacing-2);
}

.stat-value {
  font-size: var(--font-size-3xl);
  font-weight: var(--font-weight-bold);
  color: var(--color-gray-900);
}

.stat-change {
  font-size: var(--font-size-sm);
  margin-top: var(--spacing-2);
}

.stat-change.positive {
  color: var(--color-success);
}

.stat-change.negative {
  color: var(--color-error);
}

.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
  gap: var(--spacing-6);
}
```

### 5.3 レポートページ（report.php）

#### レイアウト

```
┌─────────────────────────────────────┐
│  Reports                            │
├─────────────────────────────────────┤
│  [検索フォーム]                     │
│  期間: [From] - [To]  [検索]        │
├─────────────────────────────────────┤
│  Results Table                      │
│  ID | 日付 | CV数 | 金額 | ...     │
│  --------------------------------   │
│  1  | xxxx | xxx  | xxx  | ...     │
├─────────────────────────────────────┤
│  [CSV出力] [PDF出力]                │
└─────────────────────────────────────┘
```

#### CSS

```css
.report-page {
  padding: var(--spacing-6);
}

.report-filters {
  background: var(--color-white);
  padding: var(--spacing-6);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  margin-bottom: var(--spacing-6);
}

.report-filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--spacing-4);
  margin-bottom: var(--spacing-4);
}

.report-actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-3);
}

.report-results {
  background: var(--color-white);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.report-summary {
  padding: var(--spacing-6);
  background: var(--color-gray-50);
  border-bottom: 1px solid var(--border-color);
}

.report-summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: var(--spacing-4);
}

.report-export {
  margin-top: var(--spacing-6);
  padding: var(--spacing-4);
  background: var(--color-gray-50);
  border-radius: var(--radius-lg);
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-3);
}
```

### 5.4 検索ページ（search.php）

```css
.search-page {
  padding: var(--spacing-6);
}

.search-form {
  background: var(--color-white);
  padding: var(--spacing-6);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  margin-bottom: var(--spacing-6);
}

.search-fields {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: var(--spacing-4);
}

.search-submit {
  margin-top: var(--spacing-4);
  display: flex;
  justify-content: center;
}

.search-results-count {
  padding: var(--spacing-4);
  font-size: var(--font-size-sm);
  color: var(--color-gray-600);
}
```

### 5.5 登録フォーム（regist.php）

```css
.regist-page {
  padding: var(--spacing-6);
}

.regist-form {
  background: var(--color-white);
  padding: var(--spacing-6);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  max-width: 800px;
  margin: 0 auto;
}

.regist-step-indicator {
  display: flex;
  justify-content: space-between;
  margin-bottom: var(--spacing-8);
}

.regist-step {
  flex: 1;
  text-align: center;
  position: relative;
  padding-bottom: var(--spacing-6);
}

.regist-step::before {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 2px;
  background: var(--color-gray-300);
}

.regist-step.is-active::before {
  background: var(--color-primary-500);
}

.regist-step.is-complete::before {
  background: var(--color-success);
}

.regist-step-number {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  background: var(--color-gray-300);
  color: var(--color-white);
  font-weight: var(--font-weight-bold);
  margin-bottom: var(--spacing-2);
}

.regist-step.is-active .regist-step-number {
  background: var(--color-primary-500);
}

.regist-step.is-complete .regist-step-number {
  background: var(--color-success);
}

.regist-actions {
  display: flex;
  justify-content: space-between;
  margin-top: var(--spacing-6);
  padding-top: var(--spacing-6);
  border-top: 1px solid var(--border-color);
}
```

---

## 6. AFAD固有のUI要素

### 6.1 AFADステータスバッジ

```css
/* AFAD Postback Status Badges */
.afad-status {
  display: inline-flex;
  align-items: center;
  padding: var(--spacing-1) var(--spacing-3);
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-semibold);
  border-radius: var(--radius-full);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.afad-status::before {
  content: "";
  display: inline-block;
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
  margin-right: var(--spacing-2);
}

.afad-status-pending {
  background-color: rgba(255, 193, 7, 0.1);
  color: var(--color-afad-pending);
  border: 1px solid var(--color-afad-pending);
}

.afad-status-pending::before {
  background-color: var(--color-afad-pending);
  animation: pulse 2s infinite;
}

.afad-status-sent {
  background-color: rgba(76, 175, 80, 0.1);
  color: var(--color-afad-sent);
  border: 1px solid var(--color-afad-sent);
}

.afad-status-sent::before {
  background-color: var(--color-afad-sent);
}

.afad-status-failed {
  background-color: rgba(220, 53, 69, 0.1);
  color: var(--color-afad-failed);
  border: 1px solid var(--color-afad-failed);
}

.afad-status-failed::before {
  background-color: var(--color-afad-failed);
}

.afad-status-retry {
  background-color: rgba(255, 152, 0, 0.1);
  color: var(--color-afad-retry);
  border: 1px solid var(--color-afad-retry);
}

.afad-status-retry::before {
  background-color: var(--color-afad-retry);
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}
```

### 6.2 AFAD統計カード

```css
.afad-stats-card {
  background: var(--color-white);
  border-radius: var(--radius-lg);
  padding: var(--spacing-6);
  box-shadow: var(--shadow-base);
}

.afad-stats-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--spacing-4);
}

.afad-stats-title {
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-semibold);
  color: var(--color-gray-900);
}

.afad-stats-period {
  font-size: var(--font-size-sm);
  color: var(--color-gray-600);
}

.afad-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: var(--spacing-4);
}

.afad-stat-item {
  text-align: center;
  padding: var(--spacing-4);
  background: var(--color-gray-50);
  border-radius: var(--radius-md);
}

.afad-stat-label {
  font-size: var(--font-size-xs);
  color: var(--color-gray-600);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: var(--spacing-2);
}

.afad-stat-value {
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-bold);
  color: var(--color-gray-900);
}

.afad-stat-rate {
  font-size: var(--font-size-sm);
  margin-top: var(--spacing-1);
}

.afad-success-rate {
  color: var(--color-success);
}

.afad-failure-rate {
  color: var(--color-error);
}
```

### 6.3 AFADリトライキュー

```css
.afad-retry-queue {
  background: var(--color-white);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.afad-retry-header {
  padding: var(--spacing-4) var(--spacing-6);
  background: var(--color-warning-light);
  border-bottom: 1px solid var(--color-warning);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.afad-retry-title {
  font-weight: var(--font-weight-semibold);
  color: var(--color-warning-dark);
}

.afad-retry-count {
  background: var(--color-warning);
  color: var(--color-white);
  padding: var(--spacing-1) var(--spacing-3);
  border-radius: var(--radius-full);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-bold);
}

.afad-retry-item {
  padding: var(--spacing-4) var(--spacing-6);
  border-bottom: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.afad-retry-item:last-child {
  border-bottom: none;
}

.afad-retry-info {
  flex: 1;
}

.afad-retry-id {
  font-size: var(--font-size-sm);
  color: var(--color-gray-600);
  font-family: var(--font-family-mono);
}

.afad-retry-attempts {
  margin-top: var(--spacing-1);
  font-size: var(--font-size-sm);
  color: var(--color-gray-700);
}

.afad-retry-next {
  font-size: var(--font-size-sm);
  color: var(--color-gray-600);
}

.afad-retry-actions {
  display: flex;
  gap: var(--spacing-2);
}
```

### 6.4 AFADポストバックログ

```css
.afad-log-viewer {
  background: var(--color-white);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.afad-log-filters {
  padding: var(--spacing-4) var(--spacing-6);
  background: var(--color-gray-50);
  border-bottom: 1px solid var(--border-color);
  display: flex;
  gap: var(--spacing-3);
  align-items: center;
  flex-wrap: wrap;
}

.afad-log-item {
  padding: var(--spacing-4) var(--spacing-6);
  border-bottom: 1px solid var(--border-color);
  font-family: var(--font-family-mono);
  font-size: var(--font-size-sm);
}

.afad-log-item:hover {
  background-color: var(--color-gray-50);
}

.afad-log-timestamp {
  color: var(--color-gray-600);
  margin-right: var(--spacing-4);
}

.afad-log-level {
  display: inline-block;
  padding: var(--spacing-1) var(--spacing-2);
  border-radius: var(--radius-sm);
  font-weight: var(--font-weight-medium);
  margin-right: var(--spacing-3);
}

.afad-log-level-error {
  background: var(--color-error-light);
  color: var(--color-error-dark);
}

.afad-log-level-warning {
  background: var(--color-warning-light);
  color: var(--color-warning-dark);
}

.afad-log-level-info {
  background: var(--color-info-light);
  color: var(--color-info-dark);
}

.afad-log-level-success {
  background: var(--color-success-light);
  color: var(--color-success-dark);
}

.afad-log-message {
  color: var(--color-gray-900);
}

.afad-log-details {
  margin-top: var(--spacing-2);
  padding: var(--spacing-3);
  background: var(--color-gray-100);
  border-radius: var(--radius-sm);
  font-size: var(--font-size-xs);
  color: var(--color-gray-700);
}
```

### 6.5 AFAD設定パネル

```css
.afad-config-panel {
  background: var(--color-white);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.afad-config-section {
  padding: var(--spacing-6);
  border-bottom: 1px solid var(--border-color);
}

.afad-config-section:last-child {
  border-bottom: none;
}

.afad-config-section-title {
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-semibold);
  color: var(--color-gray-900);
  margin-bottom: var(--spacing-4);
  padding-bottom: var(--spacing-3);
  border-bottom: 2px solid var(--color-primary-500);
}

.afad-config-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: var(--spacing-4);
}

.afad-config-field {
  display: flex;
  flex-direction: column;
}

.afad-config-label {
  font-weight: var(--font-weight-medium);
  color: var(--color-gray-700);
  margin-bottom: var(--spacing-2);
}

.afad-config-description {
  font-size: var(--font-size-sm);
  color: var(--color-gray-600);
  margin-top: var(--spacing-1);
}

.afad-config-actions {
  padding: var(--spacing-6);
  background: var(--color-gray-50);
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-3);
}
```

---

## 7. レスポンシブデザイン

### 7.1 ブレークポイント

```css
:root {
  /* Breakpoints */
  --breakpoint-xs: 0;
  --breakpoint-sm: 640px;
  --breakpoint-md: 768px;
  --breakpoint-lg: 1024px;
  --breakpoint-xl: 1280px;
  --breakpoint-2xl: 1536px;
}
```

### 7.2 メディアクエリ

```css
/* Mobile First Approach */

/* Small devices (landscape phones, 640px and up) */
@media (min-width: 640px) {
  .container {
    max-width: 640px;
  }
}

/* Medium devices (tablets, 768px and up) */
@media (min-width: 768px) {
  .container {
    max-width: 768px;
  }

  .app-layout {
    grid-template-columns: 250px 1fr;
  }
}

/* Large devices (desktops, 1024px and up) */
@media (min-width: 1024px) {
  .container {
    max-width: 1024px;
  }
}

/* Extra large devices (large desktops, 1280px and up) */
@media (min-width: 1280px) {
  .container {
    max-width: 1280px;
  }
}
```

### 7.3 モバイル対応

```css
/* Mobile Navigation */
@media (max-width: 767px) {
  .app-layout {
    grid-template-areas:
      "header"
      "main"
      "footer";
    grid-template-columns: 1fr;
  }

  .app-sidebar {
    display: none;
  }

  .app-sidebar.is-open {
    display: block;
    position: fixed;
    top: 0;
    left: 0;
    width: 80%;
    max-width: 300px;
    height: 100vh;
    z-index: var(--z-index-fixed);
    box-shadow: var(--shadow-xl);
  }

  .mobile-menu-toggle {
    display: block;
  }

  .navbar-menu {
    flex-direction: column;
    align-items: flex-start;
  }

  .table-wrapper {
    overflow-x: auto;
  }

  .table {
    min-width: 600px;
  }

  .dashboard-stats {
    grid-template-columns: 1fr;
  }

  .dashboard-grid {
    grid-template-columns: 1fr;
  }

  .modal {
    width: 95%;
    max-height: 95vh;
  }
}
```

### 7.4 タブレット対応

```css
@media (min-width: 768px) and (max-width: 1023px) {
  .dashboard-stats {
    grid-template-columns: repeat(2, 1fr);
  }

  .report-filters-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
```

---

## 8. 実装ガイドライン

### 8.1 ファイル構造

```
/css/
  ├── main.css                  # メインCSSファイル（全ての定義を含む）
  ├── variables.css             # CSS変数定義のみ（オプション：分割する場合）
  ├── components.css            # コンポーネント定義（オプション：分割する場合）
  └── pages.css                 # ページ別スタイル（オプション：分割する場合）
```

### 8.2 命名規則

**BEM (Block Element Modifier) 命名規則を推奨**

```css
/* Block */
.card { }

/* Element */
.card-header { }
.card-body { }
.card-footer { }

/* Modifier */
.card--highlighted { }
.card-header--large { }
```

**状態クラス**

```css
.is-active { }
.is-disabled { }
.is-loading { }
.is-open { }
.is-hidden { }
```

### 8.3 優先順位

1. **CSS変数** - カスタムプロパティを最優先で使用
2. **ユーティリティクラス** - 再利用可能な小さなクラス
3. **コンポーネントクラス** - 特定のUIパーツ
4. **ページ固有クラス** - 特定ページのみで使用

### 8.4 パフォーマンス最適化

```css
/*
 * 1. 不要なCSSを削除（未使用のセレクタ）
 * 2. CSSを圧縮（Minify）
 * 3. クリティカルCSSをインライン化
 * 4. 非クリティカルCSSを遅延読み込み
 * 5. アニメーションはtransformとopacityのみ使用
 */

/* Good - GPU加速 */
.element {
  transform: translateX(100px);
  opacity: 0.5;
}

/* Avoid - レイアウト再計算が発生 */
.element {
  left: 100px;
  visibility: hidden;
}
```

### 8.5 アクセシビリティ

```css
/* Focus Visible - キーボード操作時のフォーカス */
:focus-visible {
  outline: 2px solid var(--color-primary-500);
  outline-offset: 2px;
}

/* Reduced Motion - アニメーション無効化 */
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
  :root {
    --color-primary-500: #0000FF;
    --border-width-1: 2px;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  :root {
    --color-gray-50: #1F1F1F;
    --color-gray-900: #F5F5F5;
    --color-white: #121212;
    --color-black: #FFFFFF;
  }
}
```

### 8.6 ブラウザサポート

**対象ブラウザ:**
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

**Fallback対応:**

```css
/* CSS Grid Fallback */
.grid {
  display: flex;
  flex-wrap: wrap;
}

@supports (display: grid) {
  .grid {
    display: grid;
  }
}

/* CSS Variables Fallback */
.element {
  color: #2196F3; /* Fallback */
  color: var(--color-primary-500);
}
```

### 8.7 印刷スタイル

```css
@media print {
  /* Hide navigation and non-essential elements */
  .navbar,
  .sidebar,
  .app-sidebar,
  .btn,
  .pagination {
    display: none !important;
  }

  /* Optimize for print */
  body {
    font-size: 12pt;
    color: #000;
    background: #fff;
  }

  .table {
    page-break-inside: avoid;
  }

  /* Add page breaks */
  .page-break {
    page-break-after: always;
  }
}
```

---

## 9. 実装チェックリスト

### 9.1 必須コンポーネント

- [ ] CSS変数定義（カラー、タイポグラフィ、スペーシング）
- [ ] レイアウトシステム（コンテナ、グリッド、フレックス）
- [ ] ボタン（プライマリ、セカンダリ、サイズバリエーション）
- [ ] フォーム（インプット、セレクト、テキストエリア、チェックボックス）
- [ ] テーブル（基本、ストライプ、ボーダー）
- [ ] カード
- [ ] ナビゲーション（ヘッダー、サイドバー）
- [ ] バッジ
- [ ] アラート
- [ ] モーダル
- [ ] ページネーション
- [ ] ローディングスピナー

### 9.2 AFAD固有コンポーネント

- [ ] AFADステータスバッジ
- [ ] AFAD統計カード
- [ ] AFADリトライキュー表示
- [ ] AFADポストバックログビューア
- [ ] AFAD設定パネル

### 9.3 ページ実装

- [ ] ログインページ
- [ ] ダッシュボード
- [ ] レポートページ
- [ ] 検索ページ
- [ ] 登録フォームページ
- [ ] AFAD管理ページ

### 9.4 レスポンシブ対応

- [ ] モバイル（〜767px）
- [ ] タブレット（768px〜1023px）
- [ ] デスクトップ（1024px〜）

### 9.5 アクセシビリティ

- [ ] キーボードナビゲーション
- [ ] フォーカスインジケーター
- [ ] ARIAラベル（必要に応じて）
- [ ] カラーコントラスト比（WCAG AA準拠）
- [ ] Reduced Motion対応

---

## 10. 参考資料

### 10.1 カラーパレット生成ツール

- [Coolors](https://coolors.co/)
- [Adobe Color](https://color.adobe.com/)
- [Material Design Color Tool](https://material.io/resources/color/)

### 10.2 タイポグラフィツール

- [Type Scale](https://type-scale.com/)
- [Modular Scale](https://www.modularscale.com/)

### 10.3 アクセシビリティチェック

- [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/)
- [WAVE Web Accessibility Evaluation Tool](https://wave.webaim.org/)

### 10.4 CSS参考サイト

- [MDN Web Docs - CSS](https://developer.mozilla.org/en-US/docs/Web/CSS)
- [CSS-Tricks](https://css-tricks.com/)
- [Can I Use](https://caniuse.com/)

---

## 改訂履歴

| バージョン | 日付 | 変更内容 | 作成者 |
|-----------|------|---------|--------|
| 1.0.0 | 2025-11-03 | 初版作成 | Claude |

---

**以上**

このCSS設計書に基づいて、orka-asp2システムの完全なスタイリングを実装できます。
各セクションは独立しているため、段階的に実装を進めることも可能です。
