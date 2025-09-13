<?php
/**
 * Main plugin class - Service Container
 *
 * @package Advanced Log Manager
 * @author Morden Team
 * @license GPL v3 or later
 * @link https://github.com/sadewadee/advanced-log-manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class ALMGR_Plugin {
    private static $instance = null;
    private $services = array();

    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
        $this->init_services();
    }

    private function init_hooks() {
        // Admin menu and scripts
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));

        // AJAX handlers
        add_action('wp_ajax_almgr_toggle_debug', array($this, 'ajax_toggle_debug'));
        add_action('wp_ajax_almgr_toggle_debug_constant', array($this, 'ajax_toggle_debug_constant'));
        add_action('wp_ajax_almgr_clear_debug_log', array($this, 'ajax_clear_debug_log'));
        add_action('wp_ajax_almgr_get_debug_log', array($this, 'ajax_get_debug_log'));
        add_action('wp_ajax_almgr_get_query_logs', array($this, 'ajax_get_query_logs'));
        add_action('wp_ajax_almgr_clear_query_log', array($this, 'ajax_clear_query_log'));
        add_action('wp_ajax_almgr_clear_all_query_logs', array($this, 'ajax_clear_all_query_logs'));
        add_action('wp_ajax_almgr_cleanup_query_logs', array($this, 'ajax_cleanup_query_logs'));
        add_action('wp_ajax_almgr_cleanup_debug_logs', array($this, 'ajax_cleanup_debug_logs'));
        add_action('wp_ajax_almgr_clear_all_debug_logs', array($this, 'ajax_clear_all_debug_logs'));
        add_action('wp_ajax_almgr_cleanup_all_logs', array($this, 'ajax_cleanup_all_logs'));
        add_action('wp_ajax_almgr_cleanup_query_rotation_logs', array($this, 'ajax_cleanup_query_rotation_logs'));
        add_action('wp_ajax_almgr_get_log_info', array($this, 'ajax_get_log_info'));
        add_action('wp_ajax_almgr_download_query_logs', array($this, 'ajax_download_query_logs'));
        add_action('wp_ajax_almgr_toggle_perf_monitor', array($this, 'ajax_toggle_perf_monitor'));
        // New granular performance feature toggles
        add_action('wp_ajax_almgr_toggle_perf_realtime', array($this, 'ajax_toggle_perf_realtime'));
        add_action('wp_ajax_almgr_toggle_perf_bootstrap', array($this, 'ajax_toggle_perf_bootstrap'));
        add_action('wp_ajax_almgr_toggle_perf_domains', array($this, 'ajax_toggle_perf_domains'));
        add_action('wp_ajax_almgr_save_htaccess', array($this, 'ajax_save_htaccess'));
        add_action('wp_ajax_almgr_restore_htaccess', array($this, 'ajax_restore_htaccess'));
        add_action('wp_ajax_almgr_apply_php_preset', array($this, 'ajax_apply_php_preset'));
        add_action('wp_ajax_almgr_test_debug_transformer', array($this, 'ajax_test_debug_transformer'));
        add_action('wp_ajax_almgr_toggle_smtp_logging', array($this, 'ajax_toggle_smtp_logging'));
        add_action('wp_ajax_almgr_toggle_smtp_ip_logging', array($this, 'ajax_toggle_smtp_ip_logging'));
        add_action('wp_ajax_almgr_get_smtp_logs', array($this, 'ajax_get_smtp_logs'));
        add_action('wp_ajax_almgr_clear_smtp_logs', array($this, 'ajax_clear_smtp_logs'));
        add_action('wp_ajax_almgr_cleanup_smtp_logs', array($this, 'ajax_cleanup_smtp_logs'));
        add_action('wp_ajax_almgr_download_smtp_logs', array($this, 'ajax_download_smtp_logs'));

        // Scheduled tasks
        add_action('init', array($this, 'schedule_log_cleanup'));
        add_action('almgr_daily_log_cleanup', array($this, 'daily_log_cleanup'));
    }

    private function init_services() {
        $this->services['debug'] = new ALMGR_Debug();
        $this->services['perf_monitor'] = new ALMGR_Perf_Monitor();
        $this->services['htaccess'] = new ALMGR_Htaccess();
        $this->services['php_config'] = new ALMGR_PHP_Config();
        $this->services['file_manager'] = new ALMGR_File_Manager();
        $this->services['smtp_logger'] = new ALMGR_SMTP_Logger();
    }

    public function get_service($name) {
        return isset($this->services[$name]) ? $this->services[$name] : null;
    }

    public function add_admin_menu() {
        add_management_page(
            __('Advanced Log Manager', 'advanced-log-manager'),
            __('Advanced Log Manager', 'advanced-log-manager'),
            'manage_options',
            'almgr',
            array($this, 'render_admin_page')
        );

        // Conditionally add unified All Log Activity page when WP_DEBUG_LOG is active
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            add_submenu_page(
                'tools.php',
                __('All Log Activity', 'advanced-log-manager'),
                __('All Log Activity', 'advanced-log-manager'),
                'manage_options',
                'almgr-all-logs-activity',
                array($this, 'render_all_logs_activity_page')
            );
        }

        // add_submenu_page(
        //     'tools.php',
        //     __('Debug Logs', 'advanced-log-manager'),
        //     __('Debug Logs', 'advanced-log-manager'),
        //     'manage_options',
        //     'almgr-logs',
        //     array($this, 'render_logs_page')
        // );

        // add_submenu_page(
        //     'tools.php',
        //     __('Query Logs', 'advanced-log-manager'),
        //     __('Query Logs', 'advanced-log-manager'),
        //     'manage_options',
        //     'almgr-query-logs',
        //     array($this, 'render_query_logs_page')
        // );

        // add_submenu_page(
        //     'tools.php',
        //     __('SMTP Logs', 'advanced-log-manager'),
        //     __('SMTP Logs', 'advanced-log-manager'),
        //     'manage_options',
        //     'almgr-smtp-logs',
        //     array($this, 'render_smtp_logs_page')
        // );
    }

    public function enqueue_admin_scripts($hook) {
        if (!in_array($hook, array('tools_page_almgr', 'tools_page_almgr-all-logs-activity'))) {
            return;
        }

        wp_enqueue_style(
            'almgr-admin',
            ALMGR_PLUGIN_URL . 'admin/assets/admin.css',
            array(),
            ALMGR_VERSION
        );

        if ($hook === 'tools_page_almgr-all-logs-activity') {
            wp_enqueue_style(
                'almgr-query-logs',
                ALMGR_PLUGIN_URL . 'admin/assets/css/query-logs.css',
                array(),
                ALMGR_VERSION
            );
        }

        // Simple editor - no external dependencies needed

        // Enqueue shared utilities first
        wp_enqueue_script(
            'almgr-shared-utils',
            ALMGR_PLUGIN_URL . 'public/assets/almgr-shared-utils.js',
            array('jquery'),
            ALMGR_VERSION,
            false
        );

        wp_enqueue_script(
            'almgr-admin',
            ALMGR_PLUGIN_URL . 'admin/assets/admin.js',
            array('jquery', 'almgr-shared-utils'),
            ALMGR_VERSION,
            false
        );

        wp_localize_script('almgr-admin', 'almgrToolkit', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('almgr_action'),
            'strings' => array(
                'confirm_clear_logs' => __('Are you sure you want to clear all debug logs?', 'advanced-log-manager'),
                'confirm_restore_htaccess' => __('Are you sure you want to restore this backup?', 'advanced-log-manager'),
                'confirm_enable_perf' => __('Enabling the Performance Bar may slightly impact page load while collecting metrics. Continue?', 'advanced-log-manager'),
                'confirm_disable_perf' => __('Disabling the Performance Bar will hide metrics and stop data collection. Continue?', 'advanced-log-manager'),
                'error_occurred' => __('An error occurred. Please try again.', 'advanced-log-manager'),
                'success' => __('Operation completed successfully.', 'advanced-log-manager'),
            )
        ));
    }

    public function enqueue_frontend_scripts() {
        $enabled = get_option('almgr_perf_monitor_enabled') &&
                   is_user_logged_in() &&
                   current_user_can('manage_options');

        if (!$enabled) {
            return;
        }

        if (function_exists('wp_enqueue_style')) {
            wp_enqueue_style(
                'almgr-performance-bar',
                ALMGR_PLUGIN_URL . 'public/assets/performance-bar.css',
                array(),
                ALMGR_VERSION
            );
        }

        if (function_exists('wp_enqueue_script')) {
            // Enqueue shared utilities first
            wp_enqueue_script(
                'almgr-shared-utils',
                ALMGR_PLUGIN_URL . 'public/assets/almgr-shared-utils.js',
                array(),
                ALMGR_VERSION,
                true
            );

            wp_enqueue_script(
                'almgr-performance-bar',
                ALMGR_PLUGIN_URL . 'public/assets/performance-bar.js',
                array('almgr-shared-utils'),
                ALMGR_VERSION,
                true
            );

            // Enqueue performance tabs for filtering, sorting, and search functionality
            wp_enqueue_script(
                'almgr-performance-tabs',
                ALMGR_PLUGIN_URL . 'public/assets/performance-tabs.js',
                array('almgr-performance-bar', 'almgr-shared-utils'),
                ALMGR_VERSION,
                true
            );

            // Localize script for front-end functionality
            wp_localize_script('almgr-performance-tabs', 'mtQueryMonitorL10n', array(
                'enableRealTimeUpdates' => __('Enable Real-time Updates', 'advanced-log-manager'),
                'stopRealTimeUpdates' => __('Stop Real-time Updates', 'advanced-log-manager'),
                'statusActive' => __('Active', 'advanced-log-manager'),
                'statusStatic' => __('Static View', 'advanced-log-manager'),
                'statusRefreshing' => __('Refreshing...', 'advanced-log-manager'),
                'statusUpdated' => __('Updated', 'advanced-log-manager'),
                'statusError' => __('Error', 'advanced-log-manager'),
                'viewDetails' => __('View Details', 'advanced-log-manager'),
                'hideDetails' => __('Hide Details', 'advanced-log-manager'),
                'toggle' => __('Toggle', 'advanced-log-manager'),
                'hide' => __('Hide', 'advanced-log-manager'),
                'nonce' => wp_create_nonce('almgr_monitor_hooks_nonce')
            ));

            // Pass AJAX data for front-end
            wp_localize_script('almgr-performance-tabs', 'mtHookMonitor', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('almgr_monitor_hooks_nonce'),
                'isActive' => false,
                'interval' => null
            ));
        }
    }

    /**
     * Render main admin page
     */
    public function render_admin_page() {
        include ALMGR_PLUGIN_DIR . 'admin/views/page-toolkit.php';
    }

    /**
     * Render logs page
     */
    public function render_logs_page() {
        include ALMGR_PLUGIN_DIR . 'admin/views/page-logs.php';
    }

    /**
     * Render query logs page
     */
    public function render_query_logs_page() {
        include ALMGR_PLUGIN_DIR . 'admin/views/page-query-logs.php';
    }

    /**
     * Render SMTP logs page
     */
    public function render_smtp_logs_page() {
        include ALMGR_PLUGIN_DIR . 'admin/views/page-smtp-logs.php';
    }

    /**
     * Render All Log Activity page (unified tabs)
     */
    public function render_all_logs_activity_page() {
        include ALMGR_PLUGIN_DIR . 'admin/views/page-all-logs-activity.php';
    }


    public function ajax_toggle_debug() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }

        $enabled = isset($_POST['enabled']) && sanitize_key($_POST['enabled']) === 'true';
        $result = $this->services['debug']->toggle_debug($enabled);

        if ($result) {
            update_option('almgr_debug_enabled', $enabled);
            wp_send_json_success(array(
                'enabled' => $enabled,
                'message' => $enabled ? __('Debug mode enabled.', 'advanced-log-manager') : __('Debug mode disabled.', 'advanced-log-manager')
            ));
        } else {
            wp_send_json_error(__('Failed to toggle debug mode.', 'advanced-log-manager'));
        }
    }

    /**
     * Toggle debug constant via AJAX
     *
     * @global array $_POST Contains the POST data including constant name and enabled status
     */
    public function ajax_toggle_debug_constant() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }

        if (!isset($_POST['constant'])) {
            wp_send_json_error(__('Missing constant parameter.', 'advanced-log-manager'));
        }

        /** @var array $_POST */
        $raw_constant = sanitize_text_field( wp_unslash( $_POST['constant'] ) );
        $constant     = ( 'display_errors' === $raw_constant ) ? 'display_errors' : strtoupper( $raw_constant );
        $enabled      = isset($_POST['enabled']) && sanitize_key($_POST['enabled']) === 'true';


        $allowed_constants = array( 'WP_DEBUG', 'WP_DEBUG_LOG', 'WP_DEBUG_DISPLAY', 'SCRIPT_DEBUG', 'SAVEQUERIES', 'SMTP_LOGGING', 'display_errors' );
        if (!in_array($constant, $allowed_constants)) {
            wp_send_json_error(__('Invalid debug constant.', 'advanced-log-manager'));
        }

        $result = $this->services['debug']->toggle_debug_constant($constant, $enabled);

        if ($result) {

            $status = $this->services['debug']->get_debug_status();

            wp_send_json_success(array(
                'constant' => $constant,
                'enabled' => $enabled,
                'status' => $status,
                'message' => sprintf(
                    /* translators: %s: feature name */
            $enabled ? __('%s enabled.', 'advanced-log-manager') : __('%s disabled.', 'advanced-log-manager'),
                    $constant
                )
            ));
        } else {
            wp_send_json_error(__('Failed to toggle debug constant.', 'advanced-log-manager'));
        }
    }

    public function ajax_clear_debug_log() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }

        $result = $this->services['debug']->clear_debug_log();

        if ($result) {
            wp_send_json_success(__('Debug log cleared.', 'advanced-log-manager'));
        } else {
            wp_send_json_error(__('Failed to clear debug log.', 'advanced-log-manager'));
        }
    }

    public function ajax_get_debug_log() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }

        $logs = $this->services['debug']->get_debug_log_entries();
        wp_send_json_success($logs);
    }

    public function ajax_get_query_logs() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }

        $logs = $this->services['debug']->get_query_log_entries();
        wp_send_json_success($logs);
    }

    public function ajax_clear_query_log() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }

        $result = $this->services['debug']->clear_query_log();

        if ($result) {
            wp_send_json_success(__('Active query log cleared successfully (content deleted).', 'advanced-log-manager'));
        } else {
            wp_send_json_error(__('Failed to clear active query log.', 'advanced-log-manager'));
        }
    }

    public function ajax_clear_all_query_logs() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }

        $result = $this->services['debug']->clear_all_query_logs();

        if ($result) {
            wp_send_json_success(__('All query logs cleared successfully (active content + rotation files removed).', 'advanced-log-manager'));
        } else {
            wp_send_json_error(__('Failed to clear all query logs.', 'advanced-log-manager'));
        }
    }

    public function ajax_cleanup_query_logs() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }

        $cleaned = $this->services['debug']->cleanup_old_query_logs();

        if ($cleaned >= 0) {
            /* translators: %d: number of files cleaned */
        wp_send_json_success(sprintf(__('Cleaned up %d rotation/archived log files (query.log.1, query.log.2, etc.). Active query.log preserved.', 'advanced-log-manager'), $cleaned));
        } else {
            wp_send_json_error(__('Failed to cleanup rotation/archived logs.', 'advanced-log-manager'));
        }
    }

    public function ajax_cleanup_debug_logs() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }

        $keep_count = isset($_POST['keep_count']) ? absint($_POST['keep_count']) : 3;
        $cleaned = 0;

        if (function_exists('almgr_cleanup_old_debug_logs')) {
            $cleaned = almgr_cleanup_old_debug_logs($keep_count);
        }

        if ($cleaned >= 0) {
            /* translators: %d: number of files cleaned */
        wp_send_json_success(sprintf(__('Cleaned up %d old debug log files.', 'advanced-log-manager'), $cleaned));
        } else {
            wp_send_json_error(__('Failed to cleanup old debug logs.', 'advanced-log-manager'));
        }
    }

    public function ajax_clear_all_debug_logs() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }

        $cleaned = 0;

        if (function_exists('almgr_clear_all_debug_logs_except_active')) {
            $cleaned = almgr_clear_all_debug_logs_except_active();
        }

        if ($cleaned >= 0) {
            /* translators: %d: number of files cleared */
        wp_send_json_success(sprintf(__('Cleared %d old debug log files. Current active log preserved.', 'advanced-log-manager'), $cleaned));
        } else {
            wp_send_json_error(__('Failed to clear debug logs.', 'advanced-log-manager'));
        }
    }

    public function ajax_cleanup_all_logs() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }

        $include_current = isset($_POST['include_current']) && sanitize_key($_POST['include_current']) === 'true';
        $cleaned = 0;

        if (function_exists('almgr_cleanup_all_log_files')) {
            $cleaned = almgr_cleanup_all_log_files($include_current);
        }

        if ($cleaned >= 0) {
            $message = $include_current ?
                /* translators: %d: number of files removed */
            sprintf(__('Removed all %d log files.', 'advanced-log-manager'), $cleaned) :
            /* translators: %d: number of files cleaned */
            sprintf(__('Cleaned up %d old log files.', 'advanced-log-manager'), $cleaned);
            wp_send_json_success($message);
        } else {
            wp_send_json_error(__('Failed to cleanup log files.', 'advanced-log-manager'));
        }
    }

    public function ajax_cleanup_query_rotation_logs() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }

        $keep_latest = isset($_POST['keep_latest']) && sanitize_key($_POST['keep_latest']) === 'true';
        $cleaned = 0;

        if (function_exists('almgr_cleanup_query_log_rotation_files')) {
            $cleaned = almgr_cleanup_query_log_rotation_files($keep_latest);
        }

        if ($cleaned >= 0) {
            $message = $keep_latest ?
                /* translators: %d: number of files cleaned */
            sprintf(__('Cleaned up %d old rotation files. Latest backup (query.log.1) preserved.', 'advanced-log-manager'), $cleaned) :
            /* translators: %d: number of files cleaned */
            sprintf(__('Cleaned up %d rotation files.', 'advanced-log-manager'), $cleaned);
            wp_send_json_success($message);
        } else {
            wp_send_json_error(__('Failed to cleanup rotation log files.', 'advanced-log-manager'));
        }
    }

    public function ajax_get_log_info() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }

        $debug_status = $this->services['debug']->get_debug_status();


        $log_info = array(
            'query_log_file_exists' => $debug_status['query_log_file_exists'],
            'query_log_file_size' => $debug_status['query_log_file_size'],
            'query_log_total_size' => isset($debug_status['query_log_total_size']) ? $debug_status['query_log_total_size'] : '',
            'query_log_max_size' => isset($debug_status['query_log_max_size']) ? $debug_status['query_log_max_size'] : ''
        );

        wp_send_json_success($log_info);
    }

    /**
     * Schedule daily log cleanup
     */
    public function schedule_log_cleanup() {
        if (function_exists('wp_next_scheduled') && function_exists('wp_schedule_event')) {
            if (!wp_next_scheduled('almgr_daily_log_cleanup')) {
                wp_schedule_event(time(), 'daily', 'almgr_daily_log_cleanup');
            }
        }
    }

    /**
     * Daily log cleanup task
     */
    public function daily_log_cleanup() {
        if (!current_user_can('manage_options')) {
            return;
        }


        $this->services['debug']->cleanup_old_query_logs();


        $cleaned_debug_logs = almgr_cleanup_old_debug_logs();
        if ($cleaned_debug_logs > 0) {
            almgr_debug_log("Cleaned up {$cleaned_debug_logs} old debug log files");
        }


        $debug_log_path = almgr_get_debug_log_path();
        if (file_exists($debug_log_path)) {
            $debug_log_size = filesize($debug_log_path);
            $max_debug_size = almgr_get_debug_log_max_size();

            if ($debug_log_size > $max_debug_size) {

                $this->truncate_debug_log($debug_log_path, 10000);
            }
        }
    }

    /**
     * Truncate debug log to keep only latest entries
     */
    private function truncate_debug_log($log_path, $max_lines = 10000) {
        $lines = file($log_path, FILE_IGNORE_NEW_LINES);

        if (count($lines) > $max_lines) {
            $lines = array_slice($lines, -$max_lines);
            $truncated_content = implode("\n", $lines);
            file_put_contents($log_path, $truncated_content);

            almgr_debug_log("Debug log truncated to {$max_lines} lines");
        }
    }

    public function ajax_download_query_logs() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied.', 'advanced-log-manager'));
        }

        $query_log_path = almgr_get_query_log_path();

        if (!file_exists($query_log_path)) {
            wp_die(__('Query log file not found.', 'advanced-log-manager'));
        }

        $content = file_get_contents($query_log_path);
        $filename = 'query-logs-' . date('Y-m-d-H-i-s') . '.txt';

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));

        echo esc_html($content);
        exit;
    }

    public function ajax_toggle_perf_monitor() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }

        $enabled = isset($_POST['enabled']) && sanitize_key($_POST['enabled']) === 'true';
        update_option('almgr_perf_monitor_enabled', $enabled);

        wp_send_json_success(array(
            'enabled' => $enabled,
            'message' => $enabled ? __('Performance monitor enabled.', 'advanced-log-manager') : __('Performance monitor disabled.', 'advanced-log-manager')
        ));
    }

    // Tambahan: granular performance toggles
    public function ajax_toggle_perf_realtime() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }
        $enabled = isset($_POST['enabled']) && sanitize_key($_POST['enabled']) === 'true';
        update_option('almgr_perf_realtime_enabled', $enabled);
        wp_send_json_success(array(
            'enabled' => $enabled,
            'message' => $enabled ? __('Real-time hooks monitoring enabled.', 'advanced-log-manager') : __('Real-time hooks monitoring disabled.', 'advanced-log-manager')
        ));
    }

    public function ajax_toggle_perf_bootstrap() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }
        $enabled = isset($_POST['enabled']) && sanitize_key($_POST['enabled']) === 'true';
        update_option('almgr_perf_bootstrap_enabled', $enabled);
        wp_send_json_success(array(
            'enabled' => $enabled,
            'message' => $enabled ? __('Bootstrap phases snapshots enabled.', 'advanced-log-manager') : __('Bootstrap phases snapshots disabled.', 'advanced-log-manager')
        ));
    }

    public function ajax_toggle_perf_domains() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }
        $enabled = isset($_POST['enabled']) && sanitize_key($_POST['enabled']) === 'true';
        update_option('almgr_perf_domains_enabled', $enabled);
        wp_send_json_success(array(
            'enabled' => $enabled,
            'message' => $enabled ? __('Domain-specific panels enabled.', 'advanced-log-manager') : __('Domain-specific panels disabled.', 'advanced-log-manager')
        ));
    }

    /**
     * Save htaccess file via AJAX
     *
     * @global array $_POST Contains the POST data including content
     */
    public function ajax_save_htaccess() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }

        if (!isset($_POST['content'])) {
            wp_send_json_error(__('Missing content parameter.', 'advanced-log-manager'));
        }

        // Sanitize the content
        /** @var array $_POST */
        $content = wp_unslash($_POST['content']);

        // Remove all HTML tags and dangerous content
        $content = wp_kses($content, array());

        // Additional sanitization using helper function
        $content = almgr_sanitize_file_content($content);

        if ($content === false) {
            wp_send_json_error(__('Content contains potentially dangerous code and was rejected.', 'advanced-log-manager'));
        }

        $result = $this->services['htaccess']->save_htaccess($content);

        if ($result) {
            wp_send_json_success(__('.htaccess file saved successfully.', 'advanced-log-manager'));
        } else {
            wp_send_json_error(__('Failed to save .htaccess file.', 'advanced-log-manager'));
        }
    }

    /**
     * Restore htaccess file via AJAX
     *
     * @global array $_POST Contains the POST data including backup index
     */
    public function ajax_restore_htaccess() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }

        if (!isset($_POST['backup_index'])) {
            wp_send_json_error(__('Missing backup index parameter.', 'advanced-log-manager'));
        }

        /** @var array $_POST */
        $backup_index = absint($_POST['backup_index']);
        $result = $this->services['htaccess']->restore_htaccess($backup_index);

        if ($result) {
            wp_send_json_success(__('.htaccess file restored successfully.', 'advanced-log-manager'));
        } else {
            wp_send_json_error(__('Failed to restore .htaccess file.', 'advanced-log-manager'));
        }
    }

    /**
     * Apply PHP preset via AJAX
     *
     * @global array $_POST Contains the POST data including preset name
     */
    public function ajax_apply_php_preset() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }

        if (!isset($_POST['preset'])) {
            wp_send_json_error(__('Missing preset parameter.', 'advanced-log-manager'));
        }

        /** @var array $_POST */
        $preset = sanitize_key($_POST['preset']);
        $allowed_presets = array('basic', 'medium', 'high');
        if (!in_array($preset, $allowed_presets)) {
            wp_send_json_error(__('Invalid preset value.', 'advanced-log-manager'));
        }

        $result = $this->services['php_config']->apply_preset($preset);

        if ($result) {
            update_option('almgr_php_preset', $preset);
            wp_send_json_success(__('PHP configuration applied successfully.', 'advanced-log-manager'));
        } else {
            wp_send_json_error(__('Failed to apply PHP configuration.', 'advanced-log-manager'));
        }
    }


    public function ajax_test_debug_transformer() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }

        almgr_debug_log('=== DEBUG TRANSFORMER TEST (via AJAX) ===');


        $test_result = $this->services['debug']->test_wp_config_transformer();


        // Using ReflectionClass to access private method
         // @phpstan-ignore-next-line
         if (!class_exists('\ReflectionClass')) {
             return false;
         }
         /** @var \ReflectionClass $reflection */
         $reflection = new \ReflectionClass($this->services['debug']);
        $method = $reflection->getMethod('get_custom_debug_log_path');
        $method->setAccessible(true);
        $custom_path = $method->invoke($this->services['debug']);

        $test_settings = [
            'WP_DEBUG' => true,
            'WP_DEBUG_LOG' => $custom_path
        ];

        almgr_debug_log('Attempting to apply test debug settings via WPConfigTransformer');
        $apply_result = ALMGR_WP_Config_Integration::apply_debug_constants($test_settings);
        almgr_debug_log('Apply result: ' . ($apply_result ? 'SUCCESS' : 'FAILED'));

        $response = [
            'transformer_test' => $test_result,
            'apply_test' => $apply_result,
            'wp_config_path' => almgr_get_wp_config_path(),
            'wp_config_writable' => is_writable(almgr_get_wp_config_path()),
            'message' => 'Test completed. Check error logs for detailed results.'
        ];

        wp_send_json_success($response);
    }



    public function ajax_toggle_smtp_logging() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }

        $enabled = isset($_POST['enabled']) && sanitize_key($_POST['enabled']) === 'true';


        update_option('almgr_smtp_logging_enabled', $enabled);


        if (isset($this->services['smtp_logger'])) {
            // Using ReflectionClass to access private properties and methods
             // @phpstan-ignore-next-line
             if (!class_exists('\ReflectionClass')) {
                 return;
             }
             /** @var \ReflectionClass $reflection */
             $reflection = new \ReflectionClass($this->services['smtp_logger']);
            $property = $reflection->getProperty('log_enabled');
            $property->setAccessible(true);
            $property->setValue($this->services['smtp_logger'], $enabled);


            if ($enabled) {
                // Get init_hooks method via reflection
                $init_method = $reflection->getMethod('init_hooks');
                $init_method->setAccessible(true);
                $init_method->invoke($this->services['smtp_logger']);
            }
        }

        wp_send_json_success(array(
            'enabled' => $enabled,
            'message' => $enabled ? __('SMTP logging enabled.', 'advanced-log-manager') : __('SMTP logging disabled.', 'advanced-log-manager')
        ));
    }

    public function ajax_toggle_smtp_ip_logging() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }

        $enabled = isset($_POST['enabled']) && sanitize_key($_POST['enabled']) === 'true';


        update_option('almgr_smtp_log_ip_address', $enabled);


        if (isset($this->services['smtp_logger'])) {
            // Using ReflectionClass to access private property
             // @phpstan-ignore-next-line
             if (!class_exists('\ReflectionClass')) {
                 return;
             }
             /** @var \ReflectionClass $reflection */
             $reflection = new \ReflectionClass($this->services['smtp_logger']);
            $property = $reflection->getProperty('log_ip_address');
            $property->setAccessible(true);
            $property->setValue($this->services['smtp_logger'], $enabled);
        }

        wp_send_json_success(array(
            'enabled' => $enabled,
            'message' => $enabled ? __('IP address logging enabled.', 'advanced-log-manager') : __('IP address logging disabled.', 'advanced-log-manager')
        ));
    }

    public function ajax_get_smtp_logs() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }

        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : null;
        $logs = $this->services['smtp_logger']->get_log_entries($date);
        wp_send_json_success($logs);
    }

    public function ajax_clear_smtp_logs() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }

        $result = $this->services['smtp_logger']->clear_current_log();

        if ($result) {
            wp_send_json_success(__('SMTP logs cleared successfully.', 'advanced-log-manager'));
        } else {
            wp_send_json_error(__('Failed to clear SMTP logs.', 'advanced-log-manager'));
        }
    }

    public function ajax_cleanup_smtp_logs() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'advanced-log-manager'));
        }

        $keep_days = isset($_POST['keep_days']) ? absint($_POST['keep_days']) : 30;
        $cleaned = $this->services['smtp_logger']->cleanup_old_logs($keep_days);

        if ($cleaned >= 0) {
            /* translators: %d: number of files cleaned */
        wp_send_json_success(sprintf(__('Cleaned up %d old SMTP log files.', 'advanced-log-manager'), $cleaned));
        } else {
            wp_send_json_error(__('Failed to cleanup old SMTP logs.', 'advanced-log-manager'));
        }
    }

    public function ajax_download_smtp_logs() {
        check_ajax_referer('almgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied.', 'advanced-log-manager'));
        }

        $date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('dmY');
        $log_file = ABSPATH . 'wp-content/advanced-log-manager/smtp-' . $date . '.log';

        if (!file_exists($log_file)) {
            wp_die(__('SMTP log file not found.', 'advanced-log-manager'));
        }

        $content = file_get_contents($log_file);
        $filename = 'smtp-logs-' . $date . '.log';

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));

        echo esc_html($content);
        exit;
    }
}
