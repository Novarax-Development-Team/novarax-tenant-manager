/**
 * NovaRax Admin JavaScript
 */

(function($) {
    'use strict';
    
    // Initialize on document ready
    $(document).ready(function() {
        NovaRaxAdmin.init();
    });
    
    var NovaRaxAdmin = {
        
        /**
         * Initialize all admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initColorPicker();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Activate tenant
            $(document).on('click', '.novarax-activate-tenant', this.activateTenant);
            
            // Suspend tenant
            $(document).on('click', '.novarax-suspend-tenant', this.suspendTenant);
            
            // Delete tenant
            $(document).on('click', '.novarax-delete-tenant', this.deleteTenant);
            
            // Provision tenant
            $(document).on('click', '.novarax-provision-tenant', this.provisionTenant);
            
            // Export CSV
            $(document).on('click', '#novarax-export-csv', this.exportCSV);
            
            // Refresh list
            $(document).on('click', '#novarax-refresh-list', function() {
                location.reload();
            });
        },
        
        /**
         * Initialize color picker
         */
        initColorPicker: function() {
            if ($.fn.wpColorPicker) {
                $('.novarax-color-picker').wpColorPicker();
            }
        },
        
        /**
         * Activate tenant
         */
        activateTenant: function(e) {
            e.preventDefault();
            
            if (!confirm(novaraxTM.strings.confirmActivate)) {
                return;
            }
            
            var $btn = $(this);
            var tenantId = $btn.data('tenant-id');
            
            $btn.prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'novarax_activate_tenant',
                nonce: novaraxTM.nonce,
                tenant_id: tenantId
            }, function(response) {
                if (response.success) {
                    NovaRaxAdmin.showNotice('success', response.data.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    NovaRaxAdmin.showNotice('error', response.data.message);
                    $btn.prop('disabled', false);
                }
            });
        },
        
        /**
         * Suspend tenant
         */
        suspendTenant: function(e) {
            e.preventDefault();
            
            if (!confirm(novaraxTM.strings.confirmSuspend)) {
                return;
            }
            
            var $btn = $(this);
            var tenantId = $btn.data('tenant-id');
            
            $btn.prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'novarax_suspend_tenant',
                nonce: novaraxTM.nonce,
                tenant_id: tenantId
            }, function(response) {
                if (response.success) {
                    NovaRaxAdmin.showNotice('success', response.data.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    NovaRaxAdmin.showNotice('error', response.data.message);
                    $btn.prop('disabled', false);
                }
            });
        },
        
        /**
         * Delete tenant
         */
        deleteTenant: function(e) {
            e.preventDefault();
            
            if (!confirm(novaraxTM.strings.confirmDelete)) {
                return;
            }
            
            var hardDelete = confirm('Permanently delete tenant and all data? (This cannot be undone!)');
            
            var $btn = $(this);
            var tenantId = $btn.data('tenant-id');
            
            $btn.prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'novarax_delete_tenant',
                nonce: novaraxTM.nonce,
                tenant_id: tenantId,
                hard_delete: hardDelete
            }, function(response) {
                if (response.success) {
                    NovaRaxAdmin.showNotice('success', response.data.message);
                    setTimeout(function() {
                        window.location.href = adminUrl + 'admin.php?page=novarax-tenants-list';
                    }, 1500);
                } else {
                    NovaRaxAdmin.showNotice('error', response.data.message);
                    $btn.prop('disabled', false);
                }
            });
        },
        
        /**
         * Provision tenant
         */
        provisionTenant: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var tenantId = $btn.data('tenant-id');
            
            $btn.prop('disabled', true).text(novaraxTM.strings.provisioning);
            
            $.post(ajaxurl, {
                action: 'novarax_provision_tenant',
                nonce: novaraxTM.nonce,
                tenant_id: tenantId
            }, function(response) {
                if (response.success) {
                    NovaRaxAdmin.showNotice('success', response.data.message);
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    NovaRaxAdmin.showNotice('error', response.data.message);
                    $btn.prop('disabled', false).text('Provision Now');
                }
            });
        },
        
        /**
         * Export tenants to CSV
         */
        exportCSV: function(e) {
            e.preventDefault();
            
            var status = $('input[name="status"]').val() || 'all';
            
            $.post(ajaxurl, {
                action: 'novarax_export_tenants',
                nonce: novaraxTM.nonce,
                status: status
            }, function(response) {
                if (response.success) {
                    // Download file
                    window.location.href = response.data.download_url;
                    NovaRaxAdmin.showNotice('success', response.data.count + ' tenants exported');
                } else {
                    NovaRaxAdmin.showNotice('error', response.data.message);
                }
            });
        },
        
        /**
         * Show admin notice
         */
        showNotice: function(type, message) {
            var noticeClass = 'notice-' + type;
            var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.wrap > h1').after($notice);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };
    
})(jQuery);


jQuery(document).ready(function($) {
    'use strict';
    
    // ============================================
    // UI TOGGLE HANDLER
    // ============================================
    
    $('#novarax-ui-toggle').on('change', function() {
        const isModern = $(this).is(':checked');
        const mode = isModern ? 'modern' : 'classic';
        const $container = $('.novarax-ui-toggle-container');
        const $form = $('#novarax-tenants-form');
        
        // Prevent multiple clicks
        if ($container.hasClass('loading')) {
            return false;
        }
        
        // Create and show progress bar
        const $progressBar = createProgressBar();
        
        // Add loading state
        $container.addClass('loading');
        
        // Visual feedback - disable toggle temporarily
        $(this).prop('disabled', true);
        
        // Send AJAX request to save preference
        $.ajax({
            url: novaraxAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'novarax_toggle_ui_mode',
                mode: mode,
                nonce: novaraxAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Complete progress bar
                    completeProgressBar($progressBar);
                    
                    // Show success message
                    showNotice(response.data.message, 'success');
                    
                    // Add transition effect and reload
                    $form.fadeOut(300, function() {
                        setTimeout(function() {
                            location.reload();
                        }, 200);
                    });
                } else {
                    // Hide progress bar
                    removeProgressBar($progressBar);
                    
                    // Show error message
                    showNotice(response.data.message || 'Failed to switch UI mode', 'error');
                    $container.removeClass('loading');
                    $('#novarax-ui-toggle').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                // Hide progress bar
                removeProgressBar($progressBar);
                
                console.error('Toggle UI Error:', error);
                showNotice('Failed to switch UI mode. Please try again.', 'error');
                $container.removeClass('loading');
                
                // Revert checkbox state
                $('#novarax-ui-toggle').prop('checked', !isModern).prop('disabled', false);
            }
        });
    });
    
    // ============================================
    // NOTIFICATION SYSTEM
    // ============================================
    
    /**
     * Create progress bar
     */
    function createProgressBar() {
        // Remove any existing progress bar
        $('.novarax-loading-bar').remove();
        
        const $progressBar = $('<div>', {
            'class': 'novarax-loading-bar'
        });
        
        const $progress = $('<div>', {
            'class': 'novarax-loading-bar-progress'
        });
        
        $progressBar.append($progress);
        $('body').append($progressBar);
        
        // Trigger reflow to enable transition
        setTimeout(function() {
            $progressBar.addClass('active');
        }, 10);
        
        return $progressBar;
    }
    
    /**
     * Complete progress bar animation
     */
    function completeProgressBar($progressBar) {
        $progressBar.addClass('complete');
        
        setTimeout(function() {
            $progressBar.addClass('fadeout');
            setTimeout(function() {
                $progressBar.remove();
            }, 300);
        }, 300);
    }
    
    /**
     * Remove progress bar
     */
    function removeProgressBar($progressBar) {
        $progressBar.addClass('fadeout');
        setTimeout(function() {
            $progressBar.remove();
        }, 300);
    }
    
    /**
     * Show notification message
     * @param {string} message - The message to display
     * @param {string} type - Type of notice: 'success', 'error', 'warning', 'info'
     * @param {number} duration - How long to show (milliseconds)
     */
    function showNotice(message, type, duration) {
        type = type || 'info';
        duration = duration || 3000;
        
        const noticeClass = 'notice-' + type;
        const icon = getNoticeIcon(type);
        
        // Create notice element
        const $notice = $('<div>', {
            'class': 'notice ' + noticeClass + ' is-dismissible novarax-toggle-notice',
            'html': '<p><span class="dashicons ' + icon + '" style="margin-right: 8px;"></span>' + message + '</p>'
        });
        
        // Add dismiss button handler
        const $dismissButton = $('<button>', {
            'type': 'button',
            'class': 'notice-dismiss',
            'html': '<span class="screen-reader-text">Dismiss this notice.</span>'
        }).on('click', function() {
            $notice.fadeOut(200, function() {
                $(this).remove();
            });
        });
        
        $notice.append($dismissButton);
        
        // Remove any existing toggle notices
        $('.novarax-toggle-notice').remove();
        
        // Insert notice
        if ($('.wrap > h1').length) {
            $('.wrap > h1').after($notice);
        } else {
            $('.wrap').prepend($notice);
        }
        
        // Animate in
        $notice.hide().slideDown(300);
        
        // Auto dismiss after duration
        if (duration > 0) {
            setTimeout(function() {
                if ($notice.length) {
                    $notice.fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            }, duration);
        }
    }
    
    /**
     * Get appropriate dashicon for notice type
     */
    function getNoticeIcon(type) {
        const icons = {
            'success': 'dashicons-yes-alt',
            'error': 'dashicons-dismiss',
            'warning': 'dashicons-warning',
            'info': 'dashicons-info'
        };
        return icons[type] || icons.info;
    }
    
    // ============================================
    // REFRESH BUTTON HANDLER
    // ============================================
    
    $('#novarax-refresh-list').on('click', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $icon = $button.find('.dashicons');
        
        // Add spinning animation
        $icon.addClass('dashicons-update-spin');
        $button.prop('disabled', true);
        
        // Reload page
        setTimeout(function() {
            location.reload();
        }, 500);
    });
    
    // ============================================
    // EXPORT CSV HANDLER
    // ============================================
    
    $('#novarax-export-csv').on('click', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        $button.prop('disabled', true).text('Exporting...');
        
        // Get current filters
        const status = $('input[name="status"]').val() || 'all';
        const search = $('input[name="s"]').val() || '';
        
        // Build export URL
        const exportUrl = novaraxAdmin.ajaxurl + 
            '?action=novarax_export_tenants' +
            '&status=' + encodeURIComponent(status) +
            '&search=' + encodeURIComponent(search) +
            '&nonce=' + novaraxAdmin.nonce;
        
        // Trigger download
        window.location.href = exportUrl;
        
        // Reset button after delay
        setTimeout(function() {
            $button.prop('disabled', false).html('<span class="dashicons dashicons-download"></span> Export CSV');
        }, 2000);
    });
    
    // ============================================
    // BULK ACTIONS CONFIRMATION
    // ============================================
    
    $('select[name="action"], select[name="action2"]').on('change', function() {
        const action = $(this).val();
        
        if (action === 'delete') {
            $(this).closest('form').on('submit', function(e) {
                const checkedCount = $('input[name="tenant_ids[]"]:checked').length;
                
                if (checkedCount > 0) {
                    const confirmed = confirm(
                        'Are you sure you want to delete ' + checkedCount + ' tenant(s)?\n' +
                        'This action cannot be undone.'
                    );
                    
                    if (!confirmed) {
                        e.preventDefault();
                        return false;
                    }
                }
            });
        }
    });
    
    // ============================================
    // SELECT ALL CHECKBOXES ENHANCEMENT
    // ============================================
    
    $('#cb-select-all-1, #cb-select-all-2').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('input[name="tenant_ids[]"]').prop('checked', isChecked);
        
        // Visual feedback
        if (isChecked) {
            $('.wp-list-table tbody tr').addClass('selected');
        } else {
            $('.wp-list-table tbody tr').removeClass('selected');
        }
    });
    
    // Individual checkbox handler
    $('input[name="tenant_ids[]"]').on('change', function() {
        const $row = $(this).closest('tr');
        
        if ($(this).prop('checked')) {
            $row.addClass('selected');
        } else {
            $row.removeClass('selected');
        }
        
        // Update select all checkbox state
        updateSelectAllCheckbox();
    });
    
    function updateSelectAllCheckbox() {
        const totalCheckboxes = $('input[name="tenant_ids[]"]').length;
        const checkedCheckboxes = $('input[name="tenant_ids[]"]:checked').length;
        
        if (checkedCheckboxes === 0) {
            $('#cb-select-all-1, #cb-select-all-2').prop('checked', false).prop('indeterminate', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            $('#cb-select-all-1, #cb-select-all-2').prop('checked', true).prop('indeterminate', false);
        } else {
            $('#cb-select-all-1, #cb-select-all-2').prop('checked', false).prop('indeterminate', true);
        }
    }
    
    // ============================================
    // SEARCH BOX ENHANCEMENT
    // ============================================
    
    const $searchInput = $('.search-box input[type="search"]');
    
    // Add clear button functionality
    if ($searchInput.length && $searchInput.val()) {
        addClearButton();
    }
    
    $searchInput.on('input', function() {
        if ($(this).val()) {
            addClearButton();
        } else {
            removeClearButton();
        }
    });
    
    function addClearButton() {
        if ($('.search-clear-btn').length) return;
        
        const $clearBtn = $('<button>', {
            'type': 'button',
            'class': 'button search-clear-btn',
            'html': '<span class="dashicons dashicons-no-alt"></span>',
            'css': {
                'margin-left': '5px',
                'padding': '0 10px'
            }
        }).on('click', function() {
            $searchInput.val('').focus();
            removeClearButton();
        });
        
        $searchInput.after($clearBtn);
    }
    
    function removeClearButton() {
        $('.search-clear-btn').remove();
    }
    
    // ============================================
    // HIGHLIGHT NEW/UPDATED ROWS
    // ============================================
    
    // Check URL for success messages
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');
    
    if (message) {
        const messages = {
            'created': 'Tenant created successfully!',
            'updated': 'Tenant updated successfully!',
            'deleted': 'Tenant deleted successfully!'
        };
        
        if (messages[message]) {
            showNotice(messages[message], 'success', 5000);
        }
    }
    
    // ============================================
    // KEYBOARD SHORTCUTS
    // ============================================
    
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + K to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            $searchInput.focus();
        }
        
        // Ctrl/Cmd + Shift + T to toggle UI mode
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'T') {
            e.preventDefault();
            $('#novarax-ui-toggle').trigger('click');
        }
    });
    
    // ============================================
    // SMOOTH SCROLLING FOR ANCHOR LINKS
    // ============================================
    
    $('a[href^="#"]').on('click', function(e) {
        const target = $(this.getAttribute('href'));
        
        if (target.length) {
            e.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 100
            }, 600);
        }
    });
    
    // ============================================
    // INITIALIZE TOOLTIPS
    // ============================================
    
    $('[data-tooltip]').each(function() {
        $(this).attr('title', $(this).data('tooltip'));
    });
    
    // ============================================
    // STORAGE BAR ANIMATION
    // ============================================
    
    $('.novarax-storage-bar-fill').each(function() {
        const $bar = $(this);
        const width = $bar.css('width');
        
        $bar.css('width', '0');
        
        setTimeout(function() {
            $bar.css('width', width);
        }, 100);
    });
    
    // ============================================
    // CONSOLE INFO
    // ============================================
    
    console.log('%c🚀 NovaRax Tenant Manager', 'color: #667eea; font-size: 16px; font-weight: bold;');
    console.log('%cUI Toggle Active', 'color: #10b981; font-size: 12px;');
    
});

// ============================================
// ADDITIONAL UTILITY FUNCTIONS
// ============================================

/**
 * Format file size to human readable format
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
}

/**
 * Copy text to clipboard
 */
function copyToClipboard(text) {
    const $temp = jQuery('<input>');
    jQuery('body').append($temp);
    $temp.val(text).select();
    document.execCommand('copy');
    $temp.remove();
    
    return true;
}