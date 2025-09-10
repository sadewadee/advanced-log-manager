/**
 * MT Admin JavaScript
 */

(function($) {
    'use strict';

    /**
     * Toggle debug settings availability
     */
    function toggleDebugSettings(enabled) {
        const $toggleGroup = $('.wpdmgr-toggle-group');
        const $wrappers = $toggleGroup.find('.wpdmgr-toggle-wrapper');
        const $inputs = $wrappers.find('input[type="checkbox"]');
        const $toggles = $wrappers.find('.wpdmgr-toggle');

        if (enabled) {
            $toggleGroup.removeAttr('data-disabled');
            $wrappers.removeClass('disabled');
            $inputs.prop('disabled', false);
        } else {
            $toggleGroup.attr('data-disabled', 'true');
            $wrappers.addClass('disabled');
            $inputs.prop('disabled', true).prop('checked', false);
            $toggles.removeClass('active');
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        initializeTabs();
        initializeToggles();
        initializeDebugActions();
        initializePerfMonitor();
        initializeSmtpLogging();
        initializeHtaccessEditor();
        initializePHPConfig();
        initializeLogsPage();
        initializeAdminBarPerformance();

        // Initialize debug settings state based on master toggle
        const debugEnabled = $('#debug-mode-toggle').is(':checked');
        toggleDebugSettings(debugEnabled);
    });

    /**
     * Initialize tab navigation
     */
    function initializeTabs() {
        $('.wpdmgr-tab-btn').on('click', function() {
            const tabId = $(this).data('tab');

            // Update tab buttons
            $('.wpdmgr-tab-btn').removeClass('active');
            $(this).addClass('active');

            // Update tab contents
            $('.wpdmgr-tab-content').removeClass('active');
            $('#tab-' + tabId).addClass('active');
        });
    }

    /**
     * Initialize debug actions
     */
    function initializeDebugActions() {
        // Debug mode toggle
        $('#debug-mode-toggle').off('change').on('change', function() {
            const enabled = $(this).is(':checked');

            // Enable/disable child toggles immediately for better UX
            toggleDebugSettings(enabled);

            showLoading();

            $.post(wpdmgrToolkit.ajaxurl, {
                action: 'wpdmgr_toggle_debug',
                enabled: enabled,
                nonce: wpdmgrToolkit.nonce
            }, function(response) {
                hideLoading();

                if (response.success) {
                    showNotice(response.data.message, 'success');
                    updateDebugStatus(enabled);
                    // Update visual toggle state
                    const $toggle = $('#debug-mode-toggle').siblings('.wpdmgr-toggle');
                    if (enabled) {
                        $toggle.addClass('active');
                    } else {
                        $toggle.removeClass('active');
                    }
                } else {
                    showNotice(response.data || wpdmgrToolkit.strings.error_occurred, 'error');
                    // Revert toggle state and settings
                    $('#debug-mode-toggle').prop('checked', !enabled);
                    toggleDebugSettings(!enabled);
                    // Revert visual toggle state
                    const $toggle = $('#debug-mode-toggle').siblings('.wpdmgr-toggle');
                    if (!enabled) {
                        $toggle.addClass('active');
                    } else {
                        $toggle.removeClass('active');
                    }
                }
            }).fail(function() {
                hideLoading();
                showNotice(wpdmgrToolkit.strings.error_occurred, 'error');
                // Revert toggle state and settings
                $('#debug-mode-toggle').prop('checked', !enabled);
                toggleDebugSettings(!enabled);
                // Revert visual toggle state
                const $toggle = $('#debug-mode-toggle').siblings('.wpdmgr-toggle');
                if (!enabled) {
                    $toggle.addClass('active');
                } else {
                    $toggle.removeClass('active');
                }
            });
        });
 
         // Add click handler for debug-mode-toggle button
         $('#debug-mode-toggle').siblings('.wpdmgr-toggle').on('click', function() {
             const $checkbox = $(this).siblings('input[type="checkbox"]');
             $checkbox.prop('checked', !$checkbox.is(':checked')).trigger('change');
         });

        // Individual debug constants
        $('#wp-debug-log-toggle, #wp-debug-display-toggle, #script-debug-toggle, #savequeries-toggle, #display-errors-toggle').on('change', function() {
            // Prevent action if master debug is disabled
            if (!$('#debug-mode-toggle').is(':checked')) {
                $(this).prop('checked', false);
                showNotice('Please enable Debug Mode first', 'error');
                return;
            }

            let constantName = $(this).attr('id').replace('-toggle', '').toUpperCase().replace(/\-/g, '_');

            // Special handling for display_errors (it's an ini setting, not a constant)
            if ($(this).attr('id') === 'display-errors-toggle') {
                constantName = 'display_errors';
            }

            const enabled = $(this).is(':checked');
            const $toggle = $(this).siblings('.wpdmgr-toggle');

            // Update visual toggle state immediately
            if (enabled) {
                $toggle.addClass('active');
            } else {
                $toggle.removeClass('active');
            }

            showLoading();

            $.post(wpdmgrToolkit.ajaxurl, {
                action: 'wpdmgr_toggle_debug_constant',
                constant: constantName,
                enabled: enabled,
                nonce: wpdmgrToolkit.nonce
            }, function(response) {
                hideLoading();

                if (response.success) {
                    showNotice(response.data.message, 'success');
                } else {
                    showNotice(response.data || wpdmgrToolkit.strings.error_occurred, 'error');
                    // Revert toggle state
                    $(this).prop('checked', !enabled);
                    // Revert visual toggle state
                    if (!enabled) {
                        $toggle.addClass('active');
                    } else {
                        $toggle.removeClass('active');
                    }
                }
            }).fail(function() {
                hideLoading();
                showNotice(wpdmgrToolkit.strings.error_occurred, 'error');
                $(this).prop('checked', !enabled);
                // Revert visual toggle state
                if (!enabled) {
                    $toggle.addClass('active');
                } else {
                    $toggle.removeClass('active');
                }
            });
        });

        // Add click handlers for debug constant toggle buttons
        $('#wp-debug-log-toggle, #wp-debug-display-toggle, #script-debug-toggle, #savequeries-toggle, #display-errors-toggle, #smtp-logging-toggle').siblings('.wpdmgr-toggle').on('click', function() {
            const $checkbox = $(this).siblings('input[type="checkbox"]');

            // Prevent action if master debug is disabled
            if (!$('#debug-mode-toggle').is(':checked')) {
                showNotice('Please enable Debug Mode first', 'error');
                return;
            }

            // Check if the wrapper is disabled
            if ($(this).closest('.wpdmgr-toggle-wrapper').hasClass('disabled')) {
                return;
            }

            $checkbox.prop('checked', !$checkbox.is(':checked')).trigger('change');
        });

        // Debug logs cleanup handler
        $('#cleanup-debug-logs').on('click', function() {
            const keepCount = $('#debug-cleanup-keep-count').val() || 3;

            if (!confirm('Are you sure you want to cleanup old debug log files? This will keep only the ' + keepCount + ' most recent files.')) {
                return;
            }

            const $button = $(this);
            const originalText = $button.html();
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> Cleaning...');

            $.post(wpdmgrToolkit.ajaxurl, {
                action: 'wpdmgr_cleanup_debug_logs',
                keep_count: keepCount,
                nonce: wpdmgrToolkit.nonce
            }, function(response) {
                $button.prop('disabled', false).html(originalText);

                if (response.success) {
                    showNotice(response.data, 'success');
                } else {
                    showNotice(response.data || 'Failed to cleanup debug logs', 'error');
                }
            }).fail(function() {
                $button.prop('disabled', false).html(originalText);
                showNotice('Network error occurred during cleanup', 'error');
            });
        });

        // Clear all debug logs handler (except active)
        $('#clear-all-debug-logs').on('click', function() {
            if (!confirm('Are you sure you want to clear all wp-errors-* log files except the currently active one? This action cannot be undone.')) {
                return;
            }

            const $button = $(this);
            const originalText = $button.html();
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> Clearing...');

            $.post(wpdmgrToolkit.ajaxurl, {
                action: 'wpdmgr_clear_all_debug_logs',
                nonce: wpdmgrToolkit.nonce
            }, function(response) {
                $button.prop('disabled', false).html(originalText);

                if (response.success) {
                    showNotice(response.data, 'success');
                } else {
                    showNotice(response.data || 'Failed to clear debug logs', 'error');
                }
            }).fail(function() {
                $button.prop('disabled', false).html(originalText);
                showNotice('Network error occurred during cleanup', 'error');
            });
        });

        // All logs cleanup handler
        $('#cleanup-all-logs').on('click', function() {
            const includeCurrent = $('#include-current-logs').is(':checked');
            const warningText = includeCurrent ?
                'Are you sure you want to remove ALL log files? This will delete all debug and query logs including current active logs. This action cannot be undone!' :
                'Are you sure you want to cleanup old log files? This will remove old debug and query logs but keep current active logs.';

            if (!confirm(warningText)) {
                return;
            }

            const $button = $(this);
            const originalText = $button.html();
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> Removing...');

            $.post(wpdmgrToolkit.ajaxurl, {
                action: 'wpdmgr_cleanup_all_logs',
                include_current: includeCurrent,
                nonce: wpdmgrToolkit.nonce
            }, function(response) {
                $button.prop('disabled', false).html(originalText);

                if (response.success) {
                    showNotice(response.data, 'success');
                } else {
                    showNotice(response.data || 'Failed to cleanup logs', 'error');
                }
            }).fail(function() {
                $button.prop('disabled', false).html(originalText);
                showNotice('Network error occurred during cleanup', 'error');
            });
        });

        // Query rotation logs cleanup handler
        $('#cleanup-query-rotation-logs').on('click', function() {
            const keepLatest = $('#keep-latest-rotation').is(':checked');
            const warningText = keepLatest ?
                'Are you sure you want to cleanup old query rotation files? The latest backup (query.log.1) will be preserved.' :
                'Are you sure you want to cleanup ALL query rotation files? This action cannot be undone!';

            if (!confirm(warningText)) {
                return;
            }

            const $button = $(this);
            const originalText = $button.html();
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> Cleaning...');

            $.post(wpdmgrToolkit.ajaxurl, {
                action: 'wpdmgr_cleanup_query_rotation_logs',
                keep_latest: keepLatest,
                nonce: wpdmgrToolkit.nonce
            }, function(response) {
                $button.prop('disabled', false).html(originalText);

                if (response.success) {
                    showNotice(response.data, 'success');
                } else {
                    showNotice(response.data || 'Failed to cleanup rotation logs', 'error');
                }
            }).fail(function() {
                $button.prop('disabled', false).html(originalText);
                showNotice('Network error occurred during cleanup', 'error');
            });
        });
    }

    /**
     * Initialize toggle switches (excluding those with specific handlers)
     */
    function initializeToggles() {
        // Exclude toggles that have specific handlers to prevent double execution
        const excludeSelectors = '#debug-mode-toggle, #wp-debug-log-toggle, #wp-debug-display-toggle, #script-debug-toggle, #savequeries-toggle, #display-errors-toggle, #perf-monitor-toggle, #smtp-logging-toggle, #smtp-ip-logging-toggle';

        $('.wpdmgr-toggle-wrapper input[type="checkbox"]').not(excludeSelectors).on('change', function() {
            const $toggle = $(this).siblings('.wpdmgr-toggle');
            if ($(this).is(':checked')) {
                $toggle.addClass('active');
            } else {
                $toggle.removeClass('active');
            }
        });

        $('.wpdmgr-toggle').not(excludeSelectors + ' + .wpdmgr-toggle').on('click', function() {
            const $checkbox = $(this).siblings('input[type="checkbox"]');
            if (!$checkbox.is(excludeSelectors)) {
                $checkbox.prop('checked', !$checkbox.is(':checked')).trigger('change');
            }
        });
    }

    /**
     * Initialize SMTP logging actions
     */
    function initializeSmtpLogging() {
        $('#smtp-logging-toggle').off('change').on('change', function() {
            const enabled = $(this).is(':checked');

            showLoading();

            $.post(wpdmgrToolkit.ajaxurl, {
                action: 'wpdmgr_toggle_smtp_logging',
                enabled: enabled,
                nonce: wpdmgrToolkit.nonce
            }, function(response) {
                hideLoading();

                if (response.success) {
                    showNotice(response.data.message, 'success');
                    // Update visual toggle state
                    const $toggle = $('#smtp-logging-toggle').siblings('.wpdmgr-toggle');
                    if (enabled) {
                        $toggle.addClass('active');
                        // Enable IP logging toggle
                        $('#smtp-ip-logging-toggle').closest('.wpdmgr-toggle-wrapper').removeClass('disabled');
                        $('#smtp-ip-logging-toggle').prop('disabled', false);
                    } else {
                        $toggle.removeClass('active');
                        // Disable IP logging toggle
                        $('#smtp-ip-logging-toggle').closest('.wpdmgr-toggle-wrapper').addClass('disabled');
                        $('#smtp-ip-logging-toggle').prop('disabled', true).prop('checked', false);
                        $('#smtp-ip-logging-toggle').siblings('.wpdmgr-toggle').removeClass('active');
                    }
                } else {
                    showNotice(response.data || wpdmgrToolkit.strings.error_occurred, 'error');
                    $('#smtp-logging-toggle').prop('checked', !enabled);
                    // Revert visual toggle state
                    const $toggle = $('#smtp-logging-toggle').siblings('.wpdmgr-toggle');
                    if (!enabled) {
                        $toggle.addClass('active');
                    } else {
                        $toggle.removeClass('active');
                    }
                }
            }).fail(function() {
                hideLoading();
                showNotice(wpdmgrToolkit.strings.error_occurred, 'error');
                // Revert toggle state
                $('#smtp-logging-toggle').prop('checked', !enabled);
                // Revert visual toggle state
                const $toggle = $('#smtp-logging-toggle').siblings('.wpdmgr-toggle');
                if (!enabled) {
                    $toggle.addClass('active');
                } else {
                    $toggle.removeClass('active');
                }
            });
        });

        // IP address logging toggle
        $('#smtp-ip-logging-toggle').off('change').on('change', function() {
            const enabled = $(this).is(':checked');

            showLoading();

            $.post(wpdmgrToolkit.ajaxurl, {
                action: 'wpdmgr_toggle_smtp_ip_logging',
                enabled: enabled,
                nonce: wpdmgrToolkit.nonce
            }, function(response) {
                hideLoading();

                if (response.success) {
                    showNotice(response.data.message, 'success');
                    // Update visual toggle state
                    const $toggle = $('#smtp-ip-logging-toggle').siblings('.wpdmgr-toggle');
                    if (enabled) {
                        $toggle.addClass('active');
                    } else {
                        $toggle.removeClass('active');
                    }
                } else {
                    showNotice(response.data || wpdmgrToolkit.strings.error_occurred, 'error');
                    $('#smtp-ip-logging-toggle').prop('checked', !enabled);
                    // Revert visual toggle state
                    const $toggle = $('#smtp-ip-logging-toggle').siblings('.wpdmgr-toggle');
                    if (!enabled) {
                        $toggle.addClass('active');
                    } else {
                        $toggle.removeClass('active');
                    }
                }
            }).fail(function() {
                hideLoading();
                showNotice(wpdmgrToolkit.strings.error_occurred, 'error');
                // Revert toggle state
                $('#smtp-ip-logging-toggle').prop('checked', !enabled);
                // Revert visual toggle state
                const $toggle = $('#smtp-ip-logging-toggle').siblings('.wpdmgr-toggle');
                if (!enabled) {
                    $toggle.addClass('active');
                } else {
                    $toggle.removeClass('active');
                }
            });
        });

        // Add click handler for SMTP IP logging visual toggle
        $('#smtp-ip-logging-toggle').siblings('.wpdmgr-toggle').on('click', function() {
            const $checkbox = $(this).siblings('input[type="checkbox"]');

            // Prevent action if master debug is disabled
            if (!$('#debug-mode-toggle').is(':checked')) {
                showNotice('Please enable Debug Mode first', 'error');
                return;
            }

            // Check if SMTP logging is enabled
            if (!$('#smtp-logging-toggle').is(':checked')) {
                showNotice('Please enable SMTP Logging first', 'error');
                return;
            }

            // Check if the wrapper is disabled
            if ($(this).closest('.wpdmgr-toggle-wrapper').hasClass('disabled')) {
                return;
            }

            $checkbox.prop('checked', !$checkbox.is(':checked')).trigger('change');
        });
    }

    /**
     * Initialize query monitor actions
     */
    function initializePerfMonitor() {
        $('#perf-monitor-toggle').off('change').on('change', function() {
            const enabled = $(this).is(':checked');

            showLoading();

            $.post(wpdmgrToolkit.ajaxurl, {
                action: 'wpdmgr_toggle_perf_monitor',
                enabled: enabled,
                nonce: wpdmgrToolkit.nonce
            }, function(response) {
                hideLoading();

                if (response.success) {
                    showNotice(response.data.message, 'success');
                    // Update visual toggle state
                    const $toggle = $('#perf-monitor-toggle').siblings('.wpdmgr-toggle');
                    if (enabled) {
                        $toggle.addClass('active');
                    } else {
                        $toggle.removeClass('active');
                    }
                } else {
                    showNotice(response.data || wpdmgrToolkit.strings.error_occurred, 'error');
                    $('#perf-monitor-toggle').prop('checked', !enabled);
                    // Revert visual toggle state
                    const $toggle = $('#perf-monitor-toggle').siblings('.wpdmgr-toggle');
                    if (!enabled) {
                        $toggle.addClass('active');
                    } else {
                        $toggle.removeClass('active');
                    }
                }
            });
        });

        // Add click handler for perf-monitor visual toggle (prevent double toggle under label)
        $('#perf-monitor-toggle').siblings('.wpdmgr-toggle').off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            // Delegate to native checkbox click to ensure a single change event
            $(this).siblings('input[type="checkbox"]').trigger('click');
        });
    }

    /**
     * Initialize admin bar performance toggle
     */
    function initializeAdminBarPerformance() {
        // Handle admin bar performance metrics clicks
        $(document).on('click', '#wp-admin-bar-wpdmgr-performance-monitor .ab-item, .wpdmgr-admin-perf-toggle', function(e) {
            e.preventDefault();

            const $detailsPanel = $('#wpdmgr-perf-details');

            if ($detailsPanel.length) {
                if ($detailsPanel.is(':visible')) {
                    $detailsPanel.fadeOut(200);
                } else {
                    $detailsPanel.fadeIn(200);
                }
            }

            return false;
        });

        // Handle performance tab clicks
        $(document).on('click', '.wpdmgr-perf-tab', function(e) {
            e.preventDefault();
            const tabName = $(this).data('tab');

            // Remove active class from all tabs
            $('.wpdmgr-perf-tab').removeClass('active');
            $('.wpdmgr-perf-tab-content').removeClass('active');

            // Add active class to selected tab and content
            $(this).addClass('active');
            $('#wpdmgr-perf-tab-' + tabName).addClass('active');

            return false;
        });

        // Close details panel when clicking outside
        $(document).on('click', function(e) {
            const $detailsPanel = $('#wpdmgr-perf-details');

            if ($detailsPanel.length && $detailsPanel.is(':visible')) {
                if (!$(e.target).closest('#wpdmgr-perf-details, #wp-admin-bar-wpdmgr-performance-monitor, .wpdmgr-admin-perf-toggle').length) {
                    $detailsPanel.fadeOut(200);
                }
            }
        });

        // Close details panel on Escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                const $detailsPanel = $('#wpdmgr-perf-details');
                if ($detailsPanel.length && $detailsPanel.is(':visible')) {
                    $detailsPanel.fadeOut(200);
                }
            }
        });
    }

    /**
     * Initialize .htaccess editor
     */
    function initializeHtaccessEditor() {
        let originalContent = $('#htaccess-editor').val();

        // Save .htaccess
        $('#save-htaccess').on('click', function() {
            const content = $('#htaccess-editor').val();

            showLoading();

            $.post(wpdmgrToolkit.ajaxurl, {
                action: 'wpdmgr_save_htaccess',
                content: content,
                nonce: wpdmgrToolkit.nonce
            }, function(response) {
                hideLoading();

                if (response.success) {
                    showNotice(response.data, 'success');
                    originalContent = content;
                    // Refresh page to update backup info
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotice(response.data || wpdmgrToolkit.strings.error_occurred, 'error');
                }
            });
        });

        // Restore .htaccess
        $('.wpdmgr-restore-item').on('click', function(e) {
            e.preventDefault();

            if (!confirm(wpdmgrToolkit.strings.confirm_restore_htaccess)) {
                return;
            }

            const backupIndex = $(this).data('index');

            showLoading();

            $.post(wpdmgrToolkit.ajaxurl, {
                action: 'wpdmgr_restore_htaccess',
                backup_index: backupIndex,
                nonce: wpdmgrToolkit.nonce
            }, function(response) {
                hideLoading();

                if (response.success) {
                    showNotice(response.data, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotice(response.data || wpdmgrToolkit.strings.error_occurred, 'error');
                }
            });
        });

        // Cancel changes
        $('#cancel-htaccess').on('click', function() {
            $('#htaccess-editor').val(originalContent);
        });

        // Insert snippets
        $('.wpdmgr-snippet-btn').on('click', function() {
            const snippet = $(this).data('snippet');
            const $editor = $('#htaccess-editor');
            const currentContent = $editor.val();

            if (currentContent && !currentContent.endsWith('\n')) {
                $editor.val(currentContent + '\n\n' + snippet);
            } else {
                $editor.val(currentContent + snippet);
            }
        });
    }

    /**
     * Initialize PHP config actions
     */
    function initializePHPConfig() {
        // Preset selection
        $('input[name="php_preset"]').on('change', function() {
            $('.wpdmgr-preset-option').removeClass('selected');
            $(this).closest('.wpdmgr-preset-option').addClass('selected');
        });

        // Apply configuration
        $('#apply-php-preset').on('click', function() {
            const preset = $('input[name="php_preset"]:checked').val();

            if (!preset) {
                showNotice('Please select a preset first.', 'error');
                return;
            }

            showLoading();

            $.post(wpdmgrToolkit.ajaxurl, {
                action: 'wpdmgr_apply_php_preset',
                preset: preset,
                nonce: wpdmgrToolkit.nonce
            }, function(response) {
                hideLoading();

                if (response.success) {
                    showNotice(response.data, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showNotice(response.data || wpdmgrToolkit.strings.error_occurred, 'error');
                }
            });
        });
    }

    /**
     * Validate setting value
     */
    function validateSettingValue(setting, value) {
        const numValue = parseInt(value);

        if (isNaN(numValue) || numValue <= 0) {
            return false;
        }

        // Setting-specific validation
        switch (setting) {
            case 'memory_limit':
                return numValue >= 64 && numValue <= 8192; // 64M to 8GB
            case 'upload_max_filesize':
                return numValue >= 1 && numValue <= 1024; // 1M to 1GB
            case 'post_max_size':
                return numValue >= 1 && numValue <= 2048; // 1M to 2GB
            case 'max_execution_time':
                return numValue >= 30 && numValue <= 3600; // 30s to 1 hour
            case 'max_input_vars':
                return numValue >= 1000 && numValue <= 50000; // 1K to 50K
            case 'max_input_time':
                return numValue >= 30 && numValue <= 3600; // 30s to 1 hour
            default:
                return true;
        }
    }

    /**
     * Initialize logs page functionality
     */
    function initializeLogsPage() {
        if (!$('#wpdmgr-logs-viewer').length) {
            return;
        }

        // Refresh logs
        $('#refresh-logs').on('click', function() {
            loadDebugLogs();
        });

        // Clear logs
        $('#clear-logs').on('click', function() {
            if (!confirm(wpdmgrToolkit.strings.confirm_clear_logs)) {
                return;
            }

            showLoading();

            $.post(wpdmgrToolkit.ajaxurl, {
                action: 'wpdmgr_clear_debug_log',
                nonce: wpdmgrToolkit.nonce
            }, function(response) {
                hideLoading();

                if (response.success) {
                    showNotice(response.data, 'success');
                    $('#wpdmgr-logs-content').html('<div class="wpdmgr-no-logs-message"><p>No log entries found.</p></div>');
                } else {
                    showNotice(response.data || wpdmgrToolkit.strings.error_occurred, 'error');
                }
            });
        });

        // Download logs
        $('#download-logs').on('click', function() {
            // Create download link
            const downloadUrl = wpdmgrToolkit.ajaxurl + '?action=wpdmgr_download_logs&nonce=' + wpdmgrToolkit.nonce;
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = 'debug-logs-' + new Date().toISOString().slice(0, 10) + '.txt';
            link.click();
        });

        // Filter logs
        $('#log-level-filter, #log-time-filter').on('change', function() {
            filterLogs();
        });

        $('#log-search').on('input', debounce(function() {
            filterLogs();
        }, 300));
    }

    /**
     * Load debug logs
     */
    function loadDebugLogs() {
        const $logsContent = $('#wpdmgr-logs-content');
        const $logsLoading = $('#wpdmgr-logs-viewer .wpdmgr-logs-loading');

        if (!$logsContent.length) return;

        $logsLoading.show();
        $logsContent.hide();

        $.post(wpdmgrToolkit.ajaxurl, {
            action: 'wpdmgr_get_debug_log',
            nonce: wpdmgrToolkit.nonce
        }, function(response) {
            $logsLoading.hide();

            if (response.success && response.data) {
                displayLogs(response.data);
                $logsContent.show();
            } else {
                $logsContent.html('<div class="notice notice-error"><p>' + wpdmgrToolkit.strings.error_occurred + '</p></div>');
                $logsContent.show();
            }
        });
    }

    /**
     * Display logs in the viewer
     */
    function displayLogs(logs) {
        const $logsContent = $('#wpdmgr-logs-content');

        if (!logs || logs.length === 0) {
            $logsContent.html('<div class="wpdmgr-no-logs-message"><p>No log entries found.</p></div>');
            return;
        }

        let html = '<div class="wpdmgr-logs-list">';

        logs.forEach(function(log) {
            const levelClass = 'log-level-' + log.level.toLowerCase();
            const timeFormatted = new Date(log.timestamp).toLocaleString();

            html += '<div class="wpdmgr-log-entry ' + levelClass + '">';
            html += '<div class="log-header">';
            html += '<span class="log-level">' + log.level + '</span>';
            html += '<span class="log-time">' + timeFormatted + '</span>';
            html += '</div>';
            html += '<div class="log-message">' + escapeHtml(log.message) + '</div>';

            if (log.file) {
                html += '<div class="log-file">';
                html += '<span class="dashicons dashicons-media-code"></span>';
                html += escapeHtml(log.file);
                if (log.line) {
                    html += ':' + log.line;
                }
                html += '</div>';
            }
            html += '</div>';
        });

        html += '</div>';

        $logsContent.html(html);
    }

    /**
     * Filter logs based on level, time and search
     */
    function filterLogs() {
        const level = $('#log-level-filter').val();
        const timeFilter = $('#log-time-filter').val();
        const searchTerm = $('#log-search').val().toLowerCase();

        $('.wpdmgr-log-entry').each(function() {
            let show = true;

            // Level filter
            if (level && !$(this).hasClass('log-level-' + level.toLowerCase())) {
                show = false;
            }

            // Search filter
            if (searchTerm && $(this).text().toLowerCase().indexOf(searchTerm) === -1) {
                show = false;
            }

            // Time filter would require more complex logic with actual timestamps

            $(this).toggle(show);
        });
    }

    /**
     * Update debug status display
     */
    function updateDebugStatus(enabled) {
        const $indicator = $('.wpdmgr-status-indicator');
        const $statusText = $('.wpdmgr-status-item span:last-child');

        if (enabled) {
            $indicator.removeClass('inactive').addClass('active');
            $statusText.text('Status: Debug Enabled');
        } else {
            $indicator.removeClass('active').addClass('inactive');
            $statusText.text('Status: Debug Disabled');
        }
    }

    /**
     * Show loading overlay
     */
    function showLoading() {
        $('#wpdmgr-loading-overlay').show();
    }

    /**
     * Hide loading overlay
     */
    function hideLoading() {
        $('#wpdmgr-loading-overlay').hide();
    }

    /**
     * Show notice message
     */
    function showNotice(message, type) {
        type = type || 'info';

        const noticeClass = 'notice notice-' + type;
        const $notice = $('<div class="' + noticeClass + ' is-dismissible"><p>' + message + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss</span></button></div>');

        // Insert after page title
        $('.wrap h1').after($notice);

        // Auto dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);

        // Make dismissible
        $notice.find('.notice-dismiss').on('click', function() {
            $notice.remove();
        });
    }

    // Expose showNotice globally for other scripts
    window.wpdmgrShowNotice = showNotice;

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = function() {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Expose utility functions globally for other scripts
    window.wpdmgrUtils = {
        escapeHtml: escapeHtml,
        debounce: debounce
    };

})(jQuery);
