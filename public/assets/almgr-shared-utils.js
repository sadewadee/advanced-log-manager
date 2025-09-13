/**
 * ALMGR Shared Utilities
 * Fungsi-fungsi utility yang digunakan bersama antara admin dan frontend
 *
 * @package Advanced_Log_Manager
 * @since 1.0.0
 */

(function(window) {
    'use strict';

    // Namespace untuk utilities
    window.ALMGRSharedUtils = window.ALMGRSharedUtils || {};

    /**
     * Universal Tab Navigation Handler
     * Mendukung baik jQuery selector maupun vanilla JS
     */
    window.ALMGRSharedUtils.initializeTabs = function(options) {
        const defaults = {
            tabSelector: '.almgr-tab-btn, .almgr-perf-tab',
            contentSelector: '.almgr-tab-content, .almgr-perf-tab-content',
            activeClass: 'active',
            useJQuery: typeof jQuery !== 'undefined'
        };

        const settings = Object.assign({}, defaults, options || {});

        if (settings.useJQuery && window.jQuery) {
            // jQuery implementation untuk admin area
            jQuery(settings.tabSelector).on('click', function(e) {
                e.preventDefault();
                const tabId = jQuery(this).data('tab');

                // Update tab buttons
                jQuery(settings.tabSelector).removeClass(settings.activeClass);
                jQuery(this).addClass(settings.activeClass);

                // Update tab contents
                jQuery(settings.contentSelector).removeClass(settings.activeClass);
                jQuery('#tab-' + tabId + ', #almgr-perf-tab-' + tabId).addClass(settings.activeClass);
            });
        } else {
            // Vanilla JS implementation untuk frontend
            const tabs = document.querySelectorAll(settings.tabSelector);
            const tabContents = document.querySelectorAll(settings.contentSelector);

            tabs.forEach(function(tab) {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    const tabName = this.getAttribute('data-tab');

                    // Remove active class from all tabs
                    tabs.forEach(function(t) {
                        t.classList.remove(settings.activeClass);
                    });

                    tabContents.forEach(function(content) {
                        content.classList.remove(settings.activeClass);
                    });

                    // Add active class to selected tab and content
                    this.classList.add(settings.activeClass);
                    const selectedContent = document.getElementById('tab-' + tabName) ||
                                          document.getElementById('almgr-perf-tab-' + tabName);

                    if (selectedContent) {
                        selectedContent.classList.add(settings.activeClass);
                    }
                });
            });
        }
    };

    /**
     * Debounce function - universal implementation
     */
    window.ALMGRSharedUtils.debounce = function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = function() {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };

    /**
     * Format numbers for display
     */
    window.ALMGRSharedUtils.formatNumber = function(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    };

    /**
     * Format bytes for display
     */
    window.ALMGRSharedUtils.formatBytes = function(bytes) {
        if (bytes === 0) return '0 B';

        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    };

    /**
     * Format time for display
     */
    window.ALMGRSharedUtils.formatTime = function(seconds) {
        if (seconds < 1) {
            return Math.round(seconds * 1000) + 'ms';
        }
        return seconds.toFixed(3) + 's';
    };

    /**
     * Escape HTML
     */
    window.ALMGRSharedUtils.escapeHtml = function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    /**
     * Show loading overlay (universal)
     */
    window.ALMGRSharedUtils.showLoading = function(selector) {
        selector = selector || '#almgr-loading-overlay';

        if (window.jQuery) {
            jQuery(selector).show();
        } else {
            const element = document.querySelector(selector);
            if (element) {
                element.style.display = 'block';
            }
        }
    };

    /**
     * Hide loading overlay (universal)
     */
    window.ALMGRSharedUtils.hideLoading = function(selector) {
        selector = selector || '#almgr-loading-overlay';

        if (window.jQuery) {
            jQuery(selector).hide();
        } else {
            const element = document.querySelector(selector);
            if (element) {
                element.style.display = 'none';
            }
        }
    };

    // Backward compatibility - expose ke namespace lama jika ada
    if (window.almgrUtils) {
        Object.assign(window.almgrUtils, {
            formatNumber: window.ALMGRSharedUtils.formatNumber,
            formatBytes: window.ALMGRSharedUtils.formatBytes,
            formatTime: window.ALMGRSharedUtils.formatTime,
            debounce: window.ALMGRSharedUtils.debounce,
            escapeHtml: window.ALMGRSharedUtils.escapeHtml
        });
    } else {
        window.almgrUtils = {
            formatNumber: window.ALMGRSharedUtils.formatNumber,
            formatBytes: window.ALMGRSharedUtils.formatBytes,
            formatTime: window.ALMGRSharedUtils.formatTime,
            debounce: window.ALMGRSharedUtils.debounce,
            escapeHtml: window.ALMGRSharedUtils.escapeHtml
        };
    }

})(window);