<?php
/**
 * SystemUtil Class - System utilities
 */
class SystemUtil {
    private static $systemInstances = [];

    /**
     * Get system object for user type
     * @param string $loginUserType - user type
     * @return SystemObject - system object instance
     */
    public static function getSystem($loginUserType) {
        // Return cached instance if exists
        if (isset(self::$systemInstances[$loginUserType])) {
            return self::$systemInstances[$loginUserType];
        }

        // Create new instance
        require_once __DIR__ . '/SystemObject.php';
        $system = new SystemObject($loginUserType);

        // Cache for future use
        self::$systemInstances[$loginUserType] = $system;

        return $system;
    }

    /**
     * Logout user
     * @param string $loginUserType - user type
     * @return bool - success
     */
    public static function logout($loginUserType) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        return true;
    }

    /**
     * Login user
     * @param mixed $id - user ID
     * @param string $loginUserType - user type
     */
    public static function login($id, $loginUserType) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['LOGIN_ID'] = $id;
        $_SESSION['LOGIN_USER_TYPE'] = $loginUserType;
    }

    /**
     * Check login credentials
     * @param string $userType - user type
     * @param string $key - username/email
     * @param string $password - password
     * @return mixed - user ID if successful, false otherwise
     */
    public static function login_check($userType, $key, $password) {
        global $gm;

        if (!isset($gm[$userType])) {
            return false;
        }

        $db = $gm[$userType]->getDB();
        $table = $db->getTable();

        // Search by ID or email
        $table = $db->searchTable($table, 'id', '=', $key);

        if ($db->getRow($table) == 0) {
            // Try email if ID didn't match
            $table = $db->getTable();
            $table = $db->searchTable($table, 'mail', '=', $key);
        }

        if ($db->getRow($table) == 0) {
            return false;
        }

        $rec = $db->getRecord($table, 0);
        $storedPass = $db->getData($rec, 'pass');

        // Check password (assuming encoded)
        if (self::checkPassword($password, $storedPass)) {
            return $db->getData($rec, 'id');
        }

        return false;
    }

    /**
     * Encode password
     * @param string $password - plain password
     * @param string $mode - encoding mode
     * @return string - encoded password
     */
    public static function encodePassword($password, $mode = 'sha256') {
        switch ($mode) {
            case 'md5':
                return md5($password);
            case 'sha1':
                return sha1($password);
            case 'sha256':
            default:
                return hash('sha256', $password);
        }
    }

    /**
     * Check password
     * @param string $input - input password
     * @param string $stored - stored hash
     * @return bool - match result
     */
    private static function checkPassword($input, $stored) {
        // Try various encoding methods
        $methods = ['sha256', 'sha1', 'md5'];

        foreach ($methods as $method) {
            if (self::encodePassword($input, $method) === $stored) {
                return true;
            }
        }

        // Plain text comparison (for development only)
        return $input === $stored;
    }

    /**
     * Internal location redirect
     * @param string $url - URL to redirect to
     */
    public static function innerLocation($url) {
        header("Location: " . $url);
        exit();
    }
}

/**
 * Account lock logic
 */
class accountLockLogic {
    private static $maxTries = 5;
    private static $lockDuration = 900; // 15 minutes

    public static function isTryOver() {
        if (!isset($_SESSION['login_tries'])) {
            return false;
        }

        if ($_SESSION['login_tries'] >= self::$maxTries) {
            if (isset($_SESSION['lock_time'])) {
                $elapsed = time() - $_SESSION['lock_time'];
                if ($elapsed < self::$lockDuration) {
                    return true;
                } else {
                    // Lock expired
                    self::resetTryCount();
                    return false;
                }
            }
            $_SESSION['lock_time'] = time();
            return true;
        }

        return false;
    }

    public static function addTryCount() {
        if (!isset($_SESSION['login_tries'])) {
            $_SESSION['login_tries'] = 0;
        }
        $_SESSION['login_tries']++;
    }

    public static function resetTryCount() {
        unset($_SESSION['login_tries']);
        unset($_SESSION['lock_time']);
    }
}

/**
 * CheckData class for form validation
 */
class CheckData {
    private $gm;
    private $errors = [];
    private $loginUserType;
    private $loginUserRank;

    public function __construct($gm, $autoCheck, $loginUserType, $loginUserRank) {
        $this->gm = $gm;
        $this->loginUserType = $loginUserType;
        $this->loginUserRank = $loginUserRank;
    }

    public function check($strict = true) {
        // Basic validation - can be extended
        return count($this->errors) === 0;
    }

    public function addError($field, $message) {
        $this->errors[$field] = $message;
    }

    public function getErrors() {
        return $this->errors;
    }
}
?>
