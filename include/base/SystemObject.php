<?php
/**
 * SystemObject Class - Base system operations for user types
 * This class provides default implementations for login, logout, and registration operations
 */
class SystemObject {
    protected $userType;

    public function __construct($userType) {
        $this->userType = $userType;
    }

    /**
     * Login process hook
     * @param bool $login - whether login was successful
     * @param string $loginUserType - user type attempting login
     * @param mixed $id - user ID if login successful
     * @return bool - whether to proceed with login
     */
    public function loginProc($login, $loginUserType, $id) {
        // Default: allow login if credentials were valid
        return $login;
    }

    /**
     * Logout process hook
     * @param string $loginUserType - user type logging out
     * @return bool - whether logout was successful
     */
    public function logoutProc($loginUserType) {
        // Default: always allow logout
        return true;
    }

    /**
     * Registration check - validate registration data
     * @param array $gm - global manager
     * @param bool $strict - whether to perform strict validation
     * @param string $loginUserType - current user type
     * @param string $loginUserRank - current user rank
     * @return bool - whether data is valid
     */
    public function registCheck($gm, $strict, $loginUserType, $loginUserRank) {
        // Default: perform basic validation
        if (isset(System::$checkData)) {
            return System::$checkData->check($strict);
        }
        return true;
    }

    /**
     * Registration completion check
     * @param array $gm - global manager
     * @param mixed $rec - record data
     * @param string $loginUserType - current user type
     * @param string $loginUserRank - current user rank
     * @return bool - whether registration can complete
     */
    public function registCompCheck($gm, $rec, $loginUserType, $loginUserRank) {
        // Default: allow registration
        return true;
    }

    /**
     * Registration process hook - called before saving
     * @param array $gm - global manager
     * @param mixed $rec - record data
     * @param string $loginUserType - current user type
     * @param string $loginUserRank - current user rank
     * @param bool $preCheck - whether this is pre-check phase
     */
    public function registProc($gm, $rec, $loginUserType, $loginUserRank, $preCheck = false) {
        // Default: no additional processing
    }

    /**
     * Registration completion hook - called after successful save
     * @param array $gm - global manager
     * @param mixed $rec - record data
     * @param string $loginUserType - current user type
     * @param string $loginUserRank - current user rank
     */
    public function registComp($gm, $rec, $loginUserType, $loginUserRank) {
        // Default: no additional processing
    }

    /**
     * Copy check - whether copy operation is allowed
     * @param array $gm - global manager
     * @param string $loginUserType - current user type
     * @param string $loginUserRank - current user rank
     * @return bool - whether copy is allowed
     */
    public function copyCheck($gm, $loginUserType, $loginUserRank) {
        // Default: allow copy
        return true;
    }

    /**
     * Draw registration form
     * @param array $gm - global manager
     * @param mixed $rec - record data
     * @param string $loginUserType - current user type
     * @param string $loginUserRank - current user rank
     */
    public function drawRegistForm($gm, $rec, $loginUserType, $loginUserRank) {
        global $_GET;
        $type = $_GET['type'] ?? 'unknown';

        echo System::getHead($gm, $loginUserType, $loginUserRank);

        echo '<div class="card">';
        echo '<div class="card-header">';
        echo '<h2 class="card-title">新規登録: ' . htmlspecialchars($type) . '</h2>';
        echo '</div>';
        echo '<div class="card-body">';

        if (isset($gm[$type])) {
            echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?type=' . urlencode($type) . '">';
            echo $gm[$type]->drawFormInsert();
            echo '<div class="button-group">';
            echo '<button type="submit" class="btn btn-primary">確認</button>';
            echo '<a href="index.php" class="btn btn-secondary">キャンセル</a>';
            echo '</div>';
            echo '</form>';
        } else {
            echo '<p class="alert alert-error">フォームを表示できません</p>';
        }

        echo '</div>';
        echo '</div>';

        echo System::getFoot($gm, $loginUserType, $loginUserRank);
    }

    /**
     * Draw registration confirmation
     * @param array $gm - global manager
     * @param mixed $rec - record data
     * @param string $loginUserType - current user type
     * @param string $loginUserRank - current user rank
     */
    public function drawRegistCheck($gm, $rec, $loginUserType, $loginUserRank) {
        global $_GET;
        $type = $_GET['type'] ?? 'unknown';

        echo System::getHead($gm, $loginUserType, $loginUserRank);

        echo '<div class="card">';
        echo '<div class="card-header">';
        echo '<h2 class="card-title">登録内容確認</h2>';
        echo '</div>';
        echo '<div class="card-body">';

        if (isset($gm[$type])) {
            echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?type=' . urlencode($type) . '">';
            echo $gm[$type]->drawFormCheck();
            echo '<div class="button-group">';
            echo '<button type="submit" class="btn btn-primary">登録</button>';
            echo '<button type="submit" name="back" value="1" class="btn btn-secondary">戻る</button>';
            echo '</div>';
            echo '</form>';
        } else {
            echo '<p class="alert alert-error">確認画面を表示できません</p>';
        }

        echo '</div>';
        echo '</div>';

        echo System::getFoot($gm, $loginUserType, $loginUserRank);
    }

    /**
     * Draw registration completion
     * @param array $gm - global manager
     * @param mixed $rec - record data
     * @param string $loginUserType - current user type
     * @param string $loginUserRank - current user rank
     */
    public function drawRegistComp($gm, $rec, $loginUserType, $loginUserRank) {
        global $_GET;
        $type = $_GET['type'] ?? 'unknown';

        echo System::getHead($gm, $loginUserType, $loginUserRank);

        echo '<div class="card">';
        echo '<div class="card-header">';
        echo '<h2 class="card-title">登録完了</h2>';
        echo '</div>';
        echo '<div class="card-body">';
        echo '<p class="alert alert-success">登録が完了しました。</p>';
        echo '<div class="button-group">';
        echo '<a href="index.php" class="btn btn-primary">トップページへ</a>';
        echo '<a href="regist.php?type=' . urlencode($type) . '" class="btn btn-secondary">続けて登録</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo System::getFoot($gm, $loginUserType, $loginUserRank);
    }

    /**
     * Draw registration failure
     * @param array $gm - global manager
     * @param string $loginUserType - current user type
     * @param string $loginUserRank - current user rank
     */
    public function drawRegistFaled($gm, $loginUserType, $loginUserRank) {
        echo System::getHead($gm, $loginUserType, $loginUserRank);

        echo '<div class="card">';
        echo '<div class="card-header">';
        echo '<h2 class="card-title">エラー</h2>';
        echo '</div>';
        echo '<div class="card-body">';
        echo '<p class="alert alert-error">登録処理に失敗しました。</p>';
        echo '<div class="button-group">';
        echo '<a href="index.php" class="btn btn-primary">トップページへ</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo System::getFoot($gm, $loginUserType, $loginUserRank);
    }
}
?>
