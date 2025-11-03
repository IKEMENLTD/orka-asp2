<?php
require_once __DIR__ . '/Icon.php';

/**
 * AFADDraw Class - AFAD-specific component generation
 */
class AFADDraw {
    /**
     * Draw AFAD Status Badge
     *
     * @param string $status Status (pending, sent, failed, retry, timeout, skip)
     * @param string $text Badge text (optional, defaults to status)
     * @return string Status badge HTML
     */
    public static function statusBadge($status, $text = '') {
        $displayText = empty($text) ? ucfirst($status) : $text;
        return "<span class=\"afad-status afad-status-{$status}\">{$displayText}</span>";
    }

    /**
     * Draw AFAD Stats Card
     *
     * @param string $title Card title
     * @param string $period Period (e.g., "今月", "今日", "合計")
     * @param array $stats Stats array [['label' => 'Sent', 'value' => 100, 'rate' => '85%', 'type' => 'success'], ...]
     * @return string Stats card HTML
     */
    public static function statsCard($title, $period, $stats) {
        $html = '<div class="afad-stats-card">';
        $html .= '<div class="afad-stats-header">';
        $html .= '<h3 class="afad-stats-title">' . htmlspecialchars($title) . '</h3>';
        $html .= '<span class="afad-stats-period">' . htmlspecialchars($period) . '</span>';
        $html .= '</div>';

        $html .= '<div class="afad-stats-grid">';

        foreach ($stats as $stat) {
            $rateClass = isset($stat['type']) && $stat['type'] === 'success' ? 'afad-success-rate' : 'afad-failure-rate';

            $html .= '<div class="afad-stat-item">';
            $html .= '<div class="afad-stat-label">' . htmlspecialchars($stat['label']) . '</div>';
            $html .= '<div class="afad-stat-value">' . htmlspecialchars($stat['value']) . '</div>';

            if (isset($stat['rate'])) {
                $html .= '<div class="afad-stat-rate ' . $rateClass . '">' . htmlspecialchars($stat['rate']) . '</div>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Draw AFAD Retry Queue
     *
     * @param array $items Retry queue items
     * @return string Retry queue HTML
     */
    public static function retryQueue($items) {
        $count = count($items);

        $html = '<div class="afad-retry-queue">';
        $html .= '<div class="afad-retry-header">';
        $html .= '<span class="afad-retry-title">' . Icon::get('refresh', '', 20) . ' リトライキュー</span>';
        $html .= '<span class="afad-retry-count">' . $count . ' 件</span>';
        $html .= '</div>';

        if (empty($items)) {
            $html .= '<div class="afad-retry-item">';
            $html .= '<p>リトライ待ちの項目はありません。</p>';
            $html .= '</div>';
        } else {
            foreach ($items as $item) {
                $html .= '<div class="afad-retry-item">';

                $html .= '<div class="afad-retry-info">';
                $html .= '<span class="afad-retry-id">' . htmlspecialchars($item['id'] ?? 'N/A') . '</span>';
                $html .= '<div class="afad-retry-attempts">試行回数: ' . htmlspecialchars($item['attempts'] ?? 0) . '</div>';

                if (isset($item['next_retry'])) {
                    $html .= '<div class="afad-retry-next">次回: ' . htmlspecialchars($item['next_retry']) . '</div>';
                }

                $html .= '</div>';

                $html .= '<div class="afad-retry-actions">';
                $html .= '<button class="btn btn-sm btn-primary" onclick="retryNow(\'' . htmlspecialchars($item['id']) . '\')">今すぐリトライ</button>';
                $html .= '<button class="btn btn-sm btn-ghost" onclick="skipRetry(\'' . htmlspecialchars($item['id']) . '\')">スキップ</button>';
                $html .= '</div>';

                $html .= '</div>';
            }
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Draw AFAD Log Viewer
     *
     * @param array $logs Log entries
     * @param array $filters Filter options (optional)
     * @return string Log viewer HTML
     */
    public static function logViewer($logs, $filters = []) {
        $html = '<div class="afad-log-viewer">';

        // Filters
        if (!empty($filters)) {
            $html .= '<div class="afad-log-filters">';

            foreach ($filters as $filter) {
                $html .= '<select class="form-select" onchange="filterLogs(this.value)">';
                $html .= '<option value="">' . htmlspecialchars($filter['label']) . '</option>';

                foreach ($filter['options'] as $value => $text) {
                    $html .= '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($text) . '</option>';
                }

                $html .= '</select>';
            }

            $html .= '</div>';
        }

        // Logs
        if (empty($logs)) {
            $html .= '<div class="afad-log-item">';
            $html .= '<p>ログエントリがありません。</p>';
            $html .= '</div>';
        } else {
            foreach ($logs as $log) {
                $levelClass = 'afad-log-level-' . ($log['level'] ?? 'info');

                $html .= '<div class="afad-log-item">';
                $html .= '<span class="afad-log-timestamp">' . htmlspecialchars($log['timestamp'] ?? '') . '</span>';
                $html .= '<span class="afad-log-level ' . $levelClass . '">' . strtoupper($log['level'] ?? 'INFO') . '</span>';
                $html .= '<span class="afad-log-message">' . htmlspecialchars($log['message'] ?? '') . '</span>';

                if (isset($log['details'])) {
                    $html .= '<div class="afad-log-details">' . htmlspecialchars($log['details']) . '</div>';
                }

                $html .= '</div>';
            }
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Draw AFAD Config Panel
     *
     * @param array $sections Configuration sections
     * @return string Config panel HTML
     */
    public static function configPanel($sections) {
        $html = '<div class="afad-config-panel">';

        foreach ($sections as $section) {
            $html .= '<div class="afad-config-section">';
            $html .= '<h3 class="afad-config-section-title">' . htmlspecialchars($section['title']) . '</h3>';

            if (isset($section['fields']) && !empty($section['fields'])) {
                $html .= '<div class="afad-config-grid">';

                foreach ($section['fields'] as $field) {
                    $html .= '<div class="afad-config-field">';
                    $html .= '<label class="afad-config-label">' . htmlspecialchars($field['label']) . '</label>';

                    switch ($field['type']) {
                        case 'text':
                            $value = htmlspecialchars($field['value'] ?? '');
                            $html .= "<input type=\"text\" name=\"{$field['name']}\" value=\"{$value}\" class=\"form-input\">";
                            break;

                        case 'number':
                            $value = htmlspecialchars($field['value'] ?? '');
                            $html .= "<input type=\"number\" name=\"{$field['name']}\" value=\"{$value}\" class=\"form-input\">";
                            break;

                        case 'select':
                            $html .= "<select name=\"{$field['name']}\" class=\"form-select\">";
                            foreach ($field['options'] as $optValue => $optText) {
                                $selected = ($optValue == ($field['value'] ?? '')) ? 'selected' : '';
                                $html .= "<option value=\"{$optValue}\" {$selected}>" . htmlspecialchars($optText) . "</option>";
                            }
                            $html .= '</select>';
                            break;

                        case 'checkbox':
                            $checked = ($field['value'] ?? false) ? 'checked' : '';
                            $html .= "<input type=\"checkbox\" name=\"{$field['name']}\" {$checked} class=\"form-check-input\">";
                            break;

                        case 'textarea':
                            $value = htmlspecialchars($field['value'] ?? '');
                            $html .= "<textarea name=\"{$field['name']}\" class=\"form-textarea\">{$value}</textarea>";
                            break;
                    }

                    if (isset($field['description'])) {
                        $html .= '<span class="afad-config-description">' . htmlspecialchars($field['description']) . '</span>';
                    }

                    $html .= '</div>';
                }

                $html .= '</div>';
            }

            $html .= '</div>';
        }

        // Actions
        $html .= '<div class="afad-config-actions">';
        $html .= '<button type="submit" class="btn btn-primary">' . Icon::get('check', '', 16) . ' 設定を保存</button>';
        $html .= '<button type="button" class="btn btn-ghost" onclick="location.reload()">' . Icon::get('refresh', '', 16) . ' リセット</button>';
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Draw AFAD Dashboard Summary
     *
     * @param array $summary Summary data
     * @return string Dashboard summary HTML
     */
    public static function dashboardSummary($summary) {
        $html = '<div class="dashboard-stats">';

        $items = [
            ['label' => '送信成功', 'value' => $summary['sent'] ?? 0, 'icon' => 'check', 'type' => 'success'],
            ['label' => '送信待ち', 'value' => $summary['pending'] ?? 0, 'icon' => 'clock', 'type' => 'info'],
            ['label' => '失敗', 'value' => $summary['failed'] ?? 0, 'icon' => 'error', 'type' => 'error'],
            ['label' => 'リトライ', 'value' => $summary['retry'] ?? 0, 'icon' => 'refresh', 'type' => 'warning'],
        ];

        foreach ($items as $item) {
            $html .= '<div class="stat-card">';
            $html .= Icon::get($item['icon'], 'stat-icon', 32);
            $html .= '<div class="stat-label">' . htmlspecialchars($item['label']) . '</div>';
            $html .= '<div class="stat-value">' . number_format($item['value']) . '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Draw AFAD Postback Table
     *
     * @param array $postbacks Postback records
     * @param bool $showActions Show action buttons
     * @return string Postback table HTML
     */
    public static function postbackTable($postbacks, $showActions = true) {
        $html = '<div class="table-wrapper">';
        $html .= '<table class="table table-striped">';
        $html .= '<thead><tr>';
        $html .= '<th>ID</th>';
        $html .= '<th>タイムスタンプ</th>';
        $html .= '<th>ステータス</th>';
        $html .= '<th>URL</th>';
        $html .= '<th>レスポンス</th>';

        if ($showActions) {
            $html .= '<th>アクション</th>';
        }

        $html .= '</tr></thead><tbody>';

        if (empty($postbacks)) {
            $colspan = $showActions ? 6 : 5;
            $html .= "<tr><td colspan=\"{$colspan}\">ポストバック記録がありません。</td></tr>";
        } else {
            foreach ($postbacks as $pb) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($pb['id'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($pb['timestamp'] ?? '') . '</td>';
                $html .= '<td>' . self::statusBadge($pb['status'] ?? 'unknown') . '</td>';
                $html .= '<td><code>' . htmlspecialchars(substr($pb['url'] ?? '', 0, 50)) . '...</code></td>';
                $html .= '<td>' . htmlspecialchars($pb['response'] ?? 'N/A') . '</td>';

                if ($showActions) {
                    $html .= '<td>';
                    $html .= '<button class="btn btn-sm btn-outline" onclick="viewDetails(\'' . htmlspecialchars($pb['id']) . '\')">' . Icon::get('info', '', 14) . '</button> ';
                    $html .= '<button class="btn btn-sm btn-primary" onclick="retryPostback(\'' . htmlspecialchars($pb['id']) . '\')">' . Icon::get('refresh', '', 14) . '</button>';
                    $html .= '</td>';
                }

                $html .= '</tr>';
            }
        }

        $html .= '</tbody></table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Draw AFAD Connection Status
     *
     * @param bool $isConnected Connection status
     * @param string $lastCheck Last check timestamp
     * @return string Connection status HTML
     */
    public static function connectionStatus($isConnected, $lastCheck = '') {
        $statusClass = $isConnected ? 'alert-success' : 'alert-error';
        $statusIcon = $isConnected ? 'check' : 'error';
        $statusText = $isConnected ? '接続正常' : '接続エラー';

        $html = '<div class="alert ' . $statusClass . '">';
        $html .= Icon::get($statusIcon, '', 20);
        $html .= ' <span class="alert-title">' . $statusText . '</span>';

        if (!empty($lastCheck)) {
            $html .= '<p>最終確認: ' . htmlspecialchars($lastCheck) . '</p>';
        }

        $html .= '</div>';

        return $html;
    }
}
?>
