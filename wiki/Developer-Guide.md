# Developer Guide

This guide provides comprehensive information for developers who wish to extend or customize the Advanced Log Manager plugin. By utilizing the provided action and filter hooks, you can integrate custom functionalities, modify plugin behavior, and tailor it to specific project requirements.

## 🎯 Action Hooks untuk Developer

Action hooks memungkinkan Anda mengeksekusi kode custom pada titik spesifik selama lifecycle plugin atau ketika event tertentu terjadi. Berguna untuk menambah fitur baru, menampilkan informasi tambahan, atau trigger proses eksternal tanpa modifikasi core files plugin.

### `alm_log_viewer_before` - Hook Sebelum Log Viewer

**Deskripsi:** Hook ini trigger tepat sebelum interface log viewer di-render di halaman admin WordPress. Memberikan kesempatan untuk inject custom content atau lakukan actions sebelum log data atau viewer elements ditampilkan.

**Kapan Menggunakan:**
*   Menampilkan pesan custom atau warning sebelum user melihat logs
*   Menambah custom header atau introductory section
*   Enqueue custom scripts/styles yang perlu load sebelum log viewer
*   Lakukan preliminary checks atau data processing

**Contoh Praktis:**

```php
// Menampilkan notice sebelum log viewer
add_action('alm_log_viewer_before', function() {
    echo '<div class="notice notice-info is-dismissible">
        <p><strong>🔍 Debug Mode Active:</strong> Logs direkam untuk troubleshooting.</p>
    </div>';
});

// Enqueue custom CSS untuk styling log viewer
add_action('alm_log_viewer_before', function() {
    wp_enqueue_style('my-custom-log-styles',
        plugins_url('css/custom-log-styles.css', __FILE__),
        array(), '1.0.0'
    );
});

// Log aktivitas user sebelum melihat logs
add_action('alm_log_viewer_before', function() {
    $user = wp_get_current_user();
    error_log("User {$user->user_login} mengakses log viewer pada " . current_time('mysql'));
});
```

### `alm_log_viewer_after`

**Description:** This action hook is triggered after the entire log viewer interface, including all log data and controls, has been rendered on the WordPress admin page. It's ideal for adding content or executing code at the very end of the log viewer output.

**When to Use:**
*   To add a custom footer or disclaimer after the log viewer.
*   To display summary statistics or additional tools related to the logs.
*   To include a feedback form or links to documentation.
*   To enqueue scripts that should run after the log viewer content is fully loaded, for example, for post-rendering manipulations.

**Example:**

```php
add_action('alm_log_viewer_after', function() {
    echo '<p>For more detailed analysis, please refer to our <a href="https://example.com/docs/log-analysis" target="_blank">Log Analysis Guide</a>.</p>';
    // Or add a custom button:
    // echo '<button id="export-logs-button" class="button button-primary">Export All Logs</button>';
});
```

## Filter Hooks

Filter hooks allow you to modify data that the plugin processes or uses. By attaching a function to a filter hook, you can alter variables, configurations, or content before they are used by the plugin or displayed to the user.

### `alm_log_file_path`

**Description:** This filter hook allows you to dynamically change the absolute path to the log file that the Advanced Log Manager plugin reads. By default, the plugin might look for logs in standard WordPress locations, but this filter gives you full control to specify an alternative path.

**When to Use:**
*   When your log files are stored in a custom directory outside of the default WordPress paths (e.g., for security reasons or specific server configurations).
*   To switch between different log files based on certain conditions (e.g., environment, user role, or specific debugging scenarios).
*   To integrate with external logging systems that store logs in non-standard locations.

**Example:**

```php
add_filter('alm_log_file_path', function($default_log_file_path) {
    // Example 1: Change the log file path to a custom directory within WP_CONTENT_DIR
    // return WP_CONTENT_DIR . '/custom-logs/debug.log';

    // Example 2: Change log file based on environment (e.g., staging vs. production)
    if ( defined('WP_ENVIRONMENT_TYPE') && 'staging' === WP_ENVIRONMENT_TYPE ) {
        return '/var/log/apache2/staging-error.log';
    }
    return $default_log_file_path; // Always return the path, even if not modified
});
```

### `alm_log_viewer_capabilities`

**Description:** This filter hook allows you to modify the WordPress user capability required to access and view the Advanced Log Manager interface. By default, only users with administrative privileges might be able to view logs, but this filter enables you to grant access to other user roles.

**When to Use:**
*   To allow specific non-administrator roles (e.g., Editors, Custom Roles) to view logs for debugging or monitoring purposes without giving them full admin access.
*   To restrict log viewing access even further, for example, only to a super administrator in a multisite environment.
*   To integrate with custom role management plugins and define granular permissions for log access.

**Example:**

```php
add_filter('alm_log_viewer_capabilities', function($default_capability) {
    // Allow users with 'edit_posts' capability (e.g., Editors) to view the log viewer
    // return 'edit_posts';

    // Allow users with a custom capability 'manage_logs'
    // Make sure this capability is assigned to the desired roles
    // return 'manage_logs';

    // Keep the default capability if no specific change is needed
    return $default_capability;
});
```
