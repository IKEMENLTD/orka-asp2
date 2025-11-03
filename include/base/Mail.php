<?php
/**
 * Mail Class - Email sending
 */
class Mail {
    public static function send($to, $subject, $message, $headers = '') {
        return mail($to, $subject, $message, $headers);
    }
}
?>
