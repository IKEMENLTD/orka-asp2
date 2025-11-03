<?php
require_once __DIR__ . '/Icon.php';

/**
 * Draw Class - HTML utility methods for responsive purple theme
 */
class Draw {
    /**
     * Legacy Head method (for backward compatibility)
     */
    public static function Head($sqlMaster) {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Setup</title><link rel="stylesheet" href="/css/main.css"></head><body>';
    }

    /**
     * SQL Connection Error
     */
    public static function SQLConnectError() {
        echo '<div class="section">';
        echo '<div class="alert alert-error">';
        echo Icon::get('error', '', 24);
        echo ' <span class="alert-title">Database Connection Error</span>';
        echo '<p>Could not connect to the database. Please check your configuration.</p>';
        echo '</div>';
        echo '</body></html>';
    }

    /**
     * Draw Button
     *
     * @param string $text Button text
     * @param string $href Button link
     * @param string $type Button type (primary, secondary, success, danger, outline, ghost)
     * @param string $size Button size (sm, md, lg)
     * @param string $icon Icon name (optional)
     * @return string Button HTML
     */
    public static function button($text, $href = '#', $type = 'primary', $size = 'md', $icon = '') {
        $iconHtml = empty($icon) ? '' : Icon::inline($icon);
        $classes = "btn btn-{$type} btn-{$size}";

        if (!empty($href) && $href !== '#') {
            return "<a href=\"{$href}\" class=\"{$classes}\">{$iconHtml}" . htmlspecialchars($text) . "</a>";
        } else {
            return "<button class=\"{$classes}\">{$iconHtml}" . htmlspecialchars($text) . "</button>";
        }
    }

    /**
     * Draw Alert
     *
     * @param string $message Alert message
     * @param string $type Alert type (success, warning, error, info)
     * @param string $title Alert title (optional)
     * @return string Alert HTML
     */
    public static function alert($message, $type = 'info', $title = '') {
        $iconMap = [
            'success' => 'check',
            'warning' => 'alert',
            'error' => 'error',
            'info' => 'info'
        ];

        $icon = $iconMap[$type] ?? 'info';
        $iconHtml = Icon::get($icon, '', 20);

        $titleHtml = empty($title) ? '' : '<span class="alert-title">' . htmlspecialchars($title) . '</span>';

        return <<<HTML
<div class="alert alert-{$type}">
    {$iconHtml}
    {$titleHtml}
    <p>{$message}</p>
</div>
HTML;
    }

    /**
     * Draw Card
     *
     * @param string $title Card title
     * @param string $content Card content
     * @param string $footer Card footer (optional)
     * @return string Card HTML
     */
    public static function card($title, $content, $footer = '') {
        $footerHtml = empty($footer) ? '' : "<div class=\"card-footer\">{$footer}</div>";

        return <<<HTML
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{$title}</h3>
    </div>
    <div class="card-body">
        {$content}
    </div>
    {$footerHtml}
</div>
HTML;
    }

    /**
     * Draw Badge
     *
     * @param string $text Badge text
     * @param string $type Badge type (primary, success, warning, danger, info, gray)
     * @return string Badge HTML
     */
    public static function badge($text, $type = 'primary') {
        return "<span class=\"badge badge-{$type}\">" . htmlspecialchars($text) . "</span>";
    }

    /**
     * Draw Table Start
     *
     * @param array $columns Column headers
     * @param array $options Table options (striped, bordered, compact)
     * @return string Table HTML start
     */
    public static function tableStart($columns, $options = []) {
        $classes = ['table'];
        if (isset($options['striped']) && $options['striped']) $classes[] = 'table-striped';
        if (isset($options['bordered']) && $options['bordered']) $classes[] = 'table-bordered';
        if (isset($options['compact']) && $options['compact']) $classes[] = 'table-compact';

        $classStr = implode(' ', $classes);

        $html = '<div class="table-wrapper">';
        $html .= "<table class=\"{$classStr}\">";
        $html .= '<thead><tr>';

        foreach ($columns as $column) {
            $html .= '<th>' . htmlspecialchars($column) . '</th>';
        }

        $html .= '</tr></thead>';
        $html .= '<tbody>';

        return $html;
    }

    /**
     * Draw Table Row
     *
     * @param array $cells Cell data
     * @return string Table row HTML
     */
    public static function tableRow($cells) {
        $html = '<tr>';

        foreach ($cells as $cell) {
            $html .= '<td>' . htmlspecialchars($cell) . '</td>';
        }

        $html .= '</tr>';

        return $html;
    }

    /**
     * Draw Table End
     *
     * @return string Table HTML end
     */
    public static function tableEnd() {
        return '</tbody></table></div>';
    }

    /**
     * Draw Form Input
     *
     * @param string $name Input name
     * @param string $label Input label
     * @param string $type Input type
     * @param array $options Input options (required, value, placeholder, class)
     * @return string Form input HTML
     */
    public static function formInput($name, $label, $type = 'text', $options = []) {
        $required = isset($options['required']) && $options['required'] ? 'required' : '';
        $requiredClass = isset($options['required']) && $options['required'] ? 'form-label-required' : '';
        $value = isset($options['value']) ? 'value="' . htmlspecialchars($options['value']) . '"' : '';
        $placeholder = isset($options['placeholder']) ? 'placeholder="' . htmlspecialchars($options['placeholder']) . '"' : '';
        $class = isset($options['class']) ? htmlspecialchars($options['class']) : '';

        return <<<HTML
<div class="form-group">
    <label for="{$name}" class="form-label {$requiredClass}">{$label}</label>
    <input type="{$type}" id="{$name}" name="{$name}" class="form-input {$class}" {$value} {$placeholder} {$required}>
</div>
HTML;
    }

    /**
     * Draw Form Select
     *
     * @param string $name Select name
     * @param string $label Select label
     * @param array $options Select options (key => value)
     * @param string $selected Selected value
     * @param bool $required Required flag
     * @return string Form select HTML
     */
    public static function formSelect($name, $label, $options, $selected = '', $required = false) {
        $requiredAttr = $required ? 'required' : '';
        $requiredClass = $required ? 'form-label-required' : '';

        $html = <<<HTML
<div class="form-group">
    <label for="{$name}" class="form-label {$requiredClass}">{$label}</label>
    <select id="{$name}" name="{$name}" class="form-select" {$requiredAttr}>
HTML;

        foreach ($options as $value => $text) {
            $selectedAttr = ($value == $selected) ? 'selected' : '';
            $html .= "<option value=\"{$value}\" {$selectedAttr}>" . htmlspecialchars($text) . "</option>";
        }

        $html .= '</select></div>';

        return $html;
    }

    /**
     * Draw Form Textarea
     *
     * @param string $name Textarea name
     * @param string $label Textarea label
     * @param string $value Textarea value
     * @param bool $required Required flag
     * @return string Form textarea HTML
     */
    public static function formTextarea($name, $label, $value = '', $required = false) {
        $requiredAttr = $required ? 'required' : '';
        $requiredClass = $required ? 'form-label-required' : '';

        return <<<HTML
<div class="form-group">
    <label for="{$name}" class="form-label {$requiredClass}">{$label}</label>
    <textarea id="{$name}" name="{$name}" class="form-textarea" {$requiredAttr}>{$value}</textarea>
</div>
HTML;
    }

    /**
     * Draw Pagination
     *
     * @param int $currentPage Current page number
     * @param int $totalPages Total number of pages
     * @param string $baseUrl Base URL for pagination links
     * @return string Pagination HTML
     */
    public static function pagination($currentPage, $totalPages, $baseUrl) {
        if ($totalPages <= 1) return '';

        $html = '<div class="pagination">';

        // Previous button
        if ($currentPage > 1) {
            $prevPage = $currentPage - 1;
            $html .= "<a href=\"{$baseUrl}?page={$prevPage}\" class=\"pagination-item\">" . Icon::get('arrow-left', '', 16) . "</a>";
        } else {
            $html .= "<span class=\"pagination-item\" disabled>" . Icon::get('arrow-left', '', 16) . "</span>";
        }

        // Page numbers
        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);

        if ($start > 1) {
            $html .= "<a href=\"{$baseUrl}?page=1\" class=\"pagination-item\">1</a>";
            if ($start > 2) {
                $html .= "<span class=\"pagination-item\">...</span>";
            }
        }

        for ($i = $start; $i <= $end; $i++) {
            $activeClass = ($i == $currentPage) ? 'is-active' : '';
            $html .= "<a href=\"{$baseUrl}?page={$i}\" class=\"pagination-item {$activeClass}\">{$i}</a>";
        }

        if ($end < $totalPages) {
            if ($end < $totalPages - 1) {
                $html .= "<span class=\"pagination-item\">...</span>";
            }
            $html .= "<a href=\"{$baseUrl}?page={$totalPages}\" class=\"pagination-item\">{$totalPages}</a>";
        }

        // Next button
        if ($currentPage < $totalPages) {
            $nextPage = $currentPage + 1;
            $html .= "<a href=\"{$baseUrl}?page={$nextPage}\" class=\"pagination-item\">" . Icon::get('arrow-right', '', 16) . "</a>";
        } else {
            $html .= "<span class=\"pagination-item\" disabled>" . Icon::get('arrow-right', '', 16) . "</span>";
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Draw Modal
     *
     * @param string $id Modal ID
     * @param string $title Modal title
     * @param string $content Modal content
     * @param string $footer Modal footer (optional)
     * @return string Modal HTML
     */
    public static function modal($id, $title, $content, $footer = '') {
        $footerHtml = empty($footer) ? '' : "<div class=\"modal-footer\">{$footer}</div>";

        return <<<HTML
<div class="modal-backdrop" id="{$id}" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">{$title}</h3>
            <button class="modal-close" onclick="document.getElementById('{$id}').style.display='none'">
                &times;
            </button>
        </div>
        <div class="modal-body">
            {$content}
        </div>
        {$footerHtml}
    </div>
</div>
HTML;
    }

    /**
     * Draw Spinner/Loading
     *
     * @param string $size Spinner size (sm, md, lg)
     * @return string Spinner HTML
     */
    public static function spinner($size = 'md') {
        $sizeClass = $size === 'md' ? '' : "spinner-{$size}";
        return "<div class=\"spinner {$sizeClass}\"></div>";
    }

    /**
     * Draw Breadcrumb
     *
     * @param array $items Breadcrumb items [['text' => 'Home', 'url' => '/'], ...]
     * @return string Breadcrumb HTML
     */
    public static function breadcrumb($items) {
        $html = '<nav class="breadcrumb" aria-label="breadcrumb"><ol>';

        $count = count($items);
        foreach ($items as $index => $item) {
            $isLast = ($index === $count - 1);

            if ($isLast) {
                $html .= '<li class="breadcrumb-item active">' . htmlspecialchars($item['text']) . '</li>';
            } else {
                $html .= '<li class="breadcrumb-item"><a href="' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['text']) . '</a></li>';
            }
        }

        $html .= '</ol></nav>';

        return $html;
    }

    /**
     * Draw Section
     *
     * @param string $title Section title
     * @param string $content Section content
     * @return string Section HTML
     */
    public static function section($title, $content) {
        return <<<HTML
<div class="section">
    <div class="section-header">
        <h2 class="section-title">{$title}</h2>
    </div>
    {$content}
</div>
HTML;
    }

    /**
     * Draw Grid Container
     *
     * @param array $items Grid items (HTML strings)
     * @param int $columns Number of columns (1, 2, 3, 4, 6, 12)
     * @return string Grid HTML
     */
    public static function grid($items, $columns = 3) {
        $html = "<div class=\"grid grid-cols-{$columns}\">";

        foreach ($items as $item) {
            $html .= "<div>{$item}</div>";
        }

        $html .= '</div>';

        return $html;
    }
}
?>
