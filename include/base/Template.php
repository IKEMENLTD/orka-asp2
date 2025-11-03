<?php
require_once __DIR__ . '/Icon.php';

/**
 * Template Class - Template rendering with responsive purple theme
 */
class Template {
    /**
     * Draw template based on design type
     *
     * @param object $gm Global settings
     * @param mixed $rec Record data
     * @param string $loginUserType User type
     * @param string $loginUserRank User rank
     * @param string $param Additional parameter
     * @param string $designType Design type identifier
     */
    public static function drawTemplate($gm, $rec, $loginUserType, $loginUserRank, $param, $designType) {
        switch($designType) {
            case 'LOGIN_PAGE_DESIGN':
                self::renderLoginPage($gm, $rec, $param);
                break;

            case 'LOGIN_FALED_DESIGN':
                self::renderLoginFailed($gm, $rec);
                break;

            case 'LOGIN_LOCK_DESIGN':
                self::renderLoginLocked($gm, $rec);
                break;

            case 'TOP_PAGE_DESIGN':
                self::renderDashboard($gm, $rec, $loginUserType);
                break;

            case 'REPORT_DESIGN':
                self::renderReportList($gm, $loginUserType);
                break;

            case 'REPORT_CASE_NOT_FOUND':
                self::renderReportNotFound($gm);
                break;

            case 'ACCOUNT_UNLOCK_PAGE_DESIGN':
                self::renderAccountUnlockForm($gm, $rec);
                break;

            case 'ACCOUNT_UNLOCK_SUCCESS_PAGE_DESIGN':
                self::renderAccountUnlockSuccess($gm);
                break;

            case 'ACCOUNT_UNLOCK_FAILED_PAGE_DESIGN':
                self::renderAccountUnlockFailed($gm);
                break;

            case 'SEND_FORM_DESIGN':
                self::renderReminderForm($gm, $param);
                break;

            case 'SEND_COMP_DESIGN':
                self::renderReminderComplete($gm);
                break;

            case 'PASSWORD_RESET_FORM_DESIGN':
                self::renderPasswordResetForm($gm);
                break;

            case 'PASSWORD_RESET_COMP_DESIGN':
                self::renderPasswordResetComplete($gm);
                break;

            case 'PASSWORD_RESET_FALED_DESIGN':
                self::renderPasswordResetFailed($gm, $param);
                break;

            case 'QUICK_DESIGN':
                self::renderQuickLogin($gm, $rec, $loginUserType);
                break;

            case 'QUICK_FALED_DESIGN':
                self::renderQuickLoginFailed($gm);
                break;

            case 'RETURNSS_ACTION_INDEX':
                self::renderReturnActionIndex($gm, $loginUserType);
                break;

            case 'RETURNSS_EXECUTE_SUCCESS':
                self::renderReturnExecuteSuccess($gm);
                break;

            case 'RETURNSS_EXECUTE_ERROR':
                self::renderReturnExecuteError($gm);
                break;

            default:
                self::renderDefault($gm, $designType);
                break;
        }
    }

    /**
     * Login Page
     */
    private static function renderLoginPage($gm, $rec, $param) {
        $errorMsg = $gm->getVariable('error_msg') ?? '';
        $formAction = $gm->getVariable('form_action') ?? 'login.php';

        echo '<div class="login-page">';
        echo '<div class="login-card">';

        echo '<div class="login-logo">';
        echo '<h1>ORKA-ASP2</h1>';
        echo '</div>';

        echo '<h2 class="login-title">ログイン</h2>';

        if (!empty($errorMsg)) {
            echo '<div class="alert alert-error login-error">';
            echo Icon::get('error', '', 20);
            echo ' ' . htmlspecialchars($errorMsg);
            echo '</div>';
        }

        echo '<form class="login-form" method="post" action="' . htmlspecialchars($formAction) . '">';
        echo '<div class="form-group">';
        echo '<label for="login_id" class="form-label form-label-required">ログインID</label>';
        echo '<input type="text" id="login_id" name="login_id" class="form-input" required autofocus>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<label for="login_pass" class="form-label form-label-required">パスワード</label>';
        echo '<input type="password" id="login_pass" name="login_pass" class="form-input" required>';
        echo '</div>';

        echo $gm->getHiddenForm();

        echo '<button type="submit" class="btn btn-primary btn-lg login-submit">ログイン</button>';
        echo '</form>';

        echo '<div class="login-links">';
        echo '<a href="/reminder.php?type=admin">パスワードを忘れた方</a>';
        echo '</div>';

        echo '</div>'; // login-card
        echo '</div>'; // login-page
    }

    /**
     * Login Failed
     */
    private static function renderLoginFailed($gm, $rec) {
        echo '<div class="login-page">';
        echo '<div class="login-card">';
        echo '<div class="alert alert-error">';
        echo Icon::get('error', '', 24);
        echo ' <span class="alert-title">ログイン失敗</span>';
        echo '<p>IDまたはパスワードが正しくありません。</p>';
        echo '</div>';
        echo '<a href="/login.php" class="btn btn-primary btn-block">再度ログイン</a>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Login Locked
     */
    private static function renderLoginLocked($gm, $rec) {
        echo '<div class="login-page">';
        echo '<div class="login-card">';
        echo '<div class="alert alert-warning">';
        echo Icon::get('alert', '', 24);
        echo ' <span class="alert-title">アカウントロック</span>';
        echo '<p>ログイン試行回数が上限を超えました。アカウントがロックされています。</p>';
        echo '<p>管理者にお問い合わせください。</p>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Dashboard
     */
    private static function renderDashboard($gm, $rec, $loginUserType) {
        echo '<div class="dashboard">';

        echo '<header class="dashboard-header page-header">';
        echo '<h1 class="page-title">ダッシュボード</h1>';
        echo '<p class="page-subtitle">システム概要</p>';
        echo '</header>';

        // Stats cards
        echo '<div class="dashboard-stats">';
        self::renderStatCard('総登録数', '1,234', '+12', 'positive', 'dashboard');
        self::renderStatCard('今月のCV', '567', '+45', 'positive', 'chart');
        self::renderStatCard('AFAD送信', '890', '-5', 'negative', 'upload');
        self::renderStatCard('エラー', '12', '+3', 'negative', 'error');
        echo '</div>';

        // Main content sections
        echo '<div class="dashboard-grid">';

        // Recent activity
        echo '<div class="card">';
        echo '<div class="card-header">';
        echo '<h3 class="card-title">' . Icon::get('clipboard', '', 20) . ' 最近のアクティビティ</h3>';
        echo '</div>';
        echo '<div class="card-body">';
        echo '<p>最近のアクティビティがここに表示されます。</p>';
        echo '</div>';
        echo '</div>';

        // Quick links
        echo '<div class="card">';
        echo '<div class="card-header">';
        echo '<h3 class="card-title">' . Icon::get('link', '', 20) . ' クイックリンク</h3>';
        echo '</div>';
        echo '<div class="card-body">';
        echo '<a href="/report.php" class="btn btn-outline btn-block mb-4">レポート表示</a>';
        echo '<a href="/search.php?type=adwares" class="btn btn-outline btn-block mb-4">データ検索</a>';
        echo '<a href="/afad/config.php" class="btn btn-outline btn-block">AFAD設定</a>';
        echo '</div>';
        echo '</div>';

        echo '</div>'; // dashboard-grid
        echo '</div>'; // dashboard
    }

    /**
     * Stat Card Helper
     */
    private static function renderStatCard($label, $value, $change, $changeType, $icon) {
        echo '<div class="stat-card">';
        echo Icon::get($icon, 'stat-icon', 32);
        echo '<div class="stat-label">' . htmlspecialchars($label) . '</div>';
        echo '<div class="stat-value">' . htmlspecialchars($value) . '</div>';
        echo '<div class="stat-change ' . htmlspecialchars($changeType) . '">';
        echo htmlspecialchars($change);
        echo '</div>';
        echo '</div>';
    }

    /**
     * Report List
     */
    private static function renderReportList($gm, $loginUserType) {
        echo '<div class="report-page">';

        echo '<header class="page-header">';
        echo '<h1 class="page-title">レポート</h1>';
        echo '<p class="page-subtitle">各種レポートを表示します</p>';
        echo '</header>';

        echo '<div class="section">';
        echo '<div class="section-header">';
        echo '<h2 class="section-title">' . Icon::get('chart', '', 24) . ' 利用可能なレポート</h2>';
        echo '</div>';

        echo '<ul class="report-list">';
        echo '<li><a href="/report.php?name=daily" class="btn btn-outline btn-block">日次レポート</a></li>';
        echo '<li><a href="/report.php?name=monthly" class="btn btn-outline btn-block">月次レポート</a></li>';
        echo '<li><a href="/report.php?name=conversion" class="btn btn-outline btn-block">コンバージョンレポート</a></li>';
        echo '</ul>';

        echo '</div>'; // section
        echo '</div>'; // report-page
    }

    /**
     * Report Not Found
     */
    private static function renderReportNotFound($gm) {
        echo '<div class="section">';
        echo '<div class="alert alert-warning">';
        echo Icon::get('alert', '', 24);
        echo ' <span class="alert-title">レポートが見つかりません</span>';
        echo '<p>指定されたレポートは存在しないか、アクセス権限がありません。</p>';
        echo '</div>';
        echo '<a href="/report.php" class="btn btn-primary">レポート一覧に戻る</a>';
        echo '</div>';
    }

    /**
     * Account Unlock Form
     */
    private static function renderAccountUnlockForm($gm, $rec) {
        echo '<div class="section">';
        echo '<h2 class="section-title">アカウントロック解除</h2>';
        echo '<form method="post" class="form">';
        echo '<div class="form-group">';
        echo '<label class="form-label">新しいパスワードを入力してください</label>';
        echo '<input type="password" name="password" class="form-input" required>';
        echo '</div>';
        echo $gm->getHiddenForm();
        echo '<button type="submit" class="btn btn-primary">ロック解除</button>';
        echo '</form>';
        echo '</div>';
    }

    /**
     * Account Unlock Success
     */
    private static function renderAccountUnlockSuccess($gm) {
        echo '<div class="section">';
        echo '<div class="alert alert-success">';
        echo Icon::get('check', '', 24);
        echo ' <span class="alert-title">ロック解除成功</span>';
        echo '<p>アカウントのロックが解除されました。</p>';
        echo '</div>';
        echo '<a href="/login.php" class="btn btn-primary">ログインページへ</a>';
        echo '</div>';
    }

    /**
     * Account Unlock Failed
     */
    private static function renderAccountUnlockFailed($gm) {
        echo '<div class="section">';
        echo '<div class="alert alert-error">';
        echo Icon::get('error', '', 24);
        echo ' <span class="alert-title">ロック解除失敗</span>';
        echo '<p>ロック解除に失敗しました。</p>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Reminder Form (Password Reset Request)
     */
    private static function renderReminderForm($gm, $type) {
        $errorMsg = $gm->getVariable('error_msg') ?? '';

        echo '<div class="login-page">';
        echo '<div class="login-card">';
        echo '<h2 class="login-title">パスワード再設定</h2>';

        if (!empty($errorMsg)) {
            echo '<div class="alert alert-error">' . $errorMsg . '</div>';
        }

        echo '<form method="post" class="login-form">';
        echo '<div class="form-group">';
        echo '<label class="form-label">メールアドレス</label>';
        echo '<input type="email" name="mail" class="form-input" required>';
        echo '</div>';
        echo $gm->getHiddenForm();
        echo '<button type="submit" class="btn btn-primary btn-block">送信</button>';
        echo '</form>';

        echo '<div class="login-links">';
        echo '<a href="/login.php">ログインページに戻る</a>';
        echo '</div>';

        echo '</div>';
        echo '</div>';
    }

    /**
     * Reminder Complete
     */
    private static function renderReminderComplete($gm) {
        echo '<div class="login-page">';
        echo '<div class="login-card">';
        echo '<div class="alert alert-success">';
        echo Icon::get('check', '', 24);
        echo ' <span class="alert-title">送信完了</span>';
        echo '<p>パスワード再設定用のメールを送信しました。</p>';
        echo '</div>';
        echo '<a href="/login.php" class="btn btn-primary btn-block">ログインページへ</a>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Password Reset Form
     */
    private static function renderPasswordResetForm($gm) {
        echo '<div class="login-page">';
        echo '<div class="login-card">';
        echo '<h2 class="login-title">新しいパスワードを設定</h2>';
        echo '<form method="post" class="login-form">';
        echo '<div class="form-group">';
        echo '<label class="form-label">新しいパスワード</label>';
        echo '<input type="password" name="password" class="form-input" required>';
        echo '</div>';
        echo '<div class="form-group">';
        echo '<label class="form-label">パスワード確認</label>';
        echo '<input type="password" name="password_confirm" class="form-input" required>';
        echo '</div>';
        echo $gm->getHiddenForm();
        echo '<button type="submit" class="btn btn-primary btn-block">設定</button>';
        echo '</form>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Password Reset Complete
     */
    private static function renderPasswordResetComplete($gm) {
        echo '<div class="login-page">';
        echo '<div class="login-card">';
        echo '<div class="alert alert-success">';
        echo Icon::get('check', '', 24);
        echo ' <span class="alert-title">パスワード再設定完了</span>';
        echo '<p>パスワードの再設定が完了しました。</p>';
        echo '</div>';
        echo '<a href="/login.php" class="btn btn-primary btn-block">ログイン</a>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Password Reset Failed
     */
    private static function renderPasswordResetFailed($gm, $errorType) {
        $errorMessages = [
            'find' => 'トークンが見つかりません。',
            'password' => 'パスワードが一致しません。',
            'head' => '',
            'foot' => ''
        ];

        $message = $errorMessages[$errorType] ?? 'パスワード再設定に失敗しました。';

        echo '<div class="login-page">';
        echo '<div class="login-card">';
        echo '<div class="alert alert-error">';
        echo Icon::get('error', '', 24);
        echo ' <span class="alert-title">パスワード再設定失敗</span>';
        echo '<p>' . htmlspecialchars($message) . '</p>';
        echo '</div>';
        echo '<a href="/reminder.php" class="btn btn-primary btn-block">再試行</a>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Quick Login
     */
    private static function renderQuickLogin($gm, $rec, $loginUserType) {
        echo '<div class="section">';
        echo '<div class="alert alert-success">';
        echo Icon::get('check', '', 24);
        echo ' <span class="alert-title">クイックログイン成功</span>';
        echo '</div>';
        echo '<script>window.location.href = "/index.php";</script>';
        echo '</div>';
    }

    /**
     * Quick Login Failed
     */
    private static function renderQuickLoginFailed($gm) {
        echo '<div class="section">';
        echo '<div class="alert alert-error">';
        echo Icon::get('error', '', 24);
        echo ' <span class="alert-title">クイックログイン失敗</span>';
        echo '<p>クイックログインに失敗しました。</p>';
        echo '</div>';
        echo '<a href="/login.php" class="btn btn-primary">ログインページへ</a>';
        echo '</div>';
    }

    /**
     * Return Action Index
     */
    private static function renderReturnActionIndex($gm, $loginUserType) {
        echo '<div class="section">';
        echo '<h2 class="section-title">戻り値処理</h2>';
        echo '<p>戻り値処理のインデックスページです。</p>';
        echo '</div>';
    }

    /**
     * Return Execute Success
     */
    private static function renderReturnExecuteSuccess($gm) {
        echo '<div class="section">';
        echo '<div class="alert alert-success">';
        echo Icon::get('check', '', 24);
        echo ' <span class="alert-title">実行成功</span>';
        echo '<p>処理が正常に完了しました。</p>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Return Execute Error
     */
    private static function renderReturnExecuteError($gm) {
        echo '<div class="section">';
        echo '<div class="alert alert-error">';
        echo Icon::get('error', '', 24);
        echo ' <span class="alert-title">実行エラー</span>';
        echo '<p>処理中にエラーが発生しました。</p>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Default/Unknown Design Type
     */
    private static function renderDefault($gm, $designType) {
        echo '<div class="section">';
        echo '<h1 class="page-title">ORKA-ASP2</h1>';
        echo '<div class="alert alert-info">';
        echo Icon::get('info', '', 24);
        echo ' デザインタイプ: ' . htmlspecialchars($designType);
        echo '</div>';
        echo '<p>このページは開発中です。</p>';
        echo '</div>';
    }

    /**
     * Get Template (for mail, etc.)
     */
    public static function getTemplate($type, $rank, $param, $templateType) {
        // Return template string for mail/other purposes
        // This is a stub - implement based on your template system
        return "Template: {$templateType}";
    }

    /**
     * Get Template String (for dynamic template parts)
     */
    public static function getTemplateString($gm, $rec, $type, $rank, $param, $templateType, $encode = false, $data = null, $section = null) {
        // This method is used by reminder.php to get template parts
        // Return empty string for head/foot sections in PASSWORD_RESET_FALED_DESIGN
        if ($templateType === 'PASSWORD_RESET_FALED_DESIGN') {
            if ($section === 'head' || $section === 'foot') {
                return '';
            }

            $errorMessages = [
                'find' => 'トークンが見つかりません。',
                'password' => 'パスワードが一致しません。'
            ];

            $message = $errorMessages[$section] ?? 'エラーが発生しました。';
            return '<p>' . htmlspecialchars($message) . '</p>';
        }

        return '';
    }
}
?>
