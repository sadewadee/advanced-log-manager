<?php
/**
 * All Log Activity Page Template (Unified Tabs)
 *
 * Displays Debug Logs, Query Logs, and SMTP Logs in separate tabs.
 * Tabs visibility rules:
 * - Debug Log: always visible when this page is accessible (WP_DEBUG_LOG must be true to access the page)
 * - Query Log: visible only when SAVEQUERIES is true
 * - SMTP Log: visible only when SMTP logging is enabled
 *
 * @package WP Debug Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

$plugin = WPDMGR_Plugin::get_instance();
$debug_enabled = defined('WP_DEBUG_LOG') && WP_DEBUG_LOG;
$show_query_tab = defined('SAVEQUERIES') ? (bool) constant('SAVEQUERIES') : false;

// Get SMTP logging status
$smtp_service = $plugin->get_service('smtp_logger');
$smtp_status = $smtp_service ? $smtp_service->get_logging_status() : array('enabled' => false);
$smtp_enabled = !empty($smtp_status['enabled']);

// Determine initial tab from query param with fallback
$requested_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'debug';
$tabs_visibility = array(
    'debug' => true,
    'query' => $show_query_tab,
    'smtp'  => $smtp_enabled,
);
$initial_tab = 'debug';
if (isset($tabs_visibility[$requested_tab]) && $tabs_visibility[$requested_tab]) {
    $initial_tab = $requested_tab;
} else {
    foreach ($tabs_visibility as $tab => $visible) {
        if ($visible) { $initial_tab = $tab; break; }
    }
}

?>
<div class="wrap">
    <h1><?php echo esc_html__('All Log Activity', 'wp-debug-manager'); ?></h1>

    <?php if (!$debug_enabled): ?>
        <div class="notice notice-warning"><p><?php echo esc_html__('WP_DEBUG_LOG is not enabled. Enable it to access log activity.', 'wp-debug-manager'); ?></p></div>
        <?php return; ?>
    <?php endif; ?>

    <h2 class="nav-tab-wrapper" id="wpdmgr-all-logs-tabs">
        <a href="#" class="nav-tab <?php echo $initial_tab === 'debug' ? 'nav-tab-active' : ''; ?>" data-target="wpdmgr-tab-debug"><?php echo esc_html__('Debug Log', 'wp-debug-manager'); ?></a>
        <?php if ($show_query_tab): ?>
            <a href="#" class="nav-tab <?php echo $initial_tab === 'query' ? 'nav-tab-active' : ''; ?>" data-target="wpdmgr-tab-query"><?php echo esc_html__('Query Log', 'wp-debug-manager'); ?></a>
        <?php endif; ?>
        <?php if ($smtp_enabled): ?>
            <a href="#" class="nav-tab <?php echo $initial_tab === 'smtp' ? 'nav-tab-active' : ''; ?>" data-target="wpdmgr-tab-smtp"><?php echo esc_html__('SMTP Log', 'wp-debug-manager'); ?></a>
        <?php endif; ?>
    </h2>

    <div class="wpdmgr-tabs-container">
        <div id="wpdmgr-tab-debug" class="wpdmgr-tab-content" style="display: <?php echo $initial_tab === 'debug' ? 'block' : 'none'; ?>;">
            <?php include WPDMGR_PLUGIN_DIR . 'admin/views/page-logs.php'; ?>
        </div>

        <?php if ($show_query_tab): ?>
        <div id="wpdmgr-tab-query" class="wpdmgr-tab-content" style="display: <?php echo $initial_tab === 'query' ? 'block' : 'none'; ?>;">
            <?php include WPDMGR_PLUGIN_DIR . 'admin/views/page-query-logs.php'; ?>
        </div>
        <?php endif; ?>

        <?php if ($smtp_enabled): ?>
        <div id="wpdmgr-tab-smtp" class="wpdmgr-tab-content" style="display: <?php echo $initial_tab === 'smtp' ? 'block' : 'none'; ?>;">
            <?php include WPDMGR_PLUGIN_DIR . 'admin/views/page-smtp-logs.php'; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function(){
    document.addEventListener('DOMContentLoaded', function(){
        var tabsWrapper = document.getElementById('wpdmgr-all-logs-tabs');
        if (!tabsWrapper) return;
        tabsWrapper.addEventListener('click', function(e){
            var target = e.target;
            if (target && target.classList.contains('nav-tab')) {
                e.preventDefault();
                var tabId = target.getAttribute('data-target');
                if (!tabId) return;

                // Toggle active tab class
                tabsWrapper.querySelectorAll('.nav-tab').forEach(function(tab){
                    tab.classList.remove('nav-tab-active');
                });
                target.classList.add('nav-tab-active');

                // Show the selected content, hide others
                document.querySelectorAll('.wpdmgr-tab-content').forEach(function(panel){
                    panel.style.display = (panel.id === tabId) ? 'block' : 'none';
                });
            }
        });
    });
})();
</script>