<?php
/**
 * ExceptionManager Class - Exception display
 */
class ExceptionManager {
    public static function DrawErrorPage($className) {
        http_response_code(500);
        echo '<!DOCTYPE html><html><head><title>Error</title></head><body>';
        echo '<h1>Application Error</h1>';
        echo '<p>An error occurred. Please contact the administrator.</p>';
        echo '<p>Error type: ' . htmlspecialchars($className) . '</p>';
        echo '</body></html>';
        exit;
    }
}
?>
