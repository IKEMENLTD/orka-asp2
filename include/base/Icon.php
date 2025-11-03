<?php
/**
 * Icon Class - SVG icon rendering utility
 */
class Icon {
    /**
     * Get SVG icon HTML
     *
     * @param string $name Icon name (e.g., 'dashboard', 'chart', 'search')
     * @param string $class Additional CSS classes
     * @param int $size Icon size in pixels (default: 24)
     * @return string SVG HTML
     */
    public static function get($name, $class = '', $size = 24) {
        $classes = trim("icon icon-{$name} {$class}");

        return <<<HTML
<svg class="{$classes}" width="{$size}" height="{$size}" aria-hidden="true">
    <use href="/assets/icons/icons.svg#icon-{$name}"></use>
</svg>
HTML;
    }

    /**
     * Echo SVG icon HTML
     *
     * @param string $name Icon name
     * @param string $class Additional CSS classes
     * @param int $size Icon size in pixels (default: 24)
     */
    public static function render($name, $class = '', $size = 24) {
        echo self::get($name, $class, $size);
    }

    /**
     * Get inline SVG (for navigation items)
     *
     * @param string $name Icon name
     * @return string Inline SVG HTML with wrapper span
     */
    public static function inline($name) {
        return '<span class="nav-icon">' . self::get($name, '', 20) . '</span>';
    }
}
?>
