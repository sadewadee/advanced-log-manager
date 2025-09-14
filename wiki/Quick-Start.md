# Quick Start

5-minute guide to get started with Advanced Log Manager. Suitable for beginners who are new to WordPress debugging plugins.

## Initial Setup Checklist

- [ ] Plugin is installed and active
- [ ] Plugin dashboard is accessible
- [ ] Debug mode is successfully enabled
- [ ] Performance bar is visible on frontend
- [ ] Logs are successfully recorded

## Step-by-Step Setup

### Step 1: Plugin Activation
1. Login to WordPress Admin Dashboard
2. Go to Plugins → Installed Plugins
3. Find "Advanced Log Manager"
4. Click Activate if not already active

### Step 2: Access Main Dashboard
1. In the left menu, click Tools → Advance Log Manager
2. You will see the dashboard with system status:
   - Debug Mode status
   - Performance Monitor status
   - Log file sizes
   - System overview

### Step 3: Enable Debug Mode
1. In the main dashboard, find the large "Enable Debug Mode" button
2. Click the button
3. The plugin will automatically:
   - Enable WP_DEBUG in wp-config.php
   - Enable WP_DEBUG_LOG
   - Start recording errors to debug.log
4. Status will change to "Active"

### Step 4: Test Performance Monitoring
1. Click the "Performance Monitor" tab
2. Enable the "Enable Performance Bar" toggle
3. Open a new browser tab and visit your site's frontend page
4. Login as an administrator
5. You will see a black bar at the bottom of the page with metrics:
   - Load time
   - Number of queries
   - Memory usage

### Step 5: Generate and View First Logs
1. Perform some actions on the site to generate logs:
   - Visit a non-existent page (404 error)
   - Try logging in with wrong password
   - Access admin pages
2. Return to Advanced Log Manager dashboard
3. Click the "Debug Log" or "Query Log" card
4. You will see the latest log entries

## Understanding Dashboard Interface

### System Overview Cards
- Debug Mode: Active/inactive debug logging status
- Performance Monitor: Performance monitoring status
- Debug Log: Log file size and status
- Query Log: Query logging status
- SMTP Logs: Email logging status

### Feature Cards
- Debug Management: Debug settings control
- Performance Monitor: Monitoring configuration
- .htaccess Editor: Server config file editing
- PHP Config: PHP configuration presets

## Setup Troubleshooting

### If Debug Mode Does Not Activate
- Ensure wp-config.php is writable
- Check file system permissions
- Try manual wp-config.php editing

### If Performance Bar Does Not Appear
- Ensure you are logged in as administrator
- Clear browser cache
- Check browser console for JavaScript errors

### If Logs Are Not Recorded
- Verify debug mode is active
- Check wp-content folder permissions
- Ensure there are activities that generate logs

## Tips for Beginners

### Development vs Production Mode
- Development: Enable all debug features
- Staging: Test with debug mode active
- Production: Minimal logging, monitor performance

### Backup Before Changes
- Always backup wp-config.php before editing
- Backup .htaccess before using editor
- Test changes in staging environment first

### Regular Monitoring
- Check logs daily in development
- Monitor performance metrics weekly
- Review error logs before deployment

## Next Steps

After basic setup is complete:

1. Learn Log Filtering: Use filters to find specific errors
2. Configure .htaccess: Add security headers and caching rules
3. Setup PHP Presets: Choose preset according to environment
4. Monitor Performance: Use insights for optimization
5. Read Complete Documentation: Explore wiki for advanced features

## Need Help?

If you encounter issues:
1. Check Troubleshooting Guide in wiki
2. Ensure plugin is latest version
3. Check support forum or documentation
4. Contact developer if issues persist

Congratulations! You have successfully set up Advanced Log Manager. This plugin will help you maintain a healthier and more performant WordPress site.
