<?php
/**
 * NovaRax Registration Form Template - Supabase Inspired
 * Location: /wp-content/mu-plugins/novarax-registration-system/templates/registration-form.php
 */

if (!defined('ABSPATH')) exit;

// Check if user is already logged in
if (is_user_logged_in()) {
    $redirect = get_option('novarax_post_registration_redirect', '/marketplace');
    echo '<script>window.location.href = "' . esc_url(home_url($redirect)) . '";</script>';
    return;
}
?>

<div class="novarax-registration-wrapper">
    <div class="novarax-registration-container">
        
        <!-- Left Side - Branding -->
        <div class="novarax-branding">
            <div class="novarax-branding-content">
                <!-- Logo -->
                <div class="novarax-logo">
                    <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="40" height="40" rx="8" fill="url(#gradient)"/>
                        <path d="M12 28V12L28 20L12 28Z" fill="white"/>
                        <defs>
                            <linearGradient id="gradient" x1="0" y1="0" x2="40" y2="40">
                                <stop offset="0%" stop-color="#3ECFAB"/>
                                <stop offset="100%" stop-color="#1E88E5"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <span>NovaRax</span>
                </div>
                
                <!-- Headline -->
                <h1>Build your dream SaaS platform</h1>
                <p>Everything you need to manage your multi-tenant WordPress applications at scale.</p>
                
                <!-- Features -->
                <div class="novarax-features">
                    <div class="feature-item">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>Instant dashboard provisioning</span>
                    </div>
                    <div class="feature-item">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>Powerful module marketplace</span>
                    </div>
                    <div class="feature-item">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>Enterprise-grade security</span>
                    </div>
                    <div class="feature-item">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>24/7 dedicated support</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Side - Registration Form -->
        <div class="novarax-form-side">
            <div class="novarax-form-content">
                <h2>Create your account</h2>
                <p class="subtitle">Start building in minutes. No credit card required.</p>
                
                <form id="novarax-registration-form" class="novarax-form">
                    
                    <!-- Full Name -->
                    <div class="form-group">
                        <label for="full_name">Full Name <span class="required">*</span></label>
                        <input type="text" 
                               id="full_name" 
                               name="full_name" 
                               placeholder="John Doe" 
                               required
                               autocomplete="name">
                    </div>
                    
                    <!-- Username -->
                    <div class="form-group">
                        <label for="username">Username <span class="required">*</span></label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               placeholder="johndoe" 
                               required
                               autocomplete="username"
                               pattern="[a-z0-9_-]+"
                               title="Only lowercase letters, numbers, hyphens and underscores">
                        <div class="input-hint subdomain-preview">
                            Your dashboard: <strong id="subdomain-display">yourusername.app.novarax.ae</strong>
                        </div>
                        <div id="username-feedback" class="validation-message"></div>
                    </div>
                    
                    <!-- Email -->
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               placeholder="john@example.com" 
                               required
                               autocomplete="email">
                        <div id="email-feedback" class="validation-message"></div>
                    </div>
                    
                    <!-- Password -->
                    <div class="form-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <div class="password-input-wrapper">
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Create a strong password" 
                                   required
                                   minlength="8"
                                   autocomplete="new-password">
                            <button type="button" class="password-toggle" id="password-toggle" title="Show password">
                                <svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                            <button type="button" class="password-generate" id="password-generate" title="Generate password">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="23 4 23 10 17 10"></polyline>
                                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="strength-meter">
                                <div class="strength-bar" id="strength-bar"></div>
                            </div>
                            <div class="strength-text" id="strength-text">Enter a password</div>
                        </div>
                    </div>
                    
                    <!-- Company Name (Optional) -->
                    <div class="form-group">
                        <label for="company">Company Name</label>
                        <input type="text" 
                               id="company" 
                               name="company" 
                               placeholder="Acme Inc." 
                               autocomplete="organization">
                    </div>
                    
                    <!-- Phone Number (Optional) -->
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <div class="phone-input-wrapper">
                            <select id="country_code" name="country_code" class="country-select">
                                <option value="+971" data-flag="🇦🇪">🇦🇪 +971</option>
                                <option value="+1" data-flag="🇺🇸">🇺🇸 +1</option>
                                <option value="+44" data-flag="🇬🇧">🇬🇧 +44</option>
                                <option value="+961" data-flag="🇱🇧">🇱🇧 +961</option>
                                <option value="+33" data-flag="🇫🇷">🇫🇷 +33</option>
                                <option value="+49" data-flag="🇩🇪">🇩🇪 +49</option>
                            </select>
                            <input type="tel" 
                                   id="phone" 
                                   name="phone" 
                                   placeholder="50 123 4567"
                                   autocomplete="tel">
                        </div>
                    </div>
                    
                    <!-- Terms & Conditions -->
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="terms" name="terms" required>
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">
                                I agree to the <a href="/terms" target="_blank">Terms of Service</a> and 
                                <a href="/privacy" target="_blank">Privacy Policy</a>
                            </span>
                        </label>
                    </div>
                    
                    <!-- Marketing Consent -->
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="marketing" name="marketing">
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">
                                Send me product updates and special offers
                            </span>
                        </label>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn-primary" id="submit-btn">
                        <span class="btn-text">Create Account</span>
                        <span class="btn-loader" style="display:none;">
                            <svg class="spinner" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="2" x2="12" y2="6"></line>
                                <line x1="12" y1="18" x2="12" y2="22"></line>
                                <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
                                <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
                                <line x1="2" y1="12" x2="6" y2="12"></line>
                                <line x1="18" y1="12" x2="22" y2="12"></line>
                                <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
                                <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
                            </svg>
                        </span>
                    </button>
                    
                    <!-- Footer -->
                    <div class="form-footer">
                        Already have an account? <a href="<?php echo wp_login_url(); ?>">Sign in</a>
                    </div>
                </form>
                
                <!-- Error/Success Messages -->
                <div id="form-message" class="form-message" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>