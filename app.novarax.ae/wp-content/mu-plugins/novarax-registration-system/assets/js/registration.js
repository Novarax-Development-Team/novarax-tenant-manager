jQuery(document).ready(function($) {
    // Username validation with debounce
    let usernameTimeout;
    $('#username').on('input', function() {
        const username = $(this).val().toLowerCase().replace(/[^a-z0-9-]/g, '');
        $(this).val(username);
        $('#subdomain-text').text(username || 'username');
        
        clearTimeout(usernameTimeout);
        if (username.length >= 3) {
            usernameTimeout = setTimeout(() => checkUsername(username), 500);
        }
    });
    
    function checkUsername(username) {
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
                    const msg = $('#username-validation');
                    if (response.data.available) {
                        msg.removeClass('error').addClass('success').text('✓ Username available');
                    } else {
                        msg.removeClass('success').addClass('error').text('✗ ' + response.data.message);
                    }
                }
            }
        });
    }
    
    // Password strength meter
    $('#password').on('input', function() {
        const password = $(this).val();
        const strength = calculatePasswordStrength(password);
        updatePasswordStrength(strength);
    });
    
    function calculatePasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;
        return strength;
    }
    
    function updatePasswordStrength(strength) {
        const bar = $('#strength-bar');
        const text = $('#strength-text');
        const colors = ['#FF4444', '#FFA500', '#FFD700', '#90EE90', '#3ECFAB'];
        const labels = ['Too weak', 'Weak', 'Fair', 'Good', 'Strong'];
        const widths = ['20%', '40%', '60%', '80%', '100%'];
        
        bar.css({
            width: widths[strength] || '0%',
            background: colors[strength] || '#2A2A2A'
        });
        text.text(labels[strength] || 'Enter a password').css('color', colors[strength] || '#707070');
    }
    
    // Password toggle
    $('#password-toggle').on('click', function() {
        const input = $('#password');
        const type = input.attr('type') === 'password' ? 'text' : 'password';
        input.attr('type', type);
        $('.eye-open, .eye-closed').toggle();
    });
    
    // Password generator
    $('#password-generate').on('click', function() {
        const password = generateSecurePassword();
        $('#password').val(password).trigger('input');
        
        // Copy to clipboard
        navigator.clipboard.writeText(password).then(() => {
            $(this).css('color', '#3ECFAB');
            setTimeout(() => $(this).css('color', ''), 2000);
        });
    });
    
    function generateSecurePassword() {
        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        let password = '';
        for (let i = 0; i < 16; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return password;
    }
    
    // Form submission
    $('#novarax-registration-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const btn = $('#submit-btn');
        const btnText = btn.find('.btn-text');
        const btnLoader = btn.find('.btn-loader');
        
        // Disable button
        btn.prop('disabled', true);
        btnText.hide();
        btnLoader.show();
        
        $.ajax({
            url: novaraxAjax.ajaxurl,
            type: 'POST',
            data: form.serialize() + '&action=novarax_register_user&nonce=' + novaraxAjax.nonce,
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data.message);
                    setTimeout(() => {
                        window.location.href = response.data.redirect_url;
                    }, 1000);
                } else {
                    showMessage('error', response.data.message);
                    btn.prop('disabled', false);
                    btnText.show();
                    btnLoader.hide();
                }
            },
            error: function() {
                showMessage('error', 'An error occurred. Please try again.');
                btn.prop('disabled', false);
                btnText.show();
                btnLoader.hide();
            }
        });
    });
    
    function showMessage(type, message) {
        const msgBox = $('#form-message');
        msgBox.removeClass('success error').addClass(type).text(message).fadeIn();
        setTimeout(() => msgBox.fadeOut(), 5000);
    }
});