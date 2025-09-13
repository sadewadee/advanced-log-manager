<?php
/**
 * Debug Logs Page Template
 *
 * @package Advance Log Manager -
 * @author Morden Team
 * @license GPL v3 or later
 * @link https://github.com/sadewadee/advanced-log-manager
 */

if (!defined('ABSPATH')) {
    exit;
}


$plugin = ALMGR_Plugin::get_instance();
$debug_service = $plugin->get_service('debug');
$debug_status = $debug_service->get_debug_status();
?>

<div class="wrap">
    <h1><?php esc_html_e('Debug Logs', 'advanced-log-manager'); ?></h1>
    <p class="description">
        <?php esc_html_e('View dan manage WordPress debug logs.', 'advanced-log-manager'); ?>
    </p>

    <div class="almgr-logs-header">
        <div class="almgr-logs-actions">
            <button type="button" id="refresh-logs" class="button">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('Refresh', 'advanced-log-manager'); ?>
            </button>
            <button type="button" id="clear-logs" class="button">
                <span class="dashicons dashicons-trash"></span>
                <?php esc_html_e('Clear', 'advanced-log-manager'); ?>
            </button>
            <button type="button" id="download-logs" class="button">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Download', 'advanced-log-manager'); ?>
            </button>
        </div>

        <div class="almgr-logs-info">
            <?php if ($debug_status['log_file_exists']): ?>
            <span class="almgr-log-size">
                <span class="dashicons dashicons-media-text"></span>
                <?php esc_html_e('File Size:', 'advanced-log-manager'); ?> <?php echo esc_html($debug_status['log_file_size']); ?>
            </span>
            <?php else: ?>
            <span class="almgr-no-logs">
                <span class="dashicons dashicons-info"></span>
                <?php esc_html_e('No log file found', 'advanced-log-manager'); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="almgr-logs-filters">
        <div class="almgr-filter-row">
            <div class="almgr-filter-group">
                <label for="log-level-filter"><?php esc_html_e('Level:', 'advanced-log-manager'); ?></label>
                <select id="log-level-filter">
                    <option value=""><?php esc_html_e('All Levels', 'advanced-log-manager'); ?></option>
                    <option value="ERROR"><?php esc_html_e('Error', 'advanced-log-manager'); ?></option>
                    <option value="WARNING"><?php esc_html_e('Warning', 'advanced-log-manager'); ?></option>
                    <option value="NOTICE"><?php esc_html_e('Notice', 'advanced-log-manager'); ?></option>
                    <option value="DEPRECATED"><?php esc_html_e('Deprecated', 'advanced-log-manager'); ?></option>
                </select>
            </div>

            <div class="almgr-filter-group">
                <label for="log-time-filter"><?php esc_html_e('Time:', 'advanced-log-manager'); ?></label>
                <select id="log-time-filter">
                    <option value=""><?php esc_html_e('All Time', 'advanced-log-manager'); ?></option>
                    <option value="1h"><?php esc_html_e('Last Hour', 'advanced-log-manager'); ?></option>
                    <option value="24h" selected><?php esc_html_e('Last 24 Hours', 'advanced-log-manager'); ?></option>
                    <option value="7d"><?php esc_html_e('Last 7 Days', 'advanced-log-manager'); ?></option>
                    <option value="30d"><?php esc_html_e('Last 30 Days', 'advanced-log-manager'); ?></option>
                </select>
            </div>

            <div class="almgr-filter-group">
                <label for="log-search"><?php esc_html_e('Search:', 'advanced-log-manager'); ?></label>
                <input type="text" id="log-search" placeholder="<?php esc_attr_e('Search logs...', 'advanced-log-manager'); ?>">
            </div>

            <div class="almgr-filter-group">
                <label for="log-file-filter"><?php esc_html_e('File:', 'advanced-log-manager'); ?></label>
                <input type="text" id="log-file-filter" placeholder="<?php esc_attr_e('Filter by file path...', 'advanced-log-manager'); ?>">
            </div>
        </div>

        <div class="almgr-filter-row almgr-advanced-options">
            <div class="almgr-filter-group almgr-checkbox-group">
                <label class="almgr-checkbox-label">
                    <input type="checkbox" id="regex-search-toggle">
                    <span class="almgr-checkbox-text"><?php esc_html_e('Regex Search', 'advanced-log-manager'); ?></span>
                </label>
            </div>

            <div class="almgr-filter-group almgr-checkbox-group">
                <label class="almgr-checkbox-label">
                    <input type="checkbox" id="case-sensitive-toggle">
                    <span class="almgr-checkbox-text"><?php esc_html_e('Case Sensitive', 'advanced-log-manager'); ?></span>
                </label>
            </div>

            <div class="almgr-filter-actions">
                <button type="button" id="clear-filters" class="button">
                    <span class="dashicons dashicons-dismiss"></span>
                    <?php esc_html_e('Clear Filters', 'advanced-log-manager'); ?>
                </button>
                <button type="button" id="export-filtered" class="button">
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e('Export Filtered', 'advanced-log-manager'); ?>
                </button>
            </div>
        </div>

        <div class="almgr-filter-results">
            <span id="filter-results-counter"><?php esc_html_e('Loading...', 'advanced-log-manager'); ?></span>
        </div>
    </div>

    <div class="almgr-logs-container">
        <div id="almgr-logs-viewer" class="almgr-logs-viewer">
            <?php if (!$debug_status['log_file_exists']): ?>
            <div class="almgr-no-logs-message">
                <div class="dashicons dashicons-info"></div>
                <h3><?php esc_html_e('No Debug Logs Found', 'advanced-log-manager'); ?></h3>
                <p><?php esc_html_e('Debug logging is not enabled or no errors have been logged yet.', 'advanced-log-manager'); ?></p>
                <?php if (!$debug_status['enabled']): ?>
                <p>
                    <a href="<?php echo esc_url(admin_url('tools.php?page=almgr-toolkit')); ?>" class="button button-primary">
                        <?php esc_html_e('Enable Debug Mode', 'advanced-log-manager'); ?>
                    </a>
                </p>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="almgr-logs-loading">
                <div class="almgr-spinner"></div>
                <p><?php esc_html_e('Loading logs...', 'advanced-log-manager'); ?></p>
            </div>
            <div id="almgr-logs-content" style="display: none;"></div>
            <?php endif; ?>
        </div>

        <div id="almgr-logs-pagination" class="almgr-logs-pagination" style="display: none;">
            <div class="almgr-pagination-info">
                <span id="logs-showing-info"></span>
            </div>
            <div class="almgr-pagination-controls">
                <button type="button" id="logs-prev-page" class="button" disabled>
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    <?php esc_html_e('Previous', 'advanced-log-manager'); ?>
                </button>
                <span id="logs-page-info"></span>
                <button type="button" id="logs-next-page" class="button">
                    <?php esc_html_e('Next', 'advanced-log-manager'); ?>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-load logs when page loads
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('almgr-logs-content')) {
        loadDebugLogs();
    }
});

function loadDebugLogs() {
    const logsContent = document.getElementById('almgr-logs-content');
const logsLoading = document.querySelector('#almgr-logs-viewer .almgr-logs-loading');

    if (!logsContent) return;

    logsLoading.style.display = 'block';
    logsContent.style.display = 'none';

    jQuery.post(ajaxurl, {
        action: 'almgr_get_debug_log',
        nonce: almgrToolkit.nonce
    }, function(response) {
        logsLoading.style.display = 'none';

        if (response.success && response.data) {
            displayLogs(response.data);
            logsContent.style.display = 'block';
        } else {
            logsContent.innerHTML = '<div class="notice notice-error"><p>' + almgrToolkit.strings.error_occurred + '</p></div>';
            logsContent.style.display = 'block';
        }
    });
}

function displayLogs(logs) {
    const logsContent = document.getElementById('almgr-logs-content');

    if (!logs || logs.length === 0) {
        logsContent.innerHTML = '<div class="almgr-no-logs-message"><p>' + <?php echo wp_json_encode(__('No log entries found.', 'advanced-log-manager')); ?> + '</p></div>';
        return;
    }

    let html = '<div class="almgr-logs-list">';

    logs.forEach(function(log) {
        const levelClass = 'log-level-' + log.level.toLowerCase();
        const timeFormatted = new Date(log.timestamp).toLocaleString();

        html += '<div class="almgr-log-entry ' + levelClass + '">';
        html += '<div class="log-header">';
        html += '<span class="log-level">' + log.level + '</span>';
        html += '<span class="log-time">' + timeFormatted + '</span>';
        html += '</div>';
        html += '<div class="log-message">' + escapeHtml(log.message) + '</div>';

        if (log.file) {
            html += '<div class="log-file">';
            html += '<span class="dashicons dashicons-media-code"></span>';
            html += escapeHtml(log.file);
            if (log.line) {
                html += ':' + log.line;
            }
            html += '</div>';
        }
        html += '</div>';
    });

    html += '</div>';

    logsContent.innerHTML = html;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
