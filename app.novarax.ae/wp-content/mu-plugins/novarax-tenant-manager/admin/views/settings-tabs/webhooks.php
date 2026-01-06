<?php
/**
 * Webhooks Settings Tab
 * Location: /wp-content/mu-plugins/novarax-tenant-manager/admin/views/settings-tabs/webhooks.php
 */

if (!defined('ABSPATH')) exit;

// Webhook events configuration
$webhook_events = array(
    'account_created' => array(
        'label' => __('Account Created', 'novarax-tenant-manager'),
        'description' => __('Triggered when a user creates a new account (tenant status: pending)', 'novarax-tenant-manager'),
        'icon' => 'dashicons-admin-users',
    ),
    'provisioning_started' => array(
        'label' => __('Provisioning Started', 'novarax-tenant-manager'),
        'description' => __('Triggered when tenant provisioning begins (database creation, WordPress installation)', 'novarax-tenant-manager'),
        'icon' => 'dashicons-update',
    ),
    'provisioning_completed' => array(
        'label' => __('Provisioning Completed', 'novarax-tenant-manager'),
        'description' => __('Triggered when tenant is fully provisioned and active', 'novarax-tenant-manager'),
        'icon' => 'dashicons-yes-alt',
    ),
    'order_completed' => array(
        'label' => __('Order Completed', 'novarax-tenant-manager'),
        'description' => __('Triggered after successful WooCommerce order/checkout', 'novarax-tenant-manager'),
        'icon' => 'dashicons-cart',
    ),
);
?>

<div class="novarax-info-box">
    <p>
        <span class="dashicons dashicons-info"></span>
        <strong><?php _e('About Webhooks', 'novarax-tenant-manager'); ?></strong><br>
        <?php _e('Webhooks allow you to connect NovaRax with external automation tools like n8n, Zapier, Make.com, or custom applications. Configure webhook URLs for different events to trigger automated workflows such as sending emails, creating CRM records, or updating analytics.', 'novarax-tenant-manager'); ?>
    </p>
</div>

<!-- Global Webhook Settings -->
<h3><?php _e('Global Webhook Settings', 'novarax-tenant-manager'); ?></h3>
<table class="form-table" role="presentation">
    
    <!-- Master Enable/Disable -->
    <tr>
        <th scope="row">
            <label for="novarax_webhooks_enabled">
                <?php _e('Enable Webhooks', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <label>
                <input type="checkbox" 
                       id="novarax_webhooks_enabled" 
                       name="novarax_webhooks_enabled" 
                       value="1" 
                       <?php checked(get_option('novarax_webhooks_enabled', '1'), '1'); ?>>
                <?php _e('Enable webhook system globally', 'novarax-tenant-manager'); ?>
            </label>
            <p class="description">
                <?php _e('When disabled, no webhooks will be sent regardless of individual settings below.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <!-- Webhook Secret Key -->
    <tr>
        <th scope="row">
            <label for="novarax_webhook_secret">
                <?php _e('Webhook Secret Key', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <?php
            $webhook_secret = get_option('novarax_webhook_secret');
            if (empty($webhook_secret)) {
                $webhook_secret = wp_generate_password(32, false);
                update_option('novarax_webhook_secret', $webhook_secret);
            }
            ?>
            <input type="text" 
                   id="novarax_webhook_secret" 
                   name="novarax_webhook_secret" 
                   value="<?php echo esc_attr($webhook_secret); ?>" 
                   class="regular-text code" 
                   readonly>
            <button type="button" 
                    class="button" 
                    id="regenerate-webhook-secret"
                    style="margin-left: 5px;">
                <?php _e('Regenerate', 'novarax-tenant-manager'); ?>
            </button>
            <button type="button" 
                    class="button" 
                    id="copy-webhook-secret"
                    style="margin-left: 5px;">
                <?php _e('Copy', 'novarax-tenant-manager'); ?>
            </button>
            <p class="description">
                <?php _e('This secret key is used to sign webhook payloads. Use it to verify webhook authenticity on the receiving end. Each webhook request includes an X-NovaRax-Signature header with HMAC-SHA256 signature.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <!-- Retry Settings -->
    <tr>
        <th scope="row">
            <label for="novarax_webhook_retry_attempts">
                <?php _e('Retry Attempts', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <input type="number" 
                   id="novarax_webhook_retry_attempts" 
                   name="novarax_webhook_retry_attempts" 
                   value="<?php echo esc_attr(get_option('novarax_webhook_retry_attempts', '3')); ?>" 
                   class="small-text" 
                   min="0"
                   max="10">
            <p class="description">
                <?php _e('Number of times to retry failed webhook deliveries. Set to 0 to disable retries.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <!-- Timeout Settings -->
    <tr>
        <th scope="row">
            <label for="novarax_webhook_timeout">
                <?php _e('Request Timeout', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <input type="number" 
                   id="novarax_webhook_timeout" 
                   name="novarax_webhook_timeout" 
                   value="<?php echo esc_attr(get_option('novarax_webhook_timeout', '15')); ?>" 
                   class="small-text" 
                   min="5"
                   max="60"> 
            <?php _e('seconds', 'novarax-tenant-manager'); ?>
            <p class="description">
                <?php _e('Maximum time to wait for webhook endpoint response. Recommended: 10-15 seconds.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
</table>

<!-- Individual Webhook Events -->
<h3 style="margin-top: 40px;"><?php _e('Webhook Events', 'novarax-tenant-manager'); ?></h3>
<p style="color: #646970; margin-bottom: 20px;">
    <?php _e('Configure individual webhook endpoints for each event. Click "Test" to send a sample webhook payload to your endpoint.', 'novarax-tenant-manager'); ?>
</p>

<?php foreach ($webhook_events as $event_key => $event_config) : ?>
    <div class="novarax-webhook-event" style="border: 1px solid #dcdcde; border-radius: 6px; padding: 20px; margin-bottom: 20px; background: #fff;">
        
        <!-- Event Header -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
            <h4 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                <span class="dashicons <?php echo esc_attr($event_config['icon']); ?>" style="font-size: 24px; width: 24px; height: 24px;"></span>
                <?php echo esc_html($event_config['label']); ?>
            </h4>
            
            <?php
            $enabled = get_option("novarax_webhook_{$event_key}_enabled", '0');
            ?>
            <span class="webhook-status <?php echo $enabled === '1' ? 'enabled' : 'disabled'; ?>">
                <span class="dashicons <?php echo $enabled === '1' ? 'dashicons-yes' : 'dashicons-minus'; ?>"></span>
                <?php echo $enabled === '1' ? __('Enabled', 'novarax-tenant-manager') : __('Disabled', 'novarax-tenant-manager'); ?>
            </span>
        </div>
        
        <p style="color: #646970; margin: 0 0 15px 0;">
            <?php echo esc_html($event_config['description']); ?>
        </p>
        
        <!-- Event Settings -->
        <table class="form-table" style="margin-top: 0;">
            <tr>
                <th scope="row" style="width: 200px; padding-left: 0;">
                    <label for="novarax_webhook_<?php echo esc_attr($event_key); ?>_enabled">
                        <?php _e('Status', 'novarax-tenant-manager'); ?>
                    </label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" 
                               id="novarax_webhook_<?php echo esc_attr($event_key); ?>_enabled" 
                               name="novarax_webhook_<?php echo esc_attr($event_key); ?>_enabled" 
                               value="1" 
                               <?php checked($enabled, '1'); ?>>
                        <?php _e('Enable this webhook', 'novarax-tenant-manager'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row" style="padding-left: 0;">
                    <label for="novarax_webhook_<?php echo esc_attr($event_key); ?>_url">
                        <?php _e('Webhook URL', 'novarax-tenant-manager'); ?>
                    </label>
                </th>
                <td>
                    <input type="url" 
                           id="novarax_webhook_<?php echo esc_attr($event_key); ?>_url" 
                           name="novarax_webhook_<?php echo esc_attr($event_key); ?>_url" 
                           value="<?php echo esc_attr(get_option("novarax_webhook_{$event_key}_url", '')); ?>" 
                           class="regular-text" 
                           placeholder="https://your-n8n-instance.com/webhook/<?php echo esc_attr($event_key); ?>">
                    <button type="button" 
                            class="button test-webhook-btn" 
                            data-event="<?php echo esc_attr($event_key); ?>"
                            style="margin-left: 5px;">
                        <?php _e('Test Webhook', 'novarax-tenant-manager'); ?>
                    </button>
                    <p class="description">
                        <?php _e('Enter your webhook endpoint URL (n8n, Zapier, Make.com, or custom API)', 'novarax-tenant-manager'); ?>
                    </p>
                    
                    <!-- Test Result Container -->
                    <div id="webhook-test-result-<?php echo esc_attr($event_key); ?>" class="webhook-test-result"></div>
                </td>
            </tr>
        </table>
        
    </div>
<?php endforeach; ?>

<!-- Webhook Payload Documentation -->
<div style="margin-top: 40px;">
    <h3><?php _e('Webhook Payload Structure', 'novarax-tenant-manager'); ?></h3>
    <p style="color: #646970;">
        <?php _e('All webhooks send a JSON payload with the following structure:', 'novarax-tenant-manager'); ?>
    </p>
    
    <div class="novarax-code-block">
<code>{
  "event": "account_created|provisioning_started|provisioning_completed|order_completed",
  "timestamp": "2025-12-15T10:30:00Z",
  "tenant_id": 123,
  "tenant_username": "johndoe",
  "email": "john@example.com",
  "subdomain": "johndoe.app.novarax.ae",
  "status": "pending|provisioning|active",
  "order_id": 456,  // Only for order_completed event
  "metadata": {
    // Additional event-specific data
  }
}</code>
    </div>
    
    <h4 style="margin-top: 30px;"><?php _e('Webhook Headers', 'novarax-tenant-manager'); ?></h4>
    <div class="novarax-code-block">
<code>Content-Type: application/json
X-NovaRax-Event: {event_type}
X-NovaRax-Signature: {hmac_sha256_signature}
X-NovaRax-Timestamp: {unix_timestamp}
User-Agent: NovaRax-Webhooks/1.0</code>
    </div>
    
    <h4 style="margin-top: 30px;"><?php _e('Signature Verification (Example)', 'novarax-tenant-manager'); ?></h4>
    <div class="novarax-code-block">
<code>// PHP Example
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_NOVARAX_SIGNATURE'];
$secret = 'your_webhook_secret';

$calculated = hash_hmac('sha256', $payload, $secret);

if (hash_equals($calculated, $signature)) {
    // Signature is valid, process webhook
} else {
    // Invalid signature, reject request
}</code>
    </div>
</div>

<!-- Webhook Logs Link -->
<div class="novarax-info-box" style="margin-top: 30px;">
    <p>
        <span class="dashicons dashicons-admin-tools"></span>
        <strong><?php _e('Webhook Logs:', 'novarax-tenant-manager'); ?></strong> 
        <?php _e('View webhook delivery history and debug failed requests in', 'novarax-tenant-manager'); ?> 
        <a href="<?php echo admin_url('admin.php?page=novarax-tenants-logs&type=webhooks'); ?>">
            <?php _e('System Logs', 'novarax-tenant-manager'); ?>
        </a>
    </p>
</div>

<script>
jQuery(document).ready(function($) {
    
    // Copy webhook secret
    $('#copy-webhook-secret').on('click', function() {
        var secret = $('#novarax_webhook_secret').val();
        navigator.clipboard.writeText(secret).then(function() {
            alert('<?php _e('Webhook secret copied to clipboard!', 'novarax-tenant-manager'); ?>');
        });
    });
    
    // Regenerate webhook secret
    $('#regenerate-webhook-secret').on('click', function() {
        if (confirm('<?php _e('Are you sure? This will invalidate all existing webhook signatures.', 'novarax-tenant-manager'); ?>')) {
            // Generate new random secret
            var newSecret = Array.from({length: 32}, () => 
                'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'[Math.floor(Math.random() * 62)]
            ).join('');
            $('#novarax_webhook_secret').val(newSecret);
        }
    });
    
    // Test webhook button
    $('.test-webhook-btn').on('click', function() {
        var btn = $(this);
        var event = btn.data('event');
        var url = $('#novarax_webhook_' + event + '_url').val();
        var resultDiv = $('#webhook-test-result-' + event);
        
        if (!url) {
            alert('<?php _e('Please enter a webhook URL first.', 'novarax-tenant-manager'); ?>');
            return;
        }
        
        btn.prop('disabled', true).text('<?php _e('Testing...', 'novarax-tenant-manager'); ?>');
        resultDiv.hide();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'novarax_test_webhook',
                nonce: '<?php echo wp_create_nonce('novarax_test_webhook'); ?>',
                event: event,
                webhook_url: url
            },
            success: function(response) {
                if (response.success) {
                    resultDiv.removeClass('error').addClass('success')
                        .html('<strong><?php _e('Success!', 'novarax-tenant-manager'); ?></strong> ' + response.data.message)
                        .fadeIn();
                } else {
                    resultDiv.removeClass('success').addClass('error')
                        .html('<strong><?php _e('Error:', 'novarax-tenant-manager'); ?></strong> ' + response.data.message)
                        .fadeIn();
                }
            },
            error: function() {
                resultDiv.removeClass('success').addClass('error')
                    .html('<strong><?php _e('Error:', 'novarax-tenant-manager'); ?></strong> <?php _e('Failed to send test webhook.', 'novarax-tenant-manager'); ?>')
                    .fadeIn();
            },
            complete: function() {
                btn.prop('disabled', false).text('<?php _e('Test Webhook', 'novarax-tenant-manager'); ?>');
            }
        });
    });
    
});
</script>