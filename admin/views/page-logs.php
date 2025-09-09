<?php
/**
 * Debug Logs Page Template
 *
 * @package WP Debug Manager
 * @author WPDMGR Team
 * @license GPL v3 or later
 * @link https://github.com/sadewadee/wp-debug-manager
 */

if (!defined('ABSPATH')) {
    exit;
}


$plugin = WPDMGR_Plugin::get_instance();
$debug_service = $plugin->get_service('debug');
$debug_status = $debug_service->get_debug_status();
?>

<div class="wrap">
    <h1><?php esc_html_e('Debug Logs', 'wp-debug-manager'); ?></h1>
    <p class="description">
        <?php esc_html_e('View dan manage WordPress debug logs.', 'wp-debug-manager'); ?>
    </p>

    <div class="wpdmgr-logs-header">
        <div class="wpdmgr-logs-actions">
            <button type="button" id="refresh-logs" class="button">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('Refresh', 'wp-debug-manager'); ?>
            </button>
            <button type="button" id="clear-logs" class="button">
                <span class="dashicons dashicons-trash"></span>
                <?php esc_html_e('Clear', 'wp-debug-manager'); ?>
            </button>
            <button type="button" id="download-logs" class="button">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Download', 'wp-debug-manager'); ?>
            </button>
        </div>

        <div class="wpdmgr-logs-info">
            <?php if ($debug_status['log_file_exists']): ?>
            <span class="wpdmgr-log-size">
                <span class="dashicons dashicons-media-text"></span>
                <?php esc_html_e('File Size:', 'wp-debug-manager'); ?> <?php echo esc_html($debug_status['log_file_size']); ?>
            </span>
            <?php else: ?>
            <span class="wpdmgr-no-logs">
                <span class="dashicons dashicons-info"></span>
                <?php esc_html_e('No log file found', 'wp-debug-manager'); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="wpdmgr-logs-filters">
        <div class="wpdmgr-filter-group">
            <label for="log-level-filter"><?php esc_html_e('Level:', 'wp-debug-manager'); ?></label>
            <select id="log-level-filter">
                <option value=""><?php esc_html_e('All Levels', 'wp-debug-manager'); ?></option>
                <option value="ERROR"><?php esc_html_e('Error', 'wp-debug-manager'); ?></option>
                <option value="WARNING"><?php esc_html_e('Warning', 'wp-debug-manager'); ?></option>
                <option value="NOTICE"><?php esc_html_e('Notice', 'wp-debug-manager'); ?></option>
                <option value="DEPRECATED"><?php esc_html_e('Deprecated', 'wp-debug-manager'); ?></option>
            </select>
        </div>

        <div class="wpdmgr-filter-group">
            <label for="log-time-filter"><?php esc_html_e('Time:', 'wp-debug-manager'); ?></label>
            <select id="log-time-filter">
                <option value=""><?php esc_html_e('All Time', 'wp-debug-manager'); ?></option>
                <option value="1h"><?php esc_html_e('Last Hour', 'wp-debug-manager'); ?></option>
                <option value="24h" selected><?php esc_html_e('Last 24 Hours', 'wp-debug-manager'); ?></option>
                <option value="7d"><?php esc_html_e('Last 7 Days', 'wp-debug-manager'); ?></option>
            </select>
        </div>

        <div class="wpdmgr-filter-group">
            <label for="log-search"><?php esc_html_e('Search:', 'wp-debug-manager'); ?></label>
            <input type="text" id="log-search" placeholder="<?php esc_attr_e('Search logs...', 'wp-debug-manager'); ?>">
        </div>
    </div>

    <div class="wpdmgr-logs-container">
        <div id="wpdmgr-logs-viewer" class="wpdmgr-logs-viewer">
            <?php if (!$debug_status['log_file_exists']): ?>
            <div class="wpdmgr-no-logs-message">
                <div class="dashicons dashicons-info"></div>
                <h3><?php esc_html_e('No Debug Logs Found', 'wp-debug-manager'); ?></h3>
                <p><?php esc_html_e('Debug logging is not enabled or no errors have been logged yet.', 'wp-debug-manager'); ?></p>
                <?php if (!$debug_status['enabled']): ?>
                <p>
                    <a href="<?php echo admin_url('tools.php?page=wpdmgr-toolkit'); ?>" class="button button-primary">
                        <?php esc_html_e('Enable Debug Mode', 'wp-debug-manager'); ?>
                    </a>
                </p>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="wpdmgr-logs-loading">
                <div class="wpdmgr-spinner"></div>
                <p><?php esc_html_e('Loading logs...', 'wp-debug-manager'); ?></p>
            </div>
            <div id="wpdmgr-logs-content" style="display: none;"></div>
            <?php endif; ?>
        </div>

        <div id="wpdmgr-logs-pagination" class="wpdmgr-logs-pagination" style="display: none;">
            <div class="wpdmgr-pagination-info">
                <span id="logs-showing-info"></span>
            </div>
            <div class="wpdmgr-pagination-controls">
                <button type="button" id="logs-prev-page" class="button" disabled>
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    <?php esc_html_e('Previous', 'wp-debug-manager'); ?>
                </button>
                <span id="logs-page-info"></span>
                <button type="button" id="logs-next-page" class="button">
                    <?php esc_html_e('Next', 'wp-debug-manager'); ?>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-load logs when page loads
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('wpdmgr-logs-content')) {
        loadDebugLogs();
    }
});

function loadDebugLogs() {
    const logsContent = document.getElementById('wpdmgr-logs-content');
    const logsLoading = document.querySelector('.wpdmgr-logs-loading');

    if (!logsContent) return;

    logsLoading.style.display = 'block';
    logsContent.style.display = 'none';

    jQuery.post(ajaxurl, {
        action: 'wpdmgr_get_debug_log',
        nonce: wpdmgrToolkit.nonce
    }, function(response) {
        logsLoading.style.display = 'none';

        if (response.success && response.data) {
            displayLogs(response.data);
            logsContent.style.display = 'block';
        } else {
            logsContent.innerHTML = '<div class="notice notice-error"><p>' + wpdmgrToolkit.strings.error_occurred + '</p></div>';
            logsContent.style.display = 'block';
        }
    });
}

function displayLogs(logs) {
    const logsContent = document.getElementById('wpdmgr-logs-content');

    if (!logs || logs.length === 0) {
        logsContent.innerHTML = '<div class="wpdmgr-no-logs-message"><p>' + <?php echo wp_json_encode(__('No log entries found.', 'wp-debug-manager')); ?> + '</p></div>';
        return;
    }

    let html = '<div class="wpdmgr-logs-list">';

    logs.forEach(function(log) {
        const levelClass = 'log-level-' + log.level.toLowerCase();
        const timeFormatted = new Date(log.timestamp).toLocaleString();

        html += '<div class="wpdmgr-log-entry ' + levelClass + '">';
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
