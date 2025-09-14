# Configuration Guide for Advanced Log Manager

This guide will walk you through the essential configuration steps for the Advanced Log Manager plugin, ensuring you can effectively monitor and manage your WordPress site. We'll cover enabling debug mode, understanding the basic debug settings, and managing PHP configuration presets.

## 1. Enabling WordPress Debug Mode

Enabling WordPress debug mode is crucial for development and troubleshooting. It allows WordPress to record errors, warnings, and notices, which the Advanced Log Manager can then display and manage.

### Step-by-Step Guide to Edit `wp-config.php`:

1.  **Connect to your server:**
    *   Use an **FTP client** (e.g., FileZilla, Cyberduck) or your **hosting provider's file manager** (e.g., cPanel File Manager) to access your WordPress installation's root directory. This is where you'll find `wp-config.php`.

2.  **Edit the `wp-config.php` file:**
    *   Locate and open the `wp-config.php` file using a text editor.

3.  **Add the debug constants:**
    *   Find the line `/* That's all, stop editing! Happy publishing. */`.
    *   **Insert the following lines of code *just before* this comment:**

    ```php
    define( 'WP_DEBUG', true );
    define( 'WP_DEBUG_LOG', true );
    define( 'WP_DEBUG_DISPLAY', false );
    ```

    *   **`define( 'WP_DEBUG', true );`**: Activates debug mode across your WordPress site, reporting all PHP errors, notices, and warnings.
    *   **`define( 'WP_DEBUG_LOG', true );`**: Ensures all debug messages are saved to `debug.log` in the `wp-content` directory. Recommended for production to keep errors private.
    *   **`define( 'WP_DEBUG_DISPLAY', false );`**: Prevents debug messages from being shown directly on your website, enhancing security and user experience while still logging them.

4.  **Save the file:**
    *   Save changes and re-upload `wp-config.php` if using an FTP client.

## 2. Accessing the Plugin Settings

Once installed and activated, Advanced Log Manager's settings are accessible from your WordPress admin dashboard.

### Step-by-Step Guide:

1.  **Log in to your WordPress Admin Dashboard:**
    *   Navigate to `yourwebsite.com/wp-admin` and enter your credentials.

2.  **Locate the Plugin Menu Item:**
    *   In the left-hand sidebar, find **"Advanced Log Manager"** or a similar menu item.

3.  **Explore the Sub-Menus:**
    *   Clicking the main menu item reveals sub-menus like:
        *   **Dashboard:** Overview of logging status and quick access.
        *   **Debug Logs:** View, filter, and manage `debug.log`.
        *   **Query Monitor:** Analyze database queries.
        *   **SMTP Logs:** Track outgoing emails.
        *   **Htaccess Editor:** Safely edit your `.htaccess` file.
        *   **PHP Config Presets:** Manage PHP configurations.
        *   **Performance Monitor:** Access frontend performance insights.

## 3. Understanding the Settings Panel

The plugin's settings panel provides granular control over various aspects of logging and site management.

### A. System Overview Dashboard

This is your central hub for a quick glance at the plugin's status.

*   **Top Information Cards:**
    *   **DEBUG MODE: Active/Inactive**: Shows if WordPress debug mode is currently active.
    *   **PERFORMANCE MONITOR: Monitoring/Inactive**: Indicates if performance monitoring is running.
    *   **DEBUG LOG: Active - [Size]**: Status of debug logging and current log file size.
    *   **QUERY LOG: Active - [Size]**: Status of database query logging and current log file size.
    *   **SMTP LOGS: Active - [Status]**: Status of SMTP email logging.

*   **"Enable Debug Mode" Section:**
    *   **Description**: "Enable or disable all debug features. This controls WordPress debug constants and logging functionality."
    *   **"Debug Mode Enabled" Toggle**: A master switch to activate or deactivate all debug functionalities.
    *   **Status Indicators (Error Logging, Error Display, Script Debug, Query Logging)**: Visual cues (e.g., green for active, red for inactive) for specific debug aspects.

*   **Feature Cards (Bottom Section):**
    *   **Debug Management**: Controls WordPress debug settings and log management. Click "Configure" for detailed options.
    *   **Performance Monitor**: Monitors site performance and query analysis. Click "Configure" for settings.
    *   **.htaccess Editor**: Tool for safely editing your `.htaccess` file. Click "Open Editor" to access.
    *   **PHP Config**: Manages PHP configuration presets and optimization. Click "Configure" for options.

### B. Basic Debug Settings (Accessed via Debug Management "Configure")

This panel offers essential debug configurations.

*   **"Enable Debug Mode" Toggle**: Master switch for all debug functionality.
*   **WordPress Debug Constants (Expandable)**: Configure `WP_DEBUG`, `WP_DEBUG_LOG`, `WP_DEBUG_DISPLAY` directly from the UI.
*   **Email & SMTP Debug (Expandable)**: Settings for debugging email sending and SMTP issues.
*   **Log Status & Quick Actions (Expandable)**: View log file statuses and perform quick actions like clearing logs.
*   **Danger Zone (Expandable)**: Contains sensitive actions such as resetting settings or clearing all logs. Use with extreme caution.

### C. .htaccess Editor

This tool allows you to manage your server's `.htaccess` file.

*   **Backup Status**: "Backups: 0/3 available" indicates how many backups of your `.htaccess` file are available. Always make backups before making changes!
*   **Code Editor Area**: Where you can view and modify the `.htaccess` file content.
*   **"Backup & Save" Button**: Saves your changes and creates a backup.
*   **"Cancel" Button**: Discards unsaved changes.
*   **"Common Snippets"**: Pre-defined code snippets for common tasks like WordPress Rewrite Rules, Browser Caching, GZIP Compression, and Security Headers.

### D. PHP Configuration Presets (Accessed via PHP Config "Configure")

This panel helps optimize your site's PHP environment.

*   **"Configuration Method: Wp_config"**: Shows that configurations are managed via `wp-config.php`.
*   **"Select Preset"**: Choose from pre-defined PHP settings:
    *   **Basic**: For small sites with light traffic (e.g., Memory Limit: 256M, Upload Max Filesize: 8M).
    *   **Medium**: Good for most WordPress sites (e.g., Memory Limit: 512M, Upload Max Filesize: 16M).
    *   **High Performance**: For high-traffic sites and complex applications (e.g., Memory Limit: 2048M, Upload Max Filesize: 64M).
*   **"Current Configuration"**: Displays your site's currently active PHP settings.
*   **"Apply Configuration" Button**: Applies the selected preset or custom settings to your site.

By understanding and utilizing these configuration options, you can tailor the Advanced Log Manager to your specific needs, ensuring optimal monitoring and troubleshooting capabilities for your WordPress site.