<?php
/**
 * ErrorManager Class - Error handling
 */
class ErrorManager {
    public function GetExceptionStr($exception) {
        return sprintf(
            "[%s] %s in %s:%d\nStack trace:\n%s",
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
    }

    public function OutputErrorLog($message) {
        error_log($message);
    }
}
?>
