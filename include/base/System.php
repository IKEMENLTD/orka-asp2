<?php
/**
 * System Class - Basic HTML output functions
 */
class System {
    public static function getHead($gm, $loginUserType, $loginUserRank) {
        $title = $gm['system']['SITE_NAME'] ?? 'orka-asp2';
        $charset = 'UTF-8';

        return <<<HTML
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="{$charset}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .error { color: red; padding: 10px; background: #fee; border: 1px solid red; }
        .success { color: green; padding: 10px; background: #efe; border: 1px solid green; }
    </style>
</head>
<body>
<div class="container">
HTML;
    }

    public static function getFoot($gm, $loginUserType, $loginUserRank) {
        return <<<HTML
</div>
<footer style="margin-top: 50px; padding: 20px; border-top: 1px solid #ccc; text-align: center;">
    <p>&copy; 2025 orka-asp2</p>
</footer>
</body>
</html>
HTML;
    }
}
?>
