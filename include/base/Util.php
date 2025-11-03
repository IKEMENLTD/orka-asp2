<?php
/**
 * Base Utility Classes for orka-asp2
 * Minimal implementation to allow the application to start
 */

// Autoload function for missing classes
spl_autoload_register(function ($class_name) {
    $class_files = [
        'SQLConnect' => __DIR__ . '/SQLConnect.php',
        'System' => __DIR__ . '/System.php',
        'Template' => __DIR__ . '/Template.php',
        'SystemUtil' => __DIR__ . '/SystemUtil.php',
        'ConceptCheck' => __DIR__ . '/ConceptCheck.php',
        'ErrorManager' => __DIR__ . '/ErrorManager.php',
        'ExceptionManager' => __DIR__ . '/ExceptionManager.php',
        'CSV' => __DIR__ . '/CSV.php',
        'GMList' => __DIR__ . '/GMList.php',
        'Draw' => __DIR__ . '/Draw.php',
        'Mail' => __DIR__ . '/Mail.php',
        'PathUtil' => __DIR__ . '/PathUtil.php',
        'MobileUtil' => __DIR__ . '/MobileUtil.php',
    ];

    if (isset($class_files[$class_name]) && file_exists($class_files[$class_name])) {
        include_once $class_files[$class_name];
    }
});

// Basic PathUtil class
class PathUtil {
    public static function getRootPath() {
        return dirname(dirname(__DIR__)) . '/';
    }
}

// Basic ConceptCheck class
class ConceptCheck {
    public static function IsScalar($array, $keys) {
        foreach ($keys as $key) {
            if (isset($array[$key]) && !is_scalar($array[$key])) {
                throw new InvalidArgumentException("Invalid parameter: {$key}");
            }
        }
    }
}

// Basic MobileUtil class
class MobileUtil {
    public static function isMobile() {
        return false; // Simple implementation
    }
}
?>
