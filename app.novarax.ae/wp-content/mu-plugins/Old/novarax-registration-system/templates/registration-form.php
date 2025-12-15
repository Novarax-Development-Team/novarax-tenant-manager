
<div class="novarax-registration-wrapper">
    <div class="novarax-registration-container">
        <!-- Left side - Branding -->
        <div class="novarax-branding">
            <div class="novarax-branding-content">
                <div class="novarax-logo">
                    <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="40" height="40" rx="8" fill="url(#gradient)"/>
                        <path d="M12 28L20 12L28 28H24L20 20L16 28H12Z" fill="white"/>
                        <defs>
                            <linearGradient id="gradient" x1="0" y1="0" x2="40" y2="40">
                                <stop offset="0%" stop-color="#3ECFAB"/>
                                <stop offset="100%" stop-color="#1E88E5"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <span>NovaRax</span>
                </div>
                
                <h1>Start building your business today</h1>
                <p>Join thousands of teams already using NovaRax to power their operations.</p>
                
                <div class="novarax-features">
                    <div class="feature-item">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                        </svg>
                        <span>Free 14-day trial</span>
                    </div>
                    <div class="feature-item">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                        </svg>
                        <span>No credit card required</span>
                    </div>
                    <div class="feature-item">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                        </svg>
                        <span>Cancel anytime</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right side - Form -->
        <div class="novarax-form-side">
            <div class="novarax-form-content">
                <h2>Create your account</h2>
                <p class="subtitle">Get started with your free NovaRax workspace</p>
                
                <form id="novarax-registration-form" class="novarax-form">
                    <!-- Full Name -->
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" 
                               id="full_name" 
                               name="full_name" 
                               placeholder="John Doe" 
                               required
                               autocomplete="name">
                    </div>
                    
                    <!-- Username -->
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               placeholder="john-doe" 
                               required
                               autocomplete="username"
                               pattern="[a-z0-9-]+"
                               title="Letters, numbers, and hyphens only">
                        <div class="input-hint" id="username-hint">
                            <span class="subdomain-preview">Your dashboard: <strong id="subdomain-text">username</strong>.app.novarax.ae</span>
                        </div>
                        <div class="validation-message" id="username-validation"></div>
                    </div>
                    
                    <!-- Email -->
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               placeholder="john@company.com" 
                               required
                               autocomplete="email">
                    </div>
                    
                    <!-- Password -->
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <div class="password-input-wrapper">
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter a strong password" 
                                   required
                                   autocomplete="new-password">
                            <button type="button" 
                                    class="password-toggle" 
                                    id="password-toggle"
                                    aria-label="Toggle password visibility">
                                <svg class="eye-open" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                </svg>
                                <svg class="eye-closed" width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="display:none;">
                                    <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"/>
                                    <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"/>
                                </svg>
                            </button>
                            <button type="button" 
                                    class="password-generate" 
                                    id="password-generate"
                                    title="Generate secure password">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M8 3a5 5 0 100 10A5 5 0 008 3zM2 8a6 6 0 1112 0A6 6 0 012 8z"/>
                                    <path d="M8 6.5a.5.5 0 01.5.5v2a.5.5 0 01-1 0V7a.5.5 0 01.5-.5z"/>
                                    <path d="M8 10.5a.5.5 0 110 1 .5.5 0 010-1z"/>
                                </svg>
                            </button>
                        </div>
                        <div class="password-strength" id="password-strength">
                            <div class="strength-meter">
                                <div class="strength-bar" id="strength-bar"></div>
                            </div>
                            <span class="strength-text" id="strength-text">Enter a password</span>
                        </div>
                    </div>
                    
                    <!-- Company Name (Optional) -->
                    <div class="form-group">
                        <label for="company">Company Name</label>
                        <input type="text" 
                               id="company" 
                               name="company" 
                               placeholder="Acme Inc" 
                               autocomplete="organization">
                    </div>
                    
                    <!-- Phone Number -->
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <div class="phone-input-wrapper">
                            <select id="country_code" name="country_code" class="country-select">
                                <option value="+1" data-flag="🇺🇸">🇺🇸 +1</option>
                                <option value="+44" data-flag="🇬🇧">🇬🇧 +44</option>
                                <option value="+971" data-flag="🇦🇪" selected>🇦🇪 +971</option>
                                <option value="+961" data-flag="🇱🇧">🇱🇧 +961</option>
                            </select>
                            <input type="tel" 
                                   id="phone" 
                                   name="phone" 
                                   placeholder="50 123 4567" 
                                   autocomplete="tel">
                        </div>
                    </div>
                    
                    <!-- Terms & Conditions -->
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="terms" name="terms" required>
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">
                                I agree to the <a href="/terms" target="_blank">Terms of Service</a> and <a href="/privacy" target="_blank">Privacy Policy</a>
                            </span>
                        </label>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn-primary" id="submit-btn">
                        <span class="btn-text">Create Account</span>
                        <span class="btn-loader" style="display:none;">
                            <svg class="spinner" width="20" height="20" viewBox="0 0 50 50">
                                <circle cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="5" stroke-dasharray="31.4 31.4" transform="rotate(-90 25 25)">
                                    <animateTransform attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="1s" repeatCount="indefinite"/>
                                </circle>
                            </svg>
                        </span>
                    </button>
                    
                    <div class="form-footer">
                        Already have an account? <a href="/login">Sign in</a>
                    </div>
                </form>
                
                <!-- Error/Success Messages -->
                <div id="form-message" class="form-message" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>

<style>
/* Dark Mode Supabase-Inspired Styles */
:root {
    --bg-primary: #0A0A0A;
    --bg-secondary: #1A1A1A;
    --bg-tertiary: #2A2A2A;
    --text-primary: #FFFFFF;
    --text-secondary: #A0A0A0;
    --text-tertiary: #707070;
    --accent-primary: #3ECFAB;
    --accent-hover: #2FB596;
    --border-color: #2A2A2A;
    --error-color: #FF4444;
    --success-color: #3ECFAB;
    --warning-color: #FFA500;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

.novarax-registration-wrapper {
    min-height: 100vh;
    background: var(--bg-primary);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
    color: var(--text-primary);
}

.novarax-registration-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 100vh;
}

@media (max-width: 968px) {
    .novarax-registration-container {
        grid-template-columns: 1fr;
    }
    .novarax-branding {
        display: none;
    }
}

/* Left Side - Branding */
.novarax-branding {
    background: linear-gradient(135deg, #1A1A1A 0%, #0A0A0A 100%);
    padding: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.novarax-branding::before {
    content: '';
    position: absolute;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(62, 207, 171, 0.1) 0%, transparent 70%);
    top: -250px;
    left: -250px;
}

.novarax-branding-content {
    max-width: 480px;
    z-index: 1;
}

.novarax-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 40px;
}

.novarax-logo svg {
    width: 40px;
    height: 40px;
}

.novarax-logo span {
    font-size: 24px;
    font-weight: 700;
    background: linear-gradient(135deg, #3ECFAB 0%, #1E88E5 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.novarax-branding h1 {
    font-size: 42px;
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: 16px;
}

.novarax-branding p {
    font-size: 18px;
    color: var(--text-secondary);
    margin-bottom: 40px;
}

.novarax-features {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 12px;
    color: var(--text-secondary);
}

.feature-item svg {
    color: var(--accent-primary);
    flex-shrink: 0;
}

/* Right Side - Form */
.novarax-form-side {
    background: var(--bg-secondary);
    padding: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow-y: auto;
}

.novarax-form-content {
    width: 100%;
    max-width: 440px;
}

.novarax-form-content h2 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 8px;
}

.novarax-form-content .subtitle {
    color: var(--text-secondary);
    margin-bottom: 32px;
}

/* Form Styles */
.novarax-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

label {
    font-size: 14px;
    font-weight: 500;
    color: var(--text-primary);
}

input[type="text"],
input[type="email"],
input[type="tel"],
input[type="password"],
select {
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 15px;
    color: var(--text-primary);
    transition: all 0.2s;
}

input:focus,
select:focus {
    outline: none;
    border-color: var(--accent-primary);
    box-shadow: 0 0 0 3px rgba(62, 207, 171, 0.1);
}

input::placeholder {
    color: var(--text-tertiary);
}

/* Password Input */
.password-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.password-input-wrapper input {
    flex: 1;
    padding-right: 80px;
}

.password-toggle,
.password-generate {
    position: absolute;
    right: 8px;
    background: transparent;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    padding: 8px;
    border-radius: 4px;
    transition: all 0.2s;
}

.password-toggle {
    right: 40px;
}

.password-toggle:hover,
.password-generate:hover {
    color: var(--accent-primary);
    background: rgba(62, 207, 171, 0.1);
}

/* Phone Input */
.phone-input-wrapper {
    display: flex;
    gap: 8px;
}

.country-select {
    width: 120px;
    flex-shrink: 0;
}

/* Checkbox */
.checkbox-group {
    margin: 8px 0;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    display: none;
}

.checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid var(--border-color);
    border-radius: 4px;
    flex-shrink: 0;
    position: relative;
    transition: all 0.2s;
}

.checkbox-label input[type="checkbox"]:checked + .checkbox-custom {
    background: var(--accent-primary);
    border-color: var(--accent-primary);
}

.checkbox-label input[type="checkbox"]:checked + .checkbox-custom::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 14px;
    font-weight: 700;
}

.checkbox-text {
    font-size: 14px;
    color: var(--text-secondary);
}

.checkbox-text a {
    color: var(--accent-primary);
    text-decoration: none;
}

.checkbox-text a:hover {
    text-decoration: underline;
}

/* Buttons */
.btn-primary {
    background: var(--accent-primary);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 14px 24px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-primary:hover {
    background: var(--accent-hover);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(62, 207, 171, 0.3);
}

.btn-primary:active {
    transform: translateY(0);
}

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Input Hints */
.input-hint {
    font-size: 13px;
    color: var(--text-tertiary);
}

.subdomain-preview strong {
    color: var(--accent-primary);
}

/* Validation Messages */
.validation-message {
    font-size: 13px;
    margin-top: 4px;
}

.validation-message.error {
    color: var(--error-color);
}

.validation-message.success {
    color: var(--success-color);
}

/* Password Strength */
.password-strength {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.strength-meter {
    height: 4px;
    background: var(--bg-tertiary);
    border-radius: 2px;
    overflow: hidden;
}

.strength-bar {
    height: 100%;
    width: 0%;
    transition: all 0.3s;
    border-radius: 2px;
}

.strength-text {
    font-size: 12px;
    color: var(--text-tertiary);
}

/* Form Footer */
.form-footer {
    text-align: center;
    margin-top: 24px;
    font-size: 14px;
    color: var(--text-secondary);
}

.form-footer a {
    color: var(--accent-primary);
    text-decoration: none;
    font-weight: 600;
}

.form-footer a:hover {
    text-decoration: underline;
}

/* Messages */
.form-message {
    margin-top: 20px;
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 14px;
}

.form-message.success {
    background: rgba(62, 207, 171, 0.1);
    border: 1px solid var(--success-color);
    color: var(--success-color);
}

.form-message.error {
    background: rgba(255, 68, 68, 0.1);
    border: 1px solid var(--error-color);
    color: var(--error-color);
}

/* Spinner */
.spinner {
    animation: rotate 1s linear infinite;
}

@keyframes rotate {
    100% { transform: rotate(360deg); }
}
</style>