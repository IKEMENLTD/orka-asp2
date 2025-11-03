<?php
/**
 * Main Initialization File
 * This file is included by all PHP pages in the application
 * It sets up the environment, includes necessary files, and defines common functions
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set error reporting based on environment
if (getenv('DISPLAY_ERRORS') === '1' || getenv('DEBUG_MODE') === 'true') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

// Include base utility files
include_once dirname(__DIR__) . '/include/base/Util.php';

// Include custom configuration files
include_once __DIR__ . '/conf.php';
include_once __DIR__ . '/global.php';
include_once __DIR__ . '/extends/moduleConf.php';

// Include module files
include_once dirname(__DIR__) . '/module/module.inc';

/**
 * Friend Proc Function
 * This function handles friend/referral code processing
 * Called from index.php and other pages
 */
function friendProc() {
    // Check for friend/referral code in query parameters
    if (isset($_GET['friend']) || isset($_GET['ref'])) {
        $friendCode = $_GET['friend'] ?? $_GET['ref'] ?? null;

        if ($friendCode && !isset($_SESSION['friend_code'])) {
            // Store friend code in session
            $_SESSION['friend_code'] = $friendCode;
            $_SESSION['friend_timestamp'] = time();

            // Log friend code usage if logging is enabled
            if (defined('AFAD_LOG_ENABLED') && AFAD_LOG_ENABLED) {
                error_log("Friend code stored: {$friendCode}");
            }
        }
    }

    // Return friend code if exists
    return $_SESSION['friend_code'] ?? null;
}

/**
 * Get current login user information
 */
function getLoginUser() {
    return [
        'type' => $_SESSION['loginUserType'] ?? 0,
        'rank' => $_SESSION['loginUserRank'] ?? 0,
        'id' => $_SESSION['loginUserId'] ?? null,
    ];
}

// Initialize database connection if configuration exists
if (isset($SQL_MASTER, $SQL_ID, $SQL_PASS, $DB_NAME, $SQL_SERVER, $SQL_PORT)) {
    try {
        $SQL = SQLConnect::Create($SQL_MASTER, $SQL_ID, $SQL_PASS, $DB_NAME, $SQL_SERVER, $SQL_PORT);

        if (!$SQL->connect) {
            error_log("Database connection failed");
            // Don't halt execution, allow page to load with limited functionality
        }
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        $SQL = null;
    }
}

// Set timezone
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Asia/Tokyo');
}
?>
