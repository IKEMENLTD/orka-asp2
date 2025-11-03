<?php
/**
 * GMList Class - Global Master List
 */
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

// Alias for case sensitivity
class GMlist extends GMList {}
?>
