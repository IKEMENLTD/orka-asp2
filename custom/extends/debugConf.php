<?php
/**
 * Debug Configuration
 * This file contains debug-related settings
 */

// Debug mode (disable in production)
$DEBUG_MODE = getenv('DEBUG_MODE') === 'true';

if ($DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}
?>
