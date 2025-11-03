<?php
/**
 * Main Initialization File
 * This file is included by all PHP pages in the application
 * It sets up the environment, includes necessary files, and defines common functions
 */

// Debug logging
error_log("=== head_main.php START ===");
error_log("head_main.php included from: " . (__FILE__));

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    error_log("Session started in head_main.php");
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
error_log("Including Util.php from: " . dirname(__DIR__) . '/include/base/Util.php');
include_once dirname(__DIR__) . '/include/base/Util.php';
error_log("Util.php included successfully");

// Include custom configuration files
error_log("Including conf.php");
include_once __DIR__ . '/conf.php';
error_log("conf.php included successfully");

error_log("Including global.php");
include_once __DIR__ . '/global.php';
error_log("global.php included successfully");

error_log("Including moduleConf.php");
include_once __DIR__ . '/extends/moduleConf.php';
error_log("moduleConf.php included successfully");

// Include module files
error_log("Including module.inc from: " . dirname(__DIR__) . '/module/module.inc');
include_once dirname(__DIR__) . '/module/module.inc';
error_log("module.inc included successfully");

error_log("=== head_main.php END ===");

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
