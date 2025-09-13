<?php
/**
 * Plugin Name: Advanced Log Manager
 * Plugin URI: https://github.com/sadewadee/advanced-log-manager
 * Description: Advanced logging and debugging tools for WordPress: Log Manager, Query Monitor, Htaccess Editor, PHP Config presets.
 * Version: 1.2.27
 * Author: Morden Team
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: advanced-log-manager
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 *
 * Internal Logging Control:
 * Add to wp-config.php to enable internal plugin logging:
 * define('ALMGR_INTERNAL_LOGGING', true);
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ALMGR_VERSION', '1.2.27');
define('ALMGR_PLUGIN_FILE', __FILE__);
define('ALMGR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ALMGR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ALMGR_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once ALMGR_PLUGIN_DIR . 'includes/helpers.php';
require_once ALMGR_PLUGIN_DIR . 'includes/class-internal-log.php';
require_once ALMGR_PLUGIN_DIR . 'includes/class-plugin.php';
require_once ALMGR_PLUGIN_DIR . 'includes/class-debug.php';
require_once ALMGR_PLUGIN_DIR . 'includes/class-query-monitor.php';
require_once ALMGR_PLUGIN_DIR . 'includes/class-htaccess.php';
require_once ALMGR_PLUGIN_DIR . 'includes/class-php-config.php';
require_once ALMGR_PLUGIN_DIR . 'includes/class-file-manager.php';
require_once ALMGR_PLUGIN_DIR . 'includes/class-smtp-logger.php';

function almgr_init() {
    ALMGR_Plugin::get_instance();
}
add_action('plugins_loaded', 'almgr_init');



register_activation_hook(__FILE__, function() {
    if (!get_option('almgr_debug_enabled')) {
        add_option('almgr_debug_enabled', false);
    }

    $old_query_monitor = get_option('almgr_query_monitor_enabled');
    if ($old_query_monitor !== false) {
        update_option('almgr_perf_monitor_enabled', $old_query_monitor);
        delete_option('almgr_query_monitor_enabled');
    } elseif (!get_option('almgr_perf_monitor_enabled')) {
        add_option('almgr_perf_monitor_enabled', false);
    }

    if (!get_option('almgr_htaccess_backups')) {
        add_option('almgr_htaccess_backups', array());
    }

    $old_php_preset = get_option('almgr_php_preset');
    if ($old_php_preset !== false) {
        update_option('almgr_php_preset', $old_php_preset);
        delete_option('almgr_php_preset');
    } elseif (!get_option('almgr_php_preset')) {
        add_option('almgr_php_preset', 'medium');
    }
});

register_deactivation_hook(__FILE__, function() {
    if (get_option('almgr_debug_enabled')) {
        $debug_service = new ALMGR_Debug();
        $debug_service->disable_debug();
        update_option('almgr_debug_enabled', false);
    }

    $cleanup_on_deactivation = apply_filters('almgr_cleanup_logs_on_deactivation', false);

    if ($cleanup_on_deactivation && function_exists('almgr_cleanup_old_debug_logs')) {
        $cleaned = almgr_cleanup_old_debug_logs(1);
        if ($cleaned > 0) {
            almgr_debug_log("Cleaned up {$cleaned} old log files on deactivation");
        }
    }
});