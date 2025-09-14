/**
 * Performance Monitor Tab Enhancements
 * Filtering and sorting functionality for Images and Hooks tabs
 */

// Universal Sortable Functionality
var ALMGR_Sortable = {
    init: function() {
        this.bindSortableHeaders();
    },

    bindSortableHeaders: function() {
        var sortableHeaders = document.querySelectorAll('.sortable');

        sortableHeaders.forEach(function(header) {
            header.addEventListener('click', function() {
                ALMGR_Sortable.sortTable(this);
            });
            // Keyboard support
            header.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    ALMGR_Sortable.sortTable(this);
                }
            });

            // Add visual/ARIA indicators
            header.style.cursor = 'pointer';
            header.style.position = 'relative';
            header.setAttribute('tabindex', '0');
            header.setAttribute('role', 'button');
            if (!header.hasAttribute('aria-sort')) {
                header.setAttribute('aria-sort', 'none');
            }
        });
    },

    sortTable: function(header) {
        var table = header.closest('table');
        var tbody = table.querySelector('tbody');
        var column = header.getAttribute('data-column');
        var columnIndex = Array.from(header.parentNode.children).indexOf(header);

        if (!tbody || !column) return;

        var rows = Array.from(tbody.querySelectorAll('tr'));
        var isAscending = header.getAttribute('data-sort-direction') !== 'asc';

        // Remove previous sort indicators
        var allHeaders = table.querySelectorAll('.sortable');
        allHeaders.forEach(function(h) {
            h.removeAttribute('data-sort-direction');
            h.classList.remove('sorted-asc', 'sorted-desc');
            h.setAttribute('aria-sort', 'none');
        });

        // Set new sort direction
        header.setAttribute('data-sort-direction', isAscending ? 'asc' : 'desc');
        header.classList.add(isAscending ? 'sorted-asc' : 'sorted-desc');
        header.setAttribute('aria-sort', isAscending ? 'ascending' : 'descending');

        rows.sort(function(a, b) {
            var aCell = a.children[columnIndex];
            var bCell = b.children[columnIndex];

            if (!aCell || !bCell) return 0;

            var aVal = ALMGR_Sortable.getSortValue(aCell, column);
            var bVal = ALMGR_Sortable.getSortValue(bCell, column);

            var result = ALMGR_Sortable.compare(aVal, bVal, column);
            return isAscending ? result : -result;
        });

        // Reorder rows in table
        rows.forEach(function(row) {
            tbody.appendChild(row);
        });

        // Update row numbers if they exist
        ALMGR_Sortable.updateRowNumbers(tbody);
    },

    getSortValue: function(cell, column) {
        var text = cell.textContent.trim();
        var row = cell.parentElement;
        var rowData = row ? row.dataset || {} : {};
        var cellDataVal = cell.getAttribute('data-sort-value');

        // Prefer numeric data-* on row for performance tabs
        switch (column) {
            case 'time': {
                var v = rowData.timeMs || cellDataVal;
                if (v !== undefined && v !== null && v !== '') return parseFloat(v) || 0;
                var numMatch = text.match(/([0-9.]+)/);
                return numMatch ? parseFloat(numMatch[1]) : 0;
            }
            case 'loadtime': {
                var v2 = rowData.loadtimeMs || cellDataVal;
                if (v2 !== undefined && v2 !== null && v2 !== '') return parseFloat(v2) || 0;
                var nm2 = text.match(/([0-9.]+)/);
                return nm2 ? parseFloat(nm2[1]) : 0;
            }
            case 'filesize': {
                var v3 = rowData.filesizeBytes || cellDataVal;
                if (v3 !== undefined && v3 !== null && v3 !== '') return parseFloat(v3) || 0;
                var nm3 = text.match(/([0-9.]+)/);
                return nm3 ? parseFloat(nm3[1]) : 0;
            }
            case 'memory':
            case 'order':
            case 'priority': {
                var v4 = rowData[column] || cellDataVal;
                if (v4 !== undefined && v4 !== null && v4 !== '') return parseFloat(v4) || 0;
                var nm4 = text.match(/([0-9.]+)/);
                return nm4 ? parseFloat(nm4[1]) : 0;
            }
            case 'version':
                if (text === 'N/A' || text === '') return '0';
                return (cellDataVal || text).toString();
            default:
                return (cellDataVal || text).toLowerCase();
        }
    },

    compare: function(a, b, column) {
        if (typeof a === 'number' && typeof b === 'number') {
            return a - b;
        }

        if (typeof a === 'string' && typeof b === 'string') {
            return a.localeCompare(b);
        }

        return 0;
    },

    updateRowNumbers: function(tbody) {
        var visibleRows = tbody.querySelectorAll('tr:not([style*="display: none"])');
        visibleRows.forEach(function(row, index) {
            var numberCell = row.querySelector('.query-number');
            if (numberCell) {
                numberCell.textContent = index + 1;
            }
        });
    }
};

// Images Tab Functionality
var ALMGR_Images = {
    init: function() {
        this.bindEvents();
        this.populateFilters();
        this.pageSize = this.getPageSize();
        this.visibleLimit = this.pageSize;
        this.bindLoadMore();
        this.filterImages();
    },

    bindEvents: function() {
        var sourceFilter = document.getElementById('almgr-images-source-filter');
        var hostnameFilter = document.getElementById('almgr-images-hostname-filter');
        var hostScopeFilter = document.getElementById('almgr-images-host-scope');
        var sortSelect = document.getElementById('almgr-images-sort');
        var searchInput = document.getElementById('almgr-images-search');

        if (sourceFilter) {
            sourceFilter.addEventListener('change', this.resetAndFilter.bind(this));
        }
        if (hostnameFilter) {
            hostnameFilter.addEventListener('change', this.resetAndFilter.bind(this));
        }
        if (hostScopeFilter) {
            hostScopeFilter.addEventListener('change', this.resetAndFilter.bind(this));
        }
        if (sortSelect) {
            sortSelect.addEventListener('change', this.sortImages.bind(this));
        }
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce(this.resetAndFilter.bind(this), 200));
        }
    },

    // Reset visible limit then filter
    resetAndFilter: function() {
        this.visibleLimit = this.pageSize;
        this.filterImages();
    },

    getPageSize: function() {
        var btn = document.getElementById('almgr-images-load-more');
        var size = 20;
        if (btn && btn.dataset.pageSize) {
            var parsed = parseInt(btn.dataset.pageSize, 10);
            if (!isNaN(parsed) && parsed > 0) size = parsed;
        }
        return size;
    },

    bindLoadMore: function() {
        var self = this;
        var btn = document.getElementById('almgr-images-load-more');
        if (!btn) return;
        btn.style.display = 'none';
        btn.addEventListener('click', function() {
            self.visibleLimit += self.pageSize;
            self.filterImages();
        });
    },

    populateFilters: function() {
        var table = document.querySelector('.almgr-images-table tbody');
        if (!table) return;

        var sources = new Set();
        var hostnames = new Set();

        var rows = table.querySelectorAll('tr');
        rows.forEach(function(row) {
            var source = row.getAttribute('data-source');
            var hostname = row.getAttribute('data-hostname');

            if (source) sources.add(source);
            if (hostname) hostnames.add(hostname);
        });

        var sourceFilter = document.getElementById('almgr-images-source-filter');
        if (sourceFilter) {
            sources.forEach(function(source) {
                var option = document.createElement('option');
                option.value = source;
                option.textContent = source;
                sourceFilter.appendChild(option);
            });
        }

        var hostnameFilter = document.getElementById('almgr-images-hostname-filter');
        if (hostnameFilter) {
            hostnames.forEach(function(hostname) {
                var option = document.createElement('option');
                option.value = hostname;
                option.textContent = hostname;
                hostnameFilter.appendChild(option);
            });
        }
    },

    filterImages: function() {
        var sourceFilter = document.getElementById('almgr-images-source-filter');
        var hostnameFilter = document.getElementById('almgr-images-hostname-filter');
        var hostScopeFilter = document.getElementById('almgr-images-host-scope');
        var searchInput = document.getElementById('almgr-images-search');
        var table = document.querySelector('.almgr-images-table tbody');
        var loadMoreBtn = document.getElementById('almgr-images-load-more');

        if (!table) return;

        var selectedSource = sourceFilter ? sourceFilter.value : '';
        var selectedHostname = hostnameFilter ? hostnameFilter.value : '';
        var selectedScope = hostScopeFilter ? hostScopeFilter.value : '';
        var searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';

        var rows = table.querySelectorAll('tr');
        var visibleCount = 0;
        var matchedCount = 0;

        rows.forEach(function(row) {
            var source = row.getAttribute('data-source') || '';
            var hostname = row.getAttribute('data-hostname') || '';
            var scope = row.getAttribute('data-host-scope') || '';
            var alt = (row.getAttribute('data-alt') || '').toLowerCase();
            var src = (row.getAttribute('data-src') || '').toLowerCase();

            var match = true;

            if (selectedSource && source !== selectedSource) match = false;
            if (selectedHostname && hostname !== selectedHostname) match = false;
            if (selectedScope && scope !== selectedScope) match = false;
            if (searchTerm && !(alt.includes(searchTerm) || src.includes(searchTerm))) match = false;

            if (match) {
                matchedCount++;
                if (visibleCount < ALMGR_Images.visibleLimit) {
                    row.style.display = '';
                    visibleCount++;
                    var numberCell = row.querySelector('.query-number');
                    if (numberCell) {
                        numberCell.textContent = visibleCount;
                    }
                } else {
                    row.style.display = 'none';
                }
            } else {
                row.style.display = 'none';
            }
        });

        if (loadMoreBtn) {
            if (matchedCount > this.visibleLimit) {
                loadMoreBtn.style.display = '';
            } else {
                loadMoreBtn.style.display = 'none';
            }
        }
    },

    sortImages: function() {
        var sortSelect = document.getElementById('almgr-images-sort');
        var table = document.querySelector('.almgr-images-table tbody');

        if (!table || !sortSelect) return;

        var sortBy = sortSelect.value;
        var rows = Array.from(table.querySelectorAll('tr'));

        rows.sort(function(a, b) {
            var aVal, bVal;

            switch (sortBy) {
                case 'size':
                    aVal = parseInt(a.getAttribute('data-size')) || 0;
                    bVal = parseInt(b.getAttribute('data-size')) || 0;
                    return bVal - aVal;
                case 'load_time':
                    aVal = parseInt(a.getAttribute('data-load-time')) || 0;
                    bVal = parseInt(b.getAttribute('data-load-time')) || 0;
                    return bVal - aVal;
                case 'source':
                    aVal = a.getAttribute('data-source') || '';
                    bVal = b.getAttribute('data-source') || '';
                    return aVal.localeCompare(bVal);
                default:
                    return 0;
            }
        });

        rows.forEach(function(row) { table.appendChild(row); });
        this.filterImages();
    },

    updateRowNumbers: function() {
        var table = document.querySelector('.almgr-images-table tbody');
        if (!table) return;
        var visibleRows = table.querySelectorAll('tr:not([style*="display: none"])');
        visibleRows.forEach(function(row, index) {
            var numberCell = row.querySelector('.query-number');
            if (numberCell) {
                numberCell.textContent = index + 1;
            }
        });
    },

    debounce: function(fn, delay) {
        // Gunakan shared utility jika tersedia
        if (window.ALMGRSharedUtils && window.ALMGRSharedUtils.debounce) {
            return window.ALMGRSharedUtils.debounce(fn, delay);
        }
        // Fallback
        var t;
        return function() {
            var ctx = this, args = arguments;
            clearTimeout(t);
            t = setTimeout(function() { fn.apply(ctx, args); }, delay);
        };
    }
};

// Hooks Tab Functionality
var ALMGR_Hooks = {
    init: function() {
        this.bindEvents();
    },

    bindEvents: function() {
        var groupFilter = document.getElementById('almgr-hooks-group-filter');
        var sortSelect = document.getElementById('almgr-hooks-sort');
        var searchInput = document.getElementById('almgr-hooks-search');
        var minPriority = document.getElementById('almgr-hooks-min-priority');

        if (groupFilter) {
            groupFilter.addEventListener('change', this.filterHooks.bind(this));
        }
        if (sortSelect) {
            sortSelect.addEventListener('change', this.sortHooks.bind(this));
        }
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce(this.filterHooks.bind(this), 200));
        }
        if (minPriority) {
            minPriority.addEventListener('input', this.debounce(this.filterHooks.bind(this), 200));
        }
    },

    filterHooks: function() {
        var groupFilter = document.getElementById('almgr-hooks-group-filter');
        var table = document.querySelector('.almgr-hooks-table tbody');
        var searchInput = document.getElementById('almgr-hooks-search');
        var minPriority = document.getElementById('almgr-hooks-min-priority');

        if (!table) return;

        var selectedGroup = groupFilter ? groupFilter.value : '';
        var term = searchInput ? searchInput.value.toLowerCase().trim() : '';
        var minP = minPriority ? parseInt(minPriority.value, 10) : NaN;
        var rows = table.querySelectorAll('tr');
        var visibleCount = 0;

        rows.forEach(function(row) {
            var hookType = row.getAttribute('data-hook-type') || '';
            var hook = (row.getAttribute('data-hook') || '').toLowerCase();
            var callback = (row.getAttribute('data-callback') || '').toLowerCase();
            var priority = parseInt(row.getAttribute('data-priority')) || 0;
            var showRow = true;

            if (selectedGroup === 'hook' && hookType !== 'action') {
                showRow = false;
            } else if (selectedGroup === 'filter' && hookType !== 'filter') {
                showRow = false;
            }
            if (showRow && term) {
                showRow = hook.includes(term) || callback.includes(term);
            }
            if (showRow && !isNaN(minP)) {
                showRow = priority >= minP;
            }

            if (showRow) {
                row.style.display = '';
                visibleCount++;
                var numberCell = row.querySelector('.query-number');
                if (numberCell) {
                    numberCell.textContent = visibleCount;
                }
            } else {
                row.style.display = 'none';
            }
        });
    },

    sortHooks: function() {
        var sortSelect = document.getElementById('almgr-hooks-sort');
        var table = document.querySelector('.almgr-hooks-table tbody');

        if (!table || !sortSelect) return;

        var sortBy = sortSelect.value;
        var rows = Array.from(table.querySelectorAll('tr'));

        rows.sort(function(a, b) {
            var aVal, bVal;

            switch (sortBy) {
                case 'hook':
                    aVal = a.getAttribute('data-hook') || '';
                    bVal = b.getAttribute('data-hook') || '';
                    return aVal.localeCompare(bVal);
                case 'priority':
                    aVal = parseInt(a.getAttribute('data-priority')) || 0;
                    bVal = parseInt(b.getAttribute('data-priority')) || 0;
                    return aVal - bVal;
                default:
                    return 0;
            }
        });

        rows.forEach(function(row) { table.appendChild(row); });
        this.updateRowNumbers();
    },

    updateRowNumbers: function() {
        var table = document.querySelector('.almgr-hooks-table tbody');
        if (!table) return;

        var visibleRows = table.querySelectorAll('tr:not([style*="display: none"])');
        visibleRows.forEach(function(row, index) {
            var numberCell = row.querySelector('.query-number');
            if (numberCell) {
                numberCell.textContent = index + 1;
            }
        });
    },

    debounce: function(fn, delay) {
        // Gunakan shared utility jika tersedia
        if (window.ALMGRSharedUtils && window.ALMGRSharedUtils.debounce) {
            return window.ALMGRSharedUtils.debounce(fn, delay);
        }
        // Fallback
        var t;
        return function() {
            var ctx = this, args = arguments;
            clearTimeout(t);
            t = setTimeout(function() { fn.apply(ctx, args); }, delay);
        };
    }
};

// Queries Tab Functionality
var ALMGR_Queries = {
    init: function() {
        this.bindEvents();
        this.filterRows();
    },
    bindEvents: function() {
        var typeFilter = document.getElementById('almgr-queries-type-filter');
        var minTime = document.getElementById('almgr-queries-min-time');
        var searchInput = document.getElementById('almgr-queries-search');
        var self = this;
        if (typeFilter) typeFilter.addEventListener('change', function(){ self.filterRows(); });
        if (minTime) minTime.addEventListener('input', this.debounce(function(){ self.filterRows(); }, 200));
        if (searchInput) searchInput.addEventListener('input', this.debounce(function(){ self.filterRows(); }, 200));
    },
    filterRows: function() {
        var table = document.querySelector('.almgr-queries-table tbody');
        if (!table) return;
        var typeFilter = document.getElementById('almgr-queries-type-filter');
        var minTime = document.getElementById('almgr-queries-min-time');
        var searchInput = document.getElementById('almgr-queries-search');
        var t = typeFilter ? typeFilter.value : '';
        var mt = minTime ? parseFloat(minTime.value) : NaN;
        var s = searchInput ? searchInput.value.toLowerCase().trim() : '';
        var rows = table.querySelectorAll('tr');
        var visible = 0;
        rows.forEach(function(row){
            var qType = (row.getAttribute('data-query-type')||'').toLowerCase();
            var timeMs = parseFloat(row.getAttribute('data-time-ms')) || 0;
            var sql = (row.getAttribute('data-sql')||'').toLowerCase();
            var ok = true;
            if (t && qType !== t.toLowerCase()) ok = false;
            if (ok && !isNaN(mt)) ok = timeMs >= (mt * 1); // ms assumed
            if (ok && s) ok = sql.includes(s);
            row.style.display = ok ? '' : 'none';
            if (ok) {
                visible++;
                var num = row.querySelector('.query-number');
                if (num) num.textContent = visible;
            }
        });
    },
    debounce: function(fn, delay) {
        // Gunakan shared utility jika tersedia
        if (window.ALMGRSharedUtils && window.ALMGRSharedUtils.debounce) {
            return window.ALMGRSharedUtils.debounce(fn, delay);
        }
        // Fallback
        var t; return function(){ clearTimeout(t); var ctx=this, args=arguments; t=setTimeout(function(){ fn.apply(ctx,args); }, delay); };
    }
};

// Scripts Tab Functionality
var ALMGR_Scripts = {
    init: function() { this.bindEvents(); this.filterRows(); },
    bindEvents: function() {
        var p = document.getElementById('almgr-scripts-position-filter');
        var h = document.getElementById('almgr-scripts-hostname-filter');
        var c = document.getElementById('almgr-scripts-component-filter');
        var s = document.getElementById('almgr-scripts-search');
        var self = this;
        if (p) p.addEventListener('change', function(){ self.filterRows(); });
        if (h) h.addEventListener('change', function(){ self.filterRows(); });
        if (c) c.addEventListener('change', function(){ self.filterRows(); });
        if (s) s.addEventListener('input', this.debounce(function(){ self.filterRows(); }, 200));
    },
    filterRows: function() {
        var table = document.querySelector('.almgr-scripts-table tbody');
        if (!table) return;
        var p = document.getElementById('almgr-scripts-position-filter');
        var h = document.getElementById('almgr-scripts-hostname-filter');
        var c = document.getElementById('almgr-scripts-component-filter');
        var s = document.getElementById('almgr-scripts-search');
        var pv = p ? p.value : '';
        var hv = h ? h.value : '';
        var cv = c ? c.value : '';
        var sv = s ? s.value.toLowerCase().trim() : '';
        var rows = table.querySelectorAll('tr');
        var visible = 0;
        rows.forEach(function(row){
            var rp = row.getAttribute('data-position') || '';
            var rh = row.getAttribute('data-hostname') || '';
            var rc = row.getAttribute('data-component') || '';
            var rhdl = (row.getAttribute('data-handle') || '').toLowerCase();
            var rsrc = (row.getAttribute('data-source') || '').toLowerCase();
            var ok = true;
            if (pv && rp !== pv) ok = false;
            if (ok && hv && rh !== hv) ok = false;
            if (ok && cv && rc !== cv) ok = false;
            if (ok && sv) ok = rhdl.includes(sv) || rsrc.includes(sv);
            row.style.display = ok ? '' : 'none';
            if (ok) {
                visible++;
                var num = row.querySelector('.query-number');
                if (num) num.textContent = visible;
            }
        });
    },
    debounce: function(fn, delay) { var t; return function(){ clearTimeout(t); var ctx=this, a=arguments; t=setTimeout(function(){ fn.apply(ctx,a); }, delay); }; }
};

// Styles Tab Functionality
var ALMGR_Styles = {
    init: function() { this.bindEvents(); this.filterRows(); },
    bindEvents: function() {
        var p = document.getElementById('almgr-styles-position-filter');
        var h = document.getElementById('almgr-styles-hostname-filter');
        var c = document.getElementById('almgr-styles-component-filter');
        var s = document.getElementById('almgr-styles-search');
        var self = this;
        if (p) p.addEventListener('change', function(){ self.filterRows(); });
        if (h) h.addEventListener('change', function(){ self.filterRows(); });
        if (c) c.addEventListener('change', function(){ self.filterRows(); });
        if (s) s.addEventListener('input', this.debounce(function(){ self.filterRows(); }, 200));
    },
    filterRows: function() {
        var table = document.querySelector('.almgr-styles-table tbody');
        if (!table) return;
        var p = document.getElementById('almgr-styles-position-filter');
        var h = document.getElementById('almgr-styles-hostname-filter');
        var c = document.getElementById('almgr-styles-component-filter');
        var s = document.getElementById('almgr-styles-search');
        var pv = p ? p.value : '';
        var hv = h ? h.value : '';
        var cv = c ? c.value : '';
        var sv = s ? s.value.toLowerCase().trim() : '';
        var rows = table.querySelectorAll('tr');
        var visible = 0;
        rows.forEach(function(row){
            var rp = row.getAttribute('data-position') || '';
            var rh = row.getAttribute('data-hostname') || '';
            var rc = row.getAttribute('data-component') || '';
            var rhdl = (row.getAttribute('data-handle') || '').toLowerCase();
            var rsrc = (row.getAttribute('data-source') || '').toLowerCase();
            var ok = true;
            if (pv && rp !== pv) ok = false;
            if (ok && hv && rh !== hv) ok = false;
            if (ok && cv && rc !== cv) ok = false;
            if (ok && sv) ok = rhdl.includes(sv) || rsrc.includes(sv);
            row.style.display = ok ? '' : 'none';
            if (ok) {
                visible++;
                var num = row.querySelector('.query-number');
                if (num) num.textContent = visible;
            }
        });
    },
    debounce: function(fn, delay) { var t; return function(){ clearTimeout(t); var ctx=this, a=arguments; t=setTimeout(function(){ fn.apply(ctx,a); }, delay); }; }
};
/**
 * Initialize toggle functionality for domain panels and bootstrap details
 * This ensures the toggles work in both admin and front-end
 */
function initializeToggleFunctionality() {
    // Toggle bootstrap details
    document.querySelectorAll('.toggle-bootstrap-details').forEach(function(button) {
        button.addEventListener('click', function() {
            var phase = this.getAttribute('data-phase');
            var details = document.getElementById('bootstrap-details-' + phase);
            if (details) {
                details.style.display = details.style.display === 'none' ? 'block' : 'none';
                // Use fallback text if localization is not available
                var viewText = (window.mtQueryMonitorL10n && window.mtQueryMonitorL10n.viewDetails) ? window.mtQueryMonitorL10n.viewDetails : 'View Details';
                var hideText = (window.mtQueryMonitorL10n && window.mtQueryMonitorL10n.hideDetails) ? window.mtQueryMonitorL10n.hideDetails : 'Hide Details';
                this.textContent = details.style.display === 'none' ? viewText : hideText;
            }
        });
    });

    // Toggle domain panels
    document.querySelectorAll('.toggle-domain-panel').forEach(function(button) {
        button.addEventListener('click', function() {
            var domain = this.getAttribute('data-domain');
            var content = document.getElementById('domain-content-' + domain);
            if (content) {
                content.style.display = content.style.display === 'none' ? 'block' : 'none';
                // Use fallback text if localization is not available
                var toggleText = (window.mtQueryMonitorL10n && window.mtQueryMonitorL10n.toggle) ? window.mtQueryMonitorL10n.toggle : 'Toggle';
                var hideText = (window.mtQueryMonitorL10n && window.mtQueryMonitorL10n.hide) ? window.mtQueryMonitorL10n.hide : 'Hide';
                this.textContent = content.style.display === 'none' ? toggleText : hideText;
            }
        });
    });

    // Initialize real-time hooks container functionality
    var realtimeContainer = document.querySelector('.almgr-realtime-hooks-container');
    if (realtimeContainer) {
        initializeRealTimeMonitoring();
    }
}

/**
 * Initialize real-time monitoring functionality
 */
function initializeRealTimeMonitoring() {
    const toggleButton = document.getElementById('almgr-toggle-realtime');
    const refreshButton = document.getElementById('almgr-refresh-hooks');
    const statusText = document.getElementById('almgr-status-text');
    const hooksCount = document.getElementById('hooks-count');
    const memoryUsage = document.getElementById('memory-usage');

    if (toggleButton) {
        toggleButton.addEventListener('click', function() {
            if (!window.mtHookMonitor) {
                console.warn('MT Hook Monitor not initialized');
                return;
            }

            if (window.mtHookMonitor.isActive) {
                stopRealTimeMonitoring();
            } else {
                startRealTimeMonitoring();
            }
        });
    }

    if (refreshButton) {
        refreshButton.addEventListener('click', function() {
            refreshHookData();
        });
    }

    function startRealTimeMonitoring() {
        if (!window.mtHookMonitor) return;

        window.mtHookMonitor.isActive = true;
        var stopText = (window.mtQueryMonitorL10n && window.mtQueryMonitorL10n.stopRealTimeUpdates) ? window.mtQueryMonitorL10n.stopRealTimeUpdates : 'Stop Real-time Updates';
        var statusActiveText = (window.mtQueryMonitorL10n && window.mtQueryMonitorL10n.statusActive) ? window.mtQueryMonitorL10n.statusActive : 'Active';

        toggleButton.textContent = stopText;
        toggleButton.classList.remove('button-primary');
        toggleButton.classList.add('button-secondary');

        if (statusText) {
            statusText.textContent = statusActiveText;
            statusText.classList.add('active');
        }

        // Poll for updates every 5 seconds
        window.mtHookMonitor.interval = setInterval(function() {
            refreshHookData();
        }, 5000);

        console.log('Real-time hook monitoring started (every 5 seconds)');
    }

    function stopRealTimeMonitoring() {
        if (!window.mtHookMonitor) return;

        window.mtHookMonitor.isActive = false;

        if (window.mtHookMonitor.interval) {
            clearInterval(window.mtHookMonitor.interval);
            window.mtHookMonitor.interval = null;
        }

        var enableText = (window.mtQueryMonitorL10n && window.mtQueryMonitorL10n.enableRealTimeUpdates) ? window.mtQueryMonitorL10n.enableRealTimeUpdates : 'Enable Real-time Updates';
        var statusStaticText = (window.mtQueryMonitorL10n && window.mtQueryMonitorL10n.statusStatic) ? window.mtQueryMonitorL10n.statusStatic : 'Static';

        toggleButton.textContent = enableText;
        toggleButton.classList.add('button-primary');
        toggleButton.classList.remove('button-secondary');

        if (statusText) {
            statusText.textContent = statusStaticText;
            statusText.classList.remove('active');
        }

        console.log('Real-time hook monitoring stopped');
    }

    function refreshHookData() {
        if (!window.mtHookMonitor) return;

        var refreshingText = (window.mtQueryMonitorL10n && window.mtQueryMonitorL10n.statusRefreshing) ? window.mtQueryMonitorL10n.statusRefreshing : 'Refreshing...';
        var originalText = statusText ? statusText.textContent : '';

        if (statusText) {
            statusText.textContent = refreshingText;
        }

        // Send AJAX request for updated hook data
        const formData = new FormData();
        formData.append('action', 'almgr_monitor_hooks');
        formData.append('nonce', window.mtHookMonitor.nonce);

        fetch(window.mtHookMonitor.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateHookDisplay(data);
                var statusActiveText = (window.mtQueryMonitorL10n && window.mtQueryMonitorL10n.statusActive) ? window.mtQueryMonitorL10n.statusActive : 'Active';
                var statusUpdatedText = (window.mtQueryMonitorL10n && window.mtQueryMonitorL10n.statusUpdated) ? window.mtQueryMonitorL10n.statusUpdated : 'Updated';

                if (statusText) {
                    statusText.textContent = window.mtHookMonitor.isActive ? statusActiveText : statusUpdatedText;
                    statusText.classList.remove('error');
                }
            } else {
                console.error('Failed to fetch hook data:', data);
                var errorText = (window.mtQueryMonitorL10n && window.mtQueryMonitorL10n.statusError) ? window.mtQueryMonitorL10n.statusError : 'Error';

                if (statusText) {
                    statusText.textContent = errorText;
                    statusText.classList.add('error');
                }
            }
        })
        .catch(error => {
            console.error('AJAX error:', error);
            var errorText = (window.mtQueryMonitorL10n && window.mtQueryMonitorL10n.statusError) ? window.mtQueryMonitorL10n.statusError : 'Error';

            if (statusText) {
                statusText.textContent = errorText;
                statusText.classList.add('error');
            }
        });
    }

    function updateHookDisplay(data) {
        // Update summary statistics
        if (hooksCount && data.hooks_captured !== undefined) {
            hooksCount.textContent = data.hooks_captured;
        }

        if (memoryUsage && data.memory_usage) {
            memoryUsage.textContent = data.memory_usage;
        }

        // Update real-time hooks table if present
        var realtimeTable = document.querySelector('.almgr-realtime-hooks-table tbody');
        if (realtimeTable && data.hooks_html) {
            realtimeTable.innerHTML = data.hooks_html;
        }

        console.log('Hook display updated with new data');
    }
}

// Auto-initialize when performance monitor is shown
document.addEventListener('DOMContentLoaded', function() {
    // Initialize mtHookMonitor object if not exists
    if (typeof window.mtHookMonitor === 'undefined') {
        window.mtHookMonitor = {
            isActive: false,
            interval: null,
            ajaxUrl: (typeof ajaxurl !== 'undefined') ? ajaxurl : '/wp-admin/admin-ajax.php',
            nonce: (typeof window.mtQueryMonitorL10n !== 'undefined' && window.mtQueryMonitorL10n.nonce) ? window.mtQueryMonitorL10n.nonce : ''
        };
    }

    // Initialize universal sortable functionality
    ALMGR_Sortable.init();

    // Initialize toggle functionality
    initializeToggleFunctionality();

    var checkForTabs = setInterval(function() {
        var hasAnyTable = document.querySelector('.query-log-table, .almgr-images-table, .almgr-hooks-table, .almgr-queries-table, .almgr-scripts-table, .almgr-styles-table');
        if (hasAnyTable) {
            ALMGR_Sortable.init();

            if (document.querySelector('.almgr-images-table')) {
                ALMGR_Images.init();
            }
            if (document.querySelector('.almgr-hooks-table')) {
                ALMGR_Hooks.init();
            }
            if (document.querySelector('.almgr-queries-table')) {
                ALMGR_Queries.init();
            }
            if (document.querySelector('.almgr-scripts-table')) {
                ALMGR_Scripts.init();
            }
            if (document.querySelector('.almgr-styles-table')) {
                ALMGR_Styles.init();
            }

            clearInterval(checkForTabs);
        }
    }, 100);
});

if (typeof window.mtPerformanceBar !== 'undefined') {
    var originalToggle = window.mtPerformanceBar.toggle;
    window.mtPerformanceBar.toggle = function() {
        originalToggle.call(this);
        setTimeout(function() {
            ALMGR_Sortable.init();
            initializeToggleFunctionality();
            if (document.querySelector('.almgr-images-table')) ALMGR_Images.init();
            if (document.querySelector('.almgr-hooks-table')) ALMGR_Hooks.init();
            if (document.querySelector('.almgr-queries-table')) ALMGR_Queries.init();
            if (document.querySelector('.almgr-scripts-table')) ALMGR_Scripts.init();
            if (document.querySelector('.almgr-styles-table')) ALMGR_Styles.init();
        }, 200);
    };
}