# Troubleshooting Guide

This comprehensive guide provides detailed solutions to common problems you might encounter while using the Advanced Log Manager plugin. We aim to help you quickly diagnose and resolve issues to ensure a smooth experience.

## 🛠️ Panduan Troubleshooting Lengkap

Dokumen ini memberikan solusi lengkap untuk masalah umum yang mungkin Anda temui saat menggunakan Advanced Log Manager. Kami fokus pada step-by-step solutions yang mudah diikuti.

## 1. 🔧 Debug Mode Tidak Aktif / Invalid Debug Constant

**Gejala:** Error "invalid debug constant" atau fitur plugin tidak berfungsi dengan baik.

**Penyebab:** Constant `WP_DEBUG` tidak didefinisikan atau diset `false` di `wp-config.php`.

**Solusi Mudah (Recommended):**
1. **Gunakan Dashboard Plugin:**
   - Pergi ke **Tools** → **Advance Log Manager**
   - Klik tombol **"Enable Debug Mode"**
   - Plugin akan auto-konfigurasi semua constants

2. **Verifikasi:**
   - Status Debug Mode berubah ke **"Active"**
   - Indicator hijau muncul di Error Logging

**Solusi Manual (Advanced):**
1. **Akses `wp-config.php`** via FTP atau File Manager hosting
2. **Edit file** dan tambahkan sebelum `/* That's all, stop editing! Happy publishing. */`:
   ```php
   define( 'WP_DEBUG', true );
   define( 'WP_DEBUG_LOG', true );
   define( 'WP_DEBUG_DISPLAY', false );
   ```
3. **Save dan upload** kembali file
4. **Test** dengan refresh dashboard

**Tips:** Selalu backup `wp-config.php` sebelum edit manual!

## 2. Debug Mode Toggle Button Not Clickable

**Problem:** The toggle button designed to enable or disable debug mode within the Advanced Log Manager interface is unresponsive or cannot be clicked.

**Cause:** This issue is most frequently caused by a JavaScript conflict with another installed plugin or your active WordPress theme. Such conflicts can prevent the necessary scripts for the toggle button's functionality from executing correctly.

**Solution:**

1.  **Check for JavaScript Errors:**
    *   Open your web browser's developer console (usually by pressing `F12` on Windows/Linux or `Cmd + Option + I` on macOS).
    *   Navigate to the "Console" tab.
    *   Refresh the page where the toggle button is located.
    *   Look for any red error messages, which indicate JavaScript errors. These messages can often point to the conflicting script or plugin.
2.  **Perform a Conflict Check:**
    *   **Deactivate Plugins:** Go to `Plugins > Installed Plugins` in your WordPress admin area. Deactivate all plugins *except* Advanced Log Manager.
    *   **Test:** Check if the toggle button is now clickable. If it is, reactivate your plugins one by one, testing the toggle button after each activation, until you identify the conflicting plugin.
    *   **Switch Theme:** If deactivating plugins doesn't resolve the issue, switch your active theme to a default WordPress theme (e.g., Twenty Twenty-Four, Twenty Twenty-Three) by going to `Appearance > Themes`. Test the toggle button again. If it works, your theme is likely causing the conflict.
3.  **Report to Support:** Once you've identified the conflicting plugin or theme, consider reaching out to the respective developer's support channel for assistance. Provide them with details of the conflict and any JavaScript errors you found.

## 3. Performance Metrics Calculation Inconsistency

**Problem:** The performance metrics displayed by Advanced Log Manager (e.g., memory usage, page load time, query count) appear inconsistent, inaccurate, or fluctuate unexpectedly.

**Cause:** Several factors can contribute to inconsistent performance metrics:

*   **Caching:** Server-side caching, WordPress caching plugins, or browser caching can serve cached versions of pages, leading to metrics that don't reflect real-time performance.
*   **Other Plugins/Themes:** Other plugins or themes might interfere with WordPress's core functions or introduce their own performance monitoring, leading to skewed results.
*   **Server Environment:** Variations in server load, resource allocation, or network latency can naturally affect performance metrics.
*   **Measurement Timing:** The exact moment a metric is captured can influence its value, especially in dynamic environments.

**Solution:**

1.  **Clear All Caches:**
    *   **WordPress Caching Plugins:** If you use a caching plugin (e.g., WP Super Cache, W3 Total Cache, LiteSpeed Cache), clear its cache entirely.
    *   **Server-Side Caching:** If your hosting provider offers server-side caching (e.g., Varnish, Nginx caching), clear it through your hosting control panel.
    *   **Browser Cache:** Clear your browser's cache and cookies, or use an incognito/private browsing window to ensure you're loading fresh content.
2.  **Perform a Conflict Check:** Similar to the JavaScript conflict resolution, deactivate other plugins one by one and switch to a default theme to isolate if another component is interfering with the performance monitoring.
3.  **Monitor Under Controlled Conditions:** To get more reliable readings, try monitoring performance during periods of low website traffic or on a staging environment that closely mirrors your production setup.
4.  **Review Server Resources:** Check your hosting provider's metrics or contact their support to ensure your server has adequate resources (CPU, RAM) and is not experiencing high load, which can impact performance readings.
5.  **Consult Documentation/Support:** If inconsistencies persist, refer to the Advanced Log Manager documentation or contact support with details of your setup and the observed discrepancies.

## 4. Log Files Not Updating or Missing

**Problem:** You notice that log files (e.g., `debug.log`, `query.log`, `smtp.log`) are not being generated, are empty, or are not updating with new entries.

**Cause:** This can be due to incorrect file permissions, `WP_DEBUG_LOG` not being enabled, server configuration issues, or conflicts with other plugins.

**Solution:**

1.  **Verify `WP_DEBUG_LOG`:** Ensure that `define( 'WP_DEBUG_LOG', true );` is present and set to `true` in your `wp-config.php` file (as described in "Invalid Debug Constant Error" section).
2.  **Check File Permissions:**
    *   Log files are typically stored in the `wp-content` directory. Ensure that the `wp-content` directory and any existing `debug.log` file within it have correct write permissions (usually `644` for files and `755` for directories, though some hosts might require `777` for `debug.log` temporarily for testing).
    *   You can check and change permissions using an FTP client or your hosting file manager.
3.  **Server Disk Space:** Confirm that your hosting account has sufficient disk space. If the disk is full, new log entries cannot be written.
4.  **Plugin Conflicts:** Deactivate other plugins one by one to check if any plugin is preventing log file generation or updates.
5.  **Server Error Logs:** Check your server's main error logs (usually accessible via your hosting control panel) for any PHP errors or warnings that might indicate why logging is failing.

## 5. .htaccess Editor Changes Not Taking Effect

**Problem:** Changes made through the `.htaccess` editor in Advanced Log Manager do not seem to be applied to your website.

**Cause:** This can happen if your server configuration doesn't allow `.htaccess` overrides, if there are syntax errors in your `.htaccess` file, or if caching is preventing the changes from being immediately visible.

**Solution:**

1.  **Clear Caches:** Clear all types of caches (WordPress, server-side, browser) as described in the "Performance Metrics Calculation Inconsistency" section.
2.  **Check Server Configuration:**
    *   Your web server (Apache or Nginx) must be configured to allow `.htaccess` overrides. For Apache, this means `AllowOverride All` must be set in your virtual host configuration.
    *   If you are on Nginx, `.htaccess` files are not natively supported. You would need to convert `.htaccess` rules to Nginx configuration directives, which usually requires server access.
    *   Contact your hosting provider to confirm if `.htaccess` overrides are enabled and supported for your environment.
3.  **Check for Syntax Errors:** Even a small syntax error in `.htaccess` can cause a "500 Internal Server Error" or prevent rules from being applied. The Advanced Log Manager's editor should have some validation, but manual review or using an `.htaccess` validator tool can help.
4.  **File Permissions:** Ensure the `.htaccess` file has correct permissions (usually `644`).
5.  **Other Plugins:** Some security or performance plugins might also manage or interfere with `.htaccess` rules. Temporarily deactivate such plugins to test.

If you've followed these troubleshooting steps and are still experiencing issues, please don't hesitate to contact our support team with detailed information about your problem, your WordPress environment, and the steps you've already taken. We're here to help!
