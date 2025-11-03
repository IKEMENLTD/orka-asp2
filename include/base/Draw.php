<?php
/**
 * Draw Class - Drawing utilities
 */
class Draw {
    public static function Head($sqlMaster) {
        echo '<!DOCTYPE html><html><head><title>Setup</title></head><body>';
    }

    public static function SQLConnectError() {
        echo '<h1>Database Connection Error</h1>';
        echo '<p>Could not connect to the database. Please check your configuration.</p>';
        echo '</body></html>';
    }
}
?>
