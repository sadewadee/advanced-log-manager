<?php
/**
 * Main Admin Page Template
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
$perf_monitor_service = $plugin->get_service('perf_monitor');
$htaccess_service = $plugin->get_service('htaccess');
$php_config_service = $plugin->get_service('php_config');


try {
    $debug_status = $debug_service ? $debug_service->get_debug_status() : array('enabled' => false);
    $debug_enabled = isset($debug_status['enabled']) ? $debug_status['enabled'] : false;
} catch (Exception $e) {
    $debug_enabled = false;
    almgr_error_log('Debug Status Error: ' . $e->getMessage());
}

$perf_monitor_enabled = get_option('almgr_perf_monitor_enabled', false);
$display_errors_on = ini_get('display_errors') == '1' || ini_get('display_errors') === 'On';
$htaccess_info = $htaccess_service->get_htaccess_info();
$htaccess_backups = $htaccess_service->get_backups();
$php_presets = $php_config_service->get_presets();
$current_php_preset = get_option('almgr_php_preset', 'medium');
$php_config_method = $php_config_service->get_config_method_info();
$current_php_config = $php_config_service->get_current_config();
$server_memory_info = $php_config_service->get_server_memory_info();
$savequeries = defined('SAVEQUERIES') ? (bool) constant('SAVEQUERIES') : false;

// Tambahan: opsi granular Performance Monitor
$perf_realtime_enabled = get_option('almgr_perf_realtime_enabled', false);
$perf_bootstrap_enabled = get_option('almgr_perf_bootstrap_enabled', false);
$perf_domains_enabled  = get_option('almgr_perf_domains_enabled', false);

// Setting labels and units for display
$setting_labels = array(
    'memory_limit' => __('Memory Limit', 'advanced-log-manager'),
    'upload_max_filesize' => __('Upload Max Filesize', 'advanced-log-manager'),
    'post_max_size' => __('Post Max Size', 'advanced-log-manager'),
    'max_execution_time' => __('Max Execution Time', 'advanced-log-manager'),
    'max_input_vars' => __('Max Input Vars', 'advanced-log-manager'),
    'max_input_time' => __('Max Input Time', 'advanced-log-manager')
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
    <h1><?php esc_html_e('Advance Log Manager', 'advanced-log-manager'); ?></h1>
    <p class="description">
        <?php esc_html_e('Developer tools untuk WordPress: Debug Manager, Performance Monitor, Htaccess Editor, PHP Config presets.', 'advanced-log-manager'); ?>
    </p>

    <!-- Tab Navigation -->
    <div class="almgr-tab-navigation">
        <button class="almgr-tab-btn active" data-tab="debug-management">
            <span class="dashicons dashicons-admin-tools"></span>
            <?php esc_html_e('Debug Management', 'advanced-log-manager'); ?>
        </button>
        <button class="almgr-tab-btn" data-tab="perf-monitor">
            <span class="dashicons dashicons-performance"></span>
            <?php esc_html_e('Performance Monitor', 'advanced-log-manager'); ?>
        </button>
        <button class="almgr-tab-btn" data-tab="file-editor">
            <span class="dashicons dashicons-edit"></span>
            <?php esc_html_e('File Editor', 'advanced-log-manager'); ?>
        </button>
        <button class="almgr-tab-btn" data-tab="php-config">
            <span class="dashicons dashicons-admin-settings"></span>
            <?php esc_html_e('PHP Config', 'advanced-log-manager'); ?>
        </button>
    </div>

    <!-- Tab Contents -->
    <div class="almgr-tab-contents">

        <!-- Debug Management Tab -->
        <div id="tab-debug-management" class="almgr-tab-content active">
            <div class="almgr-card">
                <h2><?php esc_html_e('Debug Mode Control', 'advanced-log-manager'); ?></h2>

                <div class="almgr-form-section">
                        <div class="almgr-toggle-wrapper">
                            <input type="checkbox" id="debug-mode-toggle" <?php checked($debug_enabled); ?>>
                            <div class="almgr-toggle <?php echo esc_attr($debug_enabled ? 'active' : ''); ?>">
                                <div class="almgr-toggle-slider"></div>
                            </div>
                        <label class="almgr-toggle-label">
                            <span><?php esc_html_e('Enable Debug Mode', 'advanced-log-manager'); ?></span>
                        </label>
                    </div>
                </div>

                <!-- WP Debug Settings Section -->
                <div class="almgr-wp-debug-settings">
                    <h3><?php esc_html_e('WP Debug Settings', 'advanced-log-manager'); ?></h3>
                    <p class="description"><?php esc_html_e('WordPress debug constants configuration', 'advanced-log-manager'); ?></p>
                    <div class="almgr-toggle-group" <?php echo !$debug_enabled ? 'data-disabled="true"' : ''; ?>>
                        <div class="almgr-toggle-wrapper <?php echo esc_attr(!$debug_enabled ? 'disabled' : ''); ?>">
                            <input type="checkbox" id="wp-debug-log-toggle" <?php checked(defined('WP_DEBUG_LOG') && WP_DEBUG_LOG); ?> <?php disabled(!$debug_enabled); ?>>
                            <div class="almgr-toggle <?php echo esc_attr((defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && $debug_enabled) ? 'active' : ''); ?>">
                                <div class="almgr-toggle-slider"></div>
                            </div>
                            <label for="wp-debug-log-toggle" class="almgr-toggle-label">
                                <span>WP_DEBUG_LOG</span>
                                <small class="description"><?php esc_html_e('Log errors to file', 'advanced-log-manager'); ?></small>
                            </label>
                        </div>
                        <div class="almgr-toggle-wrapper <?php echo esc_attr(!$debug_enabled ? 'disabled' : ''); ?>">
                            <input type="checkbox" id="wp-debug-display-toggle" <?php checked(defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY); ?> <?php disabled(!$debug_enabled); ?>>
                            <div class="almgr-toggle <?php echo esc_attr((defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY && $debug_enabled) ? 'active' : ''); ?>">
                                <div class="almgr-toggle-slider"></div>
                            </div>
                            <label for="wp-debug-display-toggle" class="almgr-toggle-label">
                                <span>WP_DEBUG_DISPLAY</span>
                                <small class="description"><?php esc_html_e('Display errors on screen', 'advanced-log-manager'); ?></small>
                            </label>
                        </div>
                        <div class="almgr-toggle-wrapper <?php echo esc_attr(!$debug_enabled ? 'disabled' : ''); ?>">
                            <input type="checkbox" id="script-debug-toggle" <?php checked(defined('SCRIPT_DEBUG') && SCRIPT_DEBUG); ?> <?php disabled(!$debug_enabled); ?>>
                            <div class="almgr-toggle <?php echo esc_attr((defined('SCRIPT_DEBUG') && SCRIPT_DEBUG && $debug_enabled) ? 'active' : ''); ?>">
                                <div class="almgr-toggle-slider"></div>
                            </div>
                            <label for="script-debug-toggle" class="almgr-toggle-label">
                                <span>SCRIPT_DEBUG</span>
                                <small class="description"><?php esc_html_e('Use unminified JS/CSS', 'advanced-log-manager'); ?></small>
                            </label>
                        </div>
                        <div class="almgr-toggle-wrapper <?php echo !$debug_enabled ? 'disabled' : ''; ?>">
                            <input type="checkbox" id="savequeries-toggle" <?php checked($savequeries); ?> <?php disabled(!$debug_enabled); ?>>
                            <div class="almgr-toggle <?php echo esc_attr(($savequeries && $debug_enabled) ? 'active' : ''); ?>">
                                <div class="almgr-toggle-slider"></div>
                            </div>
                            <label for="savequeries-toggle" class="almgr-toggle-label">
                                <span>SAVEQUERIES</span>
                                <small class="description"><?php esc_html_e('Save database queries to query.log for analysis', 'advanced-log-manager'); ?></small>
                            </label>
                        </div>
                        <div class="almgr-toggle-wrapper <?php echo !$debug_enabled ? 'disabled' : ''; ?>">
                            <input type="checkbox" id="display-errors-toggle" <?php checked($display_errors_on); ?> <?php disabled(!$debug_enabled); ?>>
                            <div class="almgr-toggle <?php echo esc_attr(($display_errors_on && $debug_enabled) ? 'active' : ''); ?>">
                                <div class="almgr-toggle-slider"></div>
                            </div>
                            <label for="display-errors-toggle" class="almgr-toggle-label">
                                <span>display_errors</span>
                                <small class="description"><?php esc_html_e('Display PHP errors on screen', 'advanced-log-manager'); ?></small>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- SMTP Debug Settings Section -->
                <div class="almgr-smtp-debug-settings">
                    <h3><?php esc_html_e('SMTP Debug Settings', 'advanced-log-manager'); ?></h3>
                    <p class="description"><?php esc_html_e('Email logging and monitoring configuration', 'advanced-log-manager'); ?></p>
                    <?php
                    // Get SMTP logging status
                    $smtp_service = $plugin->get_service('smtp_logger');
                    $smtp_status = $smtp_service ? $smtp_service->get_logging_status() : array('enabled' => false);
                    $smtp_enabled = $smtp_status['enabled'];
                    ?>
                    <div class="almgr-toggle-group" <?php echo !$debug_enabled ? 'data-disabled="true"' : ''; ?>>
                        <div class="almgr-toggle-wrapper <?php echo !$debug_enabled ? 'disabled' : ''; ?>">
                            <input type="checkbox" id="smtp-logging-toggle" <?php checked($smtp_enabled); ?> <?php disabled(!$debug_enabled); ?>>
                            <div class="almgr-toggle <?php echo esc_attr(($smtp_enabled && $debug_enabled) ? 'active' : ''); ?>">
                                <div class="almgr-toggle-slider"></div>
                            </div>
                            <label for="smtp-logging-toggle" class="almgr-toggle-label">
                                <span>SMTP Logging</span>
                                <small class="description"><?php esc_html_e('Log all email activity to smtp-ddmmyyyy.log files', 'advanced-log-manager'); ?></small>
                            </label>
                        </div>
                        <?php
                        // Get IP address logging status
                        $ip_logging_enabled = \get_option('almgr_smtp_log_ip_address', false);
                        ?>
                        <div class="almgr-toggle-wrapper <?php echo esc_attr((!$debug_enabled || !$smtp_enabled) ? 'disabled' : ''); ?>">
                            <input type="checkbox" id="smtp-ip-logging-toggle" <?php checked($ip_logging_enabled); ?> <?php disabled(!$debug_enabled || !$smtp_enabled); ?>>
                            <div class="almgr-toggle <?php echo esc_attr(($ip_logging_enabled && $debug_enabled && $smtp_enabled) ? 'active' : ''); ?>">
                                <div class="almgr-toggle-slider"></div>
                            </div>
                            <label for="smtp-ip-logging-toggle" class="almgr-toggle-label">
                                <span>IP Address Logging</span>
                                <small class="description"><?php esc_html_e('Include originating IP addresses in SMTP logs (requires SMTP logging)', 'advanced-log-manager'); ?></small>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="almgr-status-info">
                    <div class="almgr-status-item">
                        <span class="almgr-status-indicator <?php echo esc_attr($debug_enabled ? 'active' : 'inactive'); ?>"></span>
                        <span><?php esc_html_e('Status:', 'advanced-log-manager'); ?>
                            <?php echo $debug_enabled ? esc_html__('Debug Enabled', 'advanced-log-manager') : esc_html__('Debug Disabled', 'advanced-log-manager'); ?>
                        </span>
                    </div>
                    <?php if (file_exists(almgr_get_debug_log_path())): ?>
                    <div class="almgr-status-item">
                        <span class="dashicons dashicons-media-text"></span>
                        <span><?php esc_html_e('Debug Log Size:', 'advanced-log-manager'); ?> <?php echo esc_html(almgr_format_bytes(filesize(almgr_get_debug_log_path()))); ?></span>
                         <a href="<?php echo esc_url( admin_url('tools.php?page=almgr-all-logs-activity&tab=debug') ); ?>" class="button button-small" style="margin-left: 10px;">
                             <?php esc_html_e('View Debug Logs', 'advanced-log-manager'); ?>
                        </a>
                        <button type="button" id="clear-all-debug-logs" class="button button-small" style="margin-left: 5px;" title="<?php esc_attr_e('Clear all wp-errors-* log files except the currently active one', 'advanced-log-manager'); ?>">
                            <span class="dashicons dashicons-trash"></span>
                            <?php esc_html_e('Clear All Logs', 'advanced-log-manager'); ?>
                        </button>
                    </div>
                    <?php endif; ?>
                    <?php if ($savequeries): ?>
                    <div class="almgr-status-item">
                        <span class="dashicons dashicons-database"></span>
                        <span><?php esc_html_e('Query Logging:', 'advanced-log-manager'); ?>
                            <?php echo $savequeries ? esc_html__('Enabled', 'advanced-log-manager') : esc_html__('Disabled', 'advanced-log-manager'); ?>
                        </span>
                        <?php if (file_exists(almgr_get_query_log_path())): ?>
                        <span style="margin-left: 10px; color: #646970;">
                            <?php esc_html_e('Size:', 'advanced-log-manager'); ?> <?php echo esc_html(almgr_format_bytes(filesize(almgr_get_query_log_path()))); ?>
                            <small style="margin-left: 5px; color: #007cba;">
                                (<?php esc_html_e('Auto-rotates at', 'advanced-log-manager'); ?> <?php echo esc_html(almgr_format_bytes(almgr_get_query_log_max_size())); ?>)
                            </small>
                        </span>
                        <?php endif; ?>
                        <a href="<?php echo esc_url( admin_url('tools.php?page=almgr-all-logs-activity&tab=query') ); ?>" class="button button-small" style="margin-left: 10px;">
                            <?php esc_html_e('View Query Logs', 'advanced-log-manager'); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php if ($smtp_enabled): ?>
                    <div class="almgr-status-item">
                        <span class="dashicons dashicons-email"></span>
                        <span><?php esc_html_e('SMTP Logging:', 'advanced-log-manager'); ?>
                            <?php echo $smtp_enabled ? esc_html__('Enabled', 'advanced-log-manager') : esc_html__('Disabled', 'advanced-log-manager'); ?>
                        </span>
                        <?php if ($smtp_status['current_log_exists']): ?>
                        <span style="margin-left: 10px; color: #646970;">
                            <?php esc_html_e('Today:', 'advanced-log-manager'); ?> <?php echo esc_html($smtp_status['current_log_size']); ?>
                            <?php if (count($smtp_status['available_files']) > 0): ?>
                            <small style="margin-left: 5px; color: #007cba;">
                                (<?php
                /* translators: %d: number of log files */
                echo esc_html( sprintf( __('%d log files', 'advanced-log-manager'), count($smtp_status['available_files']) ) ); ?>)
                            </small>
                            <?php endif; ?>
                        </span>
                        <?php endif; ?>
                        <a href="<?php echo esc_url( admin_url('tools.php?page=almgr-all-logs-activity&tab=smtp') ); ?>" class="button button-small" style="margin-left: 10px;">
                            <?php esc_html_e('View SMTP Logs', 'advanced-log-manager'); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="almgr-log-cleanup-section">
                    <h3><?php esc_html_e('Log Management', 'advanced-log-manager'); ?></h3>
                    <p class="description"><?php esc_html_e('Manage and cleanup log files created by the plugin.', 'advanced-log-manager'); ?></p>

                    <div class="almgr-cleanup-actions">
                        <div class="almgr-cleanup-item">
                            <div class="almgr-cleanup-info">
                                <strong><?php esc_html_e('Debug Logs Cleanup', 'advanced-log-manager'); ?></strong>
                                <p class="description"><?php esc_html_e('Remove old wp-errors-* log files, keeping only the most recent ones.', 'advanced-log-manager'); ?></p>
                            </div>
                            <div class="almgr-cleanup-controls almgr-logs-actions">
                                <select id="debug-cleanup-keep-count">
                                    <option value="1"><?php esc_html_e('Keep 1 file', 'advanced-log-manager'); ?></option>
                                    <option value="3" selected><?php esc_html_e('Keep 3 files', 'advanced-log-manager'); ?></option>
                                    <option value="5"><?php esc_html_e('Keep 5 files', 'advanced-log-manager'); ?></option>
                                </select>
                                <button type="button" id="cleanup-debug-logs" class="button">
                                    <span class="dashicons dashicons-admin-tools"></span>
                                    <?php esc_html_e('Cleanup Debug Logs', 'advanced-log-manager'); ?>
                                </button>
                            </div>
                        </div>

                        <div class="almgr-cleanup-item">
                            <div class="almgr-cleanup-info">
                                <strong><?php esc_html_e('All Logs Cleanup', 'advanced-log-manager'); ?></strong>
                                <p class="description"><?php esc_html_e('Remove all log files created by the plugin (wp-errors-*, wp-queries-*, etc.).', 'advanced-log-manager'); ?></p>
                            </div>
                            <div class="almgr-cleanup-controls almgr-logs-actions">
                                <label>
                                    <input type="checkbox" id="include-current-logs">
                                    <?php esc_html_e('Include current active logs', 'advanced-log-manager'); ?>
                                </label>
                                <button type="button" id="cleanup-all-logs" class="button button-secondary">
                                    <span class="dashicons dashicons-trash"></span>
                                    <?php esc_html_e('Remove All Logs', 'advanced-log-manager'); ?>
                                </button>
                            </div>
                        </div>

                        <div class="almgr-cleanup-item">
                            <div class="almgr-cleanup-info">
                                <strong><?php esc_html_e('Query Log Rotation Cleanup', 'advanced-log-manager'); ?></strong>
                                <p class="description"><?php esc_html_e('Remove old query log rotation files (query.log.1, query.log.2, etc.) that accumulate over time.', 'advanced-log-manager'); ?></p>
                            </div>
                            <div class="almgr-cleanup-controls almgr-logs-actions">
                                <label>
                                    <input type="checkbox" id="keep-latest-rotation" checked>
                                    <?php esc_html_e('Keep latest backup (query.log.1)', 'advanced-log-manager'); ?>
                                </label>
                                <button type="button" id="cleanup-query-rotation-logs" class="button button-secondary">
                                    <span class="dashicons dashicons-database-remove"></span>
                                    <?php esc_html_e('Cleanup Rotation Files', 'advanced-log-manager'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Monitor Tab -->
        <div id="tab-perf-monitor" class="almgr-tab-content">
            <div class="almgr-card">
                <h2><?php esc_html_e('Performance Monitor', 'advanced-log-manager'); ?></h2>

                <div class="almgr-form-section">
                    <label class="almgr-toggle-label">
                        <span><?php esc_html_e('Enable Performance Bar', 'advanced-log-manager'); ?></span>
                        <div class="almgr-toggle-wrapper">
                            <input type="checkbox" id="perf-monitor-toggle" <?php checked($perf_monitor_enabled); ?>>
                            <div class="almgr-toggle <?php echo esc_attr($perf_monitor_enabled ? 'active' : ''); ?>">
                                <div class="almgr-toggle-slider"></div>
                            </div>
                        </div>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Menampilkan bar performa di bagian bawah halaman untuk user yang login.', 'advanced-log-manager'); ?>
                    </p>
                    <p class="description">
                        <?php esc_html_e('Peringatan: Mengaktifkan Performance Bar dapat menambah sedikit overhead pada waktu muat halaman karena pengumpulan metrik. Disarankan hanya untuk kebutuhan debugging/lingkungan pengembangan.', 'advanced-log-manager'); ?>
                    </p>
                </div>

                <!-- Granular feature toggles -->
                <div class="almgr-form-section">
                    <label class="almgr-toggle-label">
                        <span><?php esc_html_e('Enable Real-time Hooks Monitoring', 'advanced-log-manager'); ?></span>
                        <div class="almgr-toggle-wrapper <?php echo esc_attr(!$perf_monitor_enabled ? 'disabled' : ''); ?>">
                            <input type="checkbox" id="perf-realtime-toggle" <?php checked($perf_realtime_enabled); ?> <?php echo !$perf_monitor_enabled ? 'disabled' : ''; ?>>
                            <div class="almgr-toggle <?php echo esc_attr($perf_realtime_enabled ? 'active' : ''); ?>">
                                <div class="almgr-toggle-slider"></div>
                            </div>
                        </div>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Pantau eksekusi hook strategis secara real-time (dioptimalkan, bukan hook "all").', 'advanced-log-manager'); ?>
                    </p>
                </div>

                <div class="almgr-form-section">
                    <label class="almgr-toggle-label">
                        <span><?php esc_html_e('Enable Bootstrap Phases Snapshots', 'advanced-log-manager'); ?></span>
                        <div class="almgr-toggle-wrapper <?php echo esc_attr(!$perf_monitor_enabled ? 'disabled' : ''); ?>">
                            <input type="checkbox" id="perf-bootstrap-toggle" <?php checked($perf_bootstrap_enabled); ?> <?php echo !$perf_monitor_enabled ? 'disabled' : ''; ?>>
                            <div class="almgr-toggle <?php echo esc_attr($perf_bootstrap_enabled ? 'active' : ''); ?>">
                                <div class="almgr-toggle-slider"></div>
                            </div>
                        </div>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Ambil snapshot evolusi hook pada fase bootstrap WordPress.', 'advanced-log-manager'); ?>
                    </p>
                </div>

                <div class="almgr-form-section">
                    <label class="almgr-toggle-label">
                        <span><?php esc_html_e('Enable Domain-specific Panels', 'advanced-log-manager'); ?></span>
                        <div class="almgr-toggle-wrapper <?php echo !$perf_monitor_enabled ? 'disabled' : ''; ?>">
                            <input type="checkbox" id="perf-domains-toggle" <?php checked($perf_domains_enabled); ?> <?php echo !$perf_monitor_enabled ? 'disabled' : ''; ?>>
                            <div class="almgr-toggle <?php echo esc_attr($perf_domains_enabled ? 'active' : ''); ?>">
                                <div class="almgr-toggle-slider"></div>
                            </div>
                        </div>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Tampilkan panel analisis berdasarkan domain (Database, HTTP, Template, dsb).', 'advanced-log-manager'); ?>
                    </p>
                </div>

                <div class="almgr-preview-section">
                    <h3><?php esc_html_e('Frontend Display Preview', 'advanced-log-manager'); ?></h3>
                    <div class="almgr-performance-preview">
                        <div class="almgr-perf-preview-bar">
                            <div class="almgr-perf-item">
                                <span class="dashicons dashicons-update"></span>
                                <span class="value">15</span>
                                <span class="label">queries</span>
                            </div>
                            <div class="almgr-perf-item">
                                <span class="dashicons dashicons-clock"></span>
                                <span class="value">1.2s</span>
                                <span class="label">time</span>
                            </div>
                            <div class="almgr-perf-item">
                                <span class="dashicons dashicons-database"></span>
                                <span class="value">45.2MB</span>
                                <span class="label">memory</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="almgr-form-section">
                    <label>
                        <input type="checkbox" id="perf-monitor-logged-only" checked disabled>
                        <?php esc_html_e('Show for logged-in users only', 'advanced-log-manager'); ?>
                    </label>
                </div>
            </div>
        </div>

        <!-- File Editor Tab -->
        <div id="tab-file-editor" class="almgr-tab-content">
            <div class="almgr-card">
                <h2><?php esc_html_e('.htaccess Editor', 'advanced-log-manager'); ?></h2>

                <div class="almgr-backup-info">
                    <div class="almgr-backup-status">
                        <span class="dashicons dashicons-backup"></span>
                        <span><?php esc_html_e('Backups:', 'advanced-log-manager'); ?>
                            <strong><?php echo esc_html(count($htaccess_backups)); ?>/3</strong> available
                        </span>
                    </div>
                    <?php if (!empty($htaccess_backups)): ?>
                    <div class="almgr-backup-last">
                        <span><?php esc_html_e('Last backup:', 'advanced-log-manager'); ?>
                            <?php echo human_time_diff($htaccess_backups[0]['timestamp']); ?> ago
                        </span>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!$htaccess_info['writable']): ?>
                <div class="notice notice-error">
                    <p><?php esc_html_e('.htaccess file is not writable. Please check file permissions.', 'advanced-log-manager'); ?></p>
                </div>
                <?php endif; ?>

                <div class="almgr-editor-section">
                    <textarea id="htaccess-editor" class="almgr-code-editor" rows="20" <?php echo !$htaccess_info['writable'] ? 'readonly' : ''; ?>><?php echo esc_textarea($htaccess_service->get_htaccess_content()); ?></textarea>
                </div>

                <div class="almgr-editor-actions">
                    <button type="button" id="save-htaccess" class="button button-primary" <?php echo !$htaccess_info['writable'] ? 'disabled' : ''; ?>>
                        <span class="dashicons dashicons-backup"></span>
                        <?php esc_html_e('Backup & Save', 'advanced-log-manager'); ?>
                    </button>

                    <?php if (!empty($htaccess_backups)): ?>
                    <div class="almgr-restore-dropdown">
                        <button type="button" id="restore-htaccess-btn" class="button">
                            <span class="dashicons dashicons-undo"></span>
                            <?php esc_html_e('Restore', 'advanced-log-manager'); ?>
                        </button>
                        <div class="almgr-restore-menu">
                            <?php foreach ($htaccess_backups as $index => $backup): ?>
                            <a href="#" class="almgr-restore-item" data-index="<?php echo esc_attr($index); ?>">
                                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $backup['timestamp'])); ?>
                                <span class="size">(<?php echo esc_html(almgr_format_bytes($backup['size'])); ?>)</span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <button type="button" id="cancel-htaccess" class="button">
                        <?php esc_html_e('Cancel', 'advanced-log-manager'); ?>
                    </button>
                </div>

                <div class="almgr-htaccess-snippets">
                    <h3><?php esc_html_e('Common Snippets', 'advanced-log-manager'); ?></h3>
                    <div class="almgr-snippet-buttons">
                        <?php foreach ($htaccess_service->get_common_snippets() as $key => $snippet): ?>
                        <button type="button" class="button almgr-snippet-btn" data-snippet="<?php echo esc_attr($snippet['content']); ?>">
                            <?php echo esc_html($snippet['title']); ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- PHP Config Tab -->
        <div id="tab-php-config" class="almgr-tab-content">
            <div class="almgr-card">
                <h2><?php esc_html_e('PHP Configuration Presets', 'advanced-log-manager'); ?></h2>

                <div class="almgr-config-method">
                    <p>
                        <span class="dashicons dashicons-info"></span>
                        <?php esc_html_e('Configuration Method:', 'advanced-log-manager'); ?>
                        <strong><?php echo esc_html( ucfirst($php_config_method['method'] ?: 'Not Available') ); ?></strong>
                    </p>
                </div>

                <div class="almgr-preset-selection">
                    <h3><?php esc_html_e('Select Preset:', 'advanced-log-manager'); ?></h3>
                    <div class="almgr-preset-options">
                        <?php foreach ($php_presets as $key => $preset): ?>
                        <label class="almgr-preset-option <?php echo $current_php_preset === $key ? 'selected' : ''; ?>" data-preset="<?php echo esc_attr($key); ?>">
                            <input type="radio" name="php_preset" value="<?php echo esc_attr($key); ?>" <?php checked($current_php_preset, $key); ?>>
                            <div class="almgr-preset-card">
                                <h4><?php echo esc_html($preset['name']); ?></h4>
                                <p class="description"><?php echo esc_html($preset['description']); ?></p>
                                <div class="almgr-preset-settings">
                                    <?php foreach ($preset['settings'] as $setting => $value): ?>
                                    <div class="almgr-setting-item">
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

                <div class="almgr-current-config">
                    <h3><?php esc_html_e('Current Configuration', 'advanced-log-manager'); ?></h3>
                    <div class="almgr-config-table">
                        <?php foreach ($current_php_config as $setting => $value): ?>
                        <div class="almgr-config-row">
                            <span class="config-name"><?php echo esc_html(str_replace('_', ' ', ucwords($setting, '_'))); ?>:</span>
                            <span class="config-value"><?php echo esc_html($value); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="almgr-config-actions">
                    <button type="button" id="apply-php-preset" class="button button-primary">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php esc_html_e('Apply Configuration', 'advanced-log-manager'); ?>
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Loading overlay -->
<div id="almgr-loading-overlay" class="almgr-loading-overlay" style="display: none;">
    <div class="almgr-loading-content">
        <div class="almgr-spinner"></div>
        <p><?php esc_html_e('Processing...', 'advanced-log-manager'); ?></p>
    </div>
</div>
