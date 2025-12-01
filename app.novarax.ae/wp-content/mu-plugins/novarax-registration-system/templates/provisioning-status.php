<!-- 
Beautiful provisioning progress page with realistic animations
-->

<?php
// Get current user's tenant
$current_user = wp_get_current_user();
global $wpdb;
$tenant = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}novarax_tenants WHERE user_id = %d ORDER BY id DESC LIMIT 1",
    $current_user->ID
));

if (!$tenant) {
    wp_redirect(home_url());
    exit;
}
?>

<div class="novarax-provisioning-wrapper">
    <div class="novarax-provisioning-container">
        
        <!-- Logo -->
        <div class="provisioning-logo">
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
        </div>
        
        <!-- Animation Container -->
        <div class="provisioning-animation">
            <div class="orbit-container">
                <div class="orbit orbit-1"></div>
                <div class="orbit orbit-2"></div>
                <div class="orbit orbit-3"></div>
                <div class="center-dot"></div>
            </div>
        </div>
        
        <!-- Status Text -->
        <div class="provisioning-status">
            <h1 id="status-title">Creating Your Dashboard</h1>
            <p id="status-message">Setting up your workspace...</p>
        </div>
        
        <!-- Progress Bar -->
        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress-fill" id="progress-fill"></div>
                <div class="progress-glow"></div>
            </div>
            <div class="progress-text">
                <span id="progress-percentage">0%</span>
                <span id="progress-step">Step 1 of 4</span>
            </div>
        </div>
        
        <!-- Steps List -->
        <div class="steps-list">
            <div class="step-item" id="step-1">
                <div class="step-icon">
                    <svg class="step-check" width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M13.854 3.646a.5.5 0 010 .708l-7 7a.5.5 0 01-.708 0l-3.5-3.5a.5.5 0 11.708-.708L6.5 10.293l6.646-6.647a.5.5 0 01.708 0z"/>
                    </svg>
                    <div class="step-spinner"></div>
                </div>
                <span>Creating database</span>
            </div>
            <div class="step-item" id="step-2">
                <div class="step-icon">
                    <div class="step-spinner"></div>
                </div>
                <span>Installing WordPress</span>
            </div>
            <div class="step-item" id="step-3">
                <div class="step-icon">
                    <div class="step-spinner"></div>
                </div>
                <span>Activating modules</span>
            </div>
            <div class="step-item" id="step-4">
                <div class="step-icon">
                    <div class="step-spinner"></div>
                </div>
                <span>Finalizing setup</span>
            </div>
        </div>
        
        <!-- Footer Note -->
        <div class="provisioning-footer">
            <p>This usually takes 30-60 seconds. Please don't close this window.</p>
        </div>
        
    </div>
</div>

<style>
/* Provisioning Page Styles */
.novarax-provisioning-wrapper {
    min-height: 100vh;
    background: #0A0A0A;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    color: #FFFFFF;
    padding: 20px;
}

.novarax-provisioning-container {
    max-width: 600px;
    width: 100%;
    text-align: center;
}

/* Logo */
.provisioning-logo {
    margin-bottom: 40px;
}

.provisioning-logo svg {
    width: 60px;
    height: 60px;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.05); opacity: 0.9; }
}

/* Animation */
.provisioning-animation {
    margin: 40px 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 200px;
}

.orbit-container {
    position: relative;
    width: 150px;
    height: 150px;
}

.orbit {
    position: absolute;
    border: 2px solid rgba(62, 207, 171, 0.2);
    border-radius: 50%;
    animation: rotate 3s linear infinite;
}

.orbit-1 {
    width: 100%;
    height: 100%;
    border-top-color: #3ECFAB;
}

.orbit-2 {
    width: 75%;
    height: 75%;
    top: 12.5%;
    left: 12.5%;
    border-right-color: #3ECFAB;
    animation-duration: 2s;
    animation-direction: reverse;
}

.orbit-3 {
    width: 50%;
    height: 50%;
    top: 25%;
    left: 25%;
    border-bottom-color: #3ECFAB;
    animation-duration: 1.5s;
}

@keyframes rotate {
    100% { transform: rotate(360deg); }
}

.center-dot {
    position: absolute;
    width: 20px;
    height: 20px;
    background: linear-gradient(135deg, #3ECFAB, #1E88E5);
    border-radius: 50%;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    box-shadow: 0 0 20px rgba(62, 207, 171, 0.5);
    animation: glow 2s ease-in-out infinite;
}

@keyframes glow {
    0%, 100% { box-shadow: 0 0 20px rgba(62, 207, 171, 0.5); }
    50% { box-shadow: 0 0 40px rgba(62, 207, 171, 0.8); }
}

/* Status Text */
.provisioning-status {
    margin: 40px 0;
}

.provisioning-status h1 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 12px;
    background: linear-gradient(135deg, #FFFFFF 0%, #A0A0A0 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.provisioning-status p {
    color: #A0A0A0;
    font-size: 16px;
}

/* Progress Bar */
.progress-container {
    margin: 40px 0;
}

.progress-bar {
    height: 8px;
    background: #1A1A1A;
    border-radius: 4px;
    overflow: hidden;
    position: relative;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3ECFAB 0%, #1E88E5 100%);
    border-radius: 4px;
    width: 0%;
    transition: width 0.5s ease;
    position: relative;
}

.progress-glow {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(62, 207, 171, 0.5), transparent);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.progress-text {
    display: flex;
    justify-content: space-between;
    margin-top: 12px;
    font-size: 14px;
    color: #A0A0A0;
}

#progress-percentage {
    font-weight: 600;
    color: #3ECFAB;
}

/* Steps List */
.steps-list {
    margin: 40px 0;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.step-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 20px;
    background: #1A1A1A;
    border: 1px solid #2A2A2A;
    border-radius: 12px;
    transition: all 0.3s;
    opacity: 0.5;
}

.step-item.active {
    opacity: 1;
    border-color: #3ECFAB;
    background: rgba(62, 207, 171, 0.05);
}

.step-item.completed {
    opacity: 0.7;
}

.step-icon {
    width: 24px;
    height: 24px;
    position: relative;
    flex-shrink: 0;
}

.step-spinner {
    width: 100%;
    height: 100%;
    border: 2px solid #2A2A2A;
    border-top-color: #3ECFAB;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    display: none;
}

.step-item.active .step-spinner {
    display: block;
}

@keyframes spin {
    100% { transform: rotate(360deg); }
}

.step-check {
    color: #3ECFAB;
    display: none;
}

.step-item.completed .step-check {
    display: block;
}

.step-item.completed .step-spinner {
    display: none;
}

.step-item span {
    font-size: 15px;
    text-align: left;
}

/* Footer */
.provisioning-footer {
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid #2A2A2A;
}

.provisioning-footer p {
    color: #707070;
    font-size: 14px;
}

@media (max-width: 600px) {
    .provisioning-status h1 {
        font-size: 24px;
    }
    
    .orbit-container {
        width: 120px;
        height: 120px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    const tenantId = <?php echo $tenant->id; ?>;
    let currentProgress = 0;
    let checkInterval;
    
    // Start checking provisioning status
    function checkProvisioningStatus() {
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'novarax_check_provisioning',
                tenant_id: tenantId,
                nonce: '<?php echo wp_create_nonce('novarax_registration'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // Update progress
                    updateProgress(data.progress, data.message);
                    
                    // Update steps
                    updateSteps(data.progress);
                    
                    // Check if complete
                    if (data.status === 'active' && data.progress >= 100) {
                        clearInterval(checkInterval);
                        setTimeout(() => {
                            window.location.href = data.dashboard_url;
                        }, 1500);
                    }
                }
            }
        });
    }
    
    function updateProgress(progress, message) {
        $('#progress-fill').css('width', progress + '%');
        $('#progress-percentage').text(progress + '%');
        $('#status-message').text(message);
        
        // Update step indicator
        const step = Math.ceil(progress / 25);
        $('#progress-step').text('Step ' + Math.min(step, 4) + ' of 4');
    }
    
    function updateSteps(progress) {
        $('.step-item').each(function(index) {
            const stepProgress = (index + 1) * 25;
            const $step = $(this);
            
            if (progress >= stepProgress) {
                $step.removeClass('active').addClass('completed');
            } else if (progress >= stepProgress - 25) {
                $step.addClass('active').removeClass('completed');
            } else {
                $step.removeClass('active completed');
            }
        });
    }
    
    // Start checking immediately
    checkProvisioningStatus();
    
    // Check every 2 seconds
    checkInterval = setInterval(checkProvisioningStatus, 2000);
});
</script>