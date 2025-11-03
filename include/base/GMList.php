<?php
/**
 * GMList Class - Global Master List
 */

error_log("=== GMList.php: Loading ===");
error_log("GMList class exists: " . (class_exists('GMList', false) ? 'YES' : 'NO'));
error_log("GMlist class exists: " . (class_exists('GMlist', false) ? 'YES' : 'NO'));

// Prevent duplicate class declaration
if (!class_exists('GMList')) {
    error_log("Defining GMList class");
    class GMList {
        public static function getList() {
            return [
                'system' => [
                    'SITE_NAME' => 'orka-asp2 Affiliate System',
                    'SITE_URL' => getenv('SITE_URL') ?: 'http://localhost',
                ]
            ];
        }
    }
}

// Alias for case sensitivity
if (!class_exists('GMlist')) {
    error_log("Defining GMlist class (alias)");
    class GMlist extends GMList {}
} else {
    error_log("GMlist class already exists, skipping definition");
}

error_log("=== GMList.php: Loading complete ===");
?>
