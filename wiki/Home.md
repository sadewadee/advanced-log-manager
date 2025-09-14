# Welcome to the Advanced Log Manager Wiki!

Advanced Log Manager is a comprehensive WordPress plugin designed to provide powerful logging, debugging, and monitoring capabilities for developers and site administrators. This wiki serves as your primary resource for detailed documentation on all features, ensuring you can leverage the plugin to its full potential.

## What is Advanced Log Manager?

In the dynamic WordPress environment, understanding what's happening behind the scenes is crucial for maintaining a healthy, performant, and secure website. Advanced Log Manager simplifies this by centralizing various diagnostic tools into one intuitive interface. From tracking PHP errors to monitoring database queries and managing server configurations, this plugin empowers you to quickly identify and resolve issues, optimize performance, and enhance overall site stability.

## Quick Start for Beginners (5 Minute Setup)

If you're new to Advanced Log Manager, follow these simple steps to get started:

### 1. Plugin Activation
   - Go to WordPress Admin → Plugins → Installed Plugins
   - Find "Advanced Log Manager" and click Activate

### 2. Access Dashboard
   - Go to Tools → Advance Log Manager
   - You will see the main dashboard with system status

### 3. Basic Debugging Setup
   - Click the "Enable Debug Mode" button in the dashboard
   - The plugin will automatically enable logging
   - Debug logs will be saved to wp-content/debug.log

### 4. Test Performance Monitoring
   - In the Performance Monitor tab, enable "Enable Performance Bar"
   - Visit your site's frontend page (as an admin)
   - You will see the performance bar at the bottom of the page

### 5. Check First Logs
   - Return to dashboard → Click the "Debug Log" card
   - If there are errors, they will appear in the log viewer
   - Use filters to search for specific issues

Tips for Beginners:
- Always backup your site before making configuration changes
- Start with debug mode active in development environment
- Use performance bar to monitor loading time
- Do not enable debug mode in production without monitoring

## Core Features Overview

Here is a brief overview of the core functionalities offered by Advanced Log Manager. Each feature is designed to provide granular control and clear insights into different aspects of your WordPress installation.

### 1. Log Manager (Debug Logs)

Function: Central hub for viewing, filtering, and managing all WordPress debug logs. Consolidates various log types (PHP errors, warnings, notices) into a readable format.

Benefits for Beginners:
*   Fast Debugging: Identify PHP errors, warnings, and notices that might affect site functionality
*   Centralized View: Access all relevant logs from one user-friendly interface in the WordPress dashboard
*   Filter & Search: Navigate through large log files using filtering and search capabilities
*   Log Cleaning: Clear old logs to save server space

### 2. Query Monitor

Function: Provides deep insights into WordPress database interactions. Tracks every query executed on your site.

Benefits for Beginners:
*   Performance Optimization: Identify and optimize slow database queries
*   Resource Analysis: Understand which plugins/themes generate excessive database calls
*   Debug Database Issues: Pinpoint errors or unexpected behavior related to database
*   Detailed Analysis: View execution time, caller functions, and affected rows

### 3. SMTP Logs

Function: Records all outgoing emails from your WordPress site, providing comprehensive history of delivery attempts, status, and errors.

Benefits for Beginners:
*   Email Deliverability: Verify if emails are sent successfully
*   Troubleshooting: Diagnose email problems like spam or failed delivery
*   Audit Trail: Maintain record of all outgoing emails for compliance
*   Detailed Information: View sender, recipient, subject, and status of each email

### 4. .htaccess Editor

Function: Safe and convenient way to modify your site's .htaccess file directly from the WordPress dashboard.

Benefits for Beginners:
*   Safe Editing: Edit .htaccess file without FTP access
*   Auto Backup: Automatic backup before changes
*   Security & Performance: Implement security rules and performance optimizations
*   Error Prevention: Built-in safeguards prevent common errors

### 5. PHP Config Presets

Function: Apply common PHP configuration presets for different environments (debugging, development, production).

Benefits for Beginners:
*   Easy Switching: Change PHP configuration with a few clicks
*   Streamlined Debugging: Enable verbose error reporting for debugging
*   Performance Optimized: Apply settings for production sites
*   Environment Management: Switch between development and production

### 6. Performance Monitoring

Function: Provide real-time insights into site performance metrics, often displayed in the WordPress admin bar.

Benefits for Beginners:
*   Real-time Insights: Monitor critical metrics directly from admin bar
*   Issue Detection: Spot performance degradation or resource usage spikes
*   Bottleneck Identification: Understand which areas affect site speed
*   UX Improvement: Ensure fast and responsive website

## FAQ for Beginners

Q: Is this plugin safe for production sites?
A: Yes, this plugin has multiple safety features like auto-backup and validation. However, always test in staging environment first.

Q: How do I read debug logs?
A: Logs contain timestamp, error level, and error message. Use filters to focus on specific errors.

Q: Does the performance bar slow down my site?
A: It only appears for admin users and has minimal impact. Disable if needed.

Q: What if I'm confused about configuration?
A: Start with "Basic" preset and enable debug mode. Read troubleshooting guide for common issues.

Advanced Log Manager is an all-in-one solution for robust and well-maintained WordPress sites. Explore other wiki sections for more in-depth guides and tutorials on each feature.
