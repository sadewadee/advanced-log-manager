<?php
/**
 * Query Logs Page Template
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
    <h1><?php esc_html_e('Query Logs', 'wp-debug-manager'); ?></h1>
    <p class="description">
        <?php esc_html_e('View database query logs. SAVEQUERIES must be enabled to record queries.', 'wp-debug-manager'); ?>
    </p>

    <?php if (!$debug_status['savequeries']): ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php esc_html_e('SAVEQUERIES is not enabled!', 'wp-debug-manager'); ?></strong>
            <?php esc_html_e('Database queries are not being recorded. ', 'wp-debug-manager'); ?>
            <a href="<?php echo admin_url('tools.php?page=wpdmgr'); ?>" class="button button-primary">
                <?php esc_html_e('Enable SAVEQUERIES', 'wp-debug-manager'); ?>
            </a>
        </p>
    </div>
    <?php endif; ?>

    <div class="wpdmgr-logs-header">
        <div class="wpdmgr-logs-actions">
            <button type="button" id="refresh-query-logs" class="button">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('Refresh', 'wp-debug-manager'); ?>
            </button>
            <button type="button" id="clear-query-logs" class="button" title="<?php esc_attr_e('Clear active query log content (empties query.log)', 'wp-debug-manager'); ?>">
                <span class="dashicons dashicons-trash"></span>
                <?php esc_html_e('Clear', 'wp-debug-manager'); ?>
            </button>
            <button type="button" id="cleanup-query-logs" class="button" title="<?php esc_attr_e('Remove rotation/archived files (query.log.1, query.log.2, etc.)', 'wp-debug-manager'); ?>">
                <span class="dashicons dashicons-admin-tools"></span>
                <?php esc_html_e('Cleanup', 'wp-debug-manager'); ?>
            </button>
            <button type="button" id="download-query-logs" class="button">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Download', 'wp-debug-manager'); ?>
            </button>
        </div>

        <div class="wpdmgr-logs-info">
            <?php if ($debug_status['query_log_file_exists']): ?>
            <span class="wpdmgr-log-size">
                <span class="dashicons dashicons-media-text"></span>
                <?php esc_html_e('Current:', 'wp-debug-manager'); ?> <?php echo esc_html($debug_status['query_log_file_size']); ?>
            </span>
            <?php if (isset($debug_status['query_log_total_size'])): ?>
            <span class="wpdmgr-log-total-size">
                <span class="dashicons dashicons-database"></span>
                <?php esc_html_e('Total (with backups):', 'wp-debug-manager'); ?> <?php echo esc_html($debug_status['query_log_total_size']); ?>
            </span>
            <?php endif; ?>
            <?php if (isset($debug_status['query_log_max_size'])): ?>
            <span class="wpdmgr-log-max-size">
                <span class="dashicons dashicons-info"></span>
                <?php esc_html_e('Rotation at:', 'wp-debug-manager'); ?> <?php echo esc_html($debug_status['query_log_max_size']); ?>
            </span>
            <?php endif; ?>
            <?php else: ?>
            <span class="wpdmgr-no-logs">
                <span class="dashicons dashicons-info"></span>
                <?php esc_html_e('No query log file found', 'wp-debug-manager'); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="wpdmgr-logs-filters">
        <div class="wpdmgr-filter-group">
            <label for="query-time-filter"><?php esc_html_e('Time:', 'wp-debug-manager'); ?></label>
            <select id="query-time-filter">
                <option value=""><?php esc_html_e('All Time', 'wp-debug-manager'); ?></option>
                <option value="1h"><?php esc_html_e('Last Hour', 'wp-debug-manager'); ?></option>
                <option value="24h" selected><?php esc_html_e('Last 24 Hours', 'wp-debug-manager'); ?></option>
                <option value="7d"><?php esc_html_e('Last 7 Days', 'wp-debug-manager'); ?></option>
            </select>
        </div>

        <div class="wpdmgr-filter-group">
            <label for="query-type-filter"><?php esc_html_e('Query Type:', 'wp-debug-manager'); ?></label>
            <select id="query-type-filter">
                <option value=""><?php esc_html_e('All Types', 'wp-debug-manager'); ?></option>
                <option value="SELECT"><?php esc_html_e('SELECT', 'wp-debug-manager'); ?></option>
                <option value="INSERT"><?php esc_html_e('INSERT', 'wp-debug-manager'); ?></option>
                <option value="UPDATE"><?php esc_html_e('UPDATE', 'wp-debug-manager'); ?></option>
                <option value="DELETE"><?php esc_html_e('DELETE', 'wp-debug-manager'); ?></option>
            </select>
        </div>

        <div class="wpdmgr-filter-group">
            <label for="query-slow-filter"><?php esc_html_e('Performance:', 'wp-debug-manager'); ?></label>
            <select id="query-slow-filter">
                <option value=""><?php esc_html_e('All Queries', 'wp-debug-manager'); ?></option>
                <option value="slow"><?php esc_html_e('Slow Queries Only', 'wp-debug-manager'); ?></option>
                <option value="fast"><?php esc_html_e('Fast Queries Only', 'wp-debug-manager'); ?></option>
            </select>
        </div>

        <div class="wpdmgr-filter-group">
            <label for="query-search"><?php esc_html_e('Search:', 'wp-debug-manager'); ?></label>
            <input type="text" id="query-search" placeholder="<?php esc_attr_e('Search in SQL or caller stack...', 'wp-debug-manager'); ?>">
        </div>

        <div class="wpdmgr-filter-group">
            <button type="button" id="clear-query-filters" class="button">
                <?php esc_html_e('Clear Filters', 'wp-debug-manager'); ?>
            </button>
        </div>
    </div>

    <div class="wpdmgr-query-logs-container">
        <div id="wpdmgr-query-logs-viewer" class="wpdmgr-logs-viewer">
            <?php if (!$debug_status['query_log_file_exists'] || !$debug_status['savequeries']): ?>
            <div class="wpdmgr-no-logs-message">
                <div class="dashicons dashicons-info"></div>
                <h3><?php esc_html_e('No Query Logs Found', 'wp-debug-manager'); ?></h3>
                <?php if (!$debug_status['savequeries']): ?>
                <p><?php esc_html_e('SAVEQUERIES is not enabled. Database queries are not being recorded.', 'wp-debug-manager'); ?></p>
                <p>
                    <a href="<?php echo admin_url('tools.php?page=wpdmgr'); ?>" class="button button-primary">
                        <?php esc_html_e('Enable SAVEQUERIES', 'wp-debug-manager'); ?>
                    </a>
                </p>
                <?php else: ?>
                <p><?php esc_html_e('No database queries have been logged yet. Try visiting some pages first.', 'wp-debug-manager'); ?></p>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="wpdmgr-logs-loading">
                <div class="wpdmgr-spinner"></div>
                <p><?php esc_html_e('Loading query logs...', 'wp-debug-manager'); ?></p>
            </div>
            <div id="wpdmgr-query-logs-content" style="display: none;"></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Helper functions for formatting
function parseUrlSource(url) {
    if (!url || typeof url !== 'string') return { name: 'Unknown', path: '' };

    // Extract source from URL
    let sourceName = 'WordPress Core';
    let sourcePath = url;

    try {
        if (url.includes('/plugins/')) {
            const pluginMatch = url.match(/\/plugins\/([^\/]+)/);
            if (pluginMatch && pluginMatch[1]) {
                sourceName = pluginMatch[1].replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                sourcePath = url.replace(/.*\/plugins\//, 'plugins/');
            }
        } else if (url.includes('/themes/')) {
            const themeMatch = url.match(/\/themes\/([^\/]+)/);
            if (themeMatch && themeMatch[1]) {
                sourceName = themeMatch[1].replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                sourcePath = url.replace(/.*\/themes\//, 'themes/');
            }
        } else if (url.includes('/wp-includes/') || url.includes('/wp-admin/')) {
            sourceName = 'WordPress Core';
            const coreMatch = url.match(/.*\/(wp-[^\/]+\/.*)/);
            if (coreMatch && coreMatch[1]) {
                sourcePath = coreMatch[1];
            }
        }
    } catch (e) {
        console.warn('Error parsing URL source:', e);
        sourceName = 'Unknown';
        sourcePath = url;
    }

    return { name: sourceName, path: sourcePath };
}

function formatCaller(caller) {
    if (!caller || typeof caller !== 'string') return '';

    // Safe HTML escape function
    function safeEscapeHtml(text) {
        if (window.wpdmgrUtils && window.wpdmgrUtils.escapeHtml) {
            return window.wpdmgrUtils.escapeHtml(text);
        }
        // Fallback HTML escape
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    try {
        // If already formatted with stack trace, enhance with markup
        if (caller.includes('()') && caller.includes('\n')) {
            return caller
                .split('\n')
                .map(line => {
                    line = line.trim();
                    if (!line) return line;

                    // Escape HTML first
                    const escapedLine = safeEscapeHtml(line);

                    // Function/method calls (lines that end with ())
                    if (line.endsWith('()')) {
                        return `<span class="function-name">${escapedLine}</span>`;
                    }
                    // File paths (lines that contain : and file extensions)
                    else if (line.includes(':') && (line.includes('.php') || line.includes('wp-'))) {
                        return `<span class="file-path">${escapedLine}</span>`;
                    }
                    return escapedLine;
                })
                .join('\n');
        }

        // Legacy format: replace commas with line breaks and clean up
        return safeEscapeHtml(caller
            .replace(/,\s*/g, '\n')
            .replace(/\s+/g, ' ')
            .trim());
    } catch (e) {
        console.warn('Error formatting caller:', e);
        return safeEscapeHtml(caller);
    }
}

/**
 * Update log info display with fresh data
 */
function updateLogInfo() {
    jQuery.post(ajaxurl, {
        action: 'wpdmgr_get_log_info',
        nonce: wpdmgrToolkit.nonce
    }, function(response) {
        if (response.success && response.data) {
            const logInfo = response.data;
            const infoElement = document.querySelector('.wpdmgr-logs-info');

            if (infoElement && logInfo.query_log_file_exists) {
                infoElement.innerHTML = `
                    <span class="wpdmgr-log-size">
                        <span class="dashicons dashicons-media-text"></span>
                        ${<?php echo wp_json_encode(__('Current:', 'wp-debug-manager')); ?>} ${logInfo.query_log_file_size}
                    </span>
                    ${logInfo.query_log_total_size ? `
                    <span class="wpdmgr-log-total-size">
                        <span class="dashicons dashicons-database"></span>
                        ${<?php echo wp_json_encode(__('Total (with backups):', 'wp-debug-manager')); ?>} ${logInfo.query_log_total_size}
                    </span>` : ''}
                    ${logInfo.query_log_max_size ? `
                    <span class="wpdmgr-log-max-size">
                        <span class="dashicons dashicons-info"></span>
                        ${<?php echo wp_json_encode(__('Rotation at:', 'wp-debug-manager')); ?>} ${logInfo.query_log_max_size}
                    </span>` : ''}
                `;
            } else if (infoElement) {
                infoElement.innerHTML = `
                    <span class="wpdmgr-no-logs">
                        <span class="dashicons dashicons-info"></span>
                        ${<?php echo wp_json_encode(__('No query log file found', 'wp-debug-manager')); ?>}
                    </span>
                `;
            }
        }
    });
}

// Auto-load query logs when page loads
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('wpdmgr-query-logs-content')) {
        loadQueryLogs();
        initializeQueryLogsPage();
    }
});

function initializeQueryLogsPage() {
    // Refresh query logs
    document.getElementById('refresh-query-logs').addEventListener('click', function() {
        loadQueryLogs();
    });

    // Clear query logs (empties active query.log content)
    document.getElementById('clear-query-logs').addEventListener('click', function() {
        if (!confirm(<?php echo wp_json_encode(__('Are you sure you want to clear the active query log? This will delete all recorded logs in query.log but keep rotation files (query.log.1, etc.).', 'wp-debug-manager')); ?>)) {
            return;
        }

        const logsLoading = document.querySelector('#wpdmgr-query-logs-viewer .wpdmgr-logs-loading');
        logsLoading.style.display = 'block';

        jQuery.post(ajaxurl, {
            action: 'wpdmgr_clear_query_log',
            nonce: wpdmgrToolkit.nonce
        }, function(response) {
            logsLoading.style.display = 'none';

            if (response.success) {
                window.wpdmgrShowNotice(response.data, 'success');

                // Clear the logs display immediately
                const logsContent = document.getElementById('wpdmgr-query-logs-content');
                if (logsContent) {
                    logsContent.innerHTML = '<div class="wpdmgr-no-logs-message"><p>' + <?php echo wp_json_encode(__('No query log entries found.', 'wp-debug-manager')); ?> + '</p></div>';
                }

                // Update log info immediately, then reload for complete refresh
                updateLogInfo();
                setTimeout(() => location.reload(), 1500);
            } else {
                window.wpdmgrShowNotice(response.data || <?php echo wp_json_encode(__('Error occurred', 'wp-debug-manager')); ?>, 'error');
            }
        });
    });

    // Cleanup old query logs (removes rotation/archived files like query.log.1, query.log.2, etc.)
    document.getElementById('cleanup-query-logs').addEventListener('click', function() {
        if (!confirm(<?php echo wp_json_encode(__('Are you sure you want to cleanup rotation/archived log files? This will remove query.log.1, query.log.2, etc. but keep the active query.log file.', 'wp-debug-manager')); ?>)) {
            return;
        }

        const logsLoading = document.querySelector('#wpdmgr-query-logs-viewer .wpdmgr-logs-loading');
        logsLoading.style.display = 'block';

        jQuery.post(ajaxurl, {
            action: 'wpdmgr_cleanup_query_logs',
            nonce: wpdmgrToolkit.nonce
        }, function(response) {
            logsLoading.style.display = 'none';

            if (response.success) {
                window.wpdmgrShowNotice(response.data, 'success');

                // Update log info immediately, then reload for complete refresh
                updateLogInfo();
                setTimeout(() => location.reload(), 1500);
            } else {
                window.wpdmgrShowNotice(response.data || <?php echo wp_json_encode(__('Error occurred', 'wp-debug-manager')); ?>, 'error');
            }
        });
    });

    // Download query logs
    document.getElementById('download-query-logs').addEventListener('click', function() {
        const downloadUrl = ajaxurl + '?action=wpdmgr_download_query_logs&nonce=' + wpdmgrToolkit.nonce;
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.download = 'query-logs-' + new Date().toISOString().slice(0, 10) + '.txt';
        link.click();
    });

    // Filter functionality
    document.getElementById('query-time-filter').addEventListener('change', filterQueryLogs);
    document.getElementById('query-type-filter').addEventListener('change', filterQueryLogs);
    document.getElementById('query-slow-filter').addEventListener('change', filterQueryLogs);
    document.getElementById('query-search').addEventListener('input', window.wpdmgrUtils.debounce(filterQueryLogs, 300));

    // Clear filters
    document.getElementById('clear-query-filters').addEventListener('click', function() {
        document.getElementById('query-time-filter').value = '';
        document.getElementById('query-type-filter').value = '';
        document.getElementById('query-slow-filter').value = '';
        document.getElementById('query-search').value = '';
        filterQueryLogs();
    });
}

function loadQueryLogs() {
    const logsContent = document.getElementById('wpdmgr-query-logs-content');
    const logsLoading = document.querySelector('#wpdmgr-query-logs-viewer .wpdmgr-logs-loading');

    if (!logsContent) return;

    logsLoading.style.display = 'block';
    logsContent.style.display = 'none';

    jQuery.post(ajaxurl, {
        action: 'wpdmgr_get_query_logs',
        nonce: wpdmgrToolkit.nonce
    }, function(response) {
        logsLoading.style.display = 'none';

        if (response.success && response.data) {
            displayQueryLogs(response.data);
            logsContent.style.display = 'block';
        } else {
            logsContent.innerHTML = '<div class="notice notice-error"><p>' + <?php echo wp_json_encode(__('Error occurred', 'wp-debug-manager')); ?> + '</p></div>';
            logsContent.style.display = 'block';
        }
    });
}

function displayQueryLogs(logEntries) {
    const logsContent = document.getElementById('wpdmgr-query-logs-content');

    if (!logEntries || logEntries.length === 0) {
        logsContent.innerHTML = '<div class="wpdmgr-no-logs-message"><p>' + <?php echo wp_json_encode(__('No query log entries found.', 'wp-debug-manager')); ?> + '</p></div>';
        return;
    }

    let html = '<div class="wpdmgr-logs-list">';

    logEntries.forEach(function(entry, index) {
        // Safe timestamp formatting
        let timeFormatted = 'Invalid Date';
        try {
            if (entry.timestamp) {
                const date = new Date(entry.timestamp);
                if (!isNaN(date.getTime())) {
                    timeFormatted = date.toLocaleString();
                } else {
                    // Try parsing as different format
                    const parsedDate = new Date(entry.timestamp.replace(/\[|\]/g, ''));
                    if (!isNaN(parsedDate.getTime())) {
                        timeFormatted = parsedDate.toLocaleString();
                    } else {
                        timeFormatted = entry.timestamp; // Show raw timestamp if parsing fails
                    }
                }
            }
        } catch (e) {
            console.warn('Error formatting timestamp:', e, entry.timestamp);
            timeFormatted = entry.timestamp || 'Unknown';
        }
        const entryId = 'query-entry-' + index;

        html += '<div class="wpdmgr-query-log-entry" data-entry="' + index + '">';

        // Collapsible header
        html += '<div class="query-log-header" data-toggle="' + entryId + '">';
        html += '<div class="query-log-meta">';
        html += '<span class="dashicons dashicons-arrow-right-alt2 toggle-icon"></span>';
        html += '<span class="query-log-time">' + timeFormatted + '</span>';

        // Parse URL source
        const urlSource = parseUrlSource(entry.url);
        html += '<div class="query-log-url">';
        html += '<span class="source-name">' + window.wpdmgrUtils.escapeHtml(urlSource.name) + '</span>';
        html += '<span class="source-path">' + window.wpdmgrUtils.escapeHtml(urlSource.path) + '</span>';
        html += '</div>';

        html += '</div>';
        html += '<div class="query-log-stats">';
        html += '<span class="query-count">' + entry.total_queries + ' queries</span>';
        html += '<span class="query-time">' + entry.total_time + '</span>';
        html += '<span class="query-memory">' + entry.memory_usage + '</span>';
        html += '</div>';
        html += '</div>';

        // Collapsible content (initially hidden)
        html += '<div class="query-log-details" id="' + entryId + '" style="display: none;">';
        html += '<div class="query-loading" style="display: none;">';
        html += '<div class="wpdmgr-spinner"></div>';
        html += '<p>Loading query details...</p>';
        html += '</div>';
        html += '</div>';

        html += '</div>';
    });

    html += '</div>';

    logsContent.innerHTML = html;

    // Add click handlers for collapsible headers
    document.querySelectorAll('.query-log-header[data-toggle]').forEach(function(header) {
        header.addEventListener('click', function() {
            const targetId = this.getAttribute('data-toggle');
            const targetDiv = document.getElementById(targetId);
            const icon = this.querySelector('.toggle-icon');
            const entryIndex = this.parentElement.getAttribute('data-entry');

            if (targetDiv.style.display === 'none') {
                // Show details - load via AJAX if not already loaded
                if (!targetDiv.hasAttribute('data-loaded')) {
                    loadQueryDetails(entryIndex, targetId);
                } else {
                    targetDiv.style.display = 'block';
                }
                icon.className = 'dashicons dashicons-arrow-down-alt2 toggle-icon';
            } else {
                // Hide details
                targetDiv.style.display = 'none';
                icon.className = 'dashicons dashicons-arrow-right-alt2 toggle-icon';
            }
        });
    });
}

function loadQueryDetails(entryIndex, targetId) {
    const targetDiv = document.getElementById(targetId);
    const loadingDiv = targetDiv.querySelector('.query-loading');

    // Show loading
    loadingDiv.style.display = 'block';
    targetDiv.style.display = 'block';

    // Get entry data from global variable or reload
    jQuery.post(ajaxurl, {
        action: 'wpdmgr_get_query_logs',
        nonce: wpdmgrToolkit.nonce
    }, function(response) {
        if (response.success && response.data && response.data[entryIndex]) {
            const entry = response.data[entryIndex];
            displayQueryDetailsTable(entry, targetId);
            targetDiv.setAttribute('data-loaded', 'true');
        } else {
            targetDiv.innerHTML = '<p>Error loading query details.</p>';
        }
        loadingDiv.style.display = 'none';
    });
}

function displayQueryDetailsTable(entry, targetId) {
    const targetDiv = document.getElementById(targetId);

    if (!entry.queries || entry.queries.length === 0) {
        targetDiv.innerHTML = '<p>No query details available.</p>';
        return;
    }

    let html = '<table class="query-log-table">';
    html += '<thead>';
    html += '<tr>';
    html += '<th>No</th>';
    html += '<th>Time</th>';
    html += '<th>Query</th>';
    html += '<th>Caller Stack</th>';
    html += '</tr>';
    html += '</thead>';
    html += '<tbody>';

    entry.queries.forEach(function(query, index) {
        const rowClass = query.is_slow ? 'slow-query' : '';
        const queryNumber = query.number ? query.number.replace('Query #', '') : (index + 1);

        html += '<tr class="' + rowClass + '" data-query-type="' + getQueryType(query.sql) + '">';
        html += '<td class="query-number">' + queryNumber + '</td>';
        html += '<td class="query-time">';
        if (query.is_slow) {
            html += '<span class="slow-indicator" title="Slow Query"><span class="dashicons dashicons-warning"></span></span> ';
        }
        html += query.time + '</td>';
        html += '<td class="query-sql">';
        html += '<div class="sql-container">';
        html += '<code>' + window.wpdmgrUtils.escapeHtml(query.sql) + '</code>';
        html += '<button type="button" class="button-link copy-sql" title="Copy SQL">';
        html += '<span class="dashicons dashicons-admin-page"></span>';
        html += '</button>';
        html += '</div>';
        html += '</td>';
        html += '<td class="query-caller">' + formatCaller(query.caller) + '</td>';
        html += '</tr>';
    });

    html += '</tbody>';
    html += '</table>';

    targetDiv.innerHTML = html;

    // Add copy functionality
    targetDiv.querySelectorAll('.copy-sql').forEach(function(button) {
        button.addEventListener('click', function() {
            const sql = this.previousElementSibling.textContent;
            navigator.clipboard.writeText(sql).then(function() {
                window.wpdmgrShowNotice('SQL copied to clipboard', 'success');
            });
        });
    });
}

function getQueryType(sql) {
    const sqlUpper = sql.toUpperCase().trim();
    if (sqlUpper.startsWith('SELECT')) return 'SELECT';
    if (sqlUpper.startsWith('INSERT')) return 'INSERT';
    if (sqlUpper.startsWith('UPDATE')) return 'UPDATE';
    if (sqlUpper.startsWith('DELETE')) return 'DELETE';
    return 'OTHER';
}

function filterQueryLogs() {
    const timeFilter = document.getElementById('query-time-filter').value;
    const typeFilter = document.getElementById('query-type-filter').value;
    const slowFilter = document.getElementById('query-slow-filter').value;
    const searchTerm = document.getElementById('query-search').value.toLowerCase();

    document.querySelectorAll('.wpdmgr-query-log-entry').forEach(function(entry) {
        let show = true;

        // Time filter (would require more complex logic with actual timestamps)
        // For now, just implement search and type filters

        // Search filter
        if (searchTerm) {
            const entryText = entry.textContent.toLowerCase();
            if (entryText.indexOf(searchTerm) === -1) {
                show = false;
            }
        }

        // Type filter (check queries in details if loaded)
        if (typeFilter) {
            const queries = entry.querySelectorAll('tr[data-query-type]');
            if (queries.length > 0) {
                let hasMatchingType = false;
                queries.forEach(function(queryRow) {
                    if (queryRow.getAttribute('data-query-type') === typeFilter) {
                        hasMatchingType = true;
                    }
                });
                if (!hasMatchingType) {
                    show = false;
                }
            }
        }

        // Slow query filter
        if (slowFilter) {
            const slowQueries = entry.querySelectorAll('.slow-query');
            if (slowFilter === 'slow' && slowQueries.length === 0) {
                show = false;
            } else if (slowFilter === 'fast' && slowQueries.length > 0) {
                show = false;
            }
        }

        entry.style.display = show ? 'block' : 'none';
    });
}
</script>
