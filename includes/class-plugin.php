<?php
/**
 * Main plugin class - Service Container
 *
 * @package WP Debug Manager
 * @author WPDMGR Team
 * @license GPL v3 or later
 * @link https://github.com/sadewadee/wp-debug-manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPDMGR_Plugin {
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
        add_action('wp_ajax_wpdmgr_toggle_debug', array($this, 'ajax_toggle_debug'));
        add_action('wp_ajax_wpdmgr_toggle_debug_constant', array($this, 'ajax_toggle_debug_constant'));
        add_action('wp_ajax_wpdmgr_clear_debug_log', array($this, 'ajax_clear_debug_log'));
        add_action('wp_ajax_wpdmgr_get_debug_log', array($this, 'ajax_get_debug_log'));
        add_action('wp_ajax_wpdmgr_get_query_logs', array($this, 'ajax_get_query_logs'));
        add_action('wp_ajax_wpdmgr_clear_query_log', array($this, 'ajax_clear_query_log'));
        add_action('wp_ajax_wpdmgr_clear_all_query_logs', array($this, 'ajax_clear_all_query_logs'));
        add_action('wp_ajax_wpdmgr_cleanup_query_logs', array($this, 'ajax_cleanup_query_logs'));
        add_action('wp_ajax_wpdmgr_cleanup_debug_logs', array($this, 'ajax_cleanup_debug_logs'));
        add_action('wp_ajax_wpdmgr_clear_all_debug_logs', array($this, 'ajax_clear_all_debug_logs'));
        add_action('wp_ajax_wpdmgr_cleanup_all_logs', array($this, 'ajax_cleanup_all_logs'));
        add_action('wp_ajax_wpdmgr_cleanup_query_rotation_logs', array($this, 'ajax_cleanup_query_rotation_logs'));
        add_action('wp_ajax_wpdmgr_get_log_info', array($this, 'ajax_get_log_info'));
        add_action('wp_ajax_wpdmgr_download_query_logs', array($this, 'ajax_download_query_logs'));
        add_action('wp_ajax_wpdmgr_toggle_perf_monitor', array($this, 'ajax_toggle_perf_monitor'));
        add_action('wp_ajax_wpdmgr_save_htaccess', array($this, 'ajax_save_htaccess'));
        add_action('wp_ajax_wpdmgr_restore_htaccess', array($this, 'ajax_restore_htaccess'));
        add_action('wp_ajax_wpdmgr_apply_php_preset', array($this, 'ajax_apply_php_preset'));
        add_action('wp_ajax_wpdmgr_test_debug_transformer', array($this, 'ajax_test_debug_transformer'));
        add_action('wp_ajax_wpdmgr_toggle_smtp_logging', array($this, 'ajax_toggle_smtp_logging'));
        add_action('wp_ajax_wpdmgr_toggle_smtp_ip_logging', array($this, 'ajax_toggle_smtp_ip_logging'));
        add_action('wp_ajax_wpdmgr_get_smtp_logs', array($this, 'ajax_get_smtp_logs'));
        add_action('wp_ajax_wpdmgr_clear_smtp_logs', array($this, 'ajax_clear_smtp_logs'));
        add_action('wp_ajax_wpdmgr_cleanup_smtp_logs', array($this, 'ajax_cleanup_smtp_logs'));
        add_action('wp_ajax_wpdmgr_download_smtp_logs', array($this, 'ajax_download_smtp_logs'));

        // Scheduled tasks
        add_action('init', array($this, 'schedule_log_cleanup'));
        add_action('wpdmgr_daily_log_cleanup', array($this, 'daily_log_cleanup'));
    }

    private function init_services() {
        $this->services['debug'] = new WPDMGR_Debug();
        $this->services['perf_monitor'] = new WPDMGR_Perf_Monitor();
        $this->services['htaccess'] = new WPDMGR_Htaccess();
        $this->services['php_config'] = new WPDMGR_PHP_Config();
        $this->services['file_manager'] = new WPDMGR_File_Manager();
        $this->services['smtp_logger'] = new WPDMGR_SMTP_Logger();
    }

    public function get_service($name) {
        return isset($this->services[$name]) ? $this->services[$name] : null;
    }

    public function add_admin_menu() {
        add_management_page(
            __('WP Debug Manager', 'wp-debug-manager'),
            __('WP Debug Manager', 'wp-debug-manager'),
            'manage_options',
            'wpdmgr',
            array($this, 'render_admin_page')
        );

        // Conditionally add unified All Log Activity page when WP_DEBUG_LOG is active
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            add_submenu_page(
                'tools.php',
                __('All Log Activity', 'wp-debug-manager'),
                __('All Log Activity', 'wp-debug-manager'),
                'manage_options',
                'wpdmgr-all-logs-activity',
                array($this, 'render_all_logs_activity_page')
            );
        }

        // add_submenu_page(
        //     'tools.php',
        //     __('Debug Logs', 'wp-debug-manager'),
        //     __('Debug Logs', 'wp-debug-manager'),
        //     'manage_options',
        //     'wpdmgr-logs',
        //     array($this, 'render_logs_page')
        // );

        // add_submenu_page(
        //     'tools.php',
        //     __('Query Logs', 'wp-debug-manager'),
        //     __('Query Logs', 'wp-debug-manager'),
        //     'manage_options',
        //     'wpdmgr-query-logs',
        //     array($this, 'render_query_logs_page')
        // );

        // add_submenu_page(
        //     'tools.php',
        //     __('SMTP Logs', 'wp-debug-manager'),
        //     __('SMTP Logs', 'wp-debug-manager'),
        //     'manage_options',
        //     'wpdmgr-smtp-logs',
        //     array($this, 'render_smtp_logs_page')
        // );
    }

    public function enqueue_admin_scripts($hook) {
        if (!in_array($hook, array('tools_page_wpdmgr', 'tools_page_wpdmgr-all-logs-activity'))) {
            return;
        }

        wp_enqueue_style(
            'wpdmgr-admin',
            WPDMGR_PLUGIN_URL . 'admin/assets/admin.css',
            array(),
            WPDMGR_VERSION
        );

        if ($hook === 'tools_page_wpdmgr-all-logs-activity') {
            wp_enqueue_style(
                'wpdmgr-query-logs',
                WPDMGR_PLUGIN_URL . 'admin/assets/css/query-logs.css',
                array(),
                WPDMGR_VERSION
            );
        }

        wp_enqueue_script(
            'wpdmgr-admin',
            WPDMGR_PLUGIN_URL . 'admin/assets/admin.js',
            array('jquery'),
            WPDMGR_VERSION,
            false
        );

        wp_localize_script('wpdmgr-admin', 'wpdmgrToolkit', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpdmgr_action'),
            'strings' => array(
                'confirm_clear_logs' => __('Are you sure you want to clear all debug logs?', 'wp-debug-manager'),
                'confirm_restore_htaccess' => __('Are you sure you want to restore this backup?', 'wp-debug-manager'),
                'error_occurred' => __('An error occurred. Please try again.', 'wp-debug-manager'),
                'success' => __('Operation completed successfully.', 'wp-debug-manager'),
            )
        ));
    }

    public function enqueue_frontend_scripts() {
        $enabled = get_option('wpdmgr_perf_monitor_enabled') &&
                   is_user_logged_in() &&
                   current_user_can('manage_options');

        if (!$enabled) {
            return;
        }

        wp_enqueue_script(
            'wpdmgr-admin',
            WPDMGR_PLUGIN_URL . 'admin/assets/admin.js',
            array('jquery'),
            WPDMGR_VERSION,
            false
        );

        if (function_exists('wp_localize_script')) {
            wp_localize_script('wpdmgr-admin', 'wpdmgrToolkit', array(
                'ajaxurl' => function_exists('admin_url') ? admin_url('admin-ajax.php') : '/wp-admin/admin-ajax.php',
                'nonce' => function_exists('wp_create_nonce') ? wp_create_nonce('wpdmgr_action') : '',
                'strings' => array(
                    'error_occurred' => function_exists('__') ? __('An error occurred', 'wp-debug-manager') : 'An error occurred',
                    'confirm_delete' => function_exists('__') ? __('Are you sure you want to delete this?', 'wp-debug-manager') : 'Are you sure you want to delete this?',
                    'loading' => function_exists('__') ? __('Loading...', 'wp-debug-manager') : 'Loading...'
                )
            ));
        }

        if (function_exists('wp_enqueue_style')) {
            wp_enqueue_style(
                'wpdmgr-performance-bar',
                WPDMGR_PLUGIN_URL . 'public/assets/performance-bar.css',
                array(),
                WPDMGR_VERSION
            );
        }

        if (function_exists('wp_enqueue_script')) {
            wp_enqueue_script(
                'wpdmgr-performance-bar',
                WPDMGR_PLUGIN_URL . 'public/assets/performance-bar.js',
                array(),
                WPDMGR_VERSION,
                true
            );
        }
    }

    /**
     * Render main admin page
     */
    public function render_admin_page() {
        include WPDMGR_PLUGIN_DIR . 'admin/views/page-toolkit.php';
    }

    /**
     * Render logs page
     */
    public function render_logs_page() {
        include WPDMGR_PLUGIN_DIR . 'admin/views/page-logs.php';
    }

    /**
     * Render query logs page
     */
    public function render_query_logs_page() {
        include WPDMGR_PLUGIN_DIR . 'admin/views/page-query-logs.php';
    }

    /**
     * Render SMTP logs page
     */
    public function render_smtp_logs_page() {
        include WPDMGR_PLUGIN_DIR . 'admin/views/page-smtp-logs.php';
    }

    /**
     * Render All Log Activity page (unified tabs)
     */
    public function render_all_logs_activity_page() {
        include WPDMGR_PLUGIN_DIR . 'admin/views/page-all-logs-activity.php';
    }


    public function ajax_toggle_debug() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wp-debug-manager'));
        }

        $enabled = isset($_POST['enabled']) && sanitize_key($_POST['enabled']) === 'true';
        $result = $this->services['debug']->toggle_debug($enabled);

        if ($result) {
            update_option('wpdmgr_debug_enabled', $enabled);
            wp_send_json_success(array(
                'enabled' => $enabled,
                'message' => $enabled ? __('Debug mode enabled.', 'wp-debug-manager') : __('Debug mode disabled.', 'wp-debug-manager')
            ));
        } else {
            wp_send_json_error(__('Failed to toggle debug mode.', 'wp-debug-manager'));
        }
    }

    public function ajax_toggle_debug_constant() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wp-debug-manager'));
        }

        if (!isset($_POST['constant'])) {
            wp_send_json_error(__('Missing constant parameter.', 'wp-debug-manager'));
        }


        $raw_constant = sanitize_text_field( wp_unslash( $_POST['constant'] ) );
        $constant     = ( 'display_errors' === $raw_constant ) ? 'display_errors' : strtoupper( $raw_constant );
        $enabled      = isset($_POST['enabled']) && sanitize_key($_POST['enabled']) === 'true';


        $allowed_constants = array( 'WP_DEBUG', 'WP_DEBUG_LOG', 'WP_DEBUG_DISPLAY', 'SCRIPT_DEBUG', 'SAVEQUERIES', 'SMTP_LOGGING', 'display_errors' );
        if (!in_array($constant, $allowed_constants)) {
            wp_send_json_error(__('Invalid debug constant.', 'wp-debug-manager'));
        }

        $result = $this->services['debug']->toggle_debug_constant($constant, $enabled);

        if ($result) {

            $status = $this->services['debug']->get_debug_status();

            wp_send_json_success(array(
                'constant' => $constant,
                'enabled' => $enabled,
                'status' => $status,
                'message' => sprintf(
                    $enabled ? __('%s enabled.', 'wp-debug-manager') : __('%s disabled.', 'wp-debug-manager'),
                    $constant
                )
            ));
        } else {
            wp_send_json_error(__('Failed to toggle debug constant.', 'wp-debug-manager'));
        }
    }

    public function ajax_clear_debug_log() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wp-debug-manager'));
        }

        $result = $this->services['debug']->clear_debug_log();

        if ($result) {
            wp_send_json_success(__('Debug log cleared.', 'wp-debug-manager'));
        } else {
            wp_send_json_error(__('Failed to clear debug log.', 'wp-debug-manager'));
        }
    }

    public function ajax_get_debug_log() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wp-debug-manager'));
        }

        $logs = $this->services['debug']->get_debug_log_entries();
        wp_send_json_success($logs);
    }

    public function ajax_get_query_logs() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wp-debug-manager'));
        }

        $logs = $this->services['debug']->get_query_log_entries();
        wp_send_json_success($logs);
    }

    public function ajax_clear_query_log() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wp-debug-manager'));
        }

        $result = $this->services['debug']->clear_query_log();

        if ($result) {
            wp_send_json_success(__('Active query log cleared successfully (content deleted).', 'wp-debug-manager'));
        } else {
            wp_send_json_error(__('Failed to clear active query log.', 'wp-debug-manager'));
        }
    }

    public function ajax_clear_all_query_logs() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wp-debug-manager'));
        }

        $result = $this->services['debug']->clear_all_query_logs();

        if ($result) {
            wp_send_json_success(__('All query logs cleared successfully (active content + rotation files removed).', 'wp-debug-manager'));
        } else {
            wp_send_json_error(__('Failed to clear all query logs.', 'wp-debug-manager'));
        }
    }

    public function ajax_cleanup_query_logs() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wp-debug-manager'));
        }

        $cleaned = $this->services['debug']->cleanup_old_query_logs();

        if ($cleaned >= 0) {
            wp_send_json_success(sprintf(__('Cleaned up %d rotation/archived log files (query.log.1, query.log.2, etc.). Active query.log preserved.', 'wp-debug-manager'), $cleaned));
        } else {
            wp_send_json_error(__('Failed to cleanup rotation/archived logs.', 'wp-debug-manager'));
        }
    }

    public function ajax_cleanup_debug_logs() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wp-debug-manager'));
        }

        $keep_count = isset($_POST['keep_count']) ? absint($_POST['keep_count']) : 3;
        $cleaned = 0;

        if (function_exists('wpdmgr_cleanup_old_debug_logs')) {
            $cleaned = wpdmgr_cleanup_old_debug_logs($keep_count);
        }

        if ($cleaned >= 0) {
            wp_send_json_success(sprintf(__('Cleaned up %d old debug log files.', 'wp-debug-manager'), $cleaned));
        } else {
            wp_send_json_error(__('Failed to cleanup old debug logs.', 'wp-debug-manager'));
        }
    }

    public function ajax_clear_all_debug_logs() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wp-debug-manager'));
        }

        $cleaned = 0;

        if (function_exists('wpdmgr_clear_all_debug_logs_except_active')) {
            $cleaned = wpdmgr_clear_all_debug_logs_except_active();
        }

        if ($cleaned >= 0) {
            wp_send_json_success(sprintf(__('Cleared %d old debug log files. Current active log preserved.', 'wp-debug-manager'), $cleaned));
        } else {
            wp_send_json_error(__('Failed to clear debug logs.', 'wp-debug-manager'));
        }
    }

    public function ajax_cleanup_all_logs() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wp-debug-manager'));
        }

        $include_current = isset($_POST['include_current']) && sanitize_key($_POST['include_current']) === 'true';
        $cleaned = 0;

        if (function_exists('wpdmgr_cleanup_all_log_files')) {
            $cleaned = wpdmgr_cleanup_all_log_files($include_current);
        }

        if ($cleaned >= 0) {
            $message = $include_current ?
                sprintf(__('Removed all %d log files.', 'wp-debug-manager'), $cleaned) :
                sprintf(__('Cleaned up %d old log files.', 'wp-debug-manager'), $cleaned);
            wp_send_json_success($message);
        } else {
            wp_send_json_error(__('Failed to cleanup log files.', 'wp-debug-manager'));
        }
    }

    public function ajax_cleanup_query_rotation_logs() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wp-debug-manager'));
        }

        $keep_latest = isset($_POST['keep_latest']) && sanitize_key($_POST['keep_latest']) === 'true';
        $cleaned = 0;

        if (function_exists('wpdmgr_cleanup_query_log_rotation_files')) {
            $cleaned = wpdmgr_cleanup_query_log_rotation_files($keep_latest);
        }

        if ($cleaned >= 0) {
            $message = $keep_latest ?
                sprintf(__('Cleaned up %d old rotation files. Latest backup (query.log.1) preserved.', 'wp-debug-manager'), $cleaned) :
                sprintf(__('Cleaned up %d rotation files.', 'wp-debug-manager'), $cleaned);
            wp_send_json_success($message);
        } else {
            wp_send_json_error(__('Failed to cleanup rotation log files.', 'wp-debug-manager'));
        }
    }

    public function ajax_get_log_info() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wp-debug-manager'));
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
            if (!wp_next_scheduled('wpdmgr_daily_log_cleanup')) {
                wp_schedule_event(time(), 'daily', 'wpdmgr_daily_log_cleanup');
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


        $cleaned_debug_logs = wpdmgr_cleanup_old_debug_logs();
        if ($cleaned_debug_logs > 0) {
            wpdmgr_debug_log("Cleaned up {$cleaned_debug_logs} old debug log files");
        }


        $debug_log_path = wpdmgr_get_debug_log_path();
        if (file_exists($debug_log_path)) {
            $debug_log_size = filesize($debug_log_path);
            $max_debug_size = wpdmgr_get_debug_log_max_size();

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

            wpdmgr_debug_log("Debug log truncated to {$max_lines} lines");
        }
    }

    public function ajax_download_query_logs() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied.', 'wp-debug-manager'));
        }

        $query_log_path = wpdmgr_get_query_log_path();

        if (!file_exists($query_log_path)) {
            wp_die(__('Query log file not found.', 'wp-debug-manager'));
        }

        $content = file_get_contents($query_log_path);
        $filename = 'query-logs-' . date('Y-m-d-H-i-s') . '.txt';

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));

        echo $content;
        exit;
    }

    public function ajax_toggle_perf_monitor() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wp-debug-manager'));
        }

        $enabled = isset($_POST['enabled']) && sanitize_key($_POST['enabled']) === 'true';
        update_option('wpdmgr_perf_monitor_enabled', $enabled);

        wp_send_json_success(array(
            'enabled' => $enabled,
            'message' => $enabled ? __('Performance monitor enabled.', 'wp-debug-manager') : __('Performance monitor disabled.', 'wp-debug-manager')
        ));
    }

    public function ajax_save_htaccess() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wp-debug-manager'));
        }

        if (!isset($_POST['content'])) {
            wp_send_json_error(__('Missing content parameter.', 'wp-debug-manager'));
        }

        // Sanitize the content
        $content = wp_unslash($_POST['content']);

        // Remove all HTML tags and dangerous content
        $content = wp_kses($content, array());

        // Additional sanitization using helper function
        $content = wpdmgr_sanitize_file_content($content);

        if ($content === false) {
            wp_send_json_error(__('Content contains potentially dangerous code and was rejected.', 'wp-debug-manager'));
        }

        $result = $this->services['htaccess']->save_htaccess($content);

        if ($result) {
            wp_send_json_success(__('.htaccess file saved successfully.', 'wp-debug-manager'));
        } else {
            wp_send_json_error(__('Failed to save .htaccess file.', 'wp-debug-manager'));
        }
    }

    public function ajax_restore_htaccess() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wp-debug-manager'));
        }

        if (!isset($_POST['backup_index'])) {
            wp_send_json_error(__('Missing backup index parameter.', 'wp-debug-manager'));
        }

        $backup_index = absint($_POST['backup_index']);
        $result = $this->services['htaccess']->restore_htaccess($backup_index);

        if ($result) {
            wp_send_json_success(__('.htaccess file restored successfully.', 'wp-debug-manager'));
        } else {
            wp_send_json_error(__('Failed to restore .htaccess file.', 'wp-debug-manager'));
        }
    }

    public function ajax_apply_php_preset() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wp-debug-manager'));
        }

        if (!isset($_POST['preset'])) {
            wp_send_json_error(__('Missing preset parameter.', 'wp-debug-manager'));
        }

        $preset = sanitize_key($_POST['preset']);
        $allowed_presets = array('basic', 'medium', 'high');
        if (!in_array($preset, $allowed_presets)) {
            wp_send_json_error(__('Invalid preset value.', 'wp-debug-manager'));
        }

        $result = $this->services['php_config']->apply_preset($preset);

        if ($result) {
            update_option('wpdmgr_php_preset', $preset);
            wp_send_json_success(__('PHP configuration applied successfully.', 'wp-debug-manager'));
        } else {
            wp_send_json_error(__('Failed to apply PHP configuration.', 'wp-debug-manager'));
        }
    }


    public function ajax_test_debug_transformer() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wp-debug-manager'));
        }

        wpdmgr_debug_log('=== DEBUG TRANSFORMER TEST (via AJAX) ===');


        $test_result = $this->services['debug']->test_wp_config_transformer();


        $reflection = new ReflectionClass($this->services['debug']);
        $method = $reflection->getMethod('get_custom_debug_log_path');
        $method->setAccessible(true);
        $custom_path = $method->invoke($this->services['debug']);

        $test_settings = [
            'WP_DEBUG' => true,
            'WP_DEBUG_LOG' => $custom_path
        ];

        wpdmgr_debug_log('Attempting to apply test debug settings via WPConfigTransformer');
        $apply_result = WPDMGR_WP_Config_Integration::apply_debug_constants($test_settings);
        wpdmgr_debug_log('Apply result: ' . ($apply_result ? 'SUCCESS' : 'FAILED'));

        $response = [
            'transformer_test' => $test_result,
            'apply_test' => $apply_result,
            'wp_config_path' => wpdmgr_get_wp_config_path(),
            'wp_config_writable' => is_writable(wpdmgr_get_wp_config_path()),
            'message' => 'Test completed. Check error logs for detailed results.'
        ];

        wp_send_json_success($response);
    }



    public function ajax_toggle_smtp_logging() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wp-debug-manager'));
        }

        $enabled = isset($_POST['enabled']) && sanitize_key($_POST['enabled']) === 'true';


        update_option('wpdmgr_smtp_logging_enabled', $enabled);


        if (isset($this->services['smtp_logger'])) {
            $reflection = new ReflectionClass($this->services['smtp_logger']);
            $property = $reflection->getProperty('log_enabled');
            $property->setAccessible(true);
            $property->setValue($this->services['smtp_logger'], $enabled);


            if ($enabled) {
                $init_method = $reflection->getMethod('init_hooks');
                $init_method->setAccessible(true);
                $init_method->invoke($this->services['smtp_logger']);
            }
        }

        wp_send_json_success(array(
            'enabled' => $enabled,
            'message' => $enabled ? __('SMTP logging enabled.', 'wp-debug-manager') : __('SMTP logging disabled.', 'wp-debug-manager')
        ));
    }

    public function ajax_toggle_smtp_ip_logging() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wp-debug-manager'));
        }

        $enabled = isset($_POST['enabled']) && sanitize_key($_POST['enabled']) === 'true';


        update_option('wpdmgr_smtp_log_ip_address', $enabled);


        if (isset($this->services['smtp_logger'])) {
            $reflection = new ReflectionClass($this->services['smtp_logger']);
            $property = $reflection->getProperty('log_ip_address');
            $property->setAccessible(true);
            $property->setValue($this->services['smtp_logger'], $enabled);
        }

        wp_send_json_success(array(
            'enabled' => $enabled,
            'message' => $enabled ? __('IP address logging enabled.', 'wp-debug-manager') : __('IP address logging disabled.', 'wp-debug-manager')
        ));
    }

    public function ajax_get_smtp_logs() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wp-debug-manager'));
        }

        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : null;
        $logs = $this->services['smtp_logger']->get_log_entries($date);
        wp_send_json_success($logs);
    }

    public function ajax_clear_smtp_logs() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wp-debug-manager'));
        }

        $result = $this->services['smtp_logger']->clear_current_log();

        if ($result) {
            wp_send_json_success(__('SMTP logs cleared successfully.', 'wp-debug-manager'));
        } else {
            wp_send_json_error(__('Failed to clear SMTP logs.', 'wp-debug-manager'));
        }
    }

    public function ajax_cleanup_smtp_logs() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'wp-debug-manager'));
        }

        $keep_days = isset($_POST['keep_days']) ? absint($_POST['keep_days']) : 30;
        $cleaned = $this->services['smtp_logger']->cleanup_old_logs($keep_days);

        if ($cleaned >= 0) {
            wp_send_json_success(sprintf(__('Cleaned up %d old SMTP log files.', 'wp-debug-manager'), $cleaned));
        } else {
            wp_send_json_error(__('Failed to cleanup old SMTP logs.', 'wp-debug-manager'));
        }
    }

    public function ajax_download_smtp_logs() {
        check_ajax_referer('wpdmgr_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied.', 'wp-debug-manager'));
        }

        $date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('dmY');
        $log_file = ABSPATH . 'wp-content/wp-debug-manager/smtp-' . $date . '.log';

        if (!file_exists($log_file)) {
            wp_die(__('SMTP log file not found.', 'wp-debug-manager'));
        }

        $content = file_get_contents($log_file);
        $filename = 'smtp-logs-' . $date . '.log';

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));

        echo $content;
        exit;
    }
}
