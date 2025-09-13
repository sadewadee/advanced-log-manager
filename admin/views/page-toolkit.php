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

    <!-- Overview Status Section -->
    <div class="almgr-overview-section">
        <div class="almgr-overview-header">
            <h2 class="almgr-overview-title">
                <span class="dashicons dashicons-dashboard"></span>
                <?php esc_html_e('System Overview', 'advanced-log-manager'); ?>
            </h2>
        </div>

        <div class="almgr-status-indicators">
            <!-- Debug Mode Status -->
            <div class="almgr-status-card">
                <div class="almgr-status-icon <?php echo esc_attr($debug_enabled ? 'success' : 'inactive'); ?>">
                    <span class="dashicons <?php echo esc_attr($debug_enabled ? 'dashicons-yes-alt' : 'dashicons-dismiss'); ?>"></span>
                </div>
                <div class="almgr-status-content">
                    <p class="almgr-status-label"><?php esc_html_e('Debug Mode', 'advanced-log-manager'); ?></p>
                    <p class="almgr-status-value <?php echo esc_attr($debug_enabled ? 'success' : ''); ?>">
                        <?php echo $debug_enabled ? esc_html__('Active', 'advanced-log-manager') : esc_html__('Inactive', 'advanced-log-manager'); ?>
                    </p>
                </div>
            </div>

            <!-- Performance Status -->
            <div class="almgr-status-card">
                <div class="almgr-status-icon <?php echo esc_attr($perf_monitor_enabled ? 'success' : 'inactive'); ?>">
                    <span class="dashicons <?php echo esc_attr($perf_monitor_enabled ? 'dashicons-performance' : 'dashicons-clock'); ?>"></span>
                </div>
                <div class="almgr-status-content">
                    <p class="almgr-status-label"><?php esc_html_e('Performance Monitor', 'advanced-log-manager'); ?></p>
                    <p class="almgr-status-value <?php echo esc_attr($perf_monitor_enabled ? 'success' : ''); ?>">
                        <?php echo $perf_monitor_enabled ? esc_html__('Monitoring', 'advanced-log-manager') : esc_html__('Disabled', 'advanced-log-manager'); ?>
                    </p>
                </div>
            </div>

            <!-- Debug Log Status -->
            <?php
            $debug_log_exists = file_exists(almgr_get_debug_log_path());
            $debug_log_size = $debug_log_exists ? filesize(almgr_get_debug_log_path()) : 0;
            $debug_log_status = 'inactive';
            if ($debug_log_exists && $debug_log_size > 0) {
                $debug_log_status = $debug_log_size > (10 * 1024 * 1024) ? 'warning' : 'success'; // 10MB threshold
            }
            ?>
            <div class="almgr-status-card almgr-clickable-card" data-navigate="<?php echo esc_url(admin_url('tools.php?page=almgr-all-logs-activity&tab=debug')); ?>">
                <div class="almgr-status-icon <?php echo esc_attr($debug_log_status); ?>">
                    <span class="dashicons dashicons-media-text"></span>
                </div>
                <div class="almgr-status-content">
                    <p class="almgr-status-label"><?php esc_html_e('Debug Log', 'advanced-log-manager'); ?></p>
                    <p class="almgr-status-value <?php echo esc_attr($debug_log_status); ?>">
                        <?php
                        if ($debug_log_exists && $debug_log_size > 0) {
                            echo esc_html__('Active', 'advanced-log-manager') . ' - ' . esc_html(almgr_format_bytes($debug_log_size));
                        } else {
                            esc_html_e('No logs', 'advanced-log-manager');
                        }
                        ?>
                    </p>
                </div>
            </div>

            <!-- Query Log Status -->
            <?php
            $query_log_exists = file_exists(almgr_get_query_log_path());
            $query_log_size = $query_log_exists ? filesize(almgr_get_query_log_path()) : 0;
            $query_log_status = 'inactive';
            if ($savequeries && $query_log_exists && $query_log_size > 0) {
                $query_log_status = 'warning'; // Query logging impacts performance
            } elseif ($savequeries) {
                $query_log_status = 'success';
            }
            ?>
            <div class="almgr-status-card almgr-clickable-card" data-navigate="<?php echo esc_url(admin_url('tools.php?page=almgr-all-logs-activity&tab=query')); ?>">
                <div class="almgr-status-icon <?php echo esc_attr($query_log_status); ?>">
                    <span class="dashicons dashicons-database"></span>
                </div>
                <div class="almgr-status-content">
                    <p class="almgr-status-label"><?php esc_html_e('Query Log', 'advanced-log-manager'); ?></p>
                    <p class="almgr-status-value <?php echo esc_attr($query_log_status); ?>">
                        <?php
                        if ($savequeries && $query_log_exists && $query_log_size > 0) {
                            echo esc_html__('Active', 'advanced-log-manager') . ' - ' . esc_html(almgr_format_bytes($query_log_size));
                        } elseif ($savequeries) {
                            esc_html_e('Active - No logs yet', 'advanced-log-manager');
                        } else {
                            esc_html_e('Disabled', 'advanced-log-manager');
                        }
                        ?>
                    </p>
                </div>
            </div>

            <!-- SMTP Log Status -->
            <?php
            // Get SMTP logging status for overview
            $smtp_service = $plugin->get_service('smtp_logger');
            $smtp_overview_status = $smtp_service ? $smtp_service->get_logging_status() : array('enabled' => false);
            $smtp_overview_enabled = $smtp_overview_status['enabled'];
            $smtp_overview_log_status = 'inactive';
            if ($smtp_overview_enabled && $smtp_overview_status['current_log_exists']) {
                $smtp_overview_log_status = 'success';
            } elseif ($smtp_overview_enabled) {
                $smtp_overview_log_status = 'warning';
            }
            ?>
            <div class="almgr-status-card almgr-clickable-card" data-navigate="<?php echo esc_url(admin_url('tools.php?page=almgr-all-logs-activity&tab=smtp')); ?>">
                <div class="almgr-status-icon <?php echo esc_attr($smtp_overview_log_status); ?>">
                    <span class="dashicons dashicons-email"></span>
                </div>
                <div class="almgr-status-content">
                    <p class="almgr-status-label"><?php esc_html_e('SMTP Logs', 'advanced-log-manager'); ?></p>
                    <p class="almgr-status-value <?php echo esc_attr($smtp_overview_log_status); ?>">
                        <?php
                        if ($smtp_overview_enabled && $smtp_overview_status['current_log_exists']) {
                            echo esc_html__('Active', 'advanced-log-manager') . ' - ' . esc_html($smtp_overview_status['current_log_size']);
                        } elseif ($smtp_overview_enabled) {
                            esc_html_e('Active - No logs yet', 'advanced-log-manager');
                        } else {
                            esc_html_e('Disabled', 'advanced-log-manager');
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Master Switch Section -->
    <div class="almgr-master-switch-section">
        <div class="almgr-master-switch-card">
            <div class="almgr-master-switch-header">
                <div class="almgr-master-switch-info">
                    <h2 class="almgr-master-switch-title">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php esc_html_e('Enable Debug Mode', 'advanced-log-manager'); ?>
                    </h2>
                    <p class="almgr-master-switch-description">
                        <?php esc_html_e('Enable or disable all debug features. This controls WordPress debug constants and logging functionality.', 'advanced-log-manager'); ?>
                    </p>
                </div>
                <div class="almgr-master-switch-control">
                    <div class="almgr-master-toggle-wrapper">
                        <input type="checkbox" id="master-debug-toggle" <?php checked($debug_enabled); ?>>
                        <div class="almgr-master-toggle <?php echo esc_attr($debug_enabled ? 'active' : ''); ?>">
                            <div class="almgr-master-toggle-slider"></div>
                        </div>
                        <label for="master-debug-toggle" class="almgr-master-toggle-label">
                            <span class="almgr-toggle-status">
                                <?php echo $debug_enabled ? esc_html__('Debug Mode Enabled', 'advanced-log-manager') : esc_html__('Debug Mode Disabled', 'advanced-log-manager'); ?>
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Quick Status Indicators -->
            <div class="almgr-master-switch-indicators">
                <div class="almgr-quick-indicator <?php echo esc_attr($debug_enabled && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'active' : 'inactive'); ?>">
                    <span class="dashicons dashicons-media-text"></span>
                    <span class="almgr-indicator-label"><?php esc_html_e('Error Logging', 'advanced-log-manager'); ?></span>
                </div>
                <div class="almgr-quick-indicator <?php echo esc_attr($debug_enabled && defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ? 'active' : 'inactive'); ?>">
                    <span class="dashicons dashicons-visibility"></span>
                    <span class="almgr-indicator-label"><?php esc_html_e('Error Display', 'advanced-log-manager'); ?></span>
                </div>
                <div class="almgr-quick-indicator <?php echo esc_attr($debug_enabled && defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? 'active' : 'inactive'); ?>">
                    <span class="dashicons dashicons-editor-code"></span>
                    <span class="almgr-indicator-label"><?php esc_html_e('Script Debug', 'advanced-log-manager'); ?></span>
                </div>
                <?php
                $savequeries_active = defined('SAVEQUERIES') ? constant('SAVEQUERIES') : false;
                ?>
                <div class="almgr-quick-indicator <?php echo esc_attr($debug_enabled && $savequeries_active ? 'active' : 'inactive'); ?>">
                    <span class="dashicons dashicons-database"></span>
                    <span class="almgr-indicator-label"><?php esc_html_e('Query Logging', 'advanced-log-manager'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature Cards Navigation -->
    <div class="almgr-feature-cards">
        <!-- Debug Management Card -->
        <div class="almgr-feature-card" data-tab="debug-management">
            <div class="almgr-card-header">
                <div class="almgr-card-icon">
                    <span class="dashicons dashicons-admin-tools"></span>
                </div>
                <div class="almgr-card-title">
                    <h3><?php esc_html_e('Debug Management', 'advanced-log-manager'); ?></h3>
                    <p class="almgr-card-description"><?php esc_html_e('Control WordPress debug settings and log management', 'advanced-log-manager'); ?></p>
                </div>
                <div class="almgr-card-status">
                    <span class="almgr-status-badge <?php echo esc_attr($debug_enabled ? 'active' : 'inactive'); ?>">
                        <?php echo $debug_enabled ? esc_html__('Active', 'advanced-log-manager') : esc_html__('Inactive', 'advanced-log-manager'); ?>
                    </span>
                </div>
            </div>
            <div class="almgr-card-actions">
                <button class="almgr-card-toggle-btn" data-target="debug-management">
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                    <?php esc_html_e('Configure', 'advanced-log-manager'); ?>
                </button>
            </div>
        </div>

        <!-- Performance Monitor Card -->
        <div class="almgr-feature-card" data-tab="perf-monitor">
            <div class="almgr-card-header">
                <div class="almgr-card-icon">
                    <span class="dashicons dashicons-performance"></span>
                </div>
                <div class="almgr-card-title">
                    <h3><?php esc_html_e('Performance Monitor', 'advanced-log-manager'); ?></h3>
                    <p class="almgr-card-description"><?php esc_html_e('Monitor site performance and query analysis', 'advanced-log-manager'); ?></p>
                </div>
                <div class="almgr-card-status">
                    <span class="almgr-status-badge <?php echo esc_attr($perf_monitor_enabled ? 'active' : 'inactive'); ?>">
                        <?php echo $perf_monitor_enabled ? esc_html__('Monitoring', 'advanced-log-manager') : esc_html__('Disabled', 'advanced-log-manager'); ?>
                    </span>
                </div>
            </div>
            <div class="almgr-card-actions">
                <button class="almgr-card-toggle-btn" data-target="perf-monitor">
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                    <?php esc_html_e('Configure', 'advanced-log-manager'); ?>
                </button>
            </div>
        </div>

        <!-- File Editor Card -->
        <div class="almgr-feature-card" data-tab="file-editor">
            <div class="almgr-card-header">
                <div class="almgr-card-icon">
                    <span class="dashicons dashicons-edit"></span>
                </div>
                <div class="almgr-card-title">
                    <h3><?php esc_html_e('.htaccess Editor', 'advanced-log-manager'); ?></h3>
                    <p class="almgr-card-description"><?php esc_html_e('Edit .htaccess configuration files', 'advanced-log-manager'); ?></p>
                </div>
                <div class="almgr-card-status">
                    <span class="almgr-status-badge info">
                        <?php esc_html_e('Available', 'advanced-log-manager'); ?>
                    </span>
                </div>
            </div>
            <div class="almgr-card-actions">
                <button class="almgr-card-toggle-btn" data-target="file-editor">
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                    <?php esc_html_e('Open Editor', 'advanced-log-manager'); ?>
                </button>
            </div>
        </div>

        <!-- PHP Config Card -->
        <div class="almgr-feature-card" data-tab="php-config">
            <div class="almgr-card-header">
                <div class="almgr-card-icon">
                    <span class="dashicons dashicons-admin-settings"></span>
                </div>
                <div class="almgr-card-title">
                    <h3><?php esc_html_e('PHP Config', 'advanced-log-manager'); ?></h3>
                    <p class="almgr-card-description"><?php esc_html_e('PHP configuration presets and optimization', 'advanced-log-manager'); ?></p>
                </div>
                <div class="almgr-card-status">
                    <span class="almgr-status-badge info">
                        <?php esc_html_e('Ready', 'advanced-log-manager'); ?>
                    </span>
                </div>
            </div>
            <div class="almgr-card-actions">
                <button class="almgr-card-toggle-btn" data-target="php-config">
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                    <?php esc_html_e('Configure', 'advanced-log-manager'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Tab Contents -->
    <div class="almgr-tab-contents">

        <!-- Debug Management Tab -->
        <div id="tab-debug-management" class="almgr-tab-content active">
            <div class="almgr-debug-sections">
                <!-- Basic Debug Settings -->
                <div class="almgr-collapsible-section" data-section="basic-debug">
                    <div class="almgr-section-header">
                        <div class="almgr-section-title">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <h3><?php esc_html_e('Basic Debug Settings', 'advanced-log-manager'); ?></h3>
                        </div>
                        <button class="almgr-section-toggle" type="button">
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </button>
                    </div>
                    <div class="almgr-section-content">
                        <p class="almgr-section-description"><?php esc_html_e('Essential debug configuration for WordPress development and troubleshooting.', 'advanced-log-manager'); ?></p>

                        <!-- Enable Debug Mode Toggle -->
                        <div class="almgr-setting-item">
                            <div class="almgr-toggle-wrapper">
                                <input type="checkbox" id="debug-mode-toggle" <?php checked($debug_enabled); ?>>
                                <div class="almgr-toggle <?php echo esc_attr($debug_enabled ? 'active' : ''); ?>">
                                    <div class="almgr-toggle-slider"></div>
                                </div>
                                <label for="debug-mode-toggle" class="almgr-toggle-label">
                                    <span class="almgr-setting-title"><?php esc_html_e('Enable Debug Mode', 'advanced-log-manager'); ?></span>
                                    <small class="almgr-setting-description"><?php esc_html_e('Master switch for all debug functionality', 'advanced-log-manager'); ?></small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- WordPress Debug Constants -->
                <div class="almgr-collapsible-section" data-section="wp-debug">
                    <div class="almgr-section-header">
                        <div class="almgr-section-title">
                            <span class="dashicons dashicons-wordpress"></span>
                            <h3><?php esc_html_e('WordPress Debug Constants', 'advanced-log-manager'); ?></h3>
                        </div>
                        <button class="almgr-section-toggle" type="button">
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </button>
                    </div>
                    <div class="almgr-section-content">
                        <p class="almgr-section-description"><?php esc_html_e('Configure WordPress-specific debug constants and error handling.', 'advanced-log-manager'); ?></p>

                        <div class="almgr-settings-grid" <?php echo !$debug_enabled ? 'data-disabled="true"' : ''; ?>>
                            <div class="almgr-setting-item">
                                <div class="almgr-toggle-wrapper <?php echo esc_attr(!$debug_enabled ? 'disabled' : ''); ?>">
                                    <input type="checkbox" id="wp-debug-log-toggle" <?php checked(defined('WP_DEBUG_LOG') && WP_DEBUG_LOG); ?> <?php disabled(!$debug_enabled); ?>>
                                    <div class="almgr-toggle <?php echo esc_attr((defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && $debug_enabled) ? 'active' : ''); ?>">
                                        <div class="almgr-toggle-slider"></div>
                                    </div>
                                    <label for="wp-debug-log-toggle" class="almgr-toggle-label">
                                        <span class="almgr-setting-title">WP_DEBUG_LOG</span>
                                        <small class="almgr-setting-description"><?php esc_html_e('Log errors to debug.log file', 'advanced-log-manager'); ?></small>
                                    </label>
                                </div>
                            </div>

                            <div class="almgr-setting-item">
                                <div class="almgr-toggle-wrapper <?php echo esc_attr(!$debug_enabled ? 'disabled' : ''); ?>">
                                    <input type="checkbox" id="wp-debug-display-toggle" <?php checked(defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY); ?> <?php disabled(!$debug_enabled); ?>>
                                    <div class="almgr-toggle <?php echo esc_attr((defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY && $debug_enabled) ? 'active' : ''); ?>">
                                        <div class="almgr-toggle-slider"></div>
                                    </div>
                                    <label for="wp-debug-display-toggle" class="almgr-toggle-label">
                                        <span class="almgr-setting-title">WP_DEBUG_DISPLAY</span>
                                        <small class="almgr-setting-description"><?php esc_html_e('Display errors on screen (not recommended for production)', 'advanced-log-manager'); ?></small>
                                    </label>
                                </div>
                            </div>

                            <div class="almgr-setting-item">
                                <div class="almgr-toggle-wrapper <?php echo esc_attr(!$debug_enabled ? 'disabled' : ''); ?>">
                                    <input type="checkbox" id="script-debug-toggle" <?php checked(defined('SCRIPT_DEBUG') && SCRIPT_DEBUG); ?> <?php disabled(!$debug_enabled); ?>>
                                    <div class="almgr-toggle <?php echo esc_attr((defined('SCRIPT_DEBUG') && SCRIPT_DEBUG && $debug_enabled) ? 'active' : ''); ?>">
                                        <div class="almgr-toggle-slider"></div>
                                    </div>
                                    <label for="script-debug-toggle" class="almgr-toggle-label">
                                        <span class="almgr-setting-title">SCRIPT_DEBUG</span>
                                        <small class="almgr-setting-description"><?php esc_html_e('Use unminified JavaScript and CSS files', 'advanced-log-manager'); ?></small>
                                    </label>
                                </div>
                            </div>

                            <div class="almgr-setting-item">
                                <div class="almgr-toggle-wrapper <?php echo !$debug_enabled ? 'disabled' : ''; ?>">
                                    <input type="checkbox" id="savequeries-toggle" <?php checked($savequeries); ?> <?php disabled(!$debug_enabled); ?>>
                                    <div class="almgr-toggle <?php echo esc_attr(($savequeries && $debug_enabled) ? 'active' : ''); ?>">
                                        <div class="almgr-toggle-slider"></div>
                                    </div>
                                    <label for="savequeries-toggle" class="almgr-toggle-label">
                                        <span class="almgr-setting-title">SAVEQUERIES</span>
                                        <small class="almgr-setting-description"><?php esc_html_e('Save database queries for analysis (impacts performance)', 'advanced-log-manager'); ?></small>
                                    </label>
                                </div>
                            </div>

                            <div class="almgr-setting-item">
                                <div class="almgr-toggle-wrapper <?php echo !$debug_enabled ? 'disabled' : ''; ?>">
                                    <input type="checkbox" id="display-errors-toggle" <?php checked($display_errors_on); ?> <?php disabled(!$debug_enabled); ?>>
                                    <div class="almgr-toggle <?php echo esc_attr(($display_errors_on && $debug_enabled) ? 'active' : ''); ?>">
                                        <div class="almgr-toggle-slider"></div>
                                    </div>
                                    <label for="display-errors-toggle" class="almgr-toggle-label">
                                        <span class="almgr-setting-title">display_errors</span>
                                        <small class="almgr-setting-description"><?php esc_html_e('PHP setting to display errors on screen', 'advanced-log-manager'); ?></small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email & SMTP Debug -->
                <div class="almgr-collapsible-section" data-section="smtp-debug">
                    <div class="almgr-section-header">
                        <div class="almgr-section-title">
                            <span class="dashicons dashicons-email"></span>
                            <h3><?php esc_html_e('Email & SMTP Debug', 'advanced-log-manager'); ?></h3>
                        </div>
                        <button class="almgr-section-toggle" type="button">
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </button>
                    </div>
                    <div class="almgr-section-content">
                        <p class="almgr-section-description"><?php esc_html_e('Monitor and debug email functionality including SMTP transactions.', 'advanced-log-manager'); ?></p>

                        <?php
                        // Get SMTP logging status
                        $smtp_service = $plugin->get_service('smtp_logger');
                        $smtp_status = $smtp_service ? $smtp_service->get_logging_status() : array('enabled' => false);
                        $smtp_enabled = $smtp_status['enabled'];
                        ?>

                        <div class="almgr-settings-grid" <?php echo !$debug_enabled ? 'data-disabled="true"' : ''; ?>>
                            <div class="almgr-setting-item">
                                <div class="almgr-toggle-wrapper <?php echo !$debug_enabled ? 'disabled' : ''; ?>">
                                    <input type="checkbox" id="smtp-logging-toggle" <?php checked($smtp_enabled); ?> <?php disabled(!$debug_enabled); ?>>
                                    <div class="almgr-toggle <?php echo esc_attr(($smtp_enabled && $debug_enabled) ? 'active' : ''); ?>">
                                        <div class="almgr-toggle-slider"></div>
                                    </div>
                                    <label for="smtp-logging-toggle" class="almgr-toggle-label">
                                        <span class="almgr-setting-title">SMTP Logging</span>
                                        <small class="almgr-setting-description"><?php esc_html_e('Log all email activity to daily smtp-ddmmyyyy.log files', 'advanced-log-manager'); ?></small>
                                    </label>
                                </div>
                            </div>

                            <?php
                            // Get IP address logging status
                            $ip_logging_enabled = \get_option('almgr_smtp_log_ip_address', false);
                            ?>
                            <div class="almgr-setting-item">
                                <div class="almgr-toggle-wrapper <?php echo esc_attr((!$debug_enabled || !$smtp_enabled) ? 'disabled' : ''); ?>">
                                    <input type="checkbox" id="smtp-ip-logging-toggle" <?php checked($ip_logging_enabled); ?> <?php disabled(!$debug_enabled || !$smtp_enabled); ?>>
                                    <div class="almgr-toggle <?php echo esc_attr(($ip_logging_enabled && $debug_enabled && $smtp_enabled) ? 'active' : ''); ?>">
                                        <div class="almgr-toggle-slider"></div>
                                    </div>
                                    <label for="smtp-ip-logging-toggle" class="almgr-toggle-label">
                                        <span class="almgr-setting-title">IP Address Logging</span>
                                        <small class="almgr-setting-description"><?php esc_html_e('Include originating IP addresses in SMTP logs', 'advanced-log-manager'); ?></small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Log Status & Quick Actions -->
                <div class="almgr-collapsible-section" data-section="log-status">
                    <div class="almgr-section-header">
                        <div class="almgr-section-title">
                            <span class="dashicons dashicons-media-text"></span>
                            <h3><?php esc_html_e('Log Status & Quick Actions', 'advanced-log-manager'); ?></h3>
                        </div>
                        <button class="almgr-section-toggle" type="button">
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </button>
                    </div>
                    <div class="almgr-section-content">
                        <p class="almgr-section-description"><?php esc_html_e('Monitor log file status and perform quick actions.', 'advanced-log-manager'); ?></p>

                        <div class="almgr-log-status-grid">
                            <!-- Debug Log Status -->
                            <?php if (file_exists(almgr_get_debug_log_path())): ?>
                            <div class="almgr-log-status-card">
                                <div class="almgr-log-status-header">
                                    <span class="dashicons dashicons-media-text"></span>
                                    <h4><?php esc_html_e('Debug Logs', 'advanced-log-manager'); ?></h4>
                                </div>
                                <div class="almgr-log-status-info">
                                    <span class="almgr-log-size"><?php echo esc_html(almgr_format_bytes(filesize(almgr_get_debug_log_path()))); ?></span>
                                </div>
                                <div class="almgr-log-status-actions">
                                    <a href="<?php echo esc_url( admin_url('tools.php?page=almgr-all-logs-activity&tab=debug') ); ?>" class="button button-small">
                                        <?php esc_html_e('View', 'advanced-log-manager'); ?>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Query Log Status -->
                            <?php if ($savequeries && file_exists(almgr_get_query_log_path())): ?>
                            <div class="almgr-log-status-card">
                                <div class="almgr-log-status-header">
                                    <span class="dashicons dashicons-database"></span>
                                    <h4><?php esc_html_e('Query Logs', 'advanced-log-manager'); ?></h4>
                                </div>
                                <div class="almgr-log-status-info">
                                    <span class="almgr-log-size"><?php echo esc_html(almgr_format_bytes(filesize(almgr_get_query_log_path()))); ?></span>
                                    <small class="almgr-log-note"><?php esc_html_e('Auto-rotates at', 'advanced-log-manager'); ?> <?php echo esc_html(almgr_format_bytes(almgr_get_query_log_max_size())); ?></small>
                                </div>
                                <div class="almgr-log-status-actions">
                                    <a href="<?php echo esc_url( admin_url('tools.php?page=almgr-all-logs-activity&tab=query') ); ?>" class="button button-small">
                                        <?php esc_html_e('View', 'advanced-log-manager'); ?>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- SMTP Log Status -->
                            <?php if ($smtp_enabled): ?>
                            <div class="almgr-log-status-card">
                                <div class="almgr-log-status-header">
                                    <span class="dashicons dashicons-email"></span>
                                    <h4><?php esc_html_e('SMTP Logs', 'advanced-log-manager'); ?></h4>
                                </div>
                                <div class="almgr-log-status-info">
                                    <?php if ($smtp_status['current_log_exists']): ?>
                                    <span class="almgr-log-size"><?php echo esc_html($smtp_status['current_log_size']); ?></span>
                                    <?php if (count($smtp_status['available_files']) > 0): ?>
                                    <small class="almgr-log-note"><?php echo esc_html( sprintf( __('%d log files', 'advanced-log-manager'), count($smtp_status['available_files']) ) ); ?></small>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <span class="almgr-log-size"><?php esc_html_e('No logs yet', 'advanced-log-manager'); ?></span>
                                    <small class="almgr-log-note"><?php esc_html_e('Logs will appear after first email', 'advanced-log-manager'); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="almgr-log-status-actions">
                                    <a href="<?php echo esc_url( admin_url('tools.php?page=almgr-all-logs-activity&tab=smtp') ); ?>" class="button button-small">
                                        <?php esc_html_e('View', 'advanced-log-manager'); ?>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Danger Zone -->
                <div class="almgr-collapsible-section almgr-danger-zone" data-section="danger-zone">
                    <div class="almgr-section-header">
                        <div class="almgr-section-title">
                            <span class="dashicons dashicons-warning"></span>
                            <h3><?php esc_html_e('Danger Zone', 'advanced-log-manager'); ?></h3>
                        </div>
                        <button class="almgr-section-toggle" type="button">
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </button>
                    </div>
                    <div class="almgr-section-content">
                        <div class="almgr-danger-warning">
                            <span class="dashicons dashicons-warning"></span>
                            <p><?php esc_html_e('These actions are irreversible. Please proceed with caution.', 'advanced-log-manager'); ?></p>
                        </div>

                        <div class="almgr-danger-actions">
                            <div class="almgr-danger-item">
                                <div class="almgr-danger-info">
                                    <h4><?php esc_html_e('Debug Logs Cleanup', 'advanced-log-manager'); ?></h4>
                                    <p class="description"><?php esc_html_e('Remove old wp-errors-* log files, keeping only the most recent ones.', 'advanced-log-manager'); ?></p>
                                </div>
                                <div class="almgr-danger-controls">
                                    <select id="debug-cleanup-keep-count">
                                        <option value="1"><?php esc_html_e('Keep 1 file', 'advanced-log-manager'); ?></option>
                                        <option value="3" selected><?php esc_html_e('Keep 3 files', 'advanced-log-manager'); ?></option>
                                        <option value="5"><?php esc_html_e('Keep 5 files', 'advanced-log-manager'); ?></option>
                                    </select>
                                    <button type="button" id="cleanup-debug-logs" class="button button-secondary">
                                        <span class="dashicons dashicons-admin-tools"></span>
                                        <?php esc_html_e('Cleanup Debug Logs', 'advanced-log-manager'); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="almgr-danger-item">
                                <div class="almgr-danger-info">
                                    <h4><?php esc_html_e('All Logs Cleanup', 'advanced-log-manager'); ?></h4>
                                    <p class="description"><?php esc_html_e('Remove all log files created by the plugin (wp-errors-*, wp-queries-*, etc.).', 'advanced-log-manager'); ?></p>
                                </div>
                                <div class="almgr-danger-controls">
                                    <label class="almgr-danger-checkbox">
                                        <input type="checkbox" id="include-current-logs">
                                        <?php esc_html_e('Include current active logs', 'advanced-log-manager'); ?>
                                    </label>
                                    <button type="button" id="cleanup-all-logs" class="button button-danger">
                                        <span class="dashicons dashicons-trash"></span>
                                        <?php esc_html_e('Remove All Logs', 'advanced-log-manager'); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="almgr-danger-item">
                                <div class="almgr-danger-info">
                                    <h4><?php esc_html_e('Query Log Rotation Cleanup', 'advanced-log-manager'); ?></h4>
                                    <p class="description"><?php esc_html_e('Remove old query log rotation files (query.log.1, query.log.2, etc.) that accumulate over time.', 'advanced-log-manager'); ?></p>
                                </div>
                                <div class="almgr-danger-controls">
                                    <label class="almgr-danger-checkbox">
                                        <input type="checkbox" id="keep-latest-rotation" checked>
                                        <?php esc_html_e('Keep latest backup (query.log.1)', 'advanced-log-manager'); ?>
                                    </label>
                                    <button type="button" id="cleanup-query-rotation-logs" class="button button-danger">
                                        <span class="dashicons dashicons-database-remove"></span>
                                        <?php esc_html_e('Cleanup Rotation Files', 'advanced-log-manager'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Monitor Tab -->
        <div id="tab-perf-monitor" class="almgr-tab-content">
            <!-- Performance Monitor Sections -->
            <div class="almgr-perf-sections">

                <!-- Master Performance Toggle -->
                <div class="almgr-collapsible-section almgr-section-open" data-section="perf-master">
                    <div class="almgr-section-header">
                        <div class="almgr-section-title">
                            <span class="dashicons dashicons-performance"></span>
                            <h3><?php esc_html_e('Performance Monitoring', 'advanced-log-manager'); ?></h3>
                        </div>
                        <button class="almgr-section-toggle" type="button">
                            <span class="dashicons dashicons-arrow-up-alt2"></span>
                        </button>
                    </div>
                    <div class="almgr-section-content">
                        <p class="almgr-section-description"><?php esc_html_e('Enable comprehensive performance monitoring with real-time metrics display.', 'advanced-log-manager'); ?></p>

                        <div class="almgr-settings-grid">
                            <div class="almgr-setting-item">
                                <div class="almgr-toggle-wrapper">
                                    <input type="checkbox" id="perf-monitor-toggle" <?php checked($perf_monitor_enabled); ?>>
                                    <div class="almgr-toggle <?php echo esc_attr($perf_monitor_enabled ? 'active' : ''); ?>">
                                        <div class="almgr-toggle-slider"></div>
                                    </div>
                                    <label for="perf-monitor-toggle" class="almgr-toggle-label">
                                        <span class="almgr-setting-title"><?php esc_html_e('Enable Performance Bar', 'advanced-log-manager'); ?></span>
                                        <small class="almgr-setting-description"><?php esc_html_e('Display performance metrics bar at the bottom of pages for logged-in users', 'advanced-log-manager'); ?></small>
                                    </label>
                                </div>
                            </div>

                            <div class="almgr-setting-item">
                                <div class="almgr-info-display">
                                    <div class="almgr-info-icon">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                    </div>
                                    <div class="almgr-info-content">
                                        <span class="almgr-setting-title"><?php esc_html_e('Logged Users Only', 'advanced-log-manager'); ?></span>
                                        <small class="almgr-setting-description"><?php esc_html_e('Performance bar is restricted to administrators only (security requirement)', 'advanced-log-manager'); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="almgr-warning-notice">
                            <span class="dashicons dashicons-warning"></span>
                            <p><?php esc_html_e('Performance monitoring adds minimal overhead. Recommended for development/staging environments.', 'advanced-log-manager'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Advanced Monitoring Features -->
                <div class="almgr-collapsible-section" data-section="perf-advanced">
                    <div class="almgr-section-header">
                        <div class="almgr-section-title">
                            <span class="dashicons dashicons-chart-line"></span>
                            <h3><?php esc_html_e('Advanced Monitoring', 'advanced-log-manager'); ?></h3>
                        </div>
                        <button class="almgr-section-toggle" type="button">
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </button>
                    </div>
                    <div class="almgr-section-content">
                        <p class="almgr-section-description"><?php esc_html_e('Configure detailed monitoring features for comprehensive performance analysis.', 'advanced-log-manager'); ?></p>

                        <div class="almgr-settings-grid">
                            <div class="almgr-setting-item">
                                <div class="almgr-toggle-wrapper <?php echo esc_attr(!$perf_monitor_enabled ? 'disabled' : ''); ?>">
                                    <input type="checkbox" id="perf-realtime-toggle" <?php checked($perf_realtime_enabled); ?> <?php disabled(!$perf_monitor_enabled); ?>>
                                    <div class="almgr-toggle <?php echo esc_attr($perf_realtime_enabled && $perf_monitor_enabled ? 'active' : ''); ?>">
                                        <div class="almgr-toggle-slider"></div>
                                    </div>
                                    <label for="perf-realtime-toggle" class="almgr-toggle-label">
                                        <span class="almgr-setting-title"><?php esc_html_e('Real-time Hooks Monitoring', 'advanced-log-manager'); ?></span>
                                        <small class="almgr-setting-description"><?php esc_html_e('Monitor strategic hook execution in real-time (optimized, not "all" hooks)', 'advanced-log-manager'); ?></small>
                                    </label>
                                </div>
                            </div>

                            <div class="almgr-setting-item">
                                <div class="almgr-toggle-wrapper <?php echo esc_attr(!$perf_monitor_enabled ? 'disabled' : ''); ?>">
                                    <input type="checkbox" id="perf-bootstrap-toggle" <?php checked($perf_bootstrap_enabled); ?> <?php disabled(!$perf_monitor_enabled); ?>>
                                    <div class="almgr-toggle <?php echo esc_attr($perf_bootstrap_enabled && $perf_monitor_enabled ? 'active' : ''); ?>">
                                        <div class="almgr-toggle-slider"></div>
                                    </div>
                                    <label for="perf-bootstrap-toggle" class="almgr-toggle-label">
                                        <span class="almgr-setting-title"><?php esc_html_e('Bootstrap Phases Snapshots', 'advanced-log-manager'); ?></span>
                                        <small class="almgr-setting-description"><?php esc_html_e('Capture hook evolution snapshots during WordPress bootstrap phases', 'advanced-log-manager'); ?></small>
                                    </label>
                                </div>
                            </div>

                            <div class="almgr-setting-item">
                                <div class="almgr-toggle-wrapper <?php echo esc_attr(!$perf_monitor_enabled ? 'disabled' : ''); ?>">
                                    <input type="checkbox" id="perf-domains-toggle" <?php checked($perf_domains_enabled); ?> <?php disabled(!$perf_monitor_enabled); ?>>
                                    <div class="almgr-toggle <?php echo esc_attr($perf_domains_enabled && $perf_monitor_enabled ? 'active' : ''); ?>">
                                        <div class="almgr-toggle-slider"></div>
                                    </div>
                                    <label for="perf-domains-toggle" class="almgr-toggle-label">
                                        <span class="almgr-setting-title"><?php esc_html_e('Domain-specific Panels', 'advanced-log-manager'); ?></span>
                                        <small class="almgr-setting-description"><?php esc_html_e('Show analysis panels by domain (Database, HTTP, Template, etc.)', 'advanced-log-manager'); ?></small>
                                    </label>
                                </div>
                            </div>

                            <div class="almgr-setting-item">
                                <div class="almgr-toggle-wrapper <?php echo esc_attr(!$perf_monitor_enabled ? 'disabled' : ''); ?>">
                                    <input type="checkbox" id="perf-memory-tracking" <?php disabled(!$perf_monitor_enabled); ?>>
                                    <div class="almgr-toggle">
                                        <div class="almgr-toggle-slider"></div>
                                    </div>
                                    <label for="perf-memory-tracking" class="almgr-toggle-label">
                                        <span class="almgr-setting-title"><?php esc_html_e('Memory Usage Tracking', 'advanced-log-manager'); ?></span>
                                        <small class="almgr-setting-description"><?php esc_html_e('Track detailed memory usage patterns and peak consumption', 'advanced-log-manager'); ?></small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Preview -->
                <div class="almgr-collapsible-section" data-section="perf-preview">
                    <div class="almgr-section-header">
                        <div class="almgr-section-title">
                            <span class="dashicons dashicons-visibility"></span>
                            <h3><?php esc_html_e('Frontend Preview', 'advanced-log-manager'); ?></h3>
                        </div>
                        <button class="almgr-section-toggle" type="button">
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </button>
                    </div>
                    <div class="almgr-section-content">
                        <p class="almgr-section-description"><?php esc_html_e('Preview how the performance bar will appear on your website frontend.', 'advanced-log-manager'); ?></p>

                        <!-- Enhanced Performance Preview Bar -->
                        <div class="almgr-performance-preview-container">
                            <div class="almgr-perf-preview-bar <?php echo esc_attr($perf_monitor_enabled ? 'active' : 'inactive'); ?>">
                                <!-- Core Metrics -->
                                <div class="almgr-perf-section almgr-perf-core">
                                    <div class="almgr-perf-item">
                                        <span class="almgr-perf-icon dashicons dashicons-clock"></span>
                                        <div class="almgr-perf-data">
                                            <span class="almgr-perf-value">1.24s</span>
                                            <span class="almgr-perf-label"><?php esc_html_e('Load Time', 'advanced-log-manager'); ?></span>
                                        </div>
                                    </div>

                                    <div class="almgr-perf-item">
                                        <span class="almgr-perf-icon dashicons dashicons-database"></span>
                                        <div class="almgr-perf-data">
                                            <span class="almgr-perf-value">15</span>
                                            <span class="almgr-perf-label"><?php esc_html_e('Queries', 'advanced-log-manager'); ?></span>
                                        </div>
                                    </div>

                                    <div class="almgr-perf-item">
                                        <span class="almgr-perf-icon dashicons dashicons-performance"></span>
                                        <div class="almgr-perf-data">
                                            <span class="almgr-perf-value">45.2MB</span>
                                            <span class="almgr-perf-label"><?php esc_html_e('Memory', 'advanced-log-manager'); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Advanced Indicators -->
                                <div class="almgr-perf-section almgr-perf-advanced">
                                    <div class="almgr-perf-item">
                                        <span class="almgr-perf-icon dashicons dashicons-admin-plugins"></span>
                                        <div class="almgr-perf-data">
                                            <span class="almgr-perf-value">23</span>
                                            <span class="almgr-perf-label"><?php esc_html_e('Hooks', 'advanced-log-manager'); ?></span>
                                        </div>
                                    </div>

                                    <div class="almgr-perf-item">
                                        <span class="almgr-perf-icon dashicons dashicons-admin-appearance"></span>
                                        <div class="almgr-perf-data">
                                            <span class="almgr-perf-value">0.18s</span>
                                            <span class="almgr-perf-label"><?php esc_html_e('Template', 'advanced-log-manager'); ?></span>
                                        </div>
                                    </div>

                                    <div class="almgr-perf-item">
                                        <span class="almgr-perf-icon dashicons dashicons-networking"></span>
                                        <div class="almgr-perf-data">
                                            <span class="almgr-perf-value">3</span>
                                            <span class="almgr-perf-label"><?php esc_html_e('HTTP', 'advanced-log-manager'); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status Indicators -->
                                <div class="almgr-perf-section almgr-perf-status">
                                    <div class="almgr-perf-status-indicator almgr-status-good">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        <span class="almgr-status-text"><?php esc_html_e('Good', 'advanced-log-manager'); ?></span>
                                    </div>

                                    <button class="almgr-perf-toggle-details" type="button">
                                        <span class="dashicons dashicons-info"></span>
                                        <?php esc_html_e('Details', 'advanced-log-manager'); ?>
                                    </button>
                                </div>
                            </div>

                            <!-- Preview Status -->
                            <div class="almgr-preview-status">
                                <?php if ($perf_monitor_enabled): ?>
                                    <span class="almgr-preview-badge active">
                                        <span class="dashicons dashicons-visibility"></span>
                                        <?php esc_html_e('Performance bar is active', 'advanced-log-manager'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="almgr-preview-badge inactive">
                                        <span class="dashicons dashicons-hidden"></span>
                                        <?php esc_html_e('Performance bar is disabled', 'advanced-log-manager'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
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
                            <?php echo esc_html(human_time_diff($htaccess_backups[0]['timestamp'])); ?> ago
                        </span>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!$htaccess_info['writable']): ?>
                <div class="notice notice-error">
                    <p><?php esc_html_e('.htaccess file is not writable. Please check file permissions.', 'advanced-log-manager'); ?></p>
                </div>
                <?php endif; ?>

                <div class="almgr-editor-controls">
                    <div class="almgr-editor-status">
                        <span id="editor-status" class="almgr-status-saved">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('Saved', 'advanced-log-manager'); ?>
                        </span>
                        <span id="auto-save-status" class="almgr-auto-save-status" style="display: none;">
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e('Auto-saving...', 'advanced-log-manager'); ?>
                        </span>
                    </div>
                    <div class="almgr-editor-options">
                        <label class="almgr-toggle-switch">
                            <input type="checkbox" id="auto-save-toggle" checked>
                            <span class="almgr-toggle-slider"></span>
                            <span class="almgr-toggle-label"><?php esc_html_e('Auto-save', 'advanced-log-manager'); ?></span>
                        </label>
                    </div>
                </div>

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
