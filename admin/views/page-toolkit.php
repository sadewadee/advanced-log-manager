<?php
/**
 * Main Admin Page Template
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
$perf_monitor_service = $plugin->get_service('perf_monitor');
$htaccess_service = $plugin->get_service('htaccess');
$php_config_service = $plugin->get_service('php_config');


try {
    $debug_status = $debug_service ? $debug_service->get_debug_status() : array('enabled' => false);
    $debug_enabled = isset($debug_status['enabled']) ? $debug_status['enabled'] : false;
} catch (Exception $e) {
    $debug_enabled = false;
    wpdmgr_error_log('Debug Status Error: ' . $e->getMessage());
}

$perf_monitor_enabled = get_option('wpdmgr_perf_monitor_enabled', false);
$display_errors_on = ini_get('display_errors') == '1' || ini_get('display_errors') === 'On';
$htaccess_info = $htaccess_service->get_htaccess_info();
$htaccess_backups = $htaccess_service->get_backups();
$php_presets = $php_config_service->get_presets();
$current_php_preset = get_option('wpdmgr_php_preset', 'medium');
$php_config_method = $php_config_service->get_config_method_info();
$current_php_config = $php_config_service->get_current_config();
$server_memory_info = $php_config_service->get_server_memory_info();
$savequeries = defined('SAVEQUERIES') ? (bool) constant('SAVEQUERIES') : false;

// Tambahan: opsi granular Performance Monitor
$perf_realtime_enabled = get_option('wpdmgr_perf_realtime_enabled', false);
$perf_bootstrap_enabled = get_option('wpdmgr_perf_bootstrap_enabled', false);
$perf_domains_enabled  = get_option('wpdmgr_perf_domains_enabled', false);

// Setting labels and units for display
$setting_labels = array(
    'memory_limit' => __('Memory Limit', 'wp-debug-manager'),
    'upload_max_filesize' => __('Upload Max Filesize', 'wp-debug-manager'),
    'post_max_size' => __('Post Max Size', 'wp-debug-manager'),
    'max_execution_time' => __('Max Execution Time', 'wp-debug-manager'),
    'max_input_vars' => __('Max Input Vars', 'wp-debug-manager'),
    'max_input_time' => __('Max Input Time', 'wp-debug-manager')
);

$setting_units = array(
    'memory_limit' => 'M',
    'upload_max_filesize' => 'M',
    'post_max_size' => 'M',
    'max_execution_time' => 's',
    'max_input_vars' => '',
    'max_input_time' => 's'
);
?>

<div class="wrap">
    <h1><?php esc_html_e('WP Debug Manager', 'wp-debug-manager'); ?></h1>
    <p class="description">
        <?php esc_html_e('Developer tools untuk WordPress: Debug Manager, Performance Monitor, Htaccess Editor, PHP Config presets.', 'wp-debug-manager'); ?>
    </p>

    <!-- Tab Navigation -->
    <div class="wpdmgr-tab-navigation">
        <button class="wpdmgr-tab-btn active" data-tab="debug-management">
            <span class="dashicons dashicons-admin-tools"></span>
            <?php esc_html_e('Debug Management', 'wp-debug-manager'); ?>
        </button>
        <button class="wpdmgr-tab-btn" data-tab="perf-monitor">
            <span class="dashicons dashicons-performance"></span>
            <?php esc_html_e('Performance Monitor', 'wp-debug-manager'); ?>
        </button>
        <button class="wpdmgr-tab-btn" data-tab="file-editor">
            <span class="dashicons dashicons-edit"></span>
            <?php esc_html_e('File Editor', 'wp-debug-manager'); ?>
        </button>
        <button class="wpdmgr-tab-btn" data-tab="php-config">
            <span class="dashicons dashicons-admin-settings"></span>
            <?php esc_html_e('PHP Config', 'wp-debug-manager'); ?>
        </button>
    </div>

    <!-- Tab Contents -->
    <div class="wpdmgr-tab-contents">

        <!-- Debug Management Tab -->
        <div id="tab-debug-management" class="wpdmgr-tab-content active">
            <div class="wpdmgr-card">
                <h2><?php esc_html_e('Debug Mode Control', 'wp-debug-manager'); ?></h2>

                <div class="wpdmgr-form-section">
                        <div class="wpdmgr-toggle-wrapper">
                            <input type="checkbox" id="debug-mode-toggle" <?php checked($debug_enabled); ?>>
                            <div class="wpdmgr-toggle <?php echo $debug_enabled ? 'active' : ''; ?>">
                                <div class="wpdmgr-toggle-slider"></div>
                            </div>
                        <label class="wpdmgr-toggle-label">
                            <span><?php esc_html_e('Enable Debug Mode', 'wp-debug-manager'); ?></span>
                        </label>
                    </div>
                </div>

                <!-- WP Debug Settings Section -->
                <div class="wpdmgr-wp-debug-settings">
                    <h3><?php esc_html_e('WP Debug Settings', 'wp-debug-manager'); ?></h3>
                    <p class="description"><?php esc_html_e('WordPress debug constants configuration', 'wp-debug-manager'); ?></p>
                    <div class="wpdmgr-toggle-group" <?php echo !$debug_enabled ? 'data-disabled="true"' : ''; ?>>
                        <div class="wpdmgr-toggle-wrapper <?php echo !$debug_enabled ? 'disabled' : ''; ?>">
                            <input type="checkbox" id="wp-debug-log-toggle" <?php checked(defined('WP_DEBUG_LOG') && WP_DEBUG_LOG); ?> <?php disabled(!$debug_enabled); ?>>
                            <div class="wpdmgr-toggle <?php echo (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && $debug_enabled) ? 'active' : ''; ?>">
                                <div class="wpdmgr-toggle-slider"></div>
                            </div>
                            <label for="wp-debug-log-toggle" class="wpdmgr-toggle-label">
                                <span>WP_DEBUG_LOG</span>
                                <small class="description"><?php esc_html_e('Log errors to file', 'wp-debug-manager'); ?></small>
                            </label>
                        </div>
                        <div class="wpdmgr-toggle-wrapper <?php echo !$debug_enabled ? 'disabled' : ''; ?>">
                            <input type="checkbox" id="wp-debug-display-toggle" <?php checked(defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY); ?> <?php disabled(!$debug_enabled); ?>>
                            <div class="wpdmgr-toggle <?php echo (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY && $debug_enabled) ? 'active' : ''; ?>">
                                <div class="wpdmgr-toggle-slider"></div>
                            </div>
                            <label for="wp-debug-display-toggle" class="wpdmgr-toggle-label">
                                <span>WP_DEBUG_DISPLAY</span>
                                <small class="description"><?php esc_html_e('Display errors on screen', 'wp-debug-manager'); ?></small>
                            </label>
                        </div>
                        <div class="wpdmgr-toggle-wrapper <?php echo !$debug_enabled ? 'disabled' : ''; ?>">
                            <input type="checkbox" id="script-debug-toggle" <?php checked(defined('SCRIPT_DEBUG') && SCRIPT_DEBUG); ?> <?php disabled(!$debug_enabled); ?>>
                            <div class="wpdmgr-toggle <?php echo (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG && $debug_enabled) ? 'active' : ''; ?>">
                                <div class="wpdmgr-toggle-slider"></div>
                            </div>
                            <label for="script-debug-toggle" class="wpdmgr-toggle-label">
                                <span>SCRIPT_DEBUG</span>
                                <small class="description"><?php esc_html_e('Use unminified JS/CSS', 'wp-debug-manager'); ?></small>
                            </label>
                        </div>
                        <div class="wpdmgr-toggle-wrapper <?php echo !$debug_enabled ? 'disabled' : ''; ?>">
                            <input type="checkbox" id="savequeries-toggle" <?php checked($savequeries); ?> <?php disabled(!$debug_enabled); ?>>
                            <div class="wpdmgr-toggle <?php echo ($savequeries && $debug_enabled) ? 'active' : ''; ?>">
                                <div class="wpdmgr-toggle-slider"></div>
                            </div>
                            <label for="savequeries-toggle" class="wpdmgr-toggle-label">
                                <span>SAVEQUERIES</span>
                                <small class="description"><?php esc_html_e('Save database queries to query.log for analysis', 'wp-debug-manager'); ?></small>
                            </label>
                        </div>
                        <div class="wpdmgr-toggle-wrapper <?php echo !$debug_enabled ? 'disabled' : ''; ?>">
                            <input type="checkbox" id="display-errors-toggle" <?php checked($display_errors_on); ?> <?php disabled(!$debug_enabled); ?>>
                            <div class="wpdmgr-toggle <?php echo ($display_errors_on && $debug_enabled) ? 'active' : ''; ?>">
                                <div class="wpdmgr-toggle-slider"></div>
                            </div>
                            <label for="display-errors-toggle" class="wpdmgr-toggle-label">
                                <span>display_errors</span>
                                <small class="description"><?php esc_html_e('Display PHP errors on screen', 'wp-debug-manager'); ?></small>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- SMTP Debug Settings Section -->
                <div class="wpdmgr-smtp-debug-settings">
                    <h3><?php esc_html_e('SMTP Debug Settings', 'wp-debug-manager'); ?></h3>
                    <p class="description"><?php esc_html_e('Email logging and monitoring configuration', 'wp-debug-manager'); ?></p>
                    <?php
                    // Get SMTP logging status
                    $smtp_service = $plugin->get_service('smtp_logger');
                    $smtp_status = $smtp_service ? $smtp_service->get_logging_status() : array('enabled' => false);
                    $smtp_enabled = $smtp_status['enabled'];
                    ?>
                    <div class="wpdmgr-toggle-group" <?php echo !$debug_enabled ? 'data-disabled="true"' : ''; ?>>
                        <div class="wpdmgr-toggle-wrapper <?php echo !$debug_enabled ? 'disabled' : ''; ?>">
                            <input type="checkbox" id="smtp-logging-toggle" <?php checked($smtp_enabled); ?> <?php disabled(!$debug_enabled); ?>>
                            <div class="wpdmgr-toggle <?php echo ($smtp_enabled && $debug_enabled) ? 'active' : ''; ?>">
                                <div class="wpdmgr-toggle-slider"></div>
                            </div>
                            <label for="smtp-logging-toggle" class="wpdmgr-toggle-label">
                                <span>SMTP Logging</span>
                                <small class="description"><?php esc_html_e('Log all email activity to smtp-ddmmyyyy.log files', 'wp-debug-manager'); ?></small>
                            </label>
                        </div>
                        <?php
                        // Get IP address logging status
                        $ip_logging_enabled = \get_option('wpdmgr_smtp_log_ip_address', false);
                        ?>
                        <div class="wpdmgr-toggle-wrapper <?php echo (!$debug_enabled || !$smtp_enabled) ? 'disabled' : ''; ?>">
                            <input type="checkbox" id="smtp-ip-logging-toggle" <?php checked($ip_logging_enabled); ?> <?php disabled(!$debug_enabled || !$smtp_enabled); ?>>
                            <div class="wpdmgr-toggle <?php echo ($ip_logging_enabled && $debug_enabled && $smtp_enabled) ? 'active' : ''; ?>">
                                <div class="wpdmgr-toggle-slider"></div>
                            </div>
                            <label for="smtp-ip-logging-toggle" class="wpdmgr-toggle-label">
                                <span>IP Address Logging</span>
                                <small class="description"><?php esc_html_e('Include originating IP addresses in SMTP logs (requires SMTP logging)', 'wp-debug-manager'); ?></small>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="wpdmgr-status-info">
                    <div class="wpdmgr-status-item">
                        <span class="wpdmgr-status-indicator <?php echo $debug_enabled ? 'active' : 'inactive'; ?>"></span>
                        <span><?php esc_html_e('Status:', 'wp-debug-manager'); ?>
                            <?php echo $debug_enabled ? esc_html__('Debug Enabled', 'wp-debug-manager') : esc_html__('Debug Disabled', 'wp-debug-manager'); ?>
                        </span>
                    </div>
                    <?php if (file_exists(wpdmgr_get_debug_log_path())): ?>
                    <div class="wpdmgr-status-item">
                        <span class="dashicons dashicons-media-text"></span>
                        <span><?php esc_html_e('Debug Log Size:', 'wp-debug-manager'); ?> <?php echo wpdmgr_format_bytes(filesize(wpdmgr_get_debug_log_path())); ?></span>
                        <a href="<?php echo esc_url( admin_url('tools.php?page=wpdmgr-all-logs-activity&tab=debug') ); ?>" class="button button-small" style="margin-left: 10px;">
                            <?php esc_html_e('View Debug Logs', 'wp-debug-manager'); ?>
                        </a>
                        <button type="button" id="clear-all-debug-logs" class="button button-small" style="margin-left: 5px;" title="<?php esc_attr_e('Clear all wp-errors-* log files except the currently active one', 'wp-debug-manager'); ?>">
                            <span class="dashicons dashicons-trash"></span>
                            <?php esc_html_e('Clear All Logs', 'wp-debug-manager'); ?>
                        </button>
                    </div>
                    <?php endif; ?>
                    <?php if ($savequeries): ?>
                    <div class="wpdmgr-status-item">
                        <span class="dashicons dashicons-database"></span>
                        <span><?php esc_html_e('Query Logging:', 'wp-debug-manager'); ?>
                            <?php echo $savequeries ? esc_html__('Enabled', 'wp-debug-manager') : esc_html__('Disabled', 'wp-debug-manager'); ?>
                        </span>
                        <?php if (file_exists(wpdmgr_get_query_log_path())): ?>
                        <span style="margin-left: 10px; color: #646970;">
                            <?php esc_html_e('Size:', 'wp-debug-manager'); ?> <?php echo wpdmgr_format_bytes(filesize(wpdmgr_get_query_log_path())); ?>
                            <small style="margin-left: 5px; color: #007cba;">
                                (<?php esc_html_e('Auto-rotates at', 'wp-debug-manager'); ?> <?php echo wpdmgr_format_bytes(wpdmgr_get_query_log_max_size()); ?>)
                            </small>
                        </span>
                        <?php endif; ?>
                        <a href="<?php echo esc_url( admin_url('tools.php?page=wpdmgr-all-logs-activity&tab=query') ); ?>" class="button button-small" style="margin-left: 10px;">
                            <?php esc_html_e('View Query Logs', 'wp-debug-manager'); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php if ($smtp_enabled): ?>
                    <div class="wpdmgr-status-item">
                        <span class="dashicons dashicons-email"></span>
                        <span><?php esc_html_e('SMTP Logging:', 'wp-debug-manager'); ?>
                            <?php echo $smtp_enabled ? esc_html__('Enabled', 'wp-debug-manager') : esc_html__('Disabled', 'wp-debug-manager'); ?>
                        </span>
                        <?php if ($smtp_status['current_log_exists']): ?>
                        <span style="margin-left: 10px; color: #646970;">
                            <?php esc_html_e('Today:', 'wp-debug-manager'); ?> <?php echo esc_html($smtp_status['current_log_size']); ?>
                            <?php if (count($smtp_status['available_files']) > 0): ?>
                            <small style="margin-left: 5px; color: #007cba;">
                                (<?php echo esc_html( sprintf( __('%d log files', 'wp-debug-manager'), count($smtp_status['available_files']) ) ); ?>)
                            </small>
                            <?php endif; ?>
                        </span>
                        <?php endif; ?>
                        <a href="<?php echo esc_url( admin_url('tools.php?page=wpdmgr-all-logs-activity&tab=smtp') ); ?>" class="button button-small" style="margin-left: 10px;">
                            <?php esc_html_e('View SMTP Logs', 'wp-debug-manager'); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="wpdmgr-log-cleanup-section">
                    <h3><?php esc_html_e('Log Management', 'wp-debug-manager'); ?></h3>
                    <p class="description"><?php esc_html_e('Manage and cleanup log files created by the plugin.', 'wp-debug-manager'); ?></p>

                    <div class="wpdmgr-cleanup-actions">
                        <div class="wpdmgr-cleanup-item">
                            <div class="wpdmgr-cleanup-info">
                                <strong><?php esc_html_e('Debug Logs Cleanup', 'wp-debug-manager'); ?></strong>
                                <p class="description"><?php esc_html_e('Remove old wp-errors-* log files, keeping only the most recent ones.', 'wp-debug-manager'); ?></p>
                            </div>
                            <div class="wpdmgr-cleanup-controls wpdmgr-logs-actions">
                                <select id="debug-cleanup-keep-count">
                                    <option value="1"><?php esc_html_e('Keep 1 file', 'wp-debug-manager'); ?></option>
                                    <option value="3" selected><?php esc_html_e('Keep 3 files', 'wp-debug-manager'); ?></option>
                                    <option value="5"><?php esc_html_e('Keep 5 files', 'wp-debug-manager'); ?></option>
                                </select>
                                <button type="button" id="cleanup-debug-logs" class="button">
                                    <span class="dashicons dashicons-admin-tools"></span>
                                    <?php esc_html_e('Cleanup Debug Logs', 'wp-debug-manager'); ?>
                                </button>
                            </div>
                        </div>

                        <div class="wpdmgr-cleanup-item">
                            <div class="wpdmgr-cleanup-info">
                                <strong><?php esc_html_e('All Logs Cleanup', 'wp-debug-manager'); ?></strong>
                                <p class="description"><?php esc_html_e('Remove all log files created by the plugin (wp-errors-*, wp-queries-*, etc.).', 'wp-debug-manager'); ?></p>
                            </div>
                            <div class="wpdmgr-cleanup-controls wpdmgr-logs-actions">
                                <label>
                                    <input type="checkbox" id="include-current-logs">
                                    <?php esc_html_e('Include current active logs', 'wp-debug-manager'); ?>
                                </label>
                                <button type="button" id="cleanup-all-logs" class="button button-secondary">
                                    <span class="dashicons dashicons-trash"></span>
                                    <?php esc_html_e('Remove All Logs', 'wp-debug-manager'); ?>
                                </button>
                            </div>
                        </div>

                        <div class="wpdmgr-cleanup-item">
                            <div class="wpdmgr-cleanup-info">
                                <strong><?php esc_html_e('Query Log Rotation Cleanup', 'wp-debug-manager'); ?></strong>
                                <p class="description"><?php esc_html_e('Remove old query log rotation files (query.log.1, query.log.2, etc.) that accumulate over time.', 'wp-debug-manager'); ?></p>
                            </div>
                            <div class="wpdmgr-cleanup-controls wpdmgr-logs-actions">
                                <label>
                                    <input type="checkbox" id="keep-latest-rotation" checked>
                                    <?php esc_html_e('Keep latest backup (query.log.1)', 'wp-debug-manager'); ?>
                                </label>
                                <button type="button" id="cleanup-query-rotation-logs" class="button button-secondary">
                                    <span class="dashicons dashicons-database-remove"></span>
                                    <?php esc_html_e('Cleanup Rotation Files', 'wp-debug-manager'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Monitor Tab -->
        <div id="tab-perf-monitor" class="wpdmgr-tab-content">
            <div class="wpdmgr-card">
                <h2><?php esc_html_e('Performance Monitor', 'wp-debug-manager'); ?></h2>

                <div class="wpdmgr-form-section">
                    <label class="wpdmgr-toggle-label">
                        <span><?php esc_html_e('Enable Performance Bar', 'wp-debug-manager'); ?></span>
                        <div class="wpdmgr-toggle-wrapper">
                            <input type="checkbox" id="perf-monitor-toggle" <?php checked($perf_monitor_enabled); ?>>
                            <div class="wpdmgr-toggle <?php echo $perf_monitor_enabled ? 'active' : ''; ?>">
                                <div class="wpdmgr-toggle-slider"></div>
                            </div>
                        </div>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Menampilkan bar performa di bagian bawah halaman untuk user yang login.', 'wp-debug-manager'); ?>
                    </p>
                    <p class="description">
                        <?php esc_html_e('Peringatan: Mengaktifkan Performance Bar dapat menambah sedikit overhead pada waktu muat halaman karena pengumpulan metrik. Disarankan hanya untuk kebutuhan debugging/lingkungan pengembangan.', 'wp-debug-manager'); ?>
                    </p>
                </div>

                <!-- Granular feature toggles -->
                <div class="wpdmgr-form-section">
                    <label class="wpdmgr-toggle-label">
                        <span><?php esc_html_e('Enable Real-time Hooks Monitoring', 'wp-debug-manager'); ?></span>
                        <div class="wpdmgr-toggle-wrapper <?php echo !$perf_monitor_enabled ? 'disabled' : ''; ?>">
                            <input type="checkbox" id="perf-realtime-toggle" <?php checked($perf_realtime_enabled); ?> <?php echo !$perf_monitor_enabled ? 'disabled' : ''; ?>>
                            <div class="wpdmgr-toggle <?php echo $perf_realtime_enabled ? 'active' : ''; ?>">
                                <div class="wpdmgr-toggle-slider"></div>
                            </div>
                        </div>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Pantau eksekusi hook strategis secara real-time (dioptimalkan, bukan hook "all").', 'wp-debug-manager'); ?>
                    </p>
                </div>

                <div class="wpdmgr-form-section">
                    <label class="wpdmgr-toggle-label">
                        <span><?php esc_html_e('Enable Bootstrap Phases Snapshots', 'wp-debug-manager'); ?></span>
                        <div class="wpdmgr-toggle-wrapper <?php echo !$perf_monitor_enabled ? 'disabled' : ''; ?>">
                            <input type="checkbox" id="perf-bootstrap-toggle" <?php checked($perf_bootstrap_enabled); ?> <?php echo !$perf_monitor_enabled ? 'disabled' : ''; ?>>
                            <div class="wpdmgr-toggle <?php echo $perf_bootstrap_enabled ? 'active' : ''; ?>">
                                <div class="wpdmgr-toggle-slider"></div>
                            </div>
                        </div>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Ambil snapshot evolusi hook pada fase bootstrap WordPress.', 'wp-debug-manager'); ?>
                    </p>
                </div>

                <div class="wpdmgr-form-section">
                    <label class="wpdmgr-toggle-label">
                        <span><?php esc_html_e('Enable Domain-specific Panels', 'wp-debug-manager'); ?></span>
                        <div class="wpdmgr-toggle-wrapper <?php echo !$perf_monitor_enabled ? 'disabled' : ''; ?>">
                            <input type="checkbox" id="perf-domains-toggle" <?php checked($perf_domains_enabled); ?> <?php echo !$perf_monitor_enabled ? 'disabled' : ''; ?>>
                            <div class="wpdmgr-toggle <?php echo $perf_domains_enabled ? 'active' : ''; ?>">
                                <div class="wpdmgr-toggle-slider"></div>
                            </div>
                        </div>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Tampilkan panel analisis berdasarkan domain (Database, HTTP, Template, dsb).', 'wp-debug-manager'); ?>
                    </p>
                </div>

                <div class="wpdmgr-preview-section">
                    <h3><?php esc_html_e('Frontend Display Preview', 'wp-debug-manager'); ?></h3>
                    <div class="wpdmgr-performance-preview">
                        <div class="wpdmgr-perf-preview-bar">
                            <div class="wpdmgr-perf-item">
                                <span class="dashicons dashicons-update"></span>
                                <span class="value">15</span>
                                <span class="label">queries</span>
                            </div>
                            <div class="wpdmgr-perf-item">
                                <span class="dashicons dashicons-clock"></span>
                                <span class="value">1.2s</span>
                                <span class="label">time</span>
                            </div>
                            <div class="wpdmgr-perf-item">
                                <span class="dashicons dashicons-database"></span>
                                <span class="value">45.2MB</span>
                                <span class="label">memory</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="wpdmgr-form-section">
                    <label>
                        <input type="checkbox" id="perf-monitor-logged-only" checked disabled>
                        <?php esc_html_e('Show for logged-in users only', 'wp-debug-manager'); ?>
                    </label>
                </div>
            </div>
        </div>

        <!-- File Editor Tab -->
        <div id="tab-file-editor" class="wpdmgr-tab-content">
            <div class="wpdmgr-card">
                <h2><?php esc_html_e('.htaccess Editor', 'wp-debug-manager'); ?></h2>

                <div class="wpdmgr-backup-info">
                    <div class="wpdmgr-backup-status">
                        <span class="dashicons dashicons-backup"></span>
                        <span><?php esc_html_e('Backups:', 'wp-debug-manager'); ?>
                            <strong><?php echo count($htaccess_backups); ?>/3</strong> available
                        </span>
                    </div>
                    <?php if (!empty($htaccess_backups)): ?>
                    <div class="wpdmgr-backup-last">
                        <span><?php esc_html_e('Last backup:', 'wp-debug-manager'); ?>
                            <?php echo human_time_diff($htaccess_backups[0]['timestamp']); ?> ago
                        </span>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!$htaccess_info['writable']): ?>
                <div class="notice notice-error">
                    <p><?php esc_html_e('.htaccess file is not writable. Please check file permissions.', 'wp-debug-manager'); ?></p>
                </div>
                <?php endif; ?>

                <div class="wpdmgr-editor-section">
                    <textarea id="htaccess-editor" class="wpdmgr-code-editor" rows="20" <?php echo !$htaccess_info['writable'] ? 'readonly' : ''; ?>><?php echo esc_textarea($htaccess_service->get_htaccess_content()); ?></textarea>
                </div>

                <div class="wpdmgr-editor-actions">
                    <button type="button" id="save-htaccess" class="button button-primary" <?php echo !$htaccess_info['writable'] ? 'disabled' : ''; ?>>
                        <span class="dashicons dashicons-backup"></span>
                        <?php esc_html_e('Backup & Save', 'wp-debug-manager'); ?>
                    </button>

                    <?php if (!empty($htaccess_backups)): ?>
                    <div class="wpdmgr-restore-dropdown">
                        <button type="button" id="restore-htaccess-btn" class="button">
                            <span class="dashicons dashicons-undo"></span>
                            <?php esc_html_e('Restore', 'wp-debug-manager'); ?>
                        </button>
                        <div class="wpdmgr-restore-menu">
                            <?php foreach ($htaccess_backups as $index => $backup): ?>
                            <a href="#" class="wpdmgr-restore-item" data-index="<?php echo $index; ?>">
                                <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $backup['timestamp']); ?>
                                <span class="size">(<?php echo wpdmgr_format_bytes($backup['size']); ?>)</span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <button type="button" id="cancel-htaccess" class="button">
                        <?php esc_html_e('Cancel', 'wp-debug-manager'); ?>
                    </button>
                </div>

                <div class="wpdmgr-htaccess-snippets">
                    <h3><?php esc_html_e('Common Snippets', 'wp-debug-manager'); ?></h3>
                    <div class="wpdmgr-snippet-buttons">
                        <?php foreach ($htaccess_service->get_common_snippets() as $key => $snippet): ?>
                        <button type="button" class="button wpdmgr-snippet-btn" data-snippet="<?php echo esc_attr($snippet['content']); ?>">
                            <?php echo esc_html($snippet['title']); ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- PHP Config Tab -->
        <div id="tab-php-config" class="wpdmgr-tab-content">
            <div class="wpdmgr-card">
                <h2><?php esc_html_e('PHP Configuration Presets', 'wp-debug-manager'); ?></h2>

                <div class="wpdmgr-config-method">
                    <p>
                        <span class="dashicons dashicons-info"></span>
                        <?php esc_html_e('Configuration Method:', 'wp-debug-manager'); ?>
                        <strong><?php echo esc_html( ucfirst($php_config_method['method'] ?: 'Not Available') ); ?></strong>
                    </p>
                    <div class="wpdmgr-server-memory-info" style="margin-top: 10px; padding: 10px; background: #f1f1f1; border-radius: 4px;">
                        <p><strong><?php esc_html_e('Server Memory Detection:', 'wp-debug-manager'); ?></strong></p>
                    </div>
                </div>

                <div class="wpdmgr-preset-selection">
                    <h3><?php esc_html_e('Select Preset:', 'wp-debug-manager'); ?></h3>
                    <div class="wpdmgr-preset-options">
                        <?php foreach ($php_presets as $key => $preset): ?>
                        <label class="wpdmgr-preset-option <?php echo $current_php_preset === $key ? 'selected' : ''; ?>" data-preset="<?php echo esc_attr($key); ?>">
                            <input type="radio" name="php_preset" value="<?php echo esc_attr($key); ?>" <?php checked($current_php_preset, $key); ?>>
                            <div class="wpdmgr-preset-card">
                                <h4><?php echo esc_html($preset['name']); ?></h4>
                                <p class="description"><?php echo esc_html($preset['description']); ?></p>
                                <div class="wpdmgr-preset-settings">
                                    <?php foreach ($preset['settings'] as $setting => $value): ?>
                                    <div class="wpdmgr-setting-item">
                                        <span class="setting-name"><?php echo esc_html(str_replace('_', ' ', ucwords($setting, '_'))); ?>:</span>
                                        <span class="setting-value"><?php echo esc_html($value); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="wpdmgr-current-config">
                    <h3><?php esc_html_e('Current Configuration', 'wp-debug-manager'); ?></h3>
                    <div class="wpdmgr-config-table">
                        <?php foreach ($current_php_config as $setting => $value): ?>
                        <div class="wpdmgr-config-row">
                            <span class="config-name"><?php echo esc_html(str_replace('_', ' ', ucwords($setting, '_'))); ?>:</span>
                            <span class="config-value"><?php echo esc_html($value); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="wpdmgr-config-actions">
                    <button type="button" id="apply-php-preset" class="button button-primary">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php esc_html_e('Apply Configuration', 'wp-debug-manager'); ?>
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Loading overlay -->
<div id="wpdmgr-loading-overlay" class="wpdmgr-loading-overlay" style="display: none;">
    <div class="wpdmgr-loading-content">
        <div class="wpdmgr-spinner"></div>
        <p><?php esc_html_e('Processing...', 'wp-debug-manager'); ?></p>
    </div>
</div>
