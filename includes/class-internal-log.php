<?php
/**
 * Internal Logging Helper
 *
 * Provides controlled internal logging that can be enabled/disabled
 * via wp-config.php constant: WPDMGR_INTERNAL_LOGGING
 *
 * @package WP Debug Manager
 * @author WPDMGR Team
 * @license GPL v3 or later
 * @link https://github.com/sadewadee/wp-debug-manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Internal logging function with manual control
 *
 * @param string $message Log message
 * @param string $context Context prefix (optional)
 */
function wpdmgr_internal_log($message, $context = 'MT') {
    if (!defined('WPDMGR_INTERNAL_LOGGING') || !WPDMGR_INTERNAL_LOGGING) {
        return;
    }

    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log($context . ': ' . $message);
    }
}

/**
 * Debug-level internal logging
 */
function wpdmgr_debug_log($message) {
    wpdmgr_internal_log($message, 'MT Debug');
}

/**
 * Error-level internal logging
 */
function wpdmgr_error_log($message) {
    wpdmgr_internal_log($message, 'MT Error');
}

/**
 * Config-level internal logging
 */
function wpdmgr_config_log($message) {
    wpdmgr_internal_log($message, 'MT Config');
}