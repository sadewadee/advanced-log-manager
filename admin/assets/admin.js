/**
 * MT Admin JavaScript
 */

(function($) {
    'use strict';

    /**
     * Toggle debug settings availability
     */
    function toggleDebugSettings(enabled) {
        const $toggleGroup = $('.almgr-toggle-group');
        const $wrappers = $toggleGroup.find('.almgr-toggle-wrapper');
        const $inputs = $wrappers.find('input[type="checkbox"]');
        const $toggles = $wrappers.find('.almgr-toggle');

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
        // Performance bar is handled by performance-bar.js to avoid conflicts

        // Initialize debug settings state based on master toggle
        const debugEnabled = $('#debug-mode-toggle').is(':checked');
        toggleDebugSettings(debugEnabled);

        // Set flag to indicate admin.js is initialized
        window.almgrAdminInitialized = true;

        // Initialize collapsible sections
        initializeCollapsibleSections();

        // Initialize card toggle buttons
        initializeCardToggleButtons();

        // Initialize clickable status cards
        initializeClickableStatusCards();

        // Initialize performance monitor
        initializePerformanceMonitor();
    });

    /**
     * Initialize tab navigation - menggunakan shared utility
     */
    function initializeTabs() {
        // Gunakan shared utility untuk tab navigation
        if (window.ALMGRSharedUtils && window.ALMGRSharedUtils.initializeTabs) {
            window.ALMGRSharedUtils.initializeTabs({
                tabSelector: '.almgr-tab-btn',
                contentSelector: '.almgr-tab-content',
                useJQuery: true
            });
        } else {
            // Fallback jika shared utils belum loaded
            $('.almgr-tab-btn').on('click', function() {
                const tabId = $(this).data('tab');
                $('.almgr-tab-btn').removeClass('active');
                $(this).addClass('active');
                $('.almgr-tab-content').removeClass('active');
                $('#tab-' + tabId).addClass('active');
            });
        }
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

            $.post(almgrToolkit.ajaxurl, {
                action: 'almgr_toggle_debug',
                enabled: enabled,
                nonce: almgrToolkit.nonce
            }, function(response) {
                hideLoading();

                if (response.success) {
                    showNotice(response.data.message, 'success');
                    updateDebugStatus(enabled);
                    // Update visual toggle state
                    const $toggle = $('#debug-mode-toggle').siblings('.almgr-toggle');
                    if (enabled) {
                        $toggle.addClass('active');
                    } else {
                        $toggle.removeClass('active');
                    }
                } else {
                    showNotice(response.data || almgrToolkit.strings.error_occurred, 'error');
                    // Revert toggle state and settings
                    $('#debug-mode-toggle').prop('checked', !enabled);
                    toggleDebugSettings(!enabled);
                    // Revert visual toggle state
                    const $toggle = $('#debug-mode-toggle').siblings('.almgr-toggle');
                    if (!enabled) {
                        $toggle.addClass('active');
                    } else {
                        $toggle.removeClass('active');
                    }
                }
            }).fail(function() {
                hideLoading();
                showNotice(almgrToolkit.strings.error_occurred, 'error');
                // Revert toggle state and settings
                $('#debug-mode-toggle').prop('checked', !enabled);
                toggleDebugSettings(!enabled);
                // Revert visual toggle state
                const $toggle = $('#debug-mode-toggle').siblings('.almgr-toggle');
                if (!enabled) {
                    $toggle.addClass('active');
                } else {
                    $toggle.removeClass('active');
                }
            });
        });

         // Add click handler for debug-mode-toggle button
         $('#debug-mode-toggle').siblings('.almgr-toggle').on('click', function() {
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
            const $toggle = $(this).siblings('.almgr-toggle');

            // Update visual toggle state immediately
            if (enabled) {
                $toggle.addClass('active');
            } else {
                $toggle.removeClass('active');
            }

            showLoading();

            $.post(almgrToolkit.ajaxurl, {
                action: 'almgr_toggle_debug_constant',
                constant: constantName,
                enabled: enabled,
                nonce: almgrToolkit.nonce
            }, function(response) {
                hideLoading();

                if (response.success) {
                    showNotice(response.data.message, 'success');
                } else {
                    showNotice(response.data || almgrToolkit.strings.error_occurred, 'error');
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
                showNotice(almgrToolkit.strings.error_occurred, 'error');
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
        $('#wp-debug-log-toggle, #wp-debug-display-toggle, #script-debug-toggle, #savequeries-toggle, #display-errors-toggle, #smtp-logging-toggle').siblings('.almgr-toggle').on('click', function() {
            const $checkbox = $(this).siblings('input[type="checkbox"]');

            // Prevent action if master debug is disabled
            if (!$('#debug-mode-toggle').is(':checked')) {
                showNotice('Please enable Debug Mode first', 'error');
                return;
            }

            // Check if the wrapper is disabled
            if ($(this).closest('.almgr-toggle-wrapper').hasClass('disabled')) {
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

            $.post(almgrToolkit.ajaxurl, {
                action: 'almgr_cleanup_debug_logs',
                keep_count: keepCount,
                nonce: almgrToolkit.nonce
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

            $.post(almgrToolkit.ajaxurl, {
                action: 'almgr_clear_all_debug_logs',
                nonce: almgrToolkit.nonce
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

            $.post(almgrToolkit.ajaxurl, {
                action: 'almgr_cleanup_all_logs',
                include_current: includeCurrent,
                nonce: almgrToolkit.nonce
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

            $.post(almgrToolkit.ajaxurl, {
                action: 'almgr_cleanup_query_rotation_logs',
                keep_latest: keepLatest,
                nonce: almgrToolkit.nonce
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
        const excludeSelectors = '#debug-mode-toggle, #wp-debug-log-toggle, #wp-debug-display-toggle, #script-debug-toggle, #savequeries-toggle, #display-errors-toggle, #perf-monitor-toggle, #smtp-logging-toggle, #smtp-ip-logging-toggle, #perf-realtime-toggle, #perf-bootstrap-toggle, #perf-domains-toggle';

        $('.almgr-toggle-wrapper input[type="checkbox"]').not(excludeSelectors).on('change', function() {
            const $toggle = $(this).siblings('.almgr-toggle');
            if ($(this).is(':checked')) {
                $toggle.addClass('active');
            } else {
                $toggle.removeClass('active');
            }
        });

        $('.almgr-toggle').not(excludeSelectors + ' + .almgr-toggle').on('click', function() {
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

            $.post(almgrToolkit.ajaxurl, {
                action: 'almgr_toggle_smtp_logging',
                enabled: enabled,
                nonce: almgrToolkit.nonce
            }, function(response) {
                hideLoading();

                if (response.success) {
                    showNotice(response.data.message, 'success');
                    // Update visual toggle state
                    const $toggle = $('#smtp-logging-toggle').siblings('.almgr-toggle');
                    if (enabled) {
                        $toggle.addClass('active');
                        // Enable IP logging toggle
                        $('#smtp-ip-logging-toggle').closest('.almgr-toggle-wrapper').removeClass('disabled');
                        $('#smtp-ip-logging-toggle').prop('disabled', false);
                    } else {
                        $toggle.removeClass('active');
                        // Disable IP logging toggle
                        $('#smtp-ip-logging-toggle').closest('.almgr-toggle-wrapper').addClass('disabled');
                        $('#smtp-ip-logging-toggle').prop('disabled', true).prop('checked', false);
                        $('#smtp-ip-logging-toggle').siblings('.almgr-toggle').removeClass('active');
                    }
                } else {
                    showNotice(response.data || almgrToolkit.strings.error_occurred, 'error');
                    $('#smtp-logging-toggle').prop('checked', !enabled);
                    // Revert visual toggle state
                    const $toggle = $('#smtp-logging-toggle').siblings('.almgr-toggle');
                    if (!enabled) {
                        $toggle.addClass('active');
                    } else {
                        $toggle.removeClass('active');
                    }
                }
            }).fail(function() {
                hideLoading();
                showNotice(almgrToolkit.strings.error_occurred, 'error');
                // Revert toggle state
                $('#smtp-logging-toggle').prop('checked', !enabled);
                // Revert visual toggle state
                const $toggle = $('#smtp-logging-toggle').siblings('.almgr-toggle');
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

            $.post(almgrToolkit.ajaxurl, {
                action: 'almgr_toggle_smtp_ip_logging',
                enabled: enabled,
                nonce: almgrToolkit.nonce
            }, function(response) {
                hideLoading();

                if (response.success) {
                    showNotice(response.data.message, 'success');
                    // Update visual toggle state
                    const $toggle = $('#smtp-ip-logging-toggle').siblings('.almgr-toggle');
                    if (enabled) {
                        $toggle.addClass('active');
                    } else {
                        $toggle.removeClass('active');
                    }
                } else {
                    showNotice(response.data || almgrToolkit.strings.error_occurred, 'error');
                    $('#smtp-ip-logging-toggle').prop('checked', !enabled);
                    // Revert visual toggle state
                    const $toggle = $('#smtp-ip-logging-toggle').siblings('.almgr-toggle');
                    if (!enabled) {
                        $toggle.addClass('active');
                    } else {
                        $toggle.removeClass('active');
                    }
                }
            }).fail(function() {
                hideLoading();
                showNotice(almgrToolkit.strings.error_occurred, 'error');
                // Revert toggle state
                $('#smtp-ip-logging-toggle').prop('checked', !enabled);
                // Revert visual toggle state
                const $toggle = $('#smtp-ip-logging-toggle').siblings('.almgr-toggle');
                if (!enabled) {
                    $toggle.addClass('active');
                } else {
                    $toggle.removeClass('active');
                }
            });
        });

        // Add click handler for SMTP IP logging visual toggle
        $('#smtp-ip-logging-toggle').siblings('.almgr-toggle').on('click', function() {
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
            if ($(this).closest('.almgr-toggle-wrapper').hasClass('disabled')) {
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

            // Confirmation prompt
            const msg = enabled ? (almgrToolkit.strings && almgrToolkit.strings.confirm_enable_perf ? almgrToolkit.strings.confirm_enable_perf : 'Are you sure to enable Performance Bar?')
                                : (almgrToolkit.strings && almgrToolkit.strings.confirm_disable_perf ? almgrToolkit.strings.confirm_disable_perf : 'Are you sure to disable Performance Bar?');
            if (!confirm(msg)) {
                // Revert immediately if canceled
                $(this).prop('checked', !enabled);
                return;
            }

            showLoading();

            $.post(almgrToolkit.ajaxurl, {
                action: 'almgr_toggle_perf_monitor',
                enabled: enabled,
                nonce: almgrToolkit.nonce
            }, function(response) {
                hideLoading();

                if (response.success) {
                    showNotice(response.data.message, 'success');
                    // Update visual toggle state
                    const $toggle = $('#perf-monitor-toggle').siblings('.almgr-toggle');
                    if (enabled) {
                        $toggle.addClass('active');
                        // Enable granular toggles
                        $('#perf-realtime-toggle, #perf-bootstrap-toggle, #perf-domains-toggle').each(function(){
                            $(this).closest('.almgr-toggle-wrapper').removeClass('disabled');
                            $(this).prop('disabled', false);
                        });
                    } else {
                        $toggle.removeClass('active');
                        // Disable and reset granular toggles
                        $('#perf-realtime-toggle, #perf-bootstrap-toggle, #perf-domains-toggle').each(function(){
                            $(this).closest('.almgr-toggle-wrapper').addClass('disabled');
                            $(this).prop('disabled', true).prop('checked', false);
                            $(this).siblings('.almgr-toggle').removeClass('active');
                        });
                    }
                } else {
                    showNotice(response.data || almgrToolkit.strings.error_occurred, 'error');
                    $('#perf-monitor-toggle').prop('checked', !enabled);
                    // Revert visual toggle state
                    const $toggle = $('#perf-monitor-toggle').siblings('.almgr-toggle');
                    if (!enabled) {
                        $toggle.addClass('active');
                    } else {
                        $toggle.removeClass('active');
                    }
                }
            }).fail(function(){
                hideLoading();
                showNotice(almgrToolkit.strings.error_occurred, 'error');
                $('#perf-monitor-toggle').prop('checked', !enabled);
                const $toggle = $('#perf-monitor-toggle').siblings('.almgr-toggle');
                if (!enabled) { $toggle.addClass('active'); } else { $toggle.removeClass('active'); }
            });

            // Add click handler for perf-monitor visual toggle (prevent double toggle under label)
            $('#perf-monitor-toggle').siblings('.almgr-toggle').off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                // Delegate to native checkbox click to ensure a single change event
                $(this).siblings('input[type="checkbox"]').trigger('click');
            });

            // Granular toggles handlers
            $('#perf-realtime-toggle').off('change').on('change', function(){
                if (!$('#perf-monitor-toggle').is(':checked')) {
                    $(this).prop('checked', false);
                    showNotice('Please enable Performance Bar first', 'error');
                    return;
                }
                const enabled = $(this).is(':checked');
                showLoading();
                $.post(almgrToolkit.ajaxurl, {
                    action: 'almgr_toggle_perf_realtime',
                    enabled: enabled,
                    nonce: almgrToolkit.nonce
                }, function(response){
                    hideLoading();
                    if (response.success) {
                        showNotice(response.data.message, 'success');
                        const $toggle = $('#perf-realtime-toggle').siblings('.almgr-toggle');
                        if (enabled) { $toggle.addClass('active'); } else { $toggle.removeClass('active'); }
                    } else {
                        showNotice(response.data || almgrToolkit.strings.error_occurred, 'error');
                        $('#perf-realtime-toggle').prop('checked', !enabled);
                        const $toggle = $('#perf-realtime-toggle').siblings('.almgr-toggle');
                        if (!enabled) { $toggle.addClass('active'); } else { $toggle.removeClass('active'); }
                    }
                }).fail(function(){
                    hideLoading();
                    showNotice(almgrToolkit.strings.error_occurred, 'error');
                    $('#perf-realtime-toggle').prop('checked', !enabled);
                    const $toggle = $('#perf-realtime-toggle').siblings('.almgr-toggle');
                    if (!enabled) { $toggle.addClass('active'); } else { $toggle.removeClass('active'); }
                });
            });

            $('#perf-realtime-toggle').siblings('.almgr-toggle').off('click').on('click', function(e){
                e.preventDefault();
                e.stopPropagation();
                if ($(this).closest('.almgr-toggle-wrapper').hasClass('disabled')) { return; }
                $(this).siblings('input[type="checkbox"]').trigger('click');
            });

            $('#perf-bootstrap-toggle').off('change').on('change', function(){
                if (!$('#perf-monitor-toggle').is(':checked')) {
                    $(this).prop('checked', false);
                    showNotice('Please enable Performance Bar first', 'error');
                    return;
                }
                const enabled = $(this).is(':checked');
                showLoading();
                $.post(almgrToolkit.ajaxurl, {
                    action: 'almgr_toggle_perf_bootstrap',
                    enabled: enabled,
                    nonce: almgrToolkit.nonce
                }, function(response){
                    hideLoading();
                    if (response.success) {
                        showNotice(response.data.message, 'success');
                        const $toggle = $('#perf-bootstrap-toggle').siblings('.almgr-toggle');
                        if (enabled) { $toggle.addClass('active'); } else { $toggle.removeClass('active'); }
                    } else {
                        showNotice(response.data || almgrToolkit.strings.error_occurred, 'error');
                        $('#perf-bootstrap-toggle').prop('checked', !enabled);
                        const $toggle = $('#perf-bootstrap-toggle').siblings('.almgr-toggle');
                        if (!enabled) { $toggle.addClass('active'); } else { $toggle.removeClass('active'); }
                    }
                }).fail(function(){
                    hideLoading();
                    showNotice(almgrToolkit.strings.error_occurred, 'error');
                    $('#perf-bootstrap-toggle').prop('checked', !enabled);
                    const $toggle = $('#perf-bootstrap-toggle').siblings('.almgr-toggle');
                    if (!enabled) { $toggle.addClass('active'); } else { $toggle.removeClass('active'); }
                });
            });

            $('#perf-bootstrap-toggle').siblings('.almgr-toggle').off('click').on('click', function(e){
                e.preventDefault();
                e.stopPropagation();
                if ($(this).closest('.almgr-toggle-wrapper').hasClass('disabled')) { return; }
                $(this).siblings('input[type="checkbox"]').trigger('click');
            });

            $('#perf-domains-toggle').off('change').on('change', function(){
                if (!$('#perf-monitor-toggle').is(':checked')) {
                    $(this).prop('checked', false);
                    showNotice('Please enable Performance Bar first', 'error');
                    return;
                }
                const enabled = $(this).is(':checked');
                showLoading();
                $.post(almgrToolkit.ajaxurl, {
                    action: 'almgr_toggle_perf_domains',
                    enabled: enabled,
                    nonce: almgrToolkit.nonce
                }, function(response){
                    hideLoading();
                    if (response.success) {
                        showNotice(response.data.message, 'success');
                        const $toggle = $('#perf-domains-toggle').siblings('.almgr-toggle');
                        if (enabled) { $toggle.addClass('active'); } else { $toggle.removeClass('active'); }
                    } else {
                        showNotice(response.data || almgrToolkit.strings.error_occurred, 'error');
                        $('#perf-domains-toggle').prop('checked', !enabled);
                        const $toggle = $('#perf-domains-toggle').siblings('.almgr-toggle');
                        if (!enabled) { $toggle.addClass('active'); } else { $toggle.removeClass('active'); }
                    }
                }).fail(function(){
                    hideLoading();
                    showNotice(almgrToolkit.strings.error_occurred, 'error');
                    $('#perf-domains-toggle').prop('checked', !enabled);
                    const $toggle = $('#perf-domains-toggle').siblings('.almgr-toggle');
                    if (!enabled) { $toggle.addClass('active'); } else { $toggle.removeClass('active'); }
                });
            });

            $('#perf-domains-toggle').siblings('.almgr-toggle').off('click').on('click', function(e){
                e.preventDefault();
                e.stopPropagation();
                if ($(this).closest('.almgr-toggle-wrapper').hasClass('disabled')) { return; }
                $(this).siblings('input[type="checkbox"]').trigger('click');
            });
        });
    }

    // Performance bar functionality moved to performance-bar.js to avoid conflicts
    // This ensures consistent behavior across frontend and admin areas

    /**
     * Initialize .htaccess editor with simple textarea and auto-save
     */
    function initializeHtaccessEditor() {
        let originalContent = $('#htaccess-editor').val();
        let autoSaveTimeout;
        let hasUnsavedChanges = false;
        const $editor = $('#htaccess-editor');

        // Simple textarea editor with keyboard shortcuts
        $editor.on('keydown', function(e) {
            // Ctrl+S or Cmd+S to save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                $('#save-htaccess').click();
            }

            // Tab key for indentation
            if (e.key === 'Tab') {
                e.preventDefault();
                const start = this.selectionStart;
                const end = this.selectionEnd;
                const value = this.value;
                this.value = value.substring(0, start) + '    ' + value.substring(end);
                this.selectionStart = this.selectionEnd = start + 4;
            }
        });

        // Auto-save functionality with debouncing
        $editor.on('input', function() {
            hasUnsavedChanges = true;
            updateSaveButtonState();

            // Clear existing timeout
            if (autoSaveTimeout) {
                clearTimeout(autoSaveTimeout);
            }

            // Set new timeout for auto-save (5 seconds after last change)
            autoSaveTimeout = setTimeout(function() {
                if (hasUnsavedChanges && $('#auto-save-enabled').is(':checked')) {
                    autoSaveContent();
                }
            }, 5000);
        });

        // Auto-save function
        function autoSaveContent() {
            const content = $editor.val();

            if (content === originalContent) {
                return; // No changes to save
            }

            $.post(almgrToolkit.ajaxurl, {
                action: 'almgr_auto_save_htaccess',
                content: content,
                nonce: almgrToolkit.nonce
            }, function(response) {
                if (response.success) {
                    hasUnsavedChanges = false;
                    updateSaveButtonState();
                    showAutoSaveNotice('Auto-saved successfully', 'success');
                } else {
                    showAutoSaveNotice('Auto-save failed', 'error');
                }
            }).fail(function() {
                showAutoSaveNotice('Auto-save failed', 'error');
            });
        }

        // Update save button state
        function updateSaveButtonState() {
            const $saveBtn = $('#save-htaccess');
            if (hasUnsavedChanges) {
                $saveBtn.addClass('has-changes').text($saveBtn.data('unsaved-text') || 'Save Changes*');
            } else {
                $saveBtn.removeClass('has-changes').text($saveBtn.data('saved-text') || 'Backup & Save');
            }
        }

        // Show auto-save notification
        function showAutoSaveNotice(message, type) {
            const $notice = $('<div class="almgr-auto-save-notice ' + type + '">' + message + '</div>');
            $('.almgr-editor-section').prepend($notice);
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }

        // Save .htaccess
        $('#save-htaccess').on('click', function() {
            const content = $editor.val();

            showLoading();

            $.post(almgrToolkit.ajaxurl, {
                action: 'almgr_save_htaccess',
                content: content,
                nonce: almgrToolkit.nonce
            }, function(response) {
                hideLoading();

                if (response.success) {
                    showNotice(response.data, 'success');
                    originalContent = content;
                    hasUnsavedChanges = false;
                    updateSaveButtonState();
                    // Refresh page to update backup info
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotice(response.data || almgrToolkit.strings.error_occurred, 'error');
                }
            });
        });

        // Restore .htaccess
        $('.almgr-restore-item').on('click', function(e) {
            e.preventDefault();

            if (!confirm(almgrToolkit.strings.confirm_restore_htaccess)) {
                return;
            }

            const backupIndex = $(this).data('index');

            showLoading();

            $.post(almgrToolkit.ajaxurl, {
                action: 'almgr_restore_htaccess',
                backup_index: backupIndex,
                nonce: almgrToolkit.nonce
            }, function(response) {
                hideLoading();

                if (response.success) {
                    showNotice(response.data, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotice(response.data || almgrToolkit.strings.error_occurred, 'error');
                }
            });
        });

        // Cancel changes
        $('#cancel-htaccess').on('click', function() {
            $editor.val(originalContent);
            hasUnsavedChanges = false;
            updateSaveButtonState();
        });

        // Insert snippets
        $('.almgr-snippet-btn').on('click', function() {
            const snippet = $(this).data('snippet');
            const currentContent = $editor.val();
            const cursorPos = $editor.get(0).selectionStart;

            let insertText = snippet;
            if (currentContent && !currentContent.endsWith('\n') && cursorPos === currentContent.length) {
                insertText = '\n\n' + snippet;
            }

            // Insert at cursor position
            const beforeCursor = currentContent.substring(0, cursorPos);
            const afterCursor = currentContent.substring(cursorPos);
            const newContent = beforeCursor + insertText + afterCursor;

            $editor.val(newContent);

            // Set cursor position after inserted text
            const newCursorPos = cursorPos + insertText.length;
            $editor.get(0).setSelectionRange(newCursorPos, newCursorPos);
            $editor.focus();
        });

        // Auto-save toggle
        $('#auto-save-enabled').on('change', function() {
            if ($(this).is(':checked')) {
                showAutoSaveNotice('Auto-save enabled', 'success');
            } else {
                showAutoSaveNotice('Auto-save disabled', 'info');
                if (autoSaveTimeout) {
                    clearTimeout(autoSaveTimeout);
                }
            }
        });

        // Warn before leaving with unsaved changes
        $(window).on('beforeunload', function() {
            if (hasUnsavedChanges) {
                return 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
    }

    /**
     * Initialize PHP config actions
     */
    function initializePHPConfig() {
        // Preset selection
        $('input[name="php_preset"]').on('change', function() {
            $('.almgr-preset-option').removeClass('selected');
            $(this).closest('.almgr-preset-option').addClass('selected');
        });

        // Apply configuration
        $('#apply-php-preset').on('click', function() {
            const preset = $('input[name="php_preset"]:checked').val();

            if (!preset) {
                showNotice('Please select a preset first.', 'error');
                return;
            }

            showLoading();

            $.post(almgrToolkit.ajaxurl, {
                action: 'almgr_apply_php_preset',
                preset: preset,
                nonce: almgrToolkit.nonce
            }, function(response) {
                hideLoading();

                if (response.success) {
                    showNotice(response.data, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showNotice(response.data || almgrToolkit.strings.error_occurred, 'error');
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
        if (!$('#almgr-logs-viewer').length) {
            return;
        }

        // Refresh logs
        $('#refresh-logs').on('click', function() {
            loadDebugLogs();
        });

        // Clear logs
        $('#clear-logs').on('click', function() {
            if (!confirm(almgrToolkit.strings.confirm_clear_logs)) {
                return;
            }

            showLoading();

            $.post(almgrToolkit.ajaxurl, {
                action: 'almgr_clear_debug_log',
                nonce: almgrToolkit.nonce
            }, function(response) {
                hideLoading();

                if (response.success) {
                    showNotice(response.data, 'success');
                    $('#almgr-logs-content').html('<div class="almgr-no-logs-message"><p>No log entries found.</p></div>');
                } else {
                    showNotice(response.data || almgrToolkit.strings.error_occurred, 'error');
                }
            });
        });

        // Download logs
        $('#download-logs').on('click', function() {
            // Create download link
            const downloadUrl = almgrToolkit.ajaxurl + '?action=almgr_download_logs&nonce=' + almgrToolkit.nonce;
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = 'debug-logs-' + new Date().toISOString().slice(0, 10) + '.txt';
            link.click();
        });

        // Advanced filter logs with real-time filtering
        $('#log-level-filter, #log-time-filter, #log-file-filter').on('change', function() {
            filterLogs();
        });

        $('#log-search').on('input', debounce(function() {
            filterLogs();
        }, 150)); // Faster response for real-time filtering

        // Advanced search options
        $('#regex-search-toggle, #case-sensitive-toggle').on('change', function() {
            filterLogs();
        });

        // Clear all filters
        $('#clear-filters').on('click', function() {
            $('#log-level-filter').val('');
            $('#log-time-filter').val('24h');
            $('#log-search').val('');
            $('#log-file-filter').val('');
            $('#regex-search-toggle').prop('checked', false);
            $('#case-sensitive-toggle').prop('checked', false);
            filterLogs();
        });

        // Export filtered results
        $('#export-filtered').on('click', function() {
            exportFilteredLogs();
        });

        // Real-time filter status update
        setInterval(function() {
            if ($('#almgr-logs-content .almgr-log-entry').length > 0) {
                const visible = $('#almgr-logs-content .almgr-log-entry:visible').length;
                const total = $('#almgr-logs-content .almgr-log-entry').length;
                updateFilterResults(visible, total);
            }
        }, 1000);
    }

    /**
     * Load debug logs
     */
    function loadDebugLogs() {
        const $logsContent = $('#almgr-logs-content');
        const $logsLoading = $('#almgr-logs-viewer .almgr-logs-loading');

        if (!$logsContent.length) return;

        $logsLoading.show();
        $logsContent.hide();

        $.post(almgrToolkit.ajaxurl, {
            action: 'almgr_get_debug_log',
            nonce: almgrToolkit.nonce
        }, function(response) {
            $logsLoading.hide();

            if (response.success && response.data) {
                displayLogs(response.data);
                $logsContent.show();
            } else {
                $logsContent.html('<div class="notice notice-error"><p>' + almgrToolkit.strings.error_occurred + '</p></div>');
                $logsContent.show();
            }
        });
    }

    /**
     * Display logs in the viewer
     */
    function displayLogs(logs) {
        const $logsContent = $('#almgr-logs-content');

        if (!logs || logs.length === 0) {
            $logsContent.html('<div class="almgr-no-logs-message"><p>No log entries found.</p></div>');
            return;
        }

        let html = '<div class="almgr-logs-list">';

        logs.forEach(function(log) {
            const levelClass = 'log-level-' + log.level.toLowerCase();
            const timeFormatted = new Date(log.timestamp).toLocaleString();

            html += '<div class="almgr-log-entry ' + levelClass + '">';
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
     * Advanced filter logs with real-time filtering
     */
    function filterLogs() {
        const level = $('#log-level-filter').val();
        const timeFilter = $('#log-time-filter').val();
        const searchTerm = $('#log-search').val();
        const fileFilter = $('#log-file-filter').val();
        const regexMode = $('#regex-search-toggle').is(':checked');
        const caseSensitive = $('#case-sensitive-toggle').is(':checked');

        let visibleCount = 0;
        let totalCount = 0;

        $('.almgr-log-entry').each(function() {
            totalCount++;
            let show = true;
            const $entry = $(this);
            const entryText = caseSensitive ? $entry.text() : $entry.text().toLowerCase();
            const logTime = $entry.find('.log-time').text();
            const logFile = $entry.find('.log-file').text();

            // Level filter
            if (level && !$entry.hasClass('log-level-' + level.toLowerCase())) {
                show = false;
            }

            // Advanced search filter
            if (searchTerm && show) {
                const searchText = caseSensitive ? searchTerm : searchTerm.toLowerCase();

                if (regexMode) {
                    try {
                        const regex = new RegExp(searchText, caseSensitive ? 'g' : 'gi');
                        if (!regex.test(entryText)) {
                            show = false;
                        }
                    } catch (e) {
                        // Invalid regex, fall back to normal search
                        if (entryText.indexOf(searchText) === -1) {
                            show = false;
                        }
                    }
                } else {
                    if (entryText.indexOf(searchText) === -1) {
                        show = false;
                    }
                }
            }

            // File path filter
            if (fileFilter && show) {
                const fileText = caseSensitive ? logFile : logFile.toLowerCase();
                const filterText = caseSensitive ? fileFilter : fileFilter.toLowerCase();
                if (fileText.indexOf(filterText) === -1) {
                    show = false;
                }
            }

            // Time filter
            if (timeFilter && show && logTime) {
                const entryDate = new Date(logTime);
                const now = new Date();
                let cutoffTime;

                switch (timeFilter) {
                    case '1h':
                        cutoffTime = new Date(now.getTime() - (60 * 60 * 1000));
                        break;
                    case '24h':
                        cutoffTime = new Date(now.getTime() - (24 * 60 * 60 * 1000));
                        break;
                    case '7d':
                        cutoffTime = new Date(now.getTime() - (7 * 24 * 60 * 60 * 1000));
                        break;
                    case '30d':
                        cutoffTime = new Date(now.getTime() - (30 * 24 * 60 * 60 * 1000));
                        break;
                    default:
                        cutoffTime = null;
                }

                if (cutoffTime && entryDate < cutoffTime) {
                    show = false;
                }
            }

            if (show) {
                visibleCount++;
                // Highlight search terms
                highlightSearchTerms($entry, searchTerm, regexMode, caseSensitive);
            } else {
                // Remove highlights when hidden
                removeHighlights($entry);
            }

            $entry.toggle(show);
        });

        // Update filter results counter
        updateFilterResults(visibleCount, totalCount);
    }

    /**
     * Highlight search terms in log entries
     */
    function highlightSearchTerms($entry, searchTerm, regexMode, caseSensitive) {
        if (!searchTerm) {
            removeHighlights($entry);
            return;
        }

        const $message = $entry.find('.log-message');
        let originalText = $message.data('original-text');

        if (!originalText) {
            originalText = $message.text();
            $message.data('original-text', originalText);
        }

        let highlightedText = originalText;

        if (regexMode) {
            try {
                const regex = new RegExp(searchTerm, caseSensitive ? 'g' : 'gi');
                highlightedText = originalText.replace(regex, '<mark class="almgr-highlight">$&</mark>');
            } catch (e) {
                // Invalid regex, fall back to normal highlighting
                const flags = caseSensitive ? 'g' : 'gi';
                const escapedTerm = searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                const regex = new RegExp(escapedTerm, flags);
                highlightedText = originalText.replace(regex, '<mark class="almgr-highlight">$&</mark>');
            }
        } else {
            const flags = caseSensitive ? 'g' : 'gi';
            const escapedTerm = searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const regex = new RegExp(escapedTerm, flags);
            highlightedText = originalText.replace(regex, '<mark class="almgr-highlight">$&</mark>');
        }

        $message.html(highlightedText);
    }

    /**
     * Remove highlights from log entry
     */
    function removeHighlights($entry) {
        const $message = $entry.find('.log-message');
        const originalText = $message.data('original-text');

        if (originalText) {
            $message.text(originalText);
        }
    }

    /**
     * Update filter results counter
     */
    function updateFilterResults(visible, total) {
        const $counter = $('#filter-results-counter');
        if ($counter.length) {
            if (visible === total) {
                $counter.text(`Showing all ${total} entries`);
                $counter.removeClass('filtered');
            } else {
                $counter.text(`Showing ${visible} of ${total} entries`);
                $counter.addClass('filtered');
            }
        }
    }

    /**
     * Export filtered log results
     */
    function exportFilteredLogs() {
        const visibleEntries = [];

        $('.almgr-log-entry:visible').each(function() {
            const $entry = $(this);
            const level = $entry.find('.log-level').text();
            const time = $entry.find('.log-time').text();
            const message = $entry.find('.log-message').data('original-text') || $entry.find('.log-message').text();
            const file = $entry.find('.log-file').text();

            visibleEntries.push({
                level: level,
                time: time,
                message: message,
                file: file
            });
        });

        if (visibleEntries.length === 0) {
            showNotice('No entries to export', 'warning');
            return;
        }

        // Create CSV content
        let csvContent = 'Level,Time,Message,File\n';
        visibleEntries.forEach(function(entry) {
            const escapedMessage = '"' + entry.message.replace(/"/g, '""') + '"';
            const escapedFile = '"' + entry.file.replace(/"/g, '""') + '"';
            csvContent += `${entry.level},${entry.time},${escapedMessage},${escapedFile}\n`;
        });

        // Download CSV
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', 'filtered-debug-logs-' + new Date().toISOString().slice(0, 10) + '.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        showNotice(`Exported ${visibleEntries.length} log entries`, 'success');
    }

    /**
     * Update debug status display
     */
    function updateDebugStatus(enabled) {
        const $indicator = $('.almgr-status-indicator');
        const $statusText = $('.almgr-status-item span:last-child');

        if (enabled) {
            $indicator.removeClass('inactive').addClass('active');
            $statusText.text('Status: Debug Enabled');
        } else {
            $indicator.removeClass('active').addClass('inactive');
            $statusText.text('Status: Debug Disabled');
        }
    }

    /**
     * Show loading overlay - menggunakan shared utility
     */
    function showLoading() {
        if (window.ALMGRSharedUtils && window.ALMGRSharedUtils.showLoading) {
            window.ALMGRSharedUtils.showLoading('#almgr-loading-overlay');
        } else {
            $('#almgr-loading-overlay').show();
        }
    }

    /**
     * Hide loading overlay - menggunakan shared utility
     */
    function hideLoading() {
        if (window.ALMGRSharedUtils && window.ALMGRSharedUtils.hideLoading) {
            window.ALMGRSharedUtils.hideLoading('#almgr-loading-overlay');
        } else {
            $('#almgr-loading-overlay').hide();
        }
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
    window.almgrShowNotice = showNotice;

    /**
     * Escape HTML - menggunakan shared utility
     */
    function escapeHtml(text) {
        if (window.ALMGRSharedUtils && window.ALMGRSharedUtils.escapeHtml) {
            return window.ALMGRSharedUtils.escapeHtml(text);
        }
        // Fallback
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Debounce function - menggunakan shared utility
     */
    function debounce(func, wait) {
        if (window.ALMGRSharedUtils && window.ALMGRSharedUtils.debounce) {
            return window.ALMGRSharedUtils.debounce(func, wait);
        }
        // Fallback
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
    // Shared utils akan menghandle backward compatibility
    window.almgrUtils = window.almgrUtils || {
        escapeHtml: escapeHtml,
        debounce: debounce
    };

    /**
     * Initialize card toggle buttons functionality
     */
    function initializeCardToggleButtons() {
        // Handle card toggle button clicks
        $(document).on('click', '.almgr-card-toggle-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const target = $(this).data('target');
            const $card = $(this).closest('.almgr-feature-card');
            const $icon = $(this).find('.dashicons');
            const isCurrentlyExpanded = $card.hasClass('expanded');

            // First, close all other cards and hide all tab contents
            $('.almgr-feature-card').removeClass('expanded');
            $('.almgr-tab-content').removeClass('active');

            // Reset all toggle buttons to default state
            $('.almgr-card-toggle-btn').each(function() {
                const $btn = $(this);
                const $btnIcon = $btn.find('.dashicons');
                const btnTarget = $btn.data('target');
                const closeText = btnTarget === 'debug-management' ? 'Configure' :
                                 btnTarget === 'perf-monitor' ? 'Configure' :
                                 btnTarget === 'file-editor' ? 'Configure' : 'Configure';
                $btn.find('span:not(.dashicons)').text(closeText);
                $btnIcon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
            });

            // If the clicked card wasn't expanded, expand it and show its content
            if (!isCurrentlyExpanded) {
                $card.addClass('expanded');

                // Show the target tab content
                if (target) {
                    const $targetTab = $('#tab-' + target);
                    $targetTab.addClass('active');
                }

                // Update button text and icon for the expanded card
                const closeText = target === 'debug-management' ? 'Close Debug' :
                                 target === 'perf-monitor' ? 'Close Monitor' :
                                 target === 'file-editor' ? 'Close Editor' : 'Close';
                $(this).find('span:not(.dashicons)').text(closeText);
                $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
            }
        });
    }

    /**
     * Initialize collapsible sections functionality
     */
    function initializeCollapsibleSections() {
        // Handle section toggle clicks
        $(document).on('click', '.almgr-section-toggle', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const $section = $(this).closest('.almgr-collapsible-section');
            const $content = $section.find('.almgr-section-content');
            const $icon = $(this).find('.dashicons');

            // Toggle collapsed state
            $section.toggleClass('collapsed');

            // Animate content with slide effect
            if ($section.hasClass('collapsed')) {
                $content.slideUp(300);
                $icon.css('transform', 'rotate(-90deg)');
            } else {
                $content.slideDown(300);
                $icon.css('transform', 'rotate(0deg)');
            }
        });

        // Handle header clicks (same as toggle button)
        $(document).on('click', '.almgr-section-header', function(e) {
            // Don't trigger if clicking on the toggle button itself
            if ($(e.target).closest('.almgr-section-toggle').length) {
                return;
            }

            $(this).find('.almgr-section-toggle').trigger('click');
        });

        // Initialize sections state - collapse all except first one by default
        $('.almgr-collapsible-section').each(function(index) {
            const $section = $(this);
            const $content = $section.find('.almgr-section-content');
            const $icon = $section.find('.almgr-section-toggle .dashicons');

            // Keep first section (Basic Debug Settings) open, collapse others
            if (index > 0) {
                $section.addClass('collapsed');
                $content.hide();
                $icon.css('transform', 'rotate(-90deg)');
            }
        });

        // Add smooth transitions for better UX
        $('.almgr-section-content').css({
            'overflow': 'hidden',
            'transition': 'all 0.3s ease'
        });
    }

    /**
     * Initialize Performance Monitor functionality
     */
    function initializePerformanceMonitor() {
        // Handle performance bar toggle
        $(document).on('change', '#enable_performance_bar', function() {
            const isEnabled = $(this).is(':checked');
            const $previewBar = $('.almgr-perf-preview-bar');
            const $previewBadge = $('.almgr-preview-badge');
            const $advancedToggles = $('.almgr-perf-advanced-toggles input[type="checkbox"]');

            // Update preview bar state
            if (isEnabled) {
                $previewBar.removeClass('inactive');
                $previewBadge.removeClass('inactive').addClass('active')
                    .html('<span class="dashicons dashicons-yes-alt"></span> Performance Bar Active');
                $advancedToggles.prop('disabled', false);
            } else {
                $previewBar.addClass('inactive');
                $previewBadge.removeClass('active').addClass('inactive')
                    .html('<span class="dashicons dashicons-dismiss"></span> Performance Bar Disabled');
                $advancedToggles.prop('disabled', true).prop('checked', false);
            }

            updatePerformancePreview();
        });

        // Handle advanced feature toggles
        $(document).on('change', '.almgr-perf-advanced-toggles input[type="checkbox"]', function() {
            updatePerformancePreview();
        });

        // Handle performance bar details toggle
        $(document).on('click', '.almgr-perf-toggle-details', function() {
            const $this = $(this);
            const $icon = $this.find('.dashicons');

            // Toggle expanded state (placeholder for future detailed view)
            $this.toggleClass('expanded');

            if ($this.hasClass('expanded')) {
                $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
                $this.find('span').text('Hide Details');
            } else {
                $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
                $this.find('span').text('Show Details');
            }
        });

        // Initialize performance preview on page load
        updatePerformancePreview();
    }

    /**
     * Initialize clickable status cards functionality
     */
    function initializeClickableStatusCards() {
        // Handle clickable status card clicks
        $(document).on('click', '.almgr-clickable-card', function(e) {
            e.preventDefault();
            const navigateUrl = $(this).data('navigate');
            if (navigateUrl) {
                window.location.href = navigateUrl;
            }
        });
    }

    /**
     * Update performance preview based on current settings
     */
    function updatePerformancePreview() {
        const isEnabled = $('#enable_performance_bar').is(':checked');
        const $previewBar = $('.almgr-perf-preview-bar');

        if (!isEnabled) {
            return;
        }

        // Get enabled features
        const features = {
            hooks: $('#real_time_hooks').is(':checked'),
            bootstrap: $('#bootstrap_phases').is(':checked'),
            domains: $('#domain_panels').is(':checked')
        };

        // Update performance items visibility
        $('.almgr-perf-item').each(function() {
            const $item = $(this);
            const itemType = $item.data('type');

            if (features[itemType]) {
                $item.show().css('opacity', '1');
            } else {
                $item.css('opacity', '0.3');
            }
        });

        // Update status indicators
        updatePerformanceStatus(features);
    }

    /**
     * Update performance status indicators
     */
    function updatePerformanceStatus(features) {
        const enabledCount = Object.values(features).filter(Boolean).length;
        const $statusIndicators = $('.almgr-perf-status-indicator');

        $statusIndicators.removeClass('almgr-status-good almgr-status-warning almgr-status-critical');

        if (enabledCount === 0) {
            $statusIndicators.addClass('almgr-status-critical').text('Minimal');
        } else if (enabledCount <= 2) {
            $statusIndicators.addClass('almgr-status-warning').text('Moderate');
        } else {
            $statusIndicators.addClass('almgr-status-good').text('Comprehensive');
        }
    }

})(jQuery);
