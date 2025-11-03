<?php
/**
 * SystemUtil Class - System utilities
 */
class SystemUtil {
    public static function getSystem($loginUserType) {
        return new stdClass();
    }

    public static function logout($loginUserType) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        return true;
    }
}
?>
