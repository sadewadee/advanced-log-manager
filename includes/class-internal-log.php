<?php
/**
 * Internal Logging Helper
 *
 * Provides controlled internal logging that can be enabled/disabled
 * via wp-config.php constant: ALMGR_INTERNAL_LOGGING
 *
 * @package Advanced Log Manager
 * @author Morden Team
 * @license GPL v3 or later
 * @link https://github.com/sadewadee/advanced-log-manager
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
function almgr_internal_log($message, $context = 'MT') {
    // Define default if not set in wp-config.php
    if (!defined('ALMGR_INTERNAL_LOGGING')) {
        define('ALMGR_INTERNAL_LOGGING', false);
    }
    
    if (!ALMGR_INTERNAL_LOGGING) {
        return;
    }

    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log($context . ': ' . $message);
    }
}

/**
 * Debug-level internal logging
 */
function almgr_debug_log($message) {
    almgr_internal_log($message, 'MT Debug');
}

/**
 * Error-level internal logging
 */
function almgr_error_log($message) {
    almgr_internal_log($message, 'MT Error');
}

/**
 * Config-level internal logging
 */
function almgr_config_log($message) {
    almgr_internal_log($message, 'MT Config');
}