/**
 * NovaRax Registration Form JavaScript
 * Location: /wp-content/mu-plugins/novarax-registration-system/assets/js/registration.js
 */

jQuery(document).ready(function($) {
    
    // ============================================
    // Username validation & subdomain preview
    // ============================================
    let usernameTimeout;
    $('#username').on('input', function() {
        const username = $(this).val().toLowerCase().replace(/[^a-z0-9_-]/g, '');
        $(this).val(username);
        
        const feedback = $('#username-feedback');
        const subdomainDisplay = $('#subdomain-display');
        
        // Update subdomain preview
        if (username.length > 0) {
            subdomainDisplay.text(username + '.app.novarax.ae');
        } else {
            subdomainDisplay.text('yourusername.app.novarax.ae');
        }
        
        // Clear previous timeout
        clearTimeout(usernameTimeout);
        
        if (username.length < 3) {
            feedback.removeClass('success error').text('');
            return;
        }
        
        feedback.removeClass('success error').text('Checking...').css('color', 'var(--text-tertiary)');
        
        // Debounce AJAX call
        usernameTimeout = setTimeout(function() {
            $.ajax({
                url: novaraxAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'novarax_check_username',
                    username: username,
                    nonce: novaraxAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.available) {
                            feedback.removeClass('error').addClass('success').text('✓ Available');
                        } else {
                            feedback.removeClass('success').addClass('error').text('✗ Username taken');
                        }
                    }
                },
                error: function() {
                    feedback.removeClass('success').addClass('error').text('Error checking availability');
                }
            });
        }, 500);
    });
    
    // ============================================
    // Email validation
    // ============================================
    let emailTimeout;
    $('#email').on('blur', function() {
        const email = $(this).val();
        const feedback = $('#email-feedback');
        
        if (!email || !isValidEmail(email)) {
            feedback.removeClass('success error').text('');
            return;
        }
        
        feedback.removeClass('success error').text('Checking...').css('color', 'var(--text-tertiary)');
        
        clearTimeout(emailTimeout);
        emailTimeout = setTimeout(function() {
            $.ajax({
                url: novaraxAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'novarax_check_email',
                    email: email,
                    nonce: novaraxAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.available) {
                            feedback.removeClass('error').addClass('success').text('✓ Valid');
                        } else {
                            feedback.removeClass('success').addClass('error').text('✗ Email already registered');
                        }
                    }
                }
            });
        }, 500);
    });
    
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    
    // ============================================
    // Password strength indicator
    // ============================================
    $('#password').on('input', function() {
        const password = $(this).val();
        const strengthBar = $('#strength-bar');
        const strengthText = $('#strength-text');
        
        if (password.length === 0) {
            strengthBar.removeClass('weak medium strong').css('width', '0%');
            strengthText.text('Enter a password');
            return;
        }
        
        const strength = calculatePasswordStrength(password);
        
        strengthBar.removeClass('weak medium strong');
        
        if (strength < 3) {
            strengthBar.addClass('weak');
            strengthText.text('Weak password - Add more characters and variety');
        } else if (strength < 5) {
            strengthBar.addClass('medium');
            strengthText.text('Medium strength - Consider adding special characters');
        } else {
            strengthBar.addClass('strong');
            strengthText.text('Strong password ✓');
        }
    });
    
    function calculatePasswordStrength(password) {
        let strength = 0;
        
        // Length check
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        
        // Character variety
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;
        
        return strength;
    }
    
    // ============================================
    // Password visibility toggle
    // ============================================
    $('#password-toggle').on('click', function() {
        const input = $('#password');
        const type = input.attr('type') === 'password' ? 'text' : 'password';
        input.attr('type', type);
        $('.eye-open, .eye-closed').toggle();
    });
    
    // ============================================
    // Password generator
    // ============================================
    $('#password-generate').on('click', function() {
        const password = generateSecurePassword();
        $('#password').val(password).trigger('input');
        
        // Copy to clipboard
        if (navigator.clipboard) {
            navigator.clipboard.writeText(password).then(() => {
                $(this).css('color', 'var(--accent-primary)');
                setTimeout(() => $(this).css('color', ''), 2000);
                
                // Show tooltip
                showTooltip(this, 'Copied!');
            });
        }
    });
    
    function generateSecurePassword() {
        const lowercase = 'abcdefghijklmnopqrstuvwxyz';
        const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        const numbers = '0123456789';
        const special = '!@#$%^&*';
        const allChars = lowercase + uppercase + numbers + special;
        
        let password = '';
        
        // Ensure at least one of each type
        password += lowercase[Math.floor(Math.random() * lowercase.length)];
        password += uppercase[Math.floor(Math.random() * uppercase.length)];
        password += numbers[Math.floor(Math.random() * numbers.length)];
        password += special[Math.floor(Math.random() * special.length)];
        
        // Fill the rest randomly
        for (let i = 4; i < 16; i++) {
            password += allChars[Math.floor(Math.random() * allChars.length)];
        }
        
        // Shuffle the password
        return password.split('').sort(() => Math.random() - 0.5).join('');
    }
    
    function showTooltip(element, text) {
        const tooltip = $('<div class="tooltip">' + text + '</div>');
        tooltip.css({
            position: 'absolute',
            top: $(element).offset().top - 30,
            left: $(element).offset().left,
            background: 'var(--bg-elevated)',
            color: 'var(--text-primary)',
            padding: '4px 8px',
            borderRadius: '4px',
            fontSize: '12px',
            zIndex: 1000,
            boxShadow: 'var(--shadow-md)'
        });
        $('body').append(tooltip);
        setTimeout(() => tooltip.fadeOut(() => tooltip.remove()), 2000);
    }
    
    // ============================================
    // Form submission
    // ============================================
    $('#novarax-registration-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const btn = $('#submit-btn');
        const btnText = btn.find('.btn-text');
        const btnLoader = btn.find('.btn-loader');
        
        // Validate terms checkbox
        if (!$('#terms').is(':checked')) {
            showMessage('error', 'Please agree to the Terms of Service and Privacy Policy');
            return;
        }
        
        // Disable button and show loader
        btn.prop('disabled', true);
        btnText.hide();
        btnLoader.show();
        
        // Prepare form data
        const formData = {
            action: 'novarax_register_user',
            nonce: novaraxAjax.nonce,
            full_name: $('#full_name').val(),
            username: $('#username').val(),
            email: $('#email').val(),
            password: $('#password').val(),
            company: $('#company').val(),
            country_code: $('#country_code').val(),
            phone: $('#phone').val(),
            marketing: $('#marketing').is(':checked') ? '1' : '0'
        };
        
        $.ajax({
            url: novaraxAjax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data.message || 'Account created successfully! Redirecting...');
                    
                    // Store tenant ID in session storage for checkout
                    if (response.data.tenant_id) {
                        sessionStorage.setItem('novarax_tenant_id', response.data.tenant_id);
                    }
                    
                    // Redirect after delay
                    setTimeout(() => {
                        window.location.href = response.data.redirect_url || novaraxAjax.redirectUrl;
                    }, 1500);
                } else {
                    showMessage('error', response.data.message || 'Registration failed. Please try again.');
                    btn.prop('disabled', false);
                    btnText.show();
                    btnLoader.hide();
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred. Please try again.';
                
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                }
                
                showMessage('error', errorMessage);
                btn.prop('disabled', false);
                btnText.show();
                btnLoader.hide();
            }
        });
    });
    
    // ============================================
    // Helper: Show message
    // ============================================
    function showMessage(type, message) {
        const msgBox = $('#form-message');
        msgBox.removeClass('success error').addClass(type).html(message).fadeIn();
        
        // Auto-hide after 5 seconds unless it's a success message
        if (type !== 'success') {
            setTimeout(() => msgBox.fadeOut(), 5000);
        }
    }
    
    // ============================================
    // Accessibility: Enter key navigation
    // ============================================
    $('.novarax-form input').on('keypress', function(e) {
        if (e.which === 13 && !$(this).is('#password')) {
            e.preventDefault();
            const inputs = $('.novarax-form input:visible');
            const index = inputs.index(this);
            if (index < inputs.length - 1) {
                inputs.eq(index + 1).focus();
            }
        }
    });
    
    // ============================================
    // Auto-focus first input
    // ============================================
    setTimeout(() => {
        $('#full_name').focus();
    }, 100);
    
});