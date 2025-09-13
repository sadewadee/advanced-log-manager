=== Advanced Log Manager ===
Contributors: mordenteam
Tags: logging, debug, query monitor, htaccess, php config
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 1.2.27
Requires PHP: 7.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Advanced logging and debugging tools for WordPress: Log Manager, Query Monitor, Htaccess Editor, PHP Config presets.

== Description ==

Advanced Log Manager is a comprehensive WordPress plugin that provides powerful logging and debugging capabilities for developers and site administrators.

**Key Features:**

* **Log Manager** - View, filter, and manage WordPress debug logs
* **Query Monitor** - Monitor database queries and performance
* **SMTP Logs** - Track email sending and SMTP debugging
* **Htaccess Editor** - Safely edit .htaccess files with backup
* **PHP Config Presets** - Quick PHP configuration templates
* **Performance Monitoring** - Track site performance metrics

**Perfect for:**
* Developers debugging WordPress sites
* Site administrators monitoring performance
* Agencies managing multiple WordPress installations
* Anyone who needs detailed logging capabilities

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/advanced-log-manager` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->Advanced Log Manager screen to configure the plugin
4. Enable WordPress debug logging by adding these lines to your wp-config.php:
   ```
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

== Frequently Asked Questions ==

= How do I enable internal plugin logging? =

Add this line to your wp-config.php file:
```
define('ALMGR_INTERNAL_LOGGING', true);
```

= Is this plugin safe to use on production sites? =

Yes, the plugin is designed with safety in mind. However, we recommend testing on a staging site first and being cautious when editing .htaccess files.

= Does this plugin affect site performance? =

The plugin is optimized for minimal performance impact. Logging features only activate when needed.

== Screenshots ==

1. Main log viewer interface
2. Query monitor dashboard
3. SMTP logs overview
4. Htaccess editor with syntax highlighting
5. PHP configuration presets

== Changelog ==

= 1.2.27 =
* Latest stable release
* Bug fixes and improvements
* Enhanced logging capabilities

For detailed changelog, see CHANGELOG.md file.

== Upgrade Notice ==

= 1.2.27 =
Recommended update with bug fixes and performance improvements.

== Support ==

For support and documentation, visit: https://github.com/sadewadee/advanced-log-manager

== License ==

This plugin is licensed under the GPL v3 or later.