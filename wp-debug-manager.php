<?php
/**
 * Plugin Name: WP Debug Manager
 * Plugin URI: https://github.com/sadewadee/wp-debug-manager
 * Description: Lightweight developer tools for WordPress: Debug Manager, Query Monitor, Htaccess Editor, PHP Config presets.
 * Version: 1.2.19
 * Author: WPDMGR Team
 * Author URI: https://wpdmgr.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wp-debug-manager
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 *
 * Internal Logging Control:
 * Add to wp-config.php to enable internal plugin logging:
 * define('WPDMGR_INTERNAL_LOGGING', true);
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WPDMGR_VERSION', '1.2.19');
define('WPDMGR_PLUGIN_FILE', __FILE__);
define('WPDMGR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPDMGR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPDMGR_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once WPDMGR_PLUGIN_DIR . 'includes/helpers.php';
require_once WPDMGR_PLUGIN_DIR . 'includes/class-internal-log.php';
require_once WPDMGR_PLUGIN_DIR . 'includes/class-plugin.php';
require_once WPDMGR_PLUGIN_DIR . 'includes/class-debug.php';
require_once WPDMGR_PLUGIN_DIR . 'includes/class-query-monitor.php';
require_once WPDMGR_PLUGIN_DIR . 'includes/class-htaccess.php';
require_once WPDMGR_PLUGIN_DIR . 'includes/class-php-config.php';
require_once WPDMGR_PLUGIN_DIR . 'includes/class-file-manager.php';
require_once WPDMGR_PLUGIN_DIR . 'includes/class-smtp-logger.php';

function wpdmgr_init() {
    WPDMGR_Plugin::get_instance();
}
add_action('plugins_loaded', 'wpdmgr_init');



register_activation_hook(__FILE__, function() {
    if (!get_option('wpdmgr_debug_enabled')) {
        add_option('wpdmgr_debug_enabled', false);
    }

    $old_query_monitor = get_option('wpdmgr_query_monitor_enabled');
    if ($old_query_monitor !== false) {
        update_option('wpdmgr_query_monitor_enabled', $old_query_monitor);
        delete_option('wpdmgr_query_monitor_enabled');
    } elseif (!get_option('wpdmgr_query_monitor_enabled')) {
        add_option('wpdmgr_query_monitor_enabled', false);
    }

    if (!get_option('wpdmgr_htaccess_backups')) {
        add_option('wpdmgr_htaccess_backups', array());
    }

    $old_php_preset = get_option('wpdmgr_php_preset');
    if ($old_php_preset !== false) {
        update_option('wpdmgr_php_preset', $old_php_preset);
        delete_option('wpdmgr_php_preset');
    } elseif (!get_option('wpdmgr_php_preset')) {
        add_option('wpdmgr_php_preset', 'medium');
    }
});

register_deactivation_hook(__FILE__, function() {
    if (get_option('wpdmgr_debug_enabled')) {
        $debug_service = new WPDMGR_Debug();
        $debug_service->disable_debug();
        update_option('wpdmgr_debug_enabled', false);
    }

    $cleanup_on_deactivation = apply_filters('wpdmgr_cleanup_logs_on_deactivation', false);

    if ($cleanup_on_deactivation && function_exists('wpdmgr_cleanup_old_debug_logs')) {
        $cleaned = wpdmgr_cleanup_old_debug_logs(1);
        if ($cleaned > 0) {
            wpdmgr_debug_log("Cleaned up {$cleaned} old log files on deactivation");
        }
    }
});
