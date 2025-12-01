/**
 * Tenant Form JavaScript
 * Real-time validation and helpers
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        TenantForm.init();
    });
    
    var TenantForm = {
        
        /**
         * Initialize form functionality
         */
        init: function() {
            this.bindEvents();
            this.updateSubdomainPreview(); // Initial update
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Username availability check
            $('#username').on('blur', this.checkUsername);
            $('#username').on('input', this.updateSubdomainPreview);
            
            // Email availability check
            $('#email').on('blur', this.checkEmail);
            
            // Generate password
            $('#generate-password').on('click', this.generatePassword);
            
            // Form validation
            $('.novarax-tenant-form').on('submit', this.validateForm);
        },
        
        /**
         * Check username availability
         */
        checkUsername: function() {
            var username = $(this).val().trim();
            
            if (!username || username.length < 3) {
                return;
            }
            
            var $feedback = $('#username-feedback');
            $feedback.removeClass('available unavailable')
                     .html('<span class="spinner is-active"></span> Checking...');
            
            $.post(ajaxurl, {
                action: 'novarax_check_username',
                nonce: novaraxTM.nonce,
                username: username
            }, function(response) {
                if (response.success) {
                    $feedback.addClass('available')
                             .html('✓ Available!');
                } else {
                    $feedback.addClass('unavailable')
                             .html('✗ ' + response.data.message);
                }
            });
        },
        
        /**
         * Update subdomain preview
         */
        updateSubdomainPreview: function() {
            var username = $('#username').val().trim().toLowerCase();
            
            // Get the subdomain suffix from the preview element
            var previewElement = $('#subdomain-preview');
            var originalText = previewElement.text();
            
            // Extract the suffix (everything after "username")
            var suffix = '.app.novarax.ae'; // Default
            if (originalText.indexOf('.') !== -1) {
                suffix = originalText.substring(originalText.indexOf('.'));
            }
            
            // Sanitize username - only allow letters, numbers, hyphens
            username = username.replace(/[^a-z0-9-]/g, '');
            
            // Ensure it starts with a letter
            if (username && !/^[a-z]/.test(username)) {
                username = 'x' + username;
            }
            
            // Update preview
            if (username) {
                previewElement.text(username + suffix);
            } else {
                previewElement.text('username' + suffix);
            }
        },
        
        /**
         * Check email availability
         */
        checkEmail: function() {
            var email = $(this).val().trim();
            
            if (!email) {
                return;
            }
            
            var $feedback = $('#email-feedback');
            $feedback.removeClass('available unavailable')
                     .html('<span class="spinner is-active"></span> Checking...');
            
            $.post(ajaxurl, {
                action: 'novarax_check_email',
                nonce: novaraxTM.nonce,
                email: email
            }, function(response) {
                if (response.success) {
                    $feedback.addClass('available')
                             .html('✓ Available!');
                } else {
                    $feedback.addClass('unavailable')
                             .html('✗ ' + response.data.message);
                }
            });
        },
        
        /**
         * Generate secure password
         */
        generatePassword: function(e) {
            e.preventDefault();
            
            var length = 16;
            var charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
            var password = '';
            
            for (var i = 0; i < length; i++) {
                password += charset.charAt(Math.floor(Math.random() * charset.length));
            }
            
            $('#password').val(password).attr('type', 'text');
            
            // Copy to clipboard if available
            if (navigator.clipboard) {
                navigator.clipboard.writeText(password).then(function() {
                    alert('Password generated and copied to clipboard!');
                });
            }
            
            // Show password for 5 seconds then hide
            setTimeout(function() {
                $('#password').attr('type', 'password');
            }, 5000);
        },
        
        /**
         * Validate form before submission
         */
        validateForm: function(e) {
            var isValid = true;
            var errors = [];
            
            // Username validation
            var username = $('#username').val().trim();
            if (username.length < 3) {
                errors.push('Username must be at least 3 characters');
                isValid = false;
            }
            
            // Check username format
            if (!/^[a-zA-Z0-9_-]+$/.test(username)) {
                errors.push('Username can only contain letters, numbers, hyphens, and underscores');
                isValid = false;
            }
            
            // Email validation
            var email = $('#email').val().trim();
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                errors.push('Please enter a valid email address');
                isValid = false;
            }
            
            // Password validation
            var password = $('#password').val();
            if (password.length < 12) {
                errors.push('Password must be at least 12 characters');
                isValid = false;
            }
            
            // Full name validation
            var fullName = $('#full_name').val().trim();
            if (fullName.length < 2) {
                errors.push('Please enter a valid full name');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fix the following errors:\n\n' + errors.join('\n'));
            }
            
            return isValid;
        }
    };
    
})(jQuery);