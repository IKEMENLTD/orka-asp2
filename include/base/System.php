<?php
require_once __DIR__ . '/Icon.php';

/**
 * System Class - HTML generation and layout (Purple Theme with SVG Icons)
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
            $html .= self::getNavbar($loginUserType, $loginUserRank);
            $html .= '<div class="app-layout">';
            $html .= self::getSidebar($loginUserType, $loginUserRank);
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
    <p>&copy; 2025 ORKA-ASP2. All rights reserved.</p>
</footer>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * ナビゲーションバー
     */
    private static function getNavbar($loginUserType, $loginUserRank) {
        $currentPage = basename($_SERVER['PHP_SELF'], '.php');

        // Generate navigation links with SVG icons
        $dashboardIcon = Icon::inline('dashboard');
        $chartIcon = Icon::inline('chart');
        $searchIcon = Icon::inline('search');
        $logoutIcon = Icon::inline('logout');

        $dashboardActive = ($currentPage === 'index') ? 'is-active' : '';
        $reportActive = ($currentPage === 'report') ? 'is-active' : '';
        $searchActive = ($currentPage === 'search') ? 'is-active' : '';

        return <<<HTML
<header class="app-header">
    <nav class="navbar">
        <a href="/index.php" class="navbar-brand">ORKA-ASP2</a>
        <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
            <svg class="icon" width="24" height="24" aria-hidden="true">
                <use href="/assets/icons/icons.svg#icon-menu"></use>
            </svg>
        </button>
        <ul class="navbar-menu" id="navbarMenu">
            <li><a href="/index.php" class="navbar-link {$dashboardActive}">{$dashboardIcon}ダッシュボード</a></li>
            <li><a href="/report.php" class="navbar-link {$reportActive}">{$chartIcon}レポート</a></li>
            <li><a href="/search.php?type=adwares" class="navbar-link {$searchActive}">{$searchIcon}検索</a></li>
            <li><a href="/login.php?logout=1" class="navbar-link">{$logoutIcon}ログアウト</a></li>
        </ul>
    </nav>
</header>
HTML;
    }

    /**
     * サイドバーナビゲーション
     */
    private static function getSidebar($loginUserType, $loginUserRank) {
        $currentPage = basename($_SERVER['PHP_SELF'], '.php');

        // Generate navigation items with active state
        $menuItems = [
            ['icon' => 'dashboard', 'text' => 'ダッシュボード', 'url' => '/index.php', 'page' => 'index'],
            ['icon' => 'chart', 'text' => 'レポート', 'url' => '/report.php', 'page' => 'report'],
            ['icon' => 'search', 'text' => '検索', 'url' => '/search.php?type=adwares', 'page' => 'search'],
        ];

        $adminItems = [
            ['icon' => 'tools', 'text' => 'ツール', 'url' => '/tool.php', 'page' => 'tool'],
            ['icon' => 'user', 'text' => 'ユーザー管理', 'url' => '/regist.php?type=admin', 'page' => 'regist'],
        ];

        $afadItems = [
            ['icon' => 'settings', 'text' => 'AFAD設定', 'url' => '/afad/config.php', 'page' => 'config'],
            ['icon' => 'clipboard', 'text' => 'ログ', 'url' => '/afad/logs.php', 'page' => 'logs'],
            ['icon' => 'stats', 'text' => '統計', 'url' => '/afad/stats.php', 'page' => 'stats'],
        ];

        $html = '<aside class="app-sidebar" id="appSidebar">';
        $html .= '<nav class="sidebar-nav">';

        // Main menu
        $html .= '<div class="sidebar-nav-group-title">メニュー</div>';
        $html .= '<ul>';
        foreach ($menuItems as $item) {
            $activeClass = ($currentPage === $item['page']) ? 'is-active' : '';
            $icon = Icon::inline($item['icon']);
            $html .= '<li class="sidebar-nav-item">';
            $html .= "<a href=\"{$item['url']}\" class=\"sidebar-nav-link {$activeClass}\">";
            $html .= "{$icon}{$item['text']}";
            $html .= '</a>';
            $html .= '</li>';
        }
        $html .= '</ul>';

        // Admin menu
        $html .= '<div class="sidebar-nav-group-title">管理</div>';
        $html .= '<ul>';
        foreach ($adminItems as $item) {
            $activeClass = ($currentPage === $item['page']) ? 'is-active' : '';
            $icon = Icon::inline($item['icon']);
            $html .= '<li class="sidebar-nav-item">';
            $html .= "<a href=\"{$item['url']}\" class=\"sidebar-nav-link {$activeClass}\">";
            $html .= "{$icon}{$item['text']}";
            $html .= '</a>';
            $html .= '</li>';
        }
        $html .= '</ul>';

        // AFAD menu
        $html .= '<div class="sidebar-nav-group-title">AFAD</div>';
        $html .= '<ul>';
        foreach ($afadItems as $item) {
            $icon = Icon::inline($item['icon']);
            $html .= '<li class="sidebar-nav-item">';
            $html .= "<a href=\"{$item['url']}\" class=\"sidebar-nav-link\">";
            $html .= "{$icon}{$item['text']}";
            $html .= '</a>';
            $html .= '</li>';
        }
        $html .= '</ul>';

        $html .= '</nav>';
        $html .= '</aside>';

        // Add mobile menu script
        $html .= <<<SCRIPT
<script>
(function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const appSidebar = document.getElementById('appSidebar');

    if (mobileMenuToggle && appSidebar) {
        mobileMenuToggle.addEventListener('click', function() {
            appSidebar.classList.toggle('is-open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 767) {
                if (!appSidebar.contains(event.target) && !mobileMenuToggle.contains(event.target)) {
                    appSidebar.classList.remove('is-open');
                }
            }
        });
    }
})();
</script>
SCRIPT;

        return $html;
    }
}
?>
