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
 * @package All Logs Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

$plugin = ALMGR_Plugin::get_instance();
$debug_enabled = defined('WP_DEBUG_LOG') && WP_DEBUG_LOG;
$show_query_tab = defined('SAVEQUERIES') ? (bool) constant('SAVEQUERIES') : false;

// Get SMTP logging status
$smtp_service = $plugin->get_service('smtp_logger');
$smtp_status = $smtp_service ? $smtp_service->get_logging_status() : array('enabled' => false);
$smtp_enabled = !empty($smtp_status['enabled']);

// Determine initial tab from query param with fallback
// Note: Tab switching is a read-only operation, nonce not required for security
$requested_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'debug';
$tabs_visibility = array(
    'debug' => true,
    'query' => $show_query_tab,
    'smtp'  => $smtp_enabled,
);

// Validate requested tab against allowed tabs
$allowed_tabs = array_keys(array_filter($tabs_visibility));
if (!in_array($requested_tab, $allowed_tabs, true)) {
    $requested_tab = 'debug';
}
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
    <h1><?php echo esc_html__('All Log Activity', 'advanced-log-manager'); ?></h1>

    <?php if (!$debug_enabled): ?>
        <div class="notice notice-warning"><p><?php echo esc_html__('WP_DEBUG_LOG is not enabled. Enable it to access log activity.', 'advanced-log-manager'); ?></p></div>
        <?php return; ?>
    <?php endif; ?>

    <h2 class="nav-tab-wrapper" id="almgr-all-logs-tabs">
        <a href="#" class="nav-tab <?php echo $initial_tab === 'debug' ? 'nav-tab-active' : ''; ?>" data-target="almgr-tab-debug"><?php echo esc_html__('Debug Log', 'advanced-log-manager'); ?></a>
        <?php if ($show_query_tab): ?>
            <a href="#" class="nav-tab <?php echo $initial_tab === 'query' ? 'nav-tab-active' : ''; ?>" data-target="almgr-tab-query"><?php echo esc_html__('Query Log', 'advanced-log-manager'); ?></a>
        <?php endif; ?>
        <?php if ($smtp_enabled): ?>
            <a href="#" class="nav-tab <?php echo $initial_tab === 'smtp' ? 'nav-tab-active' : ''; ?>" data-target="almgr-tab-smtp"><?php echo esc_html__('SMTP Log', 'advanced-log-manager'); ?></a>
        <?php endif; ?>
    </h2>

    <div class="almgr-tabs-container">
        <div id="almgr-tab-debug" class="almgr-tab-content" style="display: <?php echo $initial_tab === 'debug' ? 'block' : 'none'; ?>;">
            <?php $plugin->render_logs_page(); ?>
        </div>

        <?php if ($show_query_tab): ?>
        <div id="almgr-tab-query" class="almgr-tab-content" style="display: <?php echo $initial_tab === 'query' ? 'block' : 'none'; ?>;">
            <?php $plugin->render_query_logs_page(); ?>
        </div>
        <?php endif; ?>

        <?php if ($smtp_enabled): ?>
        <div id="almgr-tab-smtp" class="almgr-tab-content" style="display: <?php echo $initial_tab === 'smtp' ? 'block' : 'none'; ?>;">
            <?php $plugin->render_smtp_logs_page(); ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function(){
    document.addEventListener('DOMContentLoaded', function(){
        var tabsWrapper = document.getElementById('almgr-all-logs-tabs');
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
                document.querySelectorAll('.almgr-tab-content').forEach(function(panel){
                    panel.style.display = (panel.id === tabId) ? 'block' : 'none';
                });
            }
        });
    });
})();
</script>