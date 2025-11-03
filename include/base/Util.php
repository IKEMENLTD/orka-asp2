<?php
/**
 * Base Utility Classes for orka-asp2
 * Minimal implementation to allow the application to start
 */

// Debug logging
error_log("=== Util.php: Registering autoloader ===");

// Autoload function for missing classes
spl_autoload_register(function ($class_name) {
    error_log("Autoloader triggered for class: $class_name");

    $class_files = [
        'SQLConnect' => __DIR__ . '/SQLConnect.php',
        'System' => __DIR__ . '/System.php',
        'Template' => __DIR__ . '/Template.php',
        'SystemUtil' => __DIR__ . '/SystemUtil.php',
        'SystemObject' => __DIR__ . '/SystemObject.php',
        'ConceptCheck' => __DIR__ . '/ConceptCheck.php',
        'ErrorManager' => __DIR__ . '/ErrorManager.php',
        'ExceptionManager' => __DIR__ . '/ExceptionManager.php',
        'CSV' => __DIR__ . '/CSV.php',
        'GMList' => __DIR__ . '/GMList.php',
        'Draw' => __DIR__ . '/Draw.php',
        'Icon' => __DIR__ . '/Icon.php',
        'AFADDraw' => __DIR__ . '/AFADDraw.php',
        'Mail' => __DIR__ . '/Mail.php',
        'PathUtil' => __DIR__ . '/PathUtil.php',
        'MobileUtil' => __DIR__ . '/MobileUtil.php',
        'command_base' => __DIR__ . '/command_base.php',
    ];

    if (isset($class_files[$class_name]) && file_exists($class_files[$class_name])) {
        error_log("Autoloading $class_name from: " . $class_files[$class_name]);
        error_log("Class exists before include: " . (class_exists($class_name, false) ? 'YES' : 'NO'));
        include_once $class_files[$class_name];
        error_log("Class exists after include: " . (class_exists($class_name, false) ? 'YES' : 'NO'));
    } else {
        error_log("Class file not found for: $class_name");
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
    /**
     * Check if specified keys exist in array
     * @param array $array - array to check
     * @param array $keys - keys that must exist
     * @throws InvalidArgumentException if key doesn't exist
     */
    public static function IsEssential($array, $keys) {
        foreach ($keys as $key) {
            if (!isset($array[$key]) && !array_key_exists($key, $array)) {
                throw new InvalidArgumentException("Required parameter missing: {$key}");
            }
        }
    }

    /**
     * Check if specified keys are not null
     * @param array $array - array to check
     * @param array $keys - keys that must not be null
     * @throws InvalidArgumentException if value is null
     */
    public static function IsNotNull($array, $keys) {
        foreach ($keys as $key) {
            if (isset($array[$key]) && is_null($array[$key])) {
                throw new InvalidArgumentException("Parameter cannot be null: {$key}");
            }
        }
    }

    /**
     * Check if specified keys are scalar values
     * @param array $array - array to check
     * @param array $keys - keys that must be scalar
     * @throws InvalidArgumentException if value is not scalar
     */
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
