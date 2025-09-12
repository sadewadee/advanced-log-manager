<?php
/**
 * Query Monitor Service - Performance metrics display
 *
 * @package WP Debug Manager
 * @author Morden Team
 * @license GPL v3 or later
 * @link https://github.com/sadewadee/wp-debug-manager
 * @since 1.2.18
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * MT Query Monitor Class
 *
 * Provides performance monitoring and metrics display for WordPress
 *
 * @since 1.2.18
 */
class WPDMGR_Perf_Monitor {

    private $metrics = array();
    private $hook_collectors = array();
    private $executed_hooks = array();
    private $bootstrap_snapshots = array();
    private $domain_collectors = array();
    private $real_time_hooks = array();
    private $hook_execution_order = 0;

	/**
	 * Constructor - Initialize the Query Monitor
	 *
	 * Sets up the performance monitoring if enabled and user is logged in.
	 */
	public function __construct() {
		if (get_option('wpdmgr_perf_monitor_enabled') && is_user_logged_in()) {
			// Read granular feature flags
			$realtime_enabled  = (bool) get_option('wpdmgr_perf_realtime_enabled');
			$bootstrap_enabled = (bool) get_option('wpdmgr_perf_bootstrap_enabled');
			$domains_enabled   = (bool) get_option('wpdmgr_perf_domains_enabled');

			// Initialize only the enabled modules
			if ($domains_enabled) {
				$this->init_domain_collectors();
			}
			if ($realtime_enabled) {
				$this->start_realtime_hook_monitoring();
			}
			if ($bootstrap_enabled) {
				$this->capture_bootstrap_snapshots();
			}

			add_action('init', array($this, 'start_performance_tracking'));
			add_action('admin_bar_menu', array($this, 'add_admin_bar_metrics'), 999);
			// Frontend assets are enqueued by class-plugin to avoid duplicate loading
			// add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
			add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
            add_action('wp_ajax_wpdmgr_monitor_hooks', array($this, 'ajax_monitor_hooks'));
            add_action('wp_ajax_nopriv_wpdmgr_monitor_hooks', array($this, 'ajax_monitor_hooks'));
		}
	}

	/**
	 * Enqueue CSS and JS assets for the query monitor
	 *
	 * @since 1.2.18
	 */
	public function enqueue_assets() {
		if (!current_user_can('manage_options')) {
			return;
		}

		$plugin_url = plugin_dir_url(dirname(__FILE__));

		wp_enqueue_style(
			'wpdmgr-performance-bar',
			$plugin_url . 'public/assets/performance-bar.css',
			array(),
			WPDMGR_VERSION
		);

		// Ensure performance-bar.js is loaded in admin for unified panel handling
		wp_enqueue_script(
			'wpdmgr-performance-bar',
			$plugin_url . 'public/assets/performance-bar.js',
			array(),
			WPDMGR_VERSION,
			true
		);

        // Tabs enhancements for filtering, sorting, ARIA, shortcuts, and export
        wp_enqueue_script(
            'wpdmgr-performance-tabs',
            $plugin_url . 'public/assets/performance-tabs.js',
            array('wpdmgr-performance-bar'),
            WPDMGR_VERSION,
            true
        );

        // Localize strings for UI elements
        wp_localize_script('wpdmgr-performance-tabs', 'wpdmgrPerfTabsL10n', array(
            'searchPlaceholder' => __('Search...', 'wp-debug-manager'),
            'exportCSV' => __('Export CSV', 'wp-debug-manager'),
            'loading' => __('Loading...', 'wp-debug-manager'),
            'shortcutSearch' => __('Press / to search', 'wp-debug-manager')
        ));

		wp_enqueue_script(
			'wpdmgr-perf-monitor',
			$plugin_url . 'admin/assets/js/query-monitor.js',
			array('jquery'),
			WPDMGR_VERSION,
			true
		);

		wp_localize_script('wpdmgr-perf-monitor', 'mtQueryMonitorL10n', array(
			'enableRealTimeUpdates' => __('Enable Real-time Updates', 'wp-debug-manager'),
			'stopRealTimeUpdates' => __('Stop Real-time Updates', 'wp-debug-manager'),
			'statusActive' => __('Active', 'wp-debug-manager'),
			'statusStatic' => __('Static View', 'wp-debug-manager'),
			'statusRefreshing' => __('Refreshing...', 'wp-debug-manager'),
			'statusUpdated' => __('Updated', 'wp-debug-manager'),
			'statusError' => __('Error', 'wp-debug-manager'),
			'viewDetails' => __('View Details', 'wp-debug-manager'),
			'hideDetails' => __('Hide Details', 'wp-debug-manager'),
			'toggle' => __('Toggle', 'wp-debug-manager'),
			'hide' => __('Hide', 'wp-debug-manager'),
		));

		// Pass AJAX data
		if (function_exists('wp_create_nonce')) {
			wp_localize_script('wpdmgr-perf-monitor', 'mtHookMonitor', array(
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('wpdmgr_monitor_hooks_nonce'),
				'isActive' => false,
				'interval' => null
			));
		}
	}

    /**
     * Initialize domain-specific collectors similar to Query Monitor
     */
    private function init_domain_collectors() {
        $this->domain_collectors = array(
            'database' => array(
                'hooks' => array(),
                'queries' => array(),
                'transactions' => array()
            ),
            'http' => array(
                'hooks' => array(),
                'requests' => array(),
                'responses' => array()
            ),
            'rewrite' => array(
                'hooks' => array(),
                'rules' => array(),
                'queries' => array()
            ),
            'template' => array(
                'hooks' => array(),
                'hierarchy' => array(),
                'includes' => array()
            ),
            'capabilities' => array(
                'hooks' => array(),
                'checks' => array(),
                'roles' => array()
            ),
            'cache' => array(
                'hooks' => array(),
                'operations' => array(),
                'hits_misses' => array()
            ),
            'assets' => array(
                'hooks' => array(),
                'scripts' => array(),
                'styles' => array()
            )
        );
    }

    /**
     * Start selective hook monitoring instead of resource-intensive 'all' hook
     */
    private function start_realtime_hook_monitoring() {
        // Instead of monitoring 'all', monitor strategic hooks only
        $strategic_hooks = $this->get_strategic_hooks();

        foreach ($strategic_hooks as $hook) {
            add_action($hook, array($this, 'capture_strategic_hook'), -999999);
            add_filter($hook, array($this, 'capture_strategic_hook'), -999999);
        }

        // Track hook registration changes at key phases only
        add_filter('wp_loaded', array($this, 'capture_wp_loaded_hooks'), -1);
        add_action('wp_head', array($this, 'capture_wp_head_hooks'), -1);
        add_action('wp_footer', array($this, 'capture_wp_footer_hooks'), -1);

        // Enable AJAX endpoint for continued monitoring after page load
        add_action('wp_ajax_mt_monitor_hooks', array($this, 'ajax_monitor_hooks'));
        add_action('wp_ajax_nopriv_mt_monitor_hooks', array($this, 'ajax_monitor_hooks'));
    }

    /**
     * Capture bootstrap snapshots at different WordPress phases
     */
    private function capture_bootstrap_snapshots() {
        // Capture at various bootstrap phases
        add_action('muplugins_loaded', array($this, 'snapshot_muplugins_phase'), -999999);
        add_action('plugins_loaded', array($this, 'snapshot_plugins_phase'), -999999);
        add_action('setup_theme', array($this, 'snapshot_theme_setup_phase'), -999999);
        add_action('after_setup_theme', array($this, 'snapshot_after_theme_setup_phase'), -999999);
        add_action('init', array($this, 'snapshot_init_phase'), -999999);
        add_action('wp_loaded', array($this, 'snapshot_wp_loaded_phase'), -999999);
        add_action('parse_request', array($this, 'snapshot_parse_request_phase'), -999999);
        add_action('send_headers', array($this, 'snapshot_send_headers_phase'), -999999);
        add_action('wp', array($this, 'snapshot_wp_phase'), -999999);
    }

    /**
     * Get strategic hooks to monitor (much more selective than 'all')
     */
    private function get_strategic_hooks() {
        return array(
            // Core WordPress lifecycle - most important for debugging
            'muplugins_loaded', 'plugins_loaded', 'setup_theme', 'after_setup_theme',
            'init', 'wp_loaded', 'parse_request', 'wp', 'template_redirect',

            // Template and theming - key performance points
            'wp_head', 'wp_footer', 'wp_enqueue_scripts', 'wp_print_styles',
            'get_header', 'get_footer', 'get_sidebar',

            // Content and queries - database performance
            'pre_get_posts', 'the_posts', 'the_content', 'the_excerpt',
            'wp_insert_post', 'save_post', 'publish_post',

            // User and authentication - security monitoring
            'wp_login', 'wp_logout', 'user_register', 'profile_update',
            'wp_authenticate',

            // Admin and AJAX - backend performance
            'admin_init', 'admin_menu', 'admin_enqueue_scripts',

            // Critical performance hooks
            'shutdown', 'wp_footer', 'admin_footer',

            // Error and debugging
            'wp_die_handler', 'wp_redirect', 'wp_safe_redirect',

            // Plugin/theme specific
            'activated_plugin', 'deactivated_plugin', 'switch_theme'
        );
    }

    /**
     * Capture strategic hook execution (replaces capture_hook_execution)
     */
    public function capture_strategic_hook($hook_name = null) {
        // When used as a filter, $hook_name might be the filtered value (non-string)
        // Always get the actual hook name from current_filter()
        $actual_hook_name = current_filter();

        // Validate that we have a proper hook name
        if (!is_string($actual_hook_name) || empty($actual_hook_name)) {
            return $hook_name; // Return original value for filters
        }

        // Limit data collection to prevent memory issues
        if (count($this->real_time_hooks) > 500) {
            // Remove oldest entries to maintain reasonable memory usage
            $this->real_time_hooks = array_slice($this->real_time_hooks, -400, null, true);
        }

        $this->hook_execution_order++;

        $execution_data = array(
            'hook' => $actual_hook_name,
            'order' => $this->hook_execution_order,
            'time' => microtime(true),
            'memory' => memory_get_usage(),
            'backtrace' => $this->get_filtered_backtrace(),
            'phase' => $this->get_current_wp_phase()
        );

        $this->real_time_hooks[] = $execution_data;

        // Categorize by domain
        $domain = $this->categorize_hook_by_domain($actual_hook_name);
        if ($domain) {
            $this->domain_collectors[$domain]['hooks'][] = $execution_data;
        }

        // Return original value for filters
        return $hook_name;
    }

    /**
     * AJAX handler for continued hook monitoring after page load
     */
    public function ajax_monitor_hooks() {
        // Verify nonce for security
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        if (!wp_verify_nonce($nonce, 'wpdmgr_monitor_hooks_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        // Get current hook state for real-time updates
        $response = array(
            'success' => true,
            'timestamp' => current_time('timestamp'),
            'hooks_captured' => count($this->real_time_hooks),
            'recent_hooks' => array_slice($this->real_time_hooks, -10), // Last 10 hooks
            'memory_usage' => memory_get_usage(),
            'domain_summary' => $this->get_domain_summary()
        );

        wp_send_json($response);
    }

    /**
     * Get domain summary for AJAX updates
     */
    private function get_domain_summary() {
        $summary = array();
        foreach ($this->domain_collectors as $domain => $data) {
            $summary[$domain] = count($data['hooks']);
        }
        return $summary;
    }



    /**
     * Get filtered backtrace for hook execution context
     */
    private function get_filtered_backtrace() {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $filtered_trace = array();

        foreach ($trace as $frame) {
            // Skip our own monitoring functions
            if (isset($frame['class']) && $frame['class'] === __CLASS__) {
                continue;
            }

            if (isset($frame['function']) && in_array($frame['function'], array(
                'do_action', 'apply_filters', 'do_action_ref_array', 'apply_filters_ref_array'
            ))) {
                continue;
            }

            $filtered_trace[] = array(
                'file' => isset($frame['file']) ? basename($frame['file']) : 'unknown',
                'line' => isset($frame['line']) ? $frame['line'] : 0,
                'function' => isset($frame['function']) ? $frame['function'] : 'unknown',
                'class' => isset($frame['class']) ? $frame['class'] : null
            );

            // Limit to 5 most relevant frames
            if (count($filtered_trace) >= 5) {
                break;
            }
        }

        return $filtered_trace;
    }

    /**
     * Categorize hooks by domain for organized collection
     */
    private function categorize_hook_by_domain($hook_name) {
        // Ensure hook_name is a string to prevent preg_match errors
        if (!is_string($hook_name) || empty($hook_name)) {
            return null;
        }

        // Database domain hooks
        if (preg_match('/^(query|pre_get_|posts_|found_posts|the_posts|wp_insert_|wp_update_|wp_delete_|db_|wpdb_)/', $hook_name)) {
            return 'database';
        }

        // HTTP domain hooks
        if (preg_match('/^(http_|wp_remote_|pre_http_|wp_redirect|wp_safe_redirect)/', $hook_name)) {
            return 'http';
        }

        // Rewrite domain hooks
        if (preg_match('/^(rewrite_|redirect_|parse_request|request|query_vars|pre_get_posts)/', $hook_name)) {
            return 'rewrite';
        }

        // Template domain hooks
        if (preg_match('/^(template_|get_|locate_template|load_template|include_template|wp_head|wp_footer)/', $hook_name)) {
            return 'template';
        }

        // Capabilities domain hooks
        if (preg_match('/^(user_has_cap|map_meta_cap|role_has_cap|current_user_can|wp_roles|add_role|remove_role)/', $hook_name)) {
            return 'capabilities';
        }

        // Cache domain hooks
        if (preg_match('/^(wp_cache_|clean_|flush_|wp_suspend_cache|wp_using_ext_object_cache)/', $hook_name)) {
            return 'cache';
        }

        // Assets domain hooks
        if (preg_match('/^(wp_enqueue_|wp_dequeue_|wp_register_|script_loader_|style_loader_)/', $hook_name)) {
            return 'assets';
        }

        return null; // Uncategorized
    }

    /**
     * Get current WordPress execution phase
     */
    private function get_current_wp_phase() {
        if (!did_action('muplugins_loaded')) return 'mu-plugins';
        if (!did_action('plugins_loaded')) return 'plugins';
        if (!did_action('setup_theme')) return 'theme-setup';
        if (!did_action('after_setup_theme')) return 'after-theme-setup';
        if (!did_action('init')) return 'pre-init';
        if (!did_action('wp_loaded')) return 'init';
        if (!did_action('parse_request')) return 'loaded';
        if (!did_action('wp')) return 'request-parsing';
        if (!did_action('wp_head')) return 'pre-template';
        if (!did_action('wp_footer')) return 'template';
        return 'complete';
    }

    // Bootstrap snapshot methods
    public function snapshot_muplugins_phase() { $this->capture_wp_filter_snapshot('muplugins_loaded'); }
    public function snapshot_plugins_phase() { $this->capture_wp_filter_snapshot('plugins_loaded'); }
    public function snapshot_theme_setup_phase() { $this->capture_wp_filter_snapshot('setup_theme'); }
    public function snapshot_after_theme_setup_phase() { $this->capture_wp_filter_snapshot('after_setup_theme'); }
    public function snapshot_init_phase() { $this->capture_wp_filter_snapshot('init'); }
    public function snapshot_wp_loaded_phase() { $this->capture_wp_filter_snapshot('wp_loaded'); }
    public function snapshot_parse_request_phase() { $this->capture_wp_filter_snapshot('parse_request'); }
    public function snapshot_send_headers_phase() { $this->capture_wp_filter_snapshot('send_headers'); }
    public function snapshot_wp_phase() { $this->capture_wp_filter_snapshot('wp'); }

    /**
     * Capture wp_filter snapshot at specific bootstrap phase
     */
    private function capture_wp_filter_snapshot($phase) {
        global $wp_filter;

        if (!empty($wp_filter)) {
            $this->bootstrap_snapshots[$phase] = array(
                'time' => microtime(true),
                'memory' => memory_get_usage(),
                'hook_count' => count($wp_filter),
                'hooks' => $this->serialize_wp_filter_safely($wp_filter)
            );
        }
    }

    /**
     * Safely serialize wp_filter data to avoid circular references
     */
    private function serialize_wp_filter_safely($wp_filter) {
        $safe_data = array();
        $hook_limit = 200; // Limit to prevent memory issues
        $processed = 0;

        foreach ($wp_filter as $hook_name => $hook_obj) {
            if ($processed >= $hook_limit) break;

            if (is_object($hook_obj) && property_exists($hook_obj, 'callbacks')) {
                $safe_data[$hook_name] = array(
                    'priority_count' => count($hook_obj->callbacks),
                    'callback_count' => 0
                );

                foreach ($hook_obj->callbacks as $priority => $callbacks) {
                    $safe_data[$hook_name]['callback_count'] += count($callbacks);
                }
            }

            $processed++;
        }

        return $safe_data;
    }

    // Hook capture methods for specific phases
    public function capture_wp_loaded_hooks() {
        $this->hook_collectors['wp_loaded'] = $this->get_current_hook_state();
    }

    public function capture_wp_head_hooks() {
        $this->hook_collectors['wp_head'] = $this->get_current_hook_state();
    }

    public function capture_wp_footer_hooks() {
        $this->hook_collectors['wp_footer'] = $this->get_current_hook_state();
    }

    /**
     * Get current hook registration state
     */
    private function get_current_hook_state() {
        global $wp_filter;

        return array(
            'timestamp' => microtime(true),
            'total_hooks' => count($wp_filter),
            'memory_usage' => memory_get_usage(),
            'sample_hooks' => array_slice(array_keys($wp_filter), 0, 50, true)
        );
    }

    public function start_performance_tracking() {
        $this->metrics['start_time'] = microtime(true);
        $this->metrics['start_memory'] = memory_get_usage();


        add_action('shutdown', array($this, 'capture_final_metrics'), 0);
    }

    public function capture_final_metrics() {
        global $wpdb;

        $this->metrics['end_time'] = microtime(true);
        $this->metrics['end_memory'] = memory_get_peak_usage();
        $this->metrics['execution_time'] = $this->metrics['end_time'] - $this->metrics['start_time'];
        $this->metrics['memory_usage'] = $this->metrics['end_memory'] - $this->metrics['start_memory'];
        $this->metrics['peak_memory'] = memory_get_peak_usage();

        // Use more accurate query count calculation
        if (defined('SAVEQUERIES') && constant('SAVEQUERIES') && !empty($wpdb->queries)) {
            $this->metrics['query_count'] = count($wpdb->queries);
            // Calculate total query time for more accurate performance measurement
            $total_query_time = 0;
            foreach ($wpdb->queries as $query) {
                $total_query_time += $query[1];
            }
            $this->metrics['query_time'] = $total_query_time;
        } else {
            $this->metrics['query_count'] = $wpdb->num_queries;
            $this->metrics['query_time'] = 0;
        }

        // Store metrics in transient for display
        set_transient('wpdmgr_metrics_' . get_current_user_id(), $this->metrics, 30);
    }

    /**
     * Get current metrics
     */
    public function get_metrics() {
        $cached_metrics = get_transient('wpdmgr_metrics_' . get_current_user_id());
        if ($cached_metrics) {
            return $cached_metrics;
        }
        return $this->metrics;
    }

    /**
     * Get accurate query count using consistent logic
     */
    private function get_accurate_query_count($metrics) {
        global $wpdb;

        // Prioritize SAVEQUERIES data for accuracy
        if (defined('SAVEQUERIES') && constant('SAVEQUERIES') && !empty($wpdb->queries)) {
            return count($wpdb->queries);
        }

        // Fallback to stored metrics
        return isset($metrics['query_count']) ? $metrics['query_count'] : 0;
    }

    /**
     * Add performance metrics to admin bar
     */
    public function add_admin_bar_metrics($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }

        $metrics = $this->get_metrics();
        if (empty($metrics)) {
            return;
        }

        // Use consistent query count calculation
        $query_count = $this->get_accurate_query_count($metrics);
        $execution_time = isset($metrics['execution_time']) ? $metrics['execution_time'] : 0;
        $peak_memory = isset($metrics['peak_memory']) ? $metrics['peak_memory'] : 0;
        $query_time = isset($metrics['query_time']) ? $metrics['query_time'] : 0;

        $time_formatted = number_format($execution_time, 3) . 's';
        $memory_formatted = $this->wpdmgr_format_bytes($peak_memory);
        $db_time_formatted = number_format($query_time * 1000, 1) . 'ms';

        // Format like Query Monitor: time, memory, database time, queries
        $label_content = sprintf(
            '%s&nbsp;&nbsp;%s&nbsp;&nbsp;%s&nbsp;&nbsp;%s<small>Q</small>',
            esc_html($time_formatted),
            esc_html($memory_formatted),
            esc_html($db_time_formatted),
            esc_html($query_count)
        );

        // Add single admin bar item with all metrics
        $wp_admin_bar->add_node(array(
            'id'    => 'wpdmgr-performance-monitor',
            'title' => '<span class="ab-icon">MT</span><span class="ab-label">' . $label_content . '</span>',
            'href'  => '#',
            'meta'  => array(
                'class' => 'menupop wpdmgr-admin-perf-toggle',
                'onclick' => 'return false;'
            )
        ));

        // Render details panel immediately when admin bar is rendered
        add_action('wp_footer', array($this, 'render_details_panel'), 9999);
        add_action('admin_footer', array($this, 'render_details_panel'), 9999);
    }

    /**
     * Render only the details panel for admin bar integration
     */
    public function render_details_panel() {
        static $rendered = false;

        // Prevent multiple renders
        if ($rendered) {
            return;
        }
        $rendered = true;

        $metrics = $this->get_metrics();
        if (empty($metrics)) {
            return;
        }

        $this->render_performance_bar($metrics);
    }

    /**
     * Render performance details panel for admin bar integration
     */
    private function render_performance_bar($metrics) {
        // Use consistent calculation methods
        $query_count = $this->get_accurate_query_count($metrics);
        $execution_time = isset($metrics['execution_time']) ? $metrics['execution_time'] : 0;
        $peak_memory = isset($metrics['peak_memory']) ? $metrics['peak_memory'] : 0;
        $query_time = isset($metrics['query_time']) ? $metrics['query_time'] : 0;

        $time_formatted = number_format($execution_time, 3) . 's';
        $memory_formatted = $this->wpdmgr_format_bytes($peak_memory);
        $db_time_formatted = number_format($query_time * 1000, 1) . 'ms';

        // Get counts for tab titles - use same logic as admin bar
        global $wpdb, $wp_scripts, $wp_styles;
        $tab_query_count = $query_count; // Use consistent query count
        $scripts_count = !empty($wp_scripts->done) ? count($wp_scripts->done) : 0;
        $styles_count = !empty($wp_styles->done) ? count($wp_styles->done) : 0;
        ?>
        <div id="wpdmgr-perf-details" class="wpdmgr-perf-details" style="display: none;">
            <div class="wpdmgr-perf-details-content">
                <div class="wpdmgr-perf-sidebar">
                    <ul class="wpdmgr-perf-tabs">
                        <li class="wpdmgr-perf-tab active" data-tab="overview">
                            <span class="dashicons dashicons-dashboard"></span>
                            <?php
                            if (function_exists('_e')) {
                                esc_html_e('Overview', 'wp-debug-manager');
                            } else {
                                echo 'Overview';
                            }
                            ?>
                        </li>
                        <li class="wpdmgr-perf-tab" data-tab="queries">
                            <span class="dashicons dashicons-database"></span>
                            <?php
                            if (function_exists('printf') && function_exists('__')) {
                                /* translators: %d: number of queries */
                        printf(__('Queries (%d)', 'wp-debug-manager'), $tab_query_count);
                            } else {
                                echo 'Queries';
                            }
                            ?>
                        </li>
                        <li class="wpdmgr-perf-tab" data-tab="scripts">
                            <span class="dashicons dashicons-media-code"></span>
                            <?php
                            if (function_exists('printf') && function_exists('__')) {
                                /* translators: %d: number of scripts */
                        printf(__('Scripts (%d)', 'wp-debug-manager'), $scripts_count);
                            } else {
                                echo 'Scripts';
                            }
                            ?>
                        </li>
                        <li class="wpdmgr-perf-tab" data-tab="styles">
                            <span class="dashicons dashicons-admin-appearance"></span>
                            <?php
                            if (function_exists('printf') && function_exists('__')) {
                                /* translators: %d: number of styles */
                        printf(__('Styles (%d)', 'wp-debug-manager'), $styles_count);
                            } else {
                                echo 'Styles';
                            }
                            ?>
                        </li>
                        <li class="wpdmgr-perf-tab" data-tab="images">
                            <span class="dashicons dashicons-format-image"></span>
                            <?php
                            if (function_exists('_e')) {
                                esc_html_e('Images', 'wp-debug-manager');
                            } else {
                                echo 'Images';
                            }
                            ?>
                        </li>
                        <li class="wpdmgr-perf-tab" data-tab="hooks">
                            <span class="dashicons dashicons-admin-tools"></span>
                            <?php
                            if (function_exists('_e')) {
                                esc_html_e('Hooks & Actions', 'wp-debug-manager');
                            } else {
                                echo 'Hooks & Actions';
                            }
                            ?>
                        </li>
                        <li class="wpdmgr-perf-tab <?php echo get_option('wpdmgr_perf_realtime_enabled') ? '' : 'hide'; ?>" data-tab="realtime-hooks">
                            <span class="dashicons dashicons-clock"></span>
                            <?php
                            $realtime_count = count($this->real_time_hooks);
                            if (function_exists('printf') && function_exists('__')) {
                                /* translators: %d: number of hooks */
                        printf(__('Real-time Hooks (%d)', 'wp-debug-manager'), $realtime_count);
                            } else {
                                echo 'Real-time Hooks (' . $realtime_count . ')';
                            }
                            ?>
                        </li>
                        <li class="wpdmgr-perf-tab <?php echo get_option('wpdmgr_perf_bootstrap_enabled') ? '' : 'hide'; ?>" data-tab="bootstrap">
                            <span class="dashicons dashicons-update"></span>
                            <?php
                            $bootstrap_count = count($this->bootstrap_snapshots);
                            if (function_exists('printf') && function_exists('__')) {
                                /* translators: %d: number of phases */
                        printf(__('Bootstrap Phases (%d)', 'wp-debug-manager'), $bootstrap_count);
                            } else {
                                echo 'Bootstrap Phases (' . $bootstrap_count . ')';
                            }
                            ?>
                        </li>
                        <li class="wpdmgr-perf-tab <?php echo get_option('wpdmgr_perf_domains_enabled') ? '' : 'hide'; ?>" data-tab="domains">
                            <span class="dashicons dashicons-networking"></span>
                            <?php
                            if (function_exists('_e')) {
                                esc_html_e('Domain Panels', 'wp-debug-manager');
                            } else {
                                echo 'Domain Panels';
                            }
                            ?>
                        </li>
                        <li class="wpdmgr-perf-tab" data-tab="env">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <?php
                            if (function_exists('_e')) {
                                esc_html_e('ENV', 'wp-debug-manager');
                            } else {
                                echo 'ENV';
                            }
                            ?>
                        </li>
                    </ul>
                </div>
                <div class="wpdmgr-perf-content">
                    <!-- Overview Tab -->
                    <div id="wpdmgr-perf-tab-overview" class="wpdmgr-perf-tab-content active">
                        <h4><?php
                        if (function_exists('_e')) {
                            esc_html_e('Performance Details', 'wp-debug-manager');
                        } else {
                            echo 'Performance Details';
                        }
                        ?></h4>
                        <table class="wpdmgr-perf-table">
                            <tr>
                                <td><?php
                                if (function_exists('_e')) {
                                    esc_html_e('Database Queries:', 'wp-debug-manager');
                                } else {
                                    echo 'Database Queries:';
                                }
                                ?></td>
                                <td><?php echo function_exists('esc_html') ? esc_html($tab_query_count) : htmlspecialchars($tab_query_count); ?></td>
                            </tr>
                            <tr>
                                <td><?php
                                if (function_exists('_e')) {
                                    esc_html_e('Execution Time:', 'wp-debug-manager');
                                } else {
                                    echo 'Execution Time:';
                                }
                                ?></td>
                                <td><?php echo function_exists('esc_html') ? esc_html($time_formatted) : htmlspecialchars($time_formatted); ?></td>
                            </tr>
                            <tr>
                                <td><?php
                                if (function_exists('_e')) {
                                    esc_html_e('Database Time:', 'wp-debug-manager');
                                } else {
                                    echo 'Database Time:';
                                }
                                ?></td>
                                <td><?php echo function_exists('esc_html') ? esc_html($db_time_formatted) : htmlspecialchars($db_time_formatted); ?></td>
                            </tr>
                            <tr>
                                <td><?php
                                if (function_exists('_e')) {
                                    esc_html_e('Peak Memory:', 'wp-debug-manager');
                                } else {
                                    echo 'Peak Memory:';
                                }
                                ?></td>
                                <td><?php echo function_exists('esc_html') ? esc_html($memory_formatted) : htmlspecialchars($memory_formatted); ?></td>
                            </tr>
                            <tr>
                                <td><?php
                                if (function_exists('_e')) {
                                    esc_html_e('Memory Used:', 'wp-debug-manager');
                                } else {
                                    echo 'Memory Used:';
                                }
                                ?></td>
                                <td><?php echo function_exists('esc_html') ? esc_html(function_exists('wpdmgr_format_bytes') ? wpdmgr_format_bytes($metrics['memory_usage'] ?? 0) : $this->wpdmgr_format_bytes($metrics['memory_usage'] ?? 0)) : htmlspecialchars($this->wpdmgr_format_bytes($metrics['memory_usage'] ?? 0)); ?></td>
                            </tr>
                            <tr>
                                <td><?php
                                if (function_exists('_e')) {
                                    esc_html_e('PHP Version:', 'wp-debug-manager');
                                } else {
                                    echo 'PHP Version:';
                                }
                                ?></td>
                                <td><?php echo function_exists('esc_html') ? esc_html(PHP_VERSION) : htmlspecialchars(PHP_VERSION); ?></td>
                            </tr>
                            <tr>
                                <td><?php
                                if (function_exists('_e')) {
                                    esc_html_e('WordPress Version:', 'wp-debug-manager');
                                } else {
                                    echo 'WordPress Version:';
                                }
                                ?></td>
                                <td><?php echo function_exists('esc_html') && function_exists('get_bloginfo') ? esc_html(get_bloginfo('version')) : 'N/A'; ?></td>
                            </tr>
                        </table>
                    </div>
                    <!-- Queries Tab -->
                    <div id="wpdmgr-perf-tab-queries" class="wpdmgr-perf-tab-content">
                        <h4><?php
                        if (function_exists('printf') && function_exists('__')) {
                             /* translators: %d: number of database queries */
                        printf(__('Database Queries (%d)', 'wp-debug-manager'), $tab_query_count);
                         } else {
                             echo 'Database Queries (' . $tab_query_count . ')';
                         }
                        ?></h4>
                        <div class="wpdmgr-queries-container">
                            <?php $this->render_queries_tab(); ?>
                        </div>
                    </div>
                    <!-- Scripts Tab -->
                    <div id="wpdmgr-perf-tab-scripts" class="wpdmgr-perf-tab-content">
                        <h4><?php
                        if (function_exists('_e')) {
                            esc_html_e('Loaded Scripts', 'wp-debug-manager');
                        } else {
                            echo 'Loaded Scripts';
                        }
                        ?></h4>
                        <div class="wpdmgr-scripts-container">
                            <?php $this->render_scripts_tab(); ?>
                        </div>
                    </div>
                    <!-- Styles Tab -->
                    <div id="wpdmgr-perf-tab-styles" class="wpdmgr-perf-tab-content">
                        <h4><?php
                        if (function_exists('_e')) {
                            esc_html_e('Loaded Styles', 'wp-debug-manager');
                        } else {
                            echo 'Loaded Styles';
                        }
                        ?></h4>
                        <div class="wpdmgr-styles-container">
                            <?php $this->render_styles_tab(); ?>
                        </div>
                    </div>
                    <!-- Images Tab -->
                    <div id="wpdmgr-perf-tab-images" class="wpdmgr-perf-tab-content">
                        <h4><?php
                        if (function_exists('_e')) {
                            esc_html_e('Loaded Images', 'wp-debug-manager');
                        } else {
                            echo 'Loaded Images';
                        }
                        ?></h4>
                        <div class="wpdmgr-images-container">
                            <?php $this->render_images_tab(); ?>
                        </div>
                    </div>
                    <!-- Hooks & Actions Tab -->
                    <div id="wpdmgr-perf-tab-hooks" class="wpdmgr-perf-tab-content">
                        <h4><?php
                        if (function_exists('_e')) {
                            esc_html_e('WordPress Hooks & Actions', 'wp-debug-manager');
                        } else {
                            echo 'WordPress Hooks & Actions';
                        }
                        ?></h4>
                        <div class="wpdmgr-hooks-container">
                            <?php $this->render_hooks_tab(); ?>
                        </div>
                    </div>
                    <!-- Real-time Hooks Tab -->
                    <div id="wpdmgr-perf-tab-realtime-hooks" class="wpdmgr-perf-tab-content <?php echo get_option('wpdmgr_perf_realtime_enabled') ? '' : 'hide'; ?>">
                        <h4><?php
                        $realtime_count = count($this->real_time_hooks);
                        if (function_exists('printf') && function_exists('__')) {
                            /* translators: %d: number of hooks captured */
                        printf(__('Real-time Hook Execution (%d hooks captured)', 'wp-debug-manager'), $realtime_count);
                        } else {
                            echo 'Real-time Hook Execution (' . $realtime_count . ' hooks captured)';
                        }
                        ?></h4>
                        <div class="wpdmgr-realtime-hooks-container">
                            <?php $this->render_realtime_hooks_tab(); ?>
                        </div>
                    </div>
                    <!-- Bootstrap Phases Tab -->
                    <div id="wpdmgr-perf-tab-bootstrap" class="wpdmgr-perf-tab-content <?php echo get_option('wpdmgr_perf_bootstrap_enabled') ? '' : 'hide'; ?>">
                        <h4><?php
                        $bootstrap_count = count($this->bootstrap_snapshots);
                        if (function_exists('printf') && function_exists('__')) {
                            /* translators: %d: number of phases */
                        printf(__('Bootstrap Hook Snapshots (%d phases)', 'wp-debug-manager'), $bootstrap_count);
                        } else {
                            echo 'Bootstrap Hook Snapshots (' . $bootstrap_count . ' phases)';
                        }
                        ?></h4>
                        <div class="wpdmgr-bootstrap-container">
                            <?php $this->render_bootstrap_tab(); ?>
                        </div>
                    </div>
                    <!-- Domain Panels Tab -->
                    <div id="wpdmgr-perf-tab-domains" class="wpdmgr-perf-tab-content <?php echo get_option('wpdmgr_perf_domains_enabled') ? '' : 'hide'; ?>">
                        <h4><?php
                        if (function_exists('_e')) {
                            esc_html_e('Domain-Specific Hook Analysis', 'wp-debug-manager');
                        } else {
                            echo 'Domain-Specific Hook Analysis';
                        }
                        ?></h4>
                        <div class="wpdmgr-domains-container">
                            <?php $this->render_domains_tab(); ?>
                        </div>
                    </div>
                    <!-- ENV Tab -->
                    <div id="wpdmgr-perf-tab-env" class="wpdmgr-perf-tab-content">
                        <h4><?php
                        if (function_exists('_e')) {
                            esc_html_e('Environment Configuration', 'wp-debug-manager');
                        } else {
                            echo 'Environment Configuration';
                        }
                        ?></h4>
                        <div class="wpdmgr-env-container">
                            <?php $this->render_env_tab(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
	}

	/**
	 * Get secure site URL information to avoid direct $_SERVER usage
	 *
	 * @return array
	 */
	private function get_secure_site_info() {
		if (function_exists('home_url')) {
			$home_url = home_url();
			$parsed_url = parse_url($home_url);
			return array(
				'url' => $home_url,
				'host' => $parsed_url['host'] ?? 'localhost',
				'scheme' => $parsed_url['scheme'] ?? 'http'
			);
		}

		return array(
			'url' => 'http://localhost',
			'host' => 'localhost',
			'scheme' => 'http'
		);
	}

	/**
	 * Build full URL and metadata for asset source
	 * Centralizes URL handling to avoid $_SERVER usage
	 *
	 * @param string $src Asset source URL or path
	 * @return array
	 */
	private function get_asset_url_info($src) {
		$file_size = 'N/A';
		$load_time = 'N/A';
		$component_type = 'Unknown';

		// Determine if external or local
		if (strpos($src, 'http') === 0) {
			$parsed_url = parse_url($src);
			$hostname = $parsed_url['host'];

			// Check for external sources like Google Fonts
			if (strpos($hostname, 'fonts.googleapis.com') !== false ||
				strpos($hostname, 'fonts.gstatic.com') !== false) {
				$component_type = 'WordPress Core Component (Herald Fonts)';
			}

			$clickable_url = '<a class="wpdmgr-link" href="' . (function_exists('esc_url') ? esc_url($src) : htmlspecialchars($src)) . '" target="_blank">' . (function_exists('esc_html') ? esc_html($src) : htmlspecialchars($src)) . '</a>';

			// Try to get file size for external files
			$file_size = $this->get_remote_file_size($src);
			$load_time = $this->get_estimated_load_time($src);
		} else {
			// Handle relative URLs - use secure site info
			$site_info = $this->get_secure_site_info();
			$hostname = $site_info['host'];
			$full_url = $site_info['url'] . ltrim($src, '/');
			$clickable_url = '<a class="wpdmgr-link" href="' . (function_exists('esc_url') ? esc_url($full_url) : htmlspecialchars($full_url)) . '" target="_blank">' . (function_exists('esc_html') ? esc_html($src) : htmlspecialchars($src)) . '</a>';

			// Try to get local file size
			$local_path = ABSPATH . ltrim($src, '/');
			if (file_exists($local_path)) {
				$file_size = function_exists('wpdmgr_format_bytes') ? wpdmgr_format_bytes(filesize($local_path)) : $this->wpdmgr_format_bytes(filesize($local_path));
				$load_time = $this->get_estimated_load_time($src, filesize($local_path));
			}
		}

		// Determine component type from path
		if (strpos($src, '/plugins/') !== false) {
			$component_type = 'Plugin';
			preg_match('/\/plugins\/([^\/]+)/', $src, $matches);
			if (isset($matches[1])) {
				$component_type = 'Plugin: ' . ucwords(str_replace('-', ' ', $matches[1]));
			}
		} elseif (strpos($src, '/themes/') !== false) {
			$component_type = 'Theme';
			preg_match('/\/themes\/([^\/]+)/', $src, $matches);
			if (isset($matches[1])) {
				$component_type = 'Theme: ' . ucwords(str_replace('-', ' ', $matches[1]));
			}
		} elseif (strpos($src, '/wp-includes/') !== false || strpos($src, '/wp-admin/') !== false) {
			$component_type = 'WordPress Core';
		}

		return array(
			'hostname' => $hostname,
			'clickable_url' => $clickable_url,
			'file_size' => $file_size,
			'load_time' => $load_time,
			'component_type' => $component_type
		);
	}

	/**
	 * Get performance status class based on metrics
     */
    private function get_performance_status_class($execution_time, $query_count, $memory) {
        // Define thresholds
        $time_warning = 2.0; // seconds
        $time_poor = 5.0;
        $query_warning = 50;
        $query_poor = 100;
        $memory_warning = 64 * 1024 * 1024; // 64MB
        $memory_poor = 128 * 1024 * 1024; // 128MB

        $issues = 0;

        if ($execution_time > $time_poor || $query_count > $query_poor || $memory > $memory_poor) {
            return 'poor';
        }

        if ($execution_time > $time_warning || $query_count > $query_warning || $memory > $memory_warning) {
            return 'warning';
        }

        return 'good';
    }

    /**
     * Get detailed query information
     */
    public function get_query_details() {
        global $wpdb;

        if (!defined('SAVEQUERIES') || !constant('SAVEQUERIES')) {
            return array();
        }

        $queries = array();

        foreach ($wpdb->queries as $query) {
            $queries[] = array(
                'sql' => $query[0],
                'time' => $query[1],
                'stack' => $query[2] ?? ''
            );
        }

        return $queries;
    }

    /**
     * Render queries tab content
     */
    private function render_queries_tab() {
        global $wpdb;

        if (!defined('SAVEQUERIES') || !constant('SAVEQUERIES')) {
            echo '<p>' . (function_exists('__') ? __('Query logging is not enabled. Add define(\'SAVEQUERIES\', true); to wp-config.php to enable.', 'wp-debug-manager') : 'Query logging is not enabled. Add define(\'SAVEQUERIES\', true); to wp-config.php to enable.') . '</p>';
            return;
        }

        if (empty($wpdb->queries)) {
            echo '<p>' . (function_exists('__') ? __('No queries recorded for this page.', 'wp-debug-manager') : 'No queries recorded for this page.') . '</p>';
            return;
        }

        // Filters: Type, Min Time, Search
        echo '<div class="wpdmgr-tab-filters">';
        echo '<label>Type: <select id="wpdmgr-queries-type-filter">'
            . '<option value="">' . (function_exists('__') ? __('All', 'wp-debug-manager') : 'All') . '</option>'
            . '<option value="SELECT">SELECT</option>'
            . '<option value="INSERT">INSERT</option>'
            . '<option value="UPDATE">UPDATE</option>'
            . '<option value="DELETE">DELETE</option>'
            . '<option value="SHOW">SHOW</option>'
            . '<option value="DESCRIBE">DESCRIBE</option>'
            . '<option value="OTHER">OTHER</option>'
            . '</select></label>';
        echo '<label>Min Time (ms): <input type="number" id="wpdmgr-queries-min-time" min="0" step="1" placeholder="0"></label>';
        echo '<label>Search: <input type="search" id="wpdmgr-queries-search" placeholder="' . (function_exists('__') ? __('Search SQL...', 'wp-debug-manager') : 'Search SQL...') . '"></label>';
        echo '</div>';

        echo '<table class="query-log-table wpdmgr-queries-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>No</th>';
        echo '<th class="sortable" data-column="time">Time</th>';
        echo '<th class="sortable" data-column="query">Query</th>';
        echo '<th class="sortable" data-column="caller">Caller Stack</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($wpdb->queries as $index => $query) {
            $sql = $query[0];
            $time = $query[1];
            $stack = $query[2] ?? '';

            $query_type = $this->get_query_type($sql);
            $time_class = $time > 0.05 ? 'slow-query' : '';
            $time_formatted = number_format($time * 1000, 2) . 'ms';
            $time_ms_attr = (int) round($time * 1000);

            echo '<tr class="' . (function_exists('esc_attr') ? esc_attr($time_class) : htmlspecialchars($time_class)) .
                 '" data-query-type="' . (function_exists('esc_attr') ? esc_attr($query_type) : htmlspecialchars($query_type)) .
                 '" data-time-ms="' . $time_ms_attr .
                 '" data-sql="' . (function_exists('esc_attr') ? esc_attr($sql) : htmlspecialchars($sql)) . '">';
            echo '<td class="query-number">' . ($index + 1) . '</td>';
            echo '<td class="query-time">';
            if ($time > 0.05) {
                echo '<span class="slow-indicator" title="Slow Query"><span class="dashicons dashicons-warning"></span></span> ';
            }
            echo (function_exists('esc_html') ? esc_html($time_formatted) : htmlspecialchars($time_formatted)) . '</td>';
            echo '<td class="query-sql">';
            echo '<div class="sql-container">';
            echo '<code>' . (function_exists('esc_html') ? esc_html($sql) : htmlspecialchars($sql)) . '</code>';
            echo '<button type="button" class="button-link copy-sql" title="Copy SQL">';
            echo '<span class="dashicons dashicons-admin-page"></span>';
            echo '</button>';
            echo '</div>';
            echo '</td>';
            $formatted_stack = $this->format_enhanced_caller_stack($stack);
            echo '<td class="query-caller">' . $formatted_stack . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Get remote file size with safe measurement
     */
	/**
	 * Get remote file size with safe measurement using WP HTTP API
	 *
	 * @since 1.2.18
	 * @param string $url The URL to check
	 * @return string Formatted file size
	 */
	private function get_remote_file_size($url) {
		// Use cached result if available
		$cache_key = 'wpdmgr_file_size_' . md5($url);
		$cached_size = get_transient($cache_key);
		if ($cached_size !== false) {
			return $cached_size;
		}

		// Try to get actual file size using WP HTTP API
		$response = wp_remote_head($url, array(
			'timeout' => 3,
			'redirection' => 3,
			'user-agent' => 'WP Debug Manager Performance Monitor/1.0',
			'sslverify' => false,
		));

		if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
			$headers = wp_remote_retrieve_headers($response);
			if (isset($headers['content-length'])) {
				$size_bytes = intval($headers['content-length']);
				$formatted_size = $this->wpdmgr_format_bytes($size_bytes);
				// Cache for 1 hour
				set_transient($cache_key, $formatted_size, 3600);
				return $formatted_size;
			}
		}

		// Fallback to domain-based estimates
		$domain = parse_url($url, PHP_URL_HOST);
		$fallback_size = __('Unknown', 'wp-debug-manager');

		if (strpos($domain, 'fonts.googleapis.com') !== false) {
			$fallback_size = '~3KB';
		} elseif (strpos($domain, 'fonts.gstatic.com') !== false) {
			$fallback_size = '~25KB';
		} else {
			$fallback_size = '~50KB';
		}

		// Cache fallback for 30 minutes
		set_transient($cache_key, $fallback_size, 1800);
		return $fallback_size;
	}

	/**
	 * Get estimated load time with real measurement using WP HTTP API
	 *
	 * @since 1.2.18
	 * @param string $url The URL to check
	 * @param int|null $file_size Local file size for estimation
	 * @return string Formatted load time with color
	 */
	private function get_estimated_load_time($url, $file_size = null) {
		// Use cached result if available
		$cache_key = 'wpdmgr_load_time_' . md5($url);
		$cached_time = get_transient($cache_key);
		if ($cached_time !== false) {
			return $cached_time;
		}

		// For local files, estimate based on file size
		if ($file_size && !filter_var($url, FILTER_VALIDATE_URL)) {
			if ($file_size < 10000) return $this->format_load_time_with_color(5, 'good');
			if ($file_size < 50000) return $this->format_load_time_with_color(25, 'good');
			if ($file_size < 100000) return $this->format_load_time_with_color(75, 'warning');
			if ($file_size < 500000) return $this->format_load_time_with_color(150, 'warning');
			return $this->format_load_time_with_color(300, 'danger');
		}

		// For external files, try real measurement using WP HTTP API
		if (strpos($url, 'http') === 0) {
			$start_time = microtime(true);

			$response = wp_remote_head($url, array(
				'timeout' => 5,
				'redirection' => 3,
				'user-agent' => 'WP Debug Manager Performance Monitor/1.0',
				'sslverify' => false,
			));

			$end_time = microtime(true);
			$load_time_ms = round(($end_time - $start_time) * 1000);

			if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
				$formatted_time = $this->format_load_time_with_color($load_time_ms, $this->get_load_time_status($load_time_ms));
				// Cache for 30 minutes
				set_transient($cache_key, $formatted_time, 1800);
				return $formatted_time;
			}
		}

		// Fallback estimates
		if (strpos($url, 'http') === 0) {
			return $this->format_load_time_with_color(120, 'warning'); // Default external estimate
		}

		return __('N/A', 'wp-debug-manager');
	}

    /**
     * Get load time status based on milliseconds
     */
    private function get_load_time_status($ms) {
        if ($ms <= 100) return 'good';
        if ($ms <= 300) return 'warning';
        return 'danger';
    }

    /**
     * Format load time with color coding
     */
    private function format_load_time_with_color($ms, $status) {
        $class = 'load-time-' . $status;
        return '<span class="' . $class . '">' . $ms . 'ms</span>';
    }

    /**
     * Get file size status and format with color
     */
    private function format_file_size_with_color($size_str) {
        if (empty($size_str) || $size_str === 'N/A' || $size_str === 'Unknown') {
            return $size_str;
        }

        // Extract numeric value for comparison
        $size_bytes = 0;
        if (preg_match('/(\d+(?:\.\d+)?)\s*(KB|MB|GB|B)/i', $size_str, $matches)) {
            $value = floatval($matches[1]);
            $unit = strtoupper($matches[2]);

            switch ($unit) {
                case 'GB':
                    $size_bytes = $value * 1024 * 1024 * 1024;
                    break;
                case 'MB':
                    $size_bytes = $value * 1024 * 1024;
                    break;
                case 'KB':
                    $size_bytes = $value * 1024;
                    break;
                default:
                    $size_bytes = $value;
            }
        }

        $status = 'good';
        if ($size_bytes > 500000) { // > 500KB
            $status = 'danger';
        } elseif ($size_bytes > 100000) { // > 100KB
            $status = 'warning';
        }

        $class = 'file-size-' . $status;
        return '<span class="' . $class . '">' . $size_str . '</span>';
    }

    /**
     * Format bytes to human readable format
     */
    private function wpdmgr_format_bytes($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . 'GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . 'MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . 'KB';
        } else {
            return $bytes . 'B';
        }
    }

    // Helper: parse formatted size string (e.g. "12.3KB", "~45 MB", "Unknown") into integer bytes
    private function parse_formatted_size_to_bytes($size_str) {
        if (empty($size_str)) return 0;
        $clean = trim(str_replace('~', '', strip_tags($size_str)));
        if ($clean === 'N/A' || stripos($clean, 'Unknown') !== false) return 0;
        if (preg_match('/(\d+(?:\.\d+)?)\s*(B|KB|MB|GB)/i', $clean, $m)) {
            $val = (float) $m[1];
            $unit = strtoupper($m[2]);
            switch ($unit) {
                case 'GB': return (int) round($val * 1024 * 1024 * 1024);
                case 'MB': return (int) round($val * 1024 * 1024);
                case 'KB': return (int) round($val * 1024);
                default: return (int) round($val);
            }
        }
        // Fallback: plain number
        if (is_numeric($clean)) return (int) $clean;
        return 0;
    }

    // Helper: parse formatted load time (e.g. "123ms", colored spans) into integer milliseconds
    private function parse_formatted_load_time_to_ms($html) {
        if (empty($html)) return 0;
        $text = trim(strip_tags($html));
        if (preg_match('/(\d+(?:\.\d+)?)/', $text, $m)) {
            return (int) round((float) $m[1]);
        }
        return 0;
    }

    /**
     * Render scripts tab content
     */
    private function render_scripts_tab() {
        global $wp_scripts;

        if (!$wp_scripts || empty($wp_scripts->done)) {
            echo '<p>' . __('No scripts loaded on this page.', 'wp-debug-manager') . '</p>';
            return;
        }

        echo '<div class="wpdmgr-tab-filters">';
        echo '<label>Position: <select id="wpdmgr-scripts-position-filter"><option value="">All Positions</option><option value="header">Header</option><option value="footer">Footer</option></select></label>';
        echo '<label>Hostname: <select id="wpdmgr-scripts-hostname-filter"><option value="">All Hostnames</option></select></label>';
        echo '<label>Component: <select id="wpdmgr-scripts-component-filter"><option value="">All Components</option></select></label>';
        echo '<label>Search: <input type="search" id="wpdmgr-scripts-search" placeholder="' . ( function_exists('esc_attr__') ? esc_attr__( 'Search handle/source…', 'wp-debug-manager') : 'Search handle/source…' ) . '"></label>';
        echo '</div>';

        echo '<table class="query-log-table wpdmgr-scripts-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>No</th>';
        echo '<th class="sortable" data-column="position">Position</th>';
        echo '<th class="sortable" data-column="handle">Handle</th>';
        echo '<th class="sortable" data-column="hostname">Hostname</th>';
        echo '<th class="sortable" data-column="source">Source</th>';
        echo '<th class="sortable" data-column="component">Komponen</th>';
        echo '<th class="sortable" data-column="version">Version</th>';
        echo '<th class="sortable" data-column="filesize">File Size</th>';
        echo '<th class="sortable" data-column="loadtime">Load Time</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        $counter = 1;
        foreach ($wp_scripts->done as $handle) {
            if (!isset($wp_scripts->registered[$handle])) continue;

            $script = $wp_scripts->registered[$handle];
            $src = $script->src;
            $version = $script->ver ? $script->ver : 'N/A';

            // Determine position
            $position = 'footer';
            if (isset($wp_scripts->groups[$handle]) && $wp_scripts->groups[$handle] === 0) {
                $position = 'header';
            }

            // Parse hostname and source info
            $hostname = 'Local';
            $source_path = $src;
            $component_type = 'WordPress Core';
            $clickable_url = $src;
            $file_size = 'N/A';
            $load_time = 'N/A';
            $file_size_bytes = 0;
            $load_time_ms = 0;
            $file_size_bytes = 0;
            $load_time_ms = 0;

            if ($src) {
                // Handle full URLs
                if (strpos($src, 'http') === 0) {
                    $parsed_url = parse_url($src);
                    if (isset($parsed_url['host'])) {
                        $hostname = $parsed_url['host'];

                        // Check for external sources like Google Fonts
                        if (strpos($hostname, 'fonts.googleapis.com') !== false ||
                            strpos($hostname, 'fonts.gstatic.com') !== false) {
                            $component_type = 'WordPress Core Component (Herald Fonts)';
                        }
                    }
                    $clickable_url = '<a class="wpdmgr-link" href="' . (function_exists('esc_url') ? esc_url($src) : htmlspecialchars($src)) . '" target="_blank">' . (function_exists('esc_html') ? esc_html($src) : htmlspecialchars($src)) . '</a>';

                    // Try to get file size for external files (simulated)
                    $file_size = $this->get_remote_file_size($src);
                    $load_time = $this->get_estimated_load_time($src);
                    // Parse numeric values for JS sorting/filtering
                    $file_size_bytes = $this->parse_formatted_size_to_bytes($file_size);
                    $load_time_ms = $this->parse_formatted_load_time_to_ms($load_time);
                    // Parse numeric values for JS sorting/filtering
                    $file_size_bytes = $this->parse_formatted_size_to_bytes($file_size);
                    $load_time_ms = $this->parse_formatted_load_time_to_ms($load_time);
                } else {
                    // Handle relative URLs - get site URL
                    $site_url = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
                    $hostname = $site_url;
                    $full_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $site_url . $src;
                    $clickable_url = '<a class="wpdmgr-link" href="' . (function_exists('esc_url') ? esc_url($full_url) : htmlspecialchars($full_url)) . '" target="_blank">' . (function_exists('esc_html') ? esc_html($src) : htmlspecialchars($src)) . '</a>';

                    // Try to get local file size
                    $local_path = ABSPATH . ltrim($src, '/');
                    if (file_exists($local_path)) {
                        $file_size = function_exists('wpdmgr_format_bytes') ? wpdmgr_format_bytes(filesize($local_path)) : $this->wpdmgr_format_bytes(filesize($local_path));
                        $load_time = $this->get_estimated_load_time($src, filesize($local_path));
                        $file_size_bytes = (int) filesize($local_path);
                        $load_time_ms = $this->parse_formatted_load_time_to_ms($load_time);
                    }
                }

                if (strpos($src, '/plugins/') !== false) {
                    $component_type = 'Plugin';
                    preg_match('/\/plugins\/([^\/]+)/', $src, $matches);
                    if (isset($matches[1])) {
                        $component_type = 'Plugin: ' . ucwords(str_replace('-', ' ', $matches[1]));
                    }
                } elseif (strpos($src, '/themes/') !== false) {
                    $component_type = 'Theme';
                    preg_match('/\/themes\/([^\/]+)/', $src, $matches);
                    if (isset($matches[1])) {
                        $component_type = 'Theme: ' . ucwords(str_replace('-', ' ', $matches[1]));
                    }
                } elseif (strpos($src, '/wp-includes/') !== false || strpos($src, '/wp-admin/') !== false) {
                    $component_type = 'WordPress Core';
                }
            }

            echo '<tr'
                . ' data-position="' . (function_exists('esc_attr') ? esc_attr($position) : htmlspecialchars($position)) . '"'
                . ' data-handle="' . (function_exists('esc_attr') ? esc_attr($handle) : htmlspecialchars($handle)) . '"'
                . ' data-hostname="' . (function_exists('esc_attr') ? esc_attr($hostname) : htmlspecialchars($hostname)) . '"'
                . ' data-component="' . (function_exists('esc_attr') ? esc_attr($component_type) : htmlspecialchars($component_type)) . '"'
                . ' data-version="' . (function_exists('esc_attr') ? esc_attr($version) : htmlspecialchars($version)) . '"'
                . ' data-filesize-bytes="' . intval($file_size_bytes ?? 0) . '"'
                . ' data-loadtime-ms="' . intval($load_time_ms ?? 0) . '"'
                . ' data-source="' . (function_exists('esc_attr') ? esc_attr($src) : htmlspecialchars($src)) . '">';
            echo '<td class="query-number">' . $counter . '</td>';
            echo '<td>' . (function_exists('esc_html') ? esc_html($position) : htmlspecialchars($position)) . '</td>';
            echo '<td class="wpdmgr-script-handle">' . (function_exists('esc_html') ? esc_html($handle) : htmlspecialchars($handle)) . '</td>';
            echo '<td>' . (function_exists('esc_html') ? esc_html($hostname) : htmlspecialchars($hostname)) . '</td>';
            echo '<td class="query-sql"><div class="sql-container"><code>' . $clickable_url . '</code></div></td>';
            echo '<td>' . (function_exists('esc_html') ? esc_html($component_type) : htmlspecialchars($component_type)) . '</td>';
            echo '<td>' . (function_exists('esc_html') ? esc_html($version) : htmlspecialchars($version)) . '</td>';
            echo '<td>' . $this->format_file_size_with_color($file_size) . '</td>';
            echo '<td>' . $load_time . '</td>';
            echo '</tr>';

            $counter++;
        }

        echo '</tbody>';
        echo '</table>';

    }

    /**
     * Render styles tab content
     */
    private function render_styles_tab() {
        global $wp_styles;

        if (!$wp_styles || empty($wp_styles->done)) {
            echo '<p>' . __('No styles loaded on this page.', 'wp-debug-manager') . '</p>';
            return;
        }

        echo '<div class="wpdmgr-tab-filters">';
        echo '<label>Position: <select id="wpdmgr-styles-position-filter"><option value="">All Positions</option><option value="header">Header</option><option value="footer">Footer</option></select></label>';
        echo '<label>Hostname: <select id="wpdmgr-styles-hostname-filter"><option value="">All Hostnames</option></select></label>';
        echo '<label>Component: <select id="wpdmgr-styles-component-filter"><option value="">All Components</option></select></label>';
        echo '<label>Search: <input type="search" id="wpdmgr-styles-search" placeholder="' . ( function_exists('esc_attr__') ? esc_attr__( 'Search handle/source…', 'wp-debug-manager') : 'Search handle/source…' ) . '"></label>';
        echo '</div>';

        echo '<table class="query-log-table wpdmgr-styles-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>No</th>';
        echo '<th class="sortable" data-column="position">Position</th>';
        echo '<th class="sortable" data-column="handle">Handle</th>';
        echo '<th class="sortable" data-column="hostname">Hostname</th>';
        echo '<th class="sortable" data-column="source">Source</th>';
        echo '<th class="sortable" data-column="component">Komponen</th>';
        echo '<th class="sortable" data-column="version">Version</th>';
        echo '<th class="sortable" data-column="filesize">File Size</th>';
        echo '<th class="sortable" data-column="loadtime">Load Time</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        $counter = 1;
        foreach ($wp_styles->done as $handle) {
            if (!isset($wp_styles->registered[$handle])) continue;

            $style = $wp_styles->registered[$handle];
            $src = $style->src;
            $version = $style->ver ? $style->ver : 'N/A';
            $media = $style->args;

            // Determine position (CSS is typically in header)
            $position = 'header';

            // Parse hostname and source info
            $hostname = 'Local';
            $source_path = $src;
            $component_type = 'WordPress Core';
            $clickable_url = $src;
            $file_size = 'N/A';
            $load_time = 'N/A';

            if ($src) {
                // Handle full URLs
                if (strpos($src, 'http') === 0) {
                    $parsed_url = parse_url($src);
                    if (isset($parsed_url['host'])) {
                        $hostname = $parsed_url['host'];

                        // Check for external sources like Google Fonts
                        if (strpos($hostname, 'fonts.googleapis.com') !== false ||
                            strpos($hostname, 'fonts.gstatic.com') !== false) {
                            $component_type = 'WordPress Core Component (Herald Fonts)';
                        }
                    }
                    $clickable_url = '<a class="wpdmgr-link" href="' . (function_exists('esc_url') ? esc_url($src) : htmlspecialchars($src)) . '" target="_blank">' . (function_exists('esc_html') ? esc_html($src) : htmlspecialchars($src)) . '</a>';

                    // Try to get file size for external files (simulated)
                    $file_size = $this->get_remote_file_size($src);
                    $load_time = $this->get_estimated_load_time($src);
                } else {
                    // Handle relative URLs - get site URL
                    $site_url = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
                    $hostname = $site_url;
                    $full_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $site_url . $src;
                    $clickable_url = '<a class="wpdmgr-link" href="' . (function_exists('esc_url') ? esc_url($full_url) : htmlspecialchars($full_url)) . '" target="_blank">' . (function_exists('esc_html') ? esc_html($src) : htmlspecialchars($src)) . '</a>';

                    // Try to get local file size
                    $local_path = ABSPATH . ltrim($src, '/');
                    if (file_exists($local_path)) {
                        $file_size = function_exists('wpdmgr_format_bytes') ? wpdmgr_format_bytes(filesize($local_path)) : $this->wpdmgr_format_bytes(filesize($local_path));
                        $load_time = $this->get_estimated_load_time($src, filesize($local_path));
                        $file_size_bytes = (int) filesize($local_path);
                        $load_time_ms = $this->parse_formatted_load_time_to_ms($load_time);
                    }
                }

                if (strpos($src, '/plugins/') !== false) {
                    $component_type = 'Plugin';
                    preg_match('/\/plugins\/([^\/]+)/', $src, $matches);
                    if (isset($matches[1])) {
                        $component_type = 'Plugin: ' . ucwords(str_replace('-', ' ', $matches[1]));
                    }
                } elseif (strpos($src, '/themes/') !== false) {
                    $component_type = 'Theme';
                    preg_match('/\/themes\/([^\/]+)/', $src, $matches);
                    if (isset($matches[1])) {
                        $component_type = 'Theme: ' . ucwords(str_replace('-', ' ', $matches[1]));
                    }
                } elseif (strpos($src, '/wp-includes/') !== false || strpos($src, '/wp-admin/') !== false) {
                    $component_type = 'WordPress Core';
                }
            }

            echo '<tr'
                . ' data-position="' . (function_exists('esc_attr') ? esc_attr($position) : htmlspecialchars($position)) . '"'
                . ' data-handle="' . (function_exists('esc_attr') ? esc_attr($handle) : htmlspecialchars($handle)) . '"'
                . ' data-hostname="' . (function_exists('esc_attr') ? esc_attr($hostname) : htmlspecialchars($hostname)) . '"'
                . ' data-component="' . (function_exists('esc_attr') ? esc_attr($component_type) : htmlspecialchars($component_type)) . '"'
                . ' data-version="' . (function_exists('esc_attr') ? esc_attr($version) : htmlspecialchars($version)) . '"'
                . ' data-filesize-bytes="' . intval($file_size_bytes ?? 0) . '"'
                . ' data-loadtime-ms="' . intval($load_time_ms ?? 0) . '"'
                . ' data-source="' . (function_exists('esc_attr') ? esc_attr($src) : htmlspecialchars($src)) . '">';
            echo '<td class="query-number">' . $counter . '</td>';
            echo '<td>' . (function_exists('esc_html') ? esc_html($position) : htmlspecialchars($position)) . '</td>';
            echo '<td class="wpdmgr-style-handle">' . (function_exists('esc_html') ? esc_html($handle) : htmlspecialchars($handle)) . '</td>';
            echo '<td>' . (function_exists('esc_html') ? esc_html($hostname) : htmlspecialchars($hostname)) . '</td>';
            echo '<td class="query-sql"><div class="sql-container"><code>' . $clickable_url . '</code></div></td>';
            echo '<td>' . (function_exists('esc_html') ? esc_html($component_type) : htmlspecialchars($component_type)) . '</td>';
            echo '<td>' . (function_exists('esc_html') ? esc_html($version) : htmlspecialchars($version)) . '</td>';
            echo '<td>' . $this->format_file_size_with_color($file_size) . '</td>';
            echo '<td>' . $load_time . '</td>';
            echo '</tr>';

            $counter++;
        }

        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Get query type from SQL
     */
    private function get_query_type($sql) {
        $sql = trim(strtoupper($sql));

        if (strpos($sql, 'SELECT') === 0) return 'SELECT';
        if (strpos($sql, 'INSERT') === 0) return 'INSERT';
        if (strpos($sql, 'UPDATE') === 0) return 'UPDATE';
        if (strpos($sql, 'DELETE') === 0) return 'DELETE';
        if (strpos($sql, 'SHOW') === 0) return 'SHOW';
        if (strpos($sql, 'DESCRIBE') === 0) return 'DESCRIBE';

        return 'OTHER';
    }

    /**
     * Format enhanced caller stack using WPDMGR_Debug functionality
     */
    private function format_enhanced_caller_stack($stack) {
        if (empty($stack)) {
            return '<span style="color: #999; font-style: italic;">No stack trace available</span>';
        }

        // Get or create WPDMGR_Debug instance to use enhanced formatting
        if (class_exists('WPDMGR_Debug')) {
            static $debug_instance = null;
            if ($debug_instance === null) {
                $debug_instance = new WPDMGR_Debug();
            }

            // Use reflection to access the private format_caller_stack method
            try {
                if (class_exists('\ReflectionClass')) {
                    /** @var \ReflectionClass $reflection */
                    $reflection = new \ReflectionClass($debug_instance);
                    $method = $reflection->getMethod('format_caller_stack');
                    $method->setAccessible(true);
                    $formatted = $method->invokeArgs($debug_instance, array($stack));

                    // Convert ANSI color codes to HTML styling for web display
                    $formatted = $this->convert_ansi_to_html($formatted);

                    // Add HTML wrapper for query monitor display
                    return '<div class="enhanced-caller-stack">' . $formatted . '</div>';
                }
                // Fallback if ReflectionClass not available
                return (function_exists('esc_html') ? esc_html($stack) : htmlspecialchars($stack));
            } catch (\Exception $e) {
                // Fallback to simple display if reflection fails
                return (function_exists('esc_html') ? esc_html($stack) : htmlspecialchars($stack));
            }
        }

        // Fallback to simple display if WPDMGR_Debug is not available
        return (function_exists('esc_html') ? esc_html($stack) : htmlspecialchars($stack));
    }

    /**
     * Convert ANSI color codes to HTML styling
     */
    private function convert_ansi_to_html($text) {
        // Handle both escaped strings (\033) and actual ANSI escape codes (\x1b[)
        $html = $text;

        // First pass: Handle literal \033 strings (from string output)
        $html = preg_replace('/\\\\033\[1;32m([^\\\\]+?)\\\\033\[0m/', '<span class="caller-entry plugin">$1</span>', $html);
        $html = preg_replace('/\\\\033\[0;37m([^\\\\]+?)\\\\033\[0m/', '<span class="caller-entry core">$1</span>', $html);
        $html = preg_replace('/\\\\033\[1;34m([^\\\\]+?)\\\\033\[0m/', '<span class="caller-entry user">$1</span>', $html);
        $html = preg_replace('/\\\\033\[0;33m([^\\\\]+?)\\\\033\[0m/', '<span class="caller-entry hook">$1</span>', $html);
        $html = preg_replace('/\\\\033\[0;90m([^\\\\]+?)\\\\033\[0m/', '<span class="caller-entry bootstrap">$1</span>', $html);
        $html = preg_replace('/\\\\033\[0;36m([^\\\\]+?)\\\\033\[0m/', '<span class="caller-entry vendor">$1</span>', $html);

        // Second pass: Handle actual ANSI escape codes (\x1b[)
        $html = preg_replace('/\x1b\[1;32m([^\x1b]+?)\x1b\[0m/', '<span class="caller-entry plugin">$1</span>', $html);
        $html = preg_replace('/\x1b\[0;37m([^\x1b]+?)\x1b\[0m/', '<span class="caller-entry core">$1</span>', $html);
        $html = preg_replace('/\x1b\[1;34m([^\x1b]+?)\x1b\[0m/', '<span class="caller-entry user">$1</span>', $html);
        $html = preg_replace('/\x1b\[0;33m([^\x1b]+?)\x1b\[0m/', '<span class="caller-entry hook">$1</span>', $html);
        $html = preg_replace('/\x1b\[0;90m([^\x1b]+?)\x1b\[0m/', '<span class="caller-entry bootstrap">$1</span>', $html);
        $html = preg_replace('/\x1b\[0;36m([^\x1b]+?)\x1b\[0m/', '<span class="caller-entry vendor">$1</span>', $html);

        // Clean up any remaining ANSI codes
        $html = preg_replace('/\\\\033\[[0-9;]*m/', '', $html);
        $html = preg_replace('/\x1b\[[0-9;]*m/', '', $html);

        // Remove any remaining decorative borders
        $html = preg_replace('/={50,}/', '', $html);
        $html = preg_replace('/^\s*CALLER STACK TRACE\s*$/m', '', $html);
        $html = trim($html);

        // Handle entry prefixes and file info
        $html = preg_replace('/^(>>>\s+)(\d+\.)/m', '<span class="entry-prefix">$1</span>$2', $html);
        $html = preg_replace('/(\[ENTRY\])/', '<span class="entry-tag">$1</span>', $html);
        $html = preg_replace('/(-> [^\n]+)/', '<span class="file-info">$1</span>', $html);

        return $html;
    }

    /**
     * Render images tab content
     */
    private function render_images_tab() {
        // Get images from the current page content
        $images = $this->get_page_images();

        if (empty($images)) {
            echo '<p>' . __('No images found on this page.', 'wp-debug-manager') . '</p>';
            return;
        }

        // Determine current site host once for scope filtering
        $site_host = parse_url(home_url(), PHP_URL_HOST);

        echo '<div class="wpdmgr-tab-filters">';
        echo '<label>Filter by Source: <select id="wpdmgr-images-source-filter"><option value="">All Sources</option></select></label>';
        echo '<label>Filter by Hostname: <select id="wpdmgr-images-hostname-filter"><option value="">All Hostnames</option></select></label>';
        echo '<label>Host Scope: <select id="wpdmgr-images-host-scope"><option value="">All</option><option value="same">Same Domain</option><option value="external">External</option></select></label>';
        echo '<label>Sort by: <select id="wpdmgr-images-sort"><option value="size">File Size</option><option value="load_time">Load Time</option><option value="source">Source</option></select></label>';
        echo '<label>Search: <input type="search" id="wpdmgr-images-search" placeholder="' . (function_exists('__') ? __('Search Alt/Source...', 'wp-debug-manager') : 'Search Alt/Source...') . '"></label>';
        echo '</div>';

        echo '<table class="query-log-table wpdmgr-images-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>No</th>';
        echo '<th class="sortable" data-column="handle">Handle</th>';
        echo '<th class="sortable" data-column="hostname">Hostname</th>';
        echo '<th class="sortable" data-column="source">Source</th>';
        echo '<th class="sortable" data-column="size">Size</th>';
        echo '<th class="sortable" data-column="load_time">Load Times</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        $counter = 1;
        foreach ($images as $image) {
            $src = $image['src'];
            $alt = $image['alt'] ?? 'N/A';
            $hostname = 'Local';
            $component_type = 'Content Image';
            $clickable_url = $src;
            $file_size = 'N/A';
            $load_time = 'N/A';
            $file_size_bytes = 0;
            $load_time_ms = 0;
            $position = $image['position'] ?? 'content';

            // Determine component type based on image source and WordPress context
            if (strpos($src, '/wp-content/uploads/') !== false) {
                $component_type = 'Media Library';
            } elseif (strpos($src, '/wp-admin/') !== false || strpos($src, '/wp-includes/') !== false) {
                $component_type = 'WordPress Core';
            } elseif (strpos($src, '/wp-content/plugins/') !== false) {
                // Extract plugin name
                if (preg_match('/\/wp-content\/plugins\/([^\/]+)/', $src, $matches)) {
                    $plugin_name = ucwords(str_replace(array('-', '_'), ' ', $matches[1]));
                    $component_type = 'Plugin: ' . $plugin_name;
                } else {
                    $component_type = 'Plugin Asset';
                }
            } elseif (strpos($src, '/wp-content/themes/') !== false) {
                // Extract theme name
                if (preg_match('/\/wp-content\/themes\/([^\/]+)/', $src, $matches)) {
                    $theme_name = ucwords(str_replace(array('-', '_'), ' ', $matches[1]));
                    $component_type = 'Theme: ' . $theme_name;
                } else {
                    $component_type = 'Theme Asset';
                }
            } elseif (strpos($alt, 'Site Logo') !== false || strpos($alt, 'Logo') !== false) {
                $component_type = 'Site Branding';
            } elseif (strpos($alt, 'Featured Image') !== false || strpos($position, 'content') !== false) {
                $component_type = 'Post Content';
            } elseif (strpos($alt, 'Header') !== false || strpos($alt, 'Background') !== false) {
                $component_type = 'Theme Customizer';
            } elseif (strpos($alt, 'CSS Background') !== false) {
                $component_type = 'CSS Asset';
            }

            if ($src) {
                if (strpos($src, 'http') === 0) {
                    $parsed_url = parse_url($src);
                    if (isset($parsed_url['host'])) {
                        $hostname = $parsed_url['host'];
                        // Check if it's actually external
                        $site_host = parse_url(home_url(), PHP_URL_HOST);
                        if ($site_host && $hostname !== $site_host) {
                            $component_type = 'External Image';
                        }
                    }
                    $clickable_url = '<a class="wpdmgr-link" href="' . (function_exists('esc_url') ? esc_url($src) : htmlspecialchars($src)) . '" target="_blank">' . (function_exists('esc_html') ? esc_html($src) : htmlspecialchars($src)) . '</a>';
                    $file_size_result = $this->get_remote_file_size($src);
                    $file_size = $file_size_result;
                    $load_time = $this->get_estimated_load_time($src);
                } else {
                    $site_url = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
                    $hostname = $site_url;
                    $full_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $site_url . $src;
                    $clickable_url = '<a class="wpdmgr-link" href="' . (function_exists('esc_url') ? esc_url($full_url) : htmlspecialchars($full_url)) . '" target="_blank">' . (function_exists('esc_html') ? esc_html($src) : htmlspecialchars($src)) . '</a>';

                    $local_path = ABSPATH . ltrim($src, '/');
                    if (file_exists($local_path)) {
                        $file_size_bytes = filesize($local_path);
                        $file_size = function_exists('wpdmgr_format_bytes') ? wpdmgr_format_bytes($file_size_bytes) : $this->wpdmgr_format_bytes($file_size_bytes);
                        $load_time = $this->get_estimated_load_time($src, $file_size_bytes);
                    }
                }
            }

            // Derive numeric attributes for sorting/filtering
            $file_size_bytes_attr = $file_size_bytes;
            if (!$file_size_bytes_attr && is_string($file_size)) {
                if (preg_match('/(\d+(?:\.\d+)?)\s*(KB|MB|GB|B)/i', $file_size, $m)) {
                    $val = (float) $m[1];
                    $unit = strtoupper($m[2]);
                    $mult = 1;
                    if ($unit === 'KB') { $mult = 1024; }
                    elseif ($unit === 'MB') { $mult = 1024 * 1024; }
                    elseif ($unit === 'GB') { $mult = 1024 * 1024 * 1024; }
                    $file_size_bytes_attr = (int) round($val * $mult);
                }
            }
            $load_time_ms_attr = $load_time_ms;
            if (!$load_time_ms_attr && is_string($load_time)) {
                $plain_load = trim(strip_tags($load_time));
                if (preg_match('/(\d+(?:\.\d+)?)\s*ms/i', $plain_load, $mt)) {
                    $load_time_ms_attr = (int) round((float) $mt[1]);
                }
            }

            echo '<tr data-source="' . (function_exists('esc_attr') ? esc_attr($component_type) : htmlspecialchars($component_type)) . '" data-hostname="' . (function_exists('esc_attr') ? esc_attr($hostname) : htmlspecialchars($hostname)) . '" data-host-scope="' . (($site_host && $hostname === $site_host) ? 'same' : 'external') . '" data-size="' . $file_size_bytes_attr . '" data-load-time="' . $load_time_ms_attr . '" data-alt="' . (function_exists('esc_attr') ? esc_attr($alt) : htmlspecialchars($alt)) . '" data-src="' . (function_exists('esc_attr') ? esc_attr($src) : htmlspecialchars($src)) . '">';
            echo '<td class="query-number">' . $counter . '</td>';
            echo '<td class="wpdmgr-image-handle">' . (function_exists('esc_html') ? esc_html($alt) : htmlspecialchars($alt)) . '</td>';
            echo '<td>' . (function_exists('esc_html') ? esc_html($hostname) : htmlspecialchars($hostname)) . '</td>';
            echo '<td class="query-sql"><div class="sql-container"><code>' . $clickable_url . '</code></div></td>';
            echo '<td>' . $this->format_file_size_with_color($file_size) . '</td>';
            echo '<td>' . $load_time . '</td>';
            echo '</tr>';

            $counter++;
        }

        echo '</tbody>';
        echo '</table>';

        echo '<div class="wpdmgr-load-more-container"><button id="wpdmgr-images-load-more" type="button" data-page-size="20">' . (function_exists('__') ? __('Load More', 'wp-debug-manager') : 'Load More') . '</button></div>';
    }

    /**
     * Render hooks & actions tab content
     */
    private function render_hooks_tab() {
        global $wp_filter;

        if (empty($wp_filter)) {
            echo '<p>' . __('No hooks registered.', 'wp-debug-manager') . '</p>';
            return;
        }

        echo '<div class="wpdmgr-tab-filters">';
        echo '<label>Group by: <select id="wpdmgr-hooks-group-filter"><option value="all">All</option><option value="hook">Hook</option><option value="filter">Filter</option></select></label>';
        echo '<label>Sort by: <select id="wpdmgr-hooks-sort"><option value="hook">Hook Name</option><option value="priority">Priority</option></select></label>';
        echo '<label>Search: <input type="search" id="wpdmgr-hooks-search" placeholder="' . ( function_exists('__') ? esc_attr__( 'Search hook/callback…', 'wp-debug-manager' ) : 'Search hook/callback…' ) . '"></label>';
        echo '<label>Min Priority: <input type="number" id="wpdmgr-hooks-min-priority" min="0" step="1" placeholder="0"></label>';
        echo '</div>';

        echo '<table class="query-log-table wpdmgr-hooks-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>No.</th>';
        echo '<th class="sortable" data-column="priority">Priority</th>';
        echo '<th class="sortable" data-column="hook">Hook</th>';
        echo '<th class="sortable" data-column="action">Action</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        $counter = 1;
        $hook_count = 0;
        $hooks_data = array();

        // Collect and organize hooks data
        foreach ($wp_filter as $hook_name => $hook_obj) {
            if ($hook_count >= 100) break; // Limit to first 100 hooks for performance

            $hook_type = 'action';
            if (strpos($hook_name, 'filter') !== false ||
                strpos($hook_name, '_content') !== false ||
                strpos($hook_name, '_text') !== false ||
                strpos($hook_name, '_title') !== false) {
                $hook_type = 'filter';
            }

            if (is_object($hook_obj) && property_exists($hook_obj, 'callbacks')) {
                foreach ($hook_obj->callbacks as $priority => $callbacks) {
                    foreach ($callbacks as $callback_info) {
                        $function_name = 'Unknown';

                        if (isset($callback_info['function'])) {
                            $function = $callback_info['function'];

                            if (is_string($function)) {
                                $function_name = $function;
                            } elseif (is_array($function) && count($function) >= 2) {
                                if (is_object($function[0])) {
                                    $function_name = get_class($function[0]) . '->' . $function[1];
                                } else {
                                    $function_name = $function[0] . '::' . $function[1];
                                }
                            } elseif (is_object($function) && ($function instanceof Closure)) {
                                $function_name = 'Closure';
                            }
                        }

                        $hooks_data[] = array(
                            'hook_name' => $hook_name,
                            'hook_type' => $hook_type,
                            'priority' => $priority,
                            'function_name' => $function_name
                        );

                        $hook_count++;
                        if ($hook_count >= 100) break 3;
                    }
                }
            }
        }

        // Sort hooks data by hook name by default
        usort($hooks_data, function($a, $b) {
            $name_cmp = strcmp($a['hook_name'], $b['hook_name']);
            if ($name_cmp === 0) {
                return $a['priority'] - $b['priority'];
            }
            return $name_cmp;
        });

        // Group hooks by hook name and priority
        $grouped_hooks = array();
        foreach ($hooks_data as $hook_data) {
            $key = $hook_data['hook_name'] . '_' . $hook_data['priority'];
            if (!isset($grouped_hooks[$key])) {
                $grouped_hooks[$key] = array(
                    'hook_name' => $hook_data['hook_name'],
                    'hook_type' => $hook_data['hook_type'],
                    'priority' => $hook_data['priority'],
                    'functions' => array()
                );
            }
            $grouped_hooks[$key]['functions'][] = $hook_data['function_name'];
        }

        // Count total hooks by name for rowspan calculation
        $hook_counts = array();
        foreach ($grouped_hooks as $group) {
            if (!isset($hook_counts[$group['hook_name']])) {
                $hook_counts[$group['hook_name']] = 0;
            }
            $hook_counts[$group['hook_name']]++;
        }

        // Display grouped hooks data
        $displayed_hooks = array();
        foreach ($grouped_hooks as $group) {
            $hook_name = $group['hook_name'];
            $show_hook_cell = !isset($displayed_hooks[$hook_name]);

            echo '<tr data-hook-type="' . (function_exists('esc_attr') ? esc_attr($group['hook_type']) : htmlspecialchars($group['hook_type'])) . '" data-priority="' . $group['priority'] . '" data-hook="' . (function_exists('esc_attr') ? esc_attr($hook_name) : htmlspecialchars($hook_name)) . '" data-callback="' . (function_exists('esc_attr') ? esc_attr(implode(', ', $group['functions'])) : htmlspecialchars(implode(', ', $group['functions']))) . '">';
            echo '<td class="query-number">' . $counter . '</td>';
            echo '<td>' . $group['priority'] . '</td>';

            // Show hook name cell only for the first occurrence of this hook
            if ($show_hook_cell) {
                $rowspan = $hook_counts[$hook_name];
                echo '<td class="wpdmgr-hook-handle" rowspan="' . $rowspan . '">';
                echo '<span class="hook-type hook-type-' . $group['hook_type'] . '">' . strtoupper($group['hook_type']) . '</span> ';
                echo (function_exists('esc_html') ? esc_html($hook_name) : htmlspecialchars($hook_name));
                echo '</td>';
                $displayed_hooks[$hook_name] = true;
            }

            echo '<td class="query-sql"><div class="sql-container">';
            foreach ($group['functions'] as $index => $function_name) {
                if ($index > 0) {
                    echo '<br>';
                }
                echo '<code>' . (function_exists('esc_html') ? esc_html($function_name) : htmlspecialchars($function_name)) . '</code>';
            }
            echo '</div></td>';
            echo '</tr>';
            $counter++;
        }

        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Render real-time hooks tab with execution order and timing
     */
    /**
     * Render optimized real-time hooks tab with AJAX capability
     */
    private function render_realtime_hooks_tab() {
        if (empty($this->real_time_hooks)) {
            echo '<p>' . __('No strategic hooks captured. This indicates the monitoring has not detected any significant WordPress activity.', 'wp-debug-manager') . '</p>';
            echo '<p>Only Monitor ' . count($this->get_strategic_hooks()) . ' strategic hooks for better performance.</p>';
            return;
        }

        echo '<div class="wpdmgr-realtime-controls">';
        echo '<div class="wpdmgr-tab-filters">';
        echo '<label>Filter by Phase: <select id="wpdmgr-realtime-phase-filter"><option value="">All Phases</option></select></label>';
        echo '<label>Filter by Domain: <select id="wpdmgr-realtime-domain-filter"><option value="">All Domains</option></select></label>';
        echo '<label>Show: <select id="wpdmgr-realtime-limit"><option value="20">Last 20</option><option value="50" selected>Last 50</option><option value="100">Last 100</option></select></label>';
        echo '</div>';
        echo '<div class="wpdmgr-realtime-actions">';
        echo '<button id="wpdmgr-toggle-realtime" class="button button-primary">Enable Real-time Updates</button>';
        echo '<button id="wpdmgr-refresh-hooks" class="button">Refresh Now</button>';
        echo '<span class="wpdmgr-realtime-status">Status: <span id="wpdmgr-status-text">Static View</span></span>';
        echo '</div>';
        echo '</div>';

        echo '<div id="wpdmgr-realtime-summary" class="wpdmgr-realtime-summary">';
        echo '<h5>Strategic Hook Monitoring Summary</h5>';
        echo '<ul>';
        echo '<li>Strategic hooks captured: <span id="hooks-count">' . count($this->real_time_hooks) . '</span> (vs. 21,000+ with "all" hook)</li>';
        echo '<li>Monitoring approach: <span class="status-good">Selective strategic hooks</span> (Performance optimized)</li>';
        echo '<li>Memory usage: <span id="memory-usage">' . $this->wpdmgr_format_bytes(memory_get_usage()) . '</span></li>';
        echo '<li>Resource impact: <span class="status-good">Minimal</span> (No "all action" warning)</li>';
        echo '</ul>';
        echo '</div>';

        echo '<table class="query-log-table wpdmgr-realtime-hooks-table" id="realtime-hooks-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th class="sortable" data-column="order">Order</th>';
        echo '<th class="sortable" data-column="time">Time</th>';
        echo '<th class="sortable" data-column="memory">Memory</th>';
        echo '<th class="sortable" data-column="phase">Phase</th>';
        echo '<th class="sortable" data-column="hook">Hook Name</th>';
        echo '<th class="sortable" data-column="context">Context</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody id="realtime-hooks-tbody">';

        // Show recent hooks first, with reasonable limit
        $hooks_to_show = array_slice(array_reverse($this->real_time_hooks), 0, 50);
        $start_time = !empty($hooks_to_show) ? $hooks_to_show[count($hooks_to_show) - 1]['time'] : 0;

        foreach ($hooks_to_show as $hook_data) {
            $this->render_hook_row($hook_data, $start_time);
        }

        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Render individual hook row (extracted for reuse in AJAX)
     */
    private function render_hook_row($hook_data, $start_time) {
        $relative_time = $start_time ? ($hook_data['time'] - $start_time) * 1000 : 0;
        $memory_formatted = $this->wpdmgr_format_bytes($hook_data['memory']);
        $domain = $this->categorize_hook_by_domain($hook_data['hook']);

        echo '<tr data-phase="' . esc_attr($hook_data['phase']) . '" data-domain="' . esc_attr($domain ?: 'uncategorized') . '">';
        echo '<td class="query-number">' . esc_html($hook_data['order']) . '</td>';
        echo '<td>' . number_format($relative_time, 2) . 'ms</td>';
        echo '<td>' . esc_html($memory_formatted) . '</td>';
        echo '<td><span class="phase-badge phase-' . esc_attr($hook_data['phase']) . '">' . esc_html($hook_data['phase']) . '</span></td>';
        echo '<td class="wpdmgr-hook-name">';
        if ($domain) {
            echo '<span class="domain-badge domain-' . esc_attr($domain) . '">' . esc_html(strtoupper($domain)) . '</span> ';
        }
        echo '<strong>' . esc_html($hook_data['hook']) . '</strong>';
        echo '</td>';
        echo '<td class="query-caller">';
        if (!empty($hook_data['backtrace'])) {
            $frame = $hook_data['backtrace'][0]; // Show most relevant frame
            echo '<div class="caller-frame">';
            if ($frame['class']) {
                echo esc_html($frame['class'] . '::' . $frame['function']);
            } else {
                echo esc_html($frame['function']);
            }
            echo ' <small>(' . esc_html($frame['file'] . ':' . $frame['line']) . ')</small>';
            echo '</div>';
        } else {
            echo '<em>WordPress core</em>';
        }
        echo '</td>';
        echo '</tr>';
    }

    /**
     * Render bootstrap phases tab showing hook registration evolution
     */
    private function render_bootstrap_tab() {
        if (empty($this->bootstrap_snapshots)) {
            echo '<p>' . __('No bootstrap snapshots captured. This indicates monitoring was not active during early WordPress loading.', 'wp-debug-manager') . '</p>';
            return;
        }

        echo '<table class="query-log-table wpdmgr-bootstrap-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th class="sortable" data-column="phase">Bootstrap Phase</th>';
        echo '<th class="sortable" data-column="time">Time</th>';
        echo '<th class="sortable" data-column="memory">Memory</th>';
        echo '<th class="sortable" data-column="hooks">Total Hooks</th>';
        echo '<th class="sortable" data-column="growth">Hook Growth</th>';
        echo '<th>Details</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        $previous_hook_count = 0;
        $first_time = null;

        foreach ($this->bootstrap_snapshots as $phase => $snapshot) {
            if ($first_time === null) {
                $first_time = $snapshot['time'];
            }

            $relative_time = ($snapshot['time'] - $first_time) * 1000;
            $memory_formatted = $this->wpdmgr_format_bytes($snapshot['memory']);
            $hook_growth = $snapshot['hook_count'] - $previous_hook_count;
            $previous_hook_count = $snapshot['hook_count'];

            echo '<tr>';
            echo '<td class="bootstrap-phase">';
            echo '<span class="phase-badge phase-' . esc_attr(str_replace('_', '-', $phase)) . '">';
            echo esc_html(ucwords(str_replace('_', ' ', $phase)));
            echo '</span>';
            echo '</td>';
            echo '<td>' . number_format($relative_time, 2) . 'ms</td>';
            echo '<td>' . esc_html($memory_formatted) . '</td>';
            echo '<td>' . esc_html($snapshot['hook_count']) . '</td>';
            echo '<td>';
            if ($hook_growth > 0) {
                echo '<span class="hook-growth positive">+' . esc_html($hook_growth) . '</span>';
            } else {
                echo '<span class="hook-growth neutral">0</span>';
            }
            echo '</td>';
            echo '<td>';
            echo '<button class="button-link toggle-bootstrap-details" data-phase="' . esc_attr($phase) . '">View Details</button>';
            echo '<div class="bootstrap-details" id="bootstrap-details-' . esc_attr($phase) . '" style="display:none;">';

            if (!empty($snapshot['hooks'])) {
                echo '<strong>Top 10 hooks by callback count:</strong><br>';
                $sorted_hooks = $snapshot['hooks'];
                uasort($sorted_hooks, function($a, $b) {
                    return $b['callback_count'] - $a['callback_count'];
                });

                $top_hooks = array_slice($sorted_hooks, 0, 10, true);
                foreach ($top_hooks as $hook_name => $hook_info) {
                    echo '<code>' . esc_html($hook_name) . '</code> (' . $hook_info['callback_count'] . ' callbacks)<br>';
                }
            }

            echo '</div>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';

        echo '<div class="wpdmgr-bootstrap-summary">';
        echo '<h5>Bootstrap Analysis</h5>';
        echo '<p>This shows how WordPress hook registration evolves during the loading process. Each phase adds new hooks and callbacks.</p>';
        echo '</div>';
    }

    /**
     * Render domain-specific panels similar to Query Monitor
     */
    private function render_domains_tab() {
        echo '<div class="wpdmgr-domain-panels">';

        foreach ($this->domain_collectors as $domain => $data) {
            $hook_count = count($data['hooks']);
            if ($hook_count === 0) continue;

            echo '<div class="wpdmgr-domain-panel" id="domain-panel-' . esc_attr($domain) . '">';
            echo '<h5 class="domain-panel-title">';
            echo '<span class="domain-icon domain-icon-' . esc_attr($domain) . '"></span>';
            echo esc_html(ucwords($domain)) . ' Domain';
            echo ' <span class="domain-count">(' . $hook_count . ' hooks)</span>';
            echo '<button class="toggle-domain-panel" data-domain="' . esc_attr($domain) . '">Toggle</button>';
            echo '</h5>';

            echo '<div class="domain-panel-content" id="domain-content-' . esc_attr($domain) . '" style="display:none;">';

            // Domain-specific hook analysis
            echo '<table class="query-log-table domain-hooks-table">';
            echo '<thead><tr><th class="sortable" data-column="hook">Hook</th><th class="sortable" data-column="time">Execution Time</th><th class="sortable" data-column="phase">Phase</th><th class="sortable" data-column="context">Context</th></tr></thead>';
            echo '<tbody>';

            $domain_hooks = array_slice($data['hooks'], 0, 20); // Limit for performance
            foreach ($domain_hooks as $hook_data) {
                echo '<tr>';
                echo '<td><code>' . esc_html($hook_data['hook']) . '</code></td>';
                echo '<td>' . number_format(($hook_data['time'] - ($_SERVER['REQUEST_TIME_FLOAT'] ?? 0)) * 1000, 2) . 'ms</td>';
                echo '<td><span class="phase-badge">' . esc_html($hook_data['phase']) . '</span></td>';
                echo '<td>';
                if (!empty($hook_data['backtrace'][0])) {
                    $frame = $hook_data['backtrace'][0];
                    echo esc_html(($frame['class'] ? $frame['class'] . '::' : '') . $frame['function']);
                }
                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';

            // Domain-specific insights
            $this->render_domain_insights($domain, $data);

            echo '</div></div>';
        }

        echo '</div>';
    }

    /**
     * Render domain-specific insights and analysis
     */
    private function render_domain_insights($domain, $data) {
        echo '<div class="domain-insights">';
        echo '<h6>Domain Insights</h6>';

        switch ($domain) {
            case 'database':
                echo '<p>Database-related hooks help track query performance and data access patterns.</p>';
                break;
            case 'http':
                echo '<p>HTTP hooks monitor external requests and API calls that can affect page load time.</p>';
                break;
            case 'template':
                echo '<p>Template hooks show the theme loading process and template hierarchy decisions.</p>';
                break;
            case 'rewrite':
                echo '<p>Rewrite hooks track URL parsing and routing decisions in WordPress.</p>';
                break;
            case 'capabilities':
                echo '<p>Capability hooks monitor user permission checks and role-based access.</p>';
                break;
            case 'cache':
                echo '<p>Cache hooks track object caching operations and cache invalidation.</p>';
                break;
            case 'assets':
                echo '<p>Asset hooks monitor script and stylesheet enqueuing and loading.</p>';
                break;
        }

        $hook_count = count($data['hooks']);
        if ($hook_count > 20) {
            echo '<p><em>Showing first 20 of ' . $hook_count . ' hooks for performance.</em></p>';
        }

        echo '</div>';
    }

    /**
     * Render environment tab content
     */
    private function render_env_tab() {
        $env_data = $this->get_environment_data();

        // Group data by categories
        $categories = array();
        foreach ($env_data as $env_item) {
            $categories[$env_item['category']][] = $env_item;
        }

        // Define category order and display names
        $category_order = array(
            'PHP' => 'PHP Environment',
            'Database' => 'Database Configuration',
            'WordPress' => 'WordPress Configuration',
            'Server' => 'Server Information'
        );

        // Render each category as separate table
        foreach ($category_order as $category_key => $category_title) {
            if (!isset($categories[$category_key])) continue;

            echo '<div class="env-category-section">';
            echo '<h5 class="env-category-title">' . (function_exists('esc_html') ? esc_html($category_title) : htmlspecialchars($category_title)) . '</h5>';
            echo '<table class="query-log-table env-category-table">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Setting</th>';
            echo '<th>Value</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            foreach ($categories[$category_key] as $env_item) {
                echo '<tr>';
                echo '<td class="wpdmgr-env-handle">' . (function_exists('esc_html') ? esc_html($env_item['name']) : htmlspecialchars($env_item['name'])) . '</td>';
                echo '<td class="query-sql"><div class="sql-container"><code>' . (function_exists('esc_html') ? esc_html($env_item['value']) : htmlspecialchars($env_item['value'])) . '</code></div></td>';
                echo '</tr>';
            }

            echo '</tbody>';
            echo '</table>';
            echo '</div>';
        }
    }

    /**
     * Get page images for analysis
     */
    private function get_page_images() {
        $images = array();

        // Method 1: Get images from WordPress media/attachments used on current page
        $this->collect_attachment_images($images);

        // Method 2: Get images from theme customizer and site logos
        $this->collect_theme_images($images);

        // Method 3: Get images from CSS background-image properties
        $this->collect_css_background_images($images);

        // Method 4: Parse output buffer for inline images (if available)
        $this->collect_inline_images($images);

        // Method 5: Widgets/menus and theme extras
        $this->collect_widget_images($images);

        // Deduplicate by exact src (preserve order), and cap for performance
        $images = $this->dedupe_images_by_src($images, 100);

        return $images;
    }

    /**
     * Collect images from WordPress attachments/media library
     */
    private function collect_attachment_images(&$images) {
        // Get featured image if on single post/page
        if (function_exists('is_singular') && is_singular() && function_exists('has_post_thumbnail') && has_post_thumbnail()) {
            $thumbnail_id = get_post_thumbnail_id();
            $thumbnail_url = wp_get_attachment_image_src($thumbnail_id, 'full');
            if ($thumbnail_url) {
                $images[] = array(
                    'src' => $thumbnail_url[0],
                    'alt' => get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true) ?: 'Featured Image',
                    'position' => 'content'
                );
            }
        }

        // Get site logo
        if (function_exists('get_theme_mod')) {
            $custom_logo_id = get_theme_mod('custom_logo');
            if ($custom_logo_id) {
                $logo_url = wp_get_attachment_image_src($custom_logo_id, 'full');
                if ($logo_url) {
                    $images[] = array(
                        'src' => $logo_url[0],
                        'alt' => 'Site Logo',
                        'position' => 'header'
                    );
                }
            }
        }

        // Get site icon/favicon
        if (function_exists('get_option')) {
            $site_icon_id = get_option('site_icon');
            if ($site_icon_id) {
                $icon_url = wp_get_attachment_image_src($site_icon_id, 'full');
                if ($icon_url) {
                    $images[] = array(
                        'src' => $icon_url[0],
                        'alt' => 'Site Icon',
                        'position' => 'header'
                    );
                }
            }
        }
    }

    /**
     * Collect images from theme and customizer settings
     */
    private function collect_theme_images(&$images) {
        // Get header image
        if (function_exists('get_header_image')) {
            $header_image = get_header_image();
            if ($header_image) {
                $images[] = array(
                    'src' => $header_image,
                    'alt' => 'Header Image',
                    'position' => 'header'
                );
            }
        }

        // Get background image from customizer
        if (function_exists('get_background_image')) {
            $background_image = get_background_image();
            if ($background_image) {
                $images[] = array(
                    'src' => $background_image,
                    'alt' => 'Background Image',
                    'position' => 'background'
                );
            }
        }
    }

    /**
     * Collect images from CSS background-image properties in loaded stylesheets
     */
    private function collect_css_background_images(&$images) {
        global $wp_styles;

        if (!$wp_styles || empty($wp_styles->done)) {
            return;
        }

        foreach ($wp_styles->done as $handle) {
            if (!isset($wp_styles->registered[$handle])) continue;

            $style = $wp_styles->registered[$handle];
            $src = $style->src;

            if (!$src) {
                continue;
            }

            // Allow absolute CSS only if same host; map to local path
            $resolved_src = $src;
            if (strpos($src, '//') === 0 || strpos($src, 'http://') === 0 || strpos($src, 'https://') === 0) {
                $home_host = function_exists('home_url') ? parse_url(home_url('/'), PHP_URL_HOST) : '';
                $src_host = parse_url($src, PHP_URL_HOST);
                if (!$home_host || !$src_host || strtolower($home_host) !== strtolower($src_host)) {
                    continue; // skip remote CSS from other hosts
                }
                $path = parse_url($src, PHP_URL_PATH);
                if ($path) {
                    $resolved_src = $path;
                }
            }

            // Try to read local CSS file and extract background images
            $local_path = ABSPATH . ltrim($resolved_src, '/');
            if (file_exists($local_path)) {
                $css_content = @file_get_contents($local_path);
                if ($css_content) {
                    $this->extract_css_images($css_content, $images, $handle);
                }
            }
        }
    }

    /**
     * Extract background images from CSS content
     */
    private function extract_css_images($css_content, &$images, $css_handle) {
        global $wp_styles;

        // Match background-image: url() declarations
        $pattern = '/background(?:-image)?\s*:\s*url\s*\(\s*["\']?([^"\')]+)["\']?\s*\)/i';
        if (preg_match_all($pattern, $css_content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $image_url = trim($match[1]);

                // Skip data URIs and very small images
                if (strpos($image_url, 'data:') === 0 || strpos($image_url, '.svg') !== false) {
                    continue;
                }

                // Convert relative URLs to absolute
                if (strpos($image_url, 'http') !== 0) {
                    if (strpos($image_url, '/') === 0) {
                        if (function_exists('home_url')) {
                            $image_url = home_url($image_url);
                        }
                    } else {
                        // Relative to CSS file location
                        $css_dir = dirname($wp_styles->registered[$css_handle]->src);
                        // Normalize to path when CSS src is absolute URL
                        if (strpos($css_dir, '//') === 0 || strpos($css_dir, 'http://') === 0 || strpos($css_dir, 'https://') === 0) {
                            $path = parse_url($css_dir, PHP_URL_PATH);
                            if ($path) {
                                $css_dir = $path;
                            }
                        }
                        if (function_exists('home_url')) {
                            $image_url = home_url(rtrim($css_dir, '/') . '/' . ltrim($image_url, '/'));
                        }
                    }
                }

                $images[] = array(
                    'src' => $image_url,
                    'alt' => 'CSS Background Image',
                    'position' => 'css'
                );
            }
        }
    }

    /**
     * Collect inline images from page content (limited implementation)
     */
    private function collect_inline_images(&$images) {
        // For now, we can check if we're on a post/page and try to get images from content
        if (function_exists('is_singular') && is_singular()) {
            global $post;
            if ($post && !empty($post->post_content)) {
                $content = $post->post_content;

                // Simple regex to find img tags in content
                $pattern = '/<img[^>]+src=["\']([^"\'>]+)["\'][^>]*(?:alt=["\']([^"\'>]*)["\'])?[^>]*>/i';
                if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $src = $match[1];
                        $alt = isset($match[2]) ? $match[2] : 'Content Image';

                        // Convert relative URLs
                        if (strpos($src, 'http') !== 0 && strpos($src, '//') !== 0) {
                            if (strpos($src, '/') === 0) {
                                if (function_exists('home_url')) {
                                    $src = home_url($src);
                                }
                            }
                        }

                        $images[] = array(
                            'src' => $src,
                            'alt' => $alt,
                            'position' => 'content'
                        );
                    }
                }
            }
        }
    }

    /**
     * Get environment data for analysis
     */
    private function get_environment_data() {
        $env_data = array();

        // PHP Environment - Comprehensive data
        $env_data[] = array(
            'name' => 'Version',
            'value' => PHP_VERSION,
            'category' => 'PHP',
            'help' => '<a href="#" onclick="alert(\'PHP Version Help\'); return false;">Help</a>'
        );

        $env_data[] = array(
            'name' => 'SAPI',
            'value' => php_sapi_name(),
            'category' => 'PHP',
            'help' => ''
        );

        $env_data[] = array(
            'name' => 'User/Group',
            'value' => 'Hidden for security',
            'category' => 'PHP',
            'help' => ''
        );

        $env_data[] = array(
            'name' => 'max_execution_time',
            'value' => ini_get('max_execution_time'),
            'category' => 'PHP',
            'help' => ''
        );

        $env_data[] = array(
            'name' => 'memory_limit',
            'value' => ini_get('memory_limit'),
            'category' => 'PHP',
            'help' => ''
        );

        $env_data[] = array(
            'name' => 'upload_max_filesize',
            'value' => ini_get('upload_max_filesize'),
            'category' => 'PHP',
            'help' => ''
        );

        $env_data[] = array(
            'name' => 'post_max_size',
            'value' => ini_get('post_max_size'),
            'category' => 'PHP',
            'help' => ''
        );

        $env_data[] = array(
            'name' => 'display_errors',
            'value' => ini_get('display_errors') ? 'On' : 'Off',
            'category' => 'PHP',
            'help' => ''
        );

        $env_data[] = array(
            'name' => 'log_errors',
            'value' => ini_get('log_errors') ? '1' : '0',
            'category' => 'PHP',
            'help' => ''
        );

        $env_data[] = array(
            'name' => 'Error Reporting',
            'value' => error_reporting(),
            'category' => 'PHP',
            'help' => ''
        );

        $env_data[] = array(
            'name' => 'Extensions',
            'value' => count(get_loaded_extensions()),
            'category' => 'PHP',
            'help' => ''
        );

        // Database Environment
        if (function_exists('mysqli_get_server_info')) {
            global $wpdb;
            if (isset($wpdb->dbh)) {
                $env_data[] = array(
                    'name' => 'Server Version',
                    'value' => $wpdb->get_var($wpdb->prepare('SELECT VERSION()')),
                    'category' => 'Database',
                    'help' => ''
                );

                $env_data[] = array(
                    'name' => 'Extension',
                    'value' => 'mysqli',
                    'category' => 'Database',
                    'help' => ''
                );

                global $wpdb;
                $client_version = 'Unknown';
                if ($wpdb && method_exists($wpdb, 'db_version')) {
                    $client_version = $wpdb->db_version();
                }

                $env_data[] = array(
                    'name' => 'Client Version',
                    'value' => $client_version,
                    'category' => 'Database',
                    'help' => ''
                );

                $env_data[] = array(
                    'name' => 'Database User',
                    'value' => 'Hidden for security',
                    'category' => 'Database',
                    'help' => ''
                );

                $env_data[] = array(
                    'name' => 'Database Host',
                    'value' => 'Hidden for security',
                    'category' => 'Database',
                    'help' => ''
                );

                $env_data[] = array(
                    'name' => 'Database Name',
                    'value' => 'Hidden for security',
                    'category' => 'Database',
                    'help' => ''
                );

                // Database configuration
                $innodb_buffer = $wpdb->get_var($wpdb->prepare('SHOW VARIABLES LIKE %s', 'innodb_buffer_pool_size'));
            if ($innodb_buffer) {
                $buffer_size = $wpdb->get_var($wpdb->prepare('SELECT @@innodb_buffer_pool_size'));
                    $env_data[] = array(
                        'name' => 'innodb_buffer_pool_size',
                        'value' => $buffer_size . ' (~' . round($buffer_size / 1024 / 1024 / 1024, 1) . ' GB)',
                        'category' => 'Database',
                        'help' => ''
                    );
                }

                $key_buffer = $wpdb->get_var($wpdb->prepare('SELECT @@key_buffer_size'));
                if ($key_buffer) {
                    $env_data[] = array(
                        'name' => 'key_buffer_size',
                        'value' => $key_buffer . ' (~' . round($key_buffer / 1024 / 1024, 0) . ' MB)',
                        'category' => 'Database',
                        'help' => ''
                    );
                }

                $max_packet = $wpdb->get_var($wpdb->prepare('SELECT @@max_allowed_packet'));
                if ($max_packet) {
                    $env_data[] = array(
                        'name' => 'max_allowed_packet',
                        'value' => $max_packet . ' (~' . round($max_packet / 1024 / 1024, 0) . ' MB)',
                        'category' => 'Database',
                        'help' => ''
                    );
                }

                $max_connections = $wpdb->get_var($wpdb->prepare('SELECT @@max_connections'));
                if ($max_connections) {
                    $env_data[] = array(
                        'name' => 'max_connections',
                        'value' => $max_connections,
                        'category' => 'Database',
                        'help' => ''
                    );
                }
            }
        }

        // WordPress Environment - Comprehensive
        if (function_exists('get_bloginfo')) {
            $env_data[] = array(
                'name' => 'Version',
                'value' => get_bloginfo('version'),
                'category' => 'WordPress',
                'help' => ''
            );
        }

        $env_data[] = array(
            'name' => 'Environment Type',
            'value' => defined('WP_ENVIRONMENT_TYPE') ? constant('WP_ENVIRONMENT_TYPE') : 'production',
            'category' => 'WordPress',
            'help' => '<a href="#" onclick="alert(\'Environment Type Help\'); return false;">Help</a>'
        );

        $env_data[] = array(
            'name' => 'Development Mode',
            'value' => defined('WP_DEVELOPMENT_MODE') ? (constant('WP_DEVELOPMENT_MODE') ?: 'empty string') : 'undefined',
            'category' => 'WordPress',
            'help' => '<a href="#" onclick="alert(\'Development Mode Help\'); return false;">Help</a>'
        );

        if (defined('WP_DEBUG')) {
            $env_data[] = array(
                'name' => 'WP_DEBUG',
                'value' => WP_DEBUG ? 'true' : 'false',
                'category' => 'WordPress',
                'help' => ''
            );
        }

        if (defined('WP_DEBUG_DISPLAY')) {
            $env_data[] = array(
                'name' => 'WP_DEBUG_DISPLAY',
                'value' => WP_DEBUG_DISPLAY ? 'true' : 'false',
                'category' => 'WordPress',
                'help' => ''
            );
        }

        if (defined('WP_DEBUG_LOG')) {
            $env_data[] = array(
                'name' => 'WP_DEBUG_LOG',
                'value' => WP_DEBUG_LOG ? 'true' : 'false',
                'category' => 'WordPress',
                'help' => ''
            );
        }

        if (defined('SCRIPT_DEBUG')) {
            $env_data[] = array(
                'name' => 'SCRIPT_DEBUG',
                'value' => SCRIPT_DEBUG ? 'true' : 'false',
                'category' => 'WordPress',
                'help' => ''
            );
        }

        $env_data[] = array(
            'name' => 'WP_CACHE',
            'value' => defined('WP_CACHE') ? (WP_CACHE ? 'true' : 'false') : 'false',
            'category' => 'WordPress',
            'help' => ''
        );

        $env_data[] = array(
            'name' => 'CONCATENATE_SCRIPTS',
            'value' => defined('CONCATENATE_SCRIPTS') ? (CONCATENATE_SCRIPTS ? 'true' : 'false') : 'undefined',
            'category' => 'WordPress',
            'help' => ''
        );

        $env_data[] = array(
            'name' => 'COMPRESS_SCRIPTS',
            'value' => defined('COMPRESS_SCRIPTS') ? (COMPRESS_SCRIPTS ? 'true' : 'false') : 'undefined',
            'category' => 'WordPress',
            'help' => ''
        );

        $env_data[] = array(
            'name' => 'COMPRESS_CSS',
            'value' => defined('COMPRESS_CSS') ? (COMPRESS_CSS ? 'true' : 'false') : 'undefined',
            'category' => 'WordPress',
            'help' => ''
        );

        // Server Environment - Comprehensive
        $env_data[] = array(
            'name' => 'Software',
            'value' => 'Hidden for security',
            'category' => 'Server',
            'help' => ''
        );

        $env_data[] = array(
            'name' => 'Version',
            'value' => 'Hidden for security',
            'category' => 'Server',
            'help' => ''
        );

        $env_data[] = array(
            'name' => 'IP Address',
            'value' => 'Hidden for security',
            'category' => 'Server',
            'help' => ''
        );

        $env_data[] = array(
            'name' => 'Host',
            'value' => 'Hidden for security',
            'category' => 'Server',
            'help' => ''
        );

        $env_data[] = array(
            'name' => 'OS',
            'value' => php_uname('s r'),
            'category' => 'Server',
            'help' => ''
        );

        $env_data[] = array(
            'name' => 'Architecture',
            'value' => php_uname('m'),
            'category' => 'Server',
            'help' => ''
        );

        $env_data[] = array(
            'name' => 'Authentication',
            'value' => 'Information hidden for security',
            'category' => 'Server',
            'help' => ''
        );

        return $env_data;
    }



    /**
     * Collect images from widgets and navigation menus
     */
    private function collect_widget_images(&$images) {
        // This is a simplified implementation
        // In a full implementation, you would parse widget content and nav menus

        // Check for custom header images from themes
        if (function_exists('get_theme_support') && get_theme_support('custom-header')) {
            $header_images = get_uploaded_header_images();
            if (!empty($header_images)) {
                foreach ($header_images as $header_image) {
                    $images[] = array(
                        'src' => $header_image['url'],
                        'alt' => 'Custom Header Image',
                        'position' => 'header',
                        'width' => $header_image['width'] ?? 0,
                        'height' => $header_image['height'] ?? 0
                    );
                }
            }
        }
    }

    /**
     * Lightweight dedupe by src while preserving order, with optional cap
     *
     * @param array $images
     * @param int   $cap
     * @return array
     */
    private function dedupe_images_by_src($images, $cap = 100) {
        $seen = array();
        $out = array();
        foreach ($images as $img) {
            if (!is_array($img) || empty($img['src'])) {
                continue;
            }
            $src = (string) $img['src'];
            if (isset($seen[$src])) {
                continue;
            }
            $seen[$src] = true;
            $out[] = $img;
            if ($cap && count($out) >= $cap) {
                break;
            }
        }
        return $out;
    }
}
