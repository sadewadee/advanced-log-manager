<?php
/**
 * Uninstall script for WP Debug Manager
 *
 * @package WP Debug Manager
 * @author Morden Team
 * @license GPL v3 or later
 * @link https://github.com/sadewadee/wp-debug-manager
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Include internal logging helper
require_once __DIR__ . '/includes/class-internal-log.php';

function wpdmgr_cleanup_options() {
    $options_to_delete = array(
        'wpdmgr_debug_enabled',
        'wpdmgr_perf_monitor_enabled',
        'wpdmgr_perf_realtime_enabled',
        'wpdmgr_perf_bootstrap_enabled',
        'wpdmgr_perf_domains_enabled',
        'wpdmgr_htaccess_backups',
        'wpdmgr_php_preset',
        'wpdmgr_wp_config_backups',
        'wpdmgr_php_ini_backups',
        'wpdmgr_version'
    );

    foreach ($options_to_delete as $option) {
        delete_option($option);
    }
}

function wpdmgr_cleanup_wp_config() {
    $wp_config_path = ABSPATH . 'wp-config.php';
    if (!file_exists($wp_config_path)) {
        $wp_config_path = dirname(ABSPATH) . '/wp-config.php';
    }

    if (!file_exists($wp_config_path) || !is_writable($wp_config_path)) {
        return;
    }

    $config_content = file_get_contents($wp_config_path);


    $pattern = '/\/\/ BEGIN WP Debug Manager PHP Config.*?\/\/ END WP Debug Manager PHP Config\s*/s';
    $config_content = preg_replace($pattern, '', $config_content);

    // Disable debug constants
    $constants_to_disable = array(
        'WP_DEBUG',
        'WP_DEBUG_LOG',
        'WP_DEBUG_DISPLAY',
        'SCRIPT_DEBUG'
    );

    foreach ($constants_to_disable as $constant) {
        $pattern = "/define\s*\(\s*['\"]" . $constant . "['\"]\s*,\s*[^)]+\s*\)\s*;/i";
        $replacement = "define('" . $constant . "', false);";

        if (preg_match($pattern, $config_content)) {
            $config_content = preg_replace($pattern, $replacement, $config_content);
        }
    }

    file_put_contents($wp_config_path, $config_content);
}

function wpdmgr_cleanup_htaccess() {
    $htaccess_path = ABSPATH . '.htaccess';

    if (!file_exists($htaccess_path) || !is_writable($htaccess_path)) {
        return;
    }

    $htaccess_content = file_get_contents($htaccess_path);

    // Remove WP Debug Manager PHP config block
    $pattern = '/# BEGIN MT PHP Config.*?# END MT PHP Config/s';
    $htaccess_content = preg_replace($pattern, '', $htaccess_content);

    // Clean up extra newlines
    $htaccess_content = preg_replace('/\n{3,}/', "\n\n", $htaccess_content);

    file_put_contents($htaccess_path, $htaccess_content);
}

function wpdmgr_cleanup_php_ini() {
    $php_ini_path = ABSPATH . 'php.ini';

    if (!file_exists($php_ini_path)) {
        return;
    }

    $content = file_get_contents($php_ini_path);

    // If the file only contains WP Debug Manager config, delete it
    if (strpos($content, '; WP Debug Manager PHP Config') !== false) {
        $lines = explode("\n", $content);
        $non_wpdmgr_lines = array();

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) ||
                strpos($line, '; WP Debug Manager') !== false ||
                in_array(explode(' = ', $line)[0] ?? '', array(
                    'memory_limit',
                    'upload_max_filesize',
                    'post_max_size',
                    'max_execution_time',
                    'max_input_vars',
                    'max_input_time'
                ))) {
                continue;
            }
            $non_wpdmgr_lines[] = $line;
        }

        if (empty($non_wpdmgr_lines)) {
            unlink($php_ini_path);
        } else {
            file_put_contents($php_ini_path, implode("\n", $non_wpdmgr_lines));
        }
    }
}

function wpdmgr_cleanup_temp_files() {
    $temp_pattern = ABSPATH . '*.tmp';
    $temp_files = glob($temp_pattern);

    foreach ($temp_files as $temp_file) {
        if (is_file($temp_file) && is_writable($temp_file)) {
            unlink($temp_file);
        }
    }
}

function wpdmgr_cleanup_transients() {
    global $wpdb;

    // Delete performance metrics transients
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_mt_metrics_%'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_mt_metrics_%'));
}

function wpdmgr_cleanup_log_files() {
    $log_directory = WP_CONTENT_DIR . '/wp-debug-manager/';

    if (!is_dir($log_directory)) {
        return 0;
    }

    $patterns = [
        'wp-errors-*.log',    // Debug logs
        'wp-queries-*.log',   // Query logs
        'query.log',          // Main query log
        'query.log.*',        // Query log rotation files (query.log.1, query.log.2, etc.)
        'debug.log',          // Main debug log
        '.htaccess',          // Protection file
        'index.php'           // Protection file
    ];

    $removed_count = 0;

    foreach ($patterns as $pattern) {
        $files = glob($log_directory . $pattern);

        foreach ($files as $file) {
            if (file_exists($file) && unlink($file)) {
                $removed_count++;
            }
        }
    }

    // Try to remove the directory if it's empty
    if (is_dir($log_directory)) {
        $remaining_files = glob($log_directory . '*');
        if (empty($remaining_files)) {
            rmdir($log_directory);
        }
    }

    return $removed_count;
}

function wpdmgr_log_uninstall() {
    wpdmgr_debug_log('Plugin uninstalled and cleaned up');
}

try {
    wpdmgr_cleanup_options();
    wpdmgr_cleanup_wp_config();
    wpdmgr_cleanup_htaccess();
    wpdmgr_cleanup_php_ini();
    wpdmgr_cleanup_temp_files();
    wpdmgr_cleanup_transients();

    $removed_logs = wpdmgr_cleanup_log_files();
    if ($removed_logs > 0) {
        wpdmgr_debug_log("Removed {$removed_logs} log files during uninstall");
    }

    wpdmgr_log_uninstall();
} catch (Exception $e) {
    wpdmgr_error_log('Uninstall error: ' . $e->getMessage());
}
