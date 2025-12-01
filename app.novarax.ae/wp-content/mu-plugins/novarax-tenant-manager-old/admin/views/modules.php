<?php
/**
 * Modules Management Page
 * 
 * Manage registered modules, map to WooCommerce products,
 * and view module statistics.
 *
 * @package NovaRax\TenantManager
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

// Get module manager
$module_manager = new NovaRax_Module_Manager();
$tenant_ops = new NovaRax_Tenant_Operations();

// Handle actions
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
$module_id = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;

if ($action && $module_id) {
    check_admin_referer('novarax_module_action');
    
    switch ($action) {
        case 'activate':
            $module_manager->update_module($module_id, array('status' => 'active'));
            $message = __('Module activated successfully', 'novarax-tenant-manager');
            break;
            
        case 'deactivate':
            $module_manager->update_module($module_id, array('status' => 'inactive'));
            $message = __('Module deactivated successfully', 'novarax-tenant-manager');
            break;
    }
}

// Get all modules
$modules = $module_manager->get_all_modules(array('status' => null)); // Get all regardless of status
$module_stats = $module_manager->get_statistics();

// Get WooCommerce products for mapping
$products = array();
if (class_exists('WooCommerce')) {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    );
    $product_query = new WP_Query($args);
    $products = $product_query->posts;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Modules Management', 'novarax-tenant-manager'); ?></h1>
    <a href="#" class="page-title-action" id="add-new-module"><?php _e('Register Module', 'novarax-tenant-manager'); ?></a>
    <hr class="wp-header-end">
    
    <?php if (isset($message)) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Module Statistics -->
    <div class="novarax-stats-grid" style="margin: 20px 0;">
        <div class="novarax-stat-card novarax-stat-primary">
            <div class="novarax-stat-icon">
                <span class="dashicons dashicons-admin-plugins"></span>
            </div>
            <div class="novarax-stat-content">
                <h3><?php echo number_format($module_stats['total_modules']); ?></h3>
                <p><?php _e('Total Modules', 'novarax-tenant-manager'); ?></p>
            </div>
        </div>
        
        <div class="novarax-stat-card novarax-stat-success">
            <div class="novarax-stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="novarax-stat-content">
                <h3><?php echo number_format($module_stats['active_modules']); ?></h3>
                <p><?php _e('Active Modules', 'novarax-tenant-manager'); ?></p>
            </div>
        </div>
        
        <div class="novarax-stat-card novarax-stat-warning">
            <div class="novarax-stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="novarax-stat-content">
                <h3><?php echo number_format($module_stats['total_activations']); ?></h3>
                <p><?php _e('Total Activations', 'novarax-tenant-manager'); ?></p>
            </div>
        </div>
        
        <div class="novarax-stat-card novarax-stat-info">
            <div class="novarax-stat-icon">
                <span class="dashicons dashicons-admin-users"></span>
            </div>
            <div class="novarax-stat-content">
                <h3><?php echo number_format($module_stats['active_activations']); ?></h3>
                <p><?php _e('Active Subscriptions', 'novarax-tenant-manager'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Modules Table -->
    <div class="novarax-widget">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php _e('Icon', 'novarax-tenant-manager'); ?></th>
                    <th><?php _e('Module', 'novarax-tenant-manager'); ?></th>
                    <th><?php _e('Version', 'novarax-tenant-manager'); ?></th>
                    <th><?php _e('Plugin Path', 'novarax-tenant-manager'); ?></th>
                    <th><?php _e('WooCommerce Product', 'novarax-tenant-manager'); ?></th>
                    <th><?php _e('Activations', 'novarax-tenant-manager'); ?></th>
                    <th><?php _e('Status', 'novarax-tenant-manager'); ?></th>
                    <th><?php _e('Actions', 'novarax-tenant-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($modules)) : ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <p><?php _e('No modules registered yet.', 'novarax-tenant-manager'); ?></p>
                            <button type="button" class="button button-primary" id="add-first-module">
                                <?php _e('Register Your First Module', 'novarax-tenant-manager'); ?>
                            </button>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($modules as $module) : ?>
                        <?php
                        // Get activation count
                        global $wpdb;
                        $activation_count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}novarax_tenant_modules WHERE module_id = %d",
                            $module->id
                        ));
                        
                        // Get product name
                        $product_name = '—';
                        if ($module->product_id) {
                            $product = get_post($module->product_id);
                            if ($product) {
                                $product_name = $product->post_title;
                            }
                        }
                        ?>
                        <tr>
                            <td>
                                <?php if ($module->icon_url) : ?>
                                    <img src="<?php echo esc_url($module->icon_url); ?>" style="width: 40px; height: 40px; border-radius: 4px;">
                                <?php else : ?>
                                    <span class="dashicons dashicons-admin-plugins" style="font-size: 40px; color: #0073aa;"></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo esc_html($module->module_name); ?></strong>
                                <br>
                                <code style="font-size: 11px;"><?php echo esc_html($module->module_slug); ?></code>
                                <?php if ($module->description) : ?>
                                    <br>
                                    <small class="description"><?php echo esc_html($module->description); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code><?php echo esc_html($module->version); ?></code>
                                <?php if ($module->requires_php) : ?>
                                    <br><small>PHP <?php echo esc_html($module->requires_php); ?>+</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code style="font-size: 11px;"><?php echo esc_html($module->plugin_path); ?></code>
                            </td>
                            <td>
                                <?php if ($module->product_id) : ?>
                                    <a href="<?php echo admin_url('post.php?post=' . $module->product_id . '&action=edit'); ?>" target="_blank">
                                        <?php echo esc_html($product_name); ?>
                                    </a>
                                <?php else : ?>
                                    <span class="description"><?php _e('Not mapped', 'novarax-tenant-manager'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo number_format($activation_count); ?></strong> 
                                <?php _e('tenants', 'novarax-tenant-manager'); ?>
                            </td>
                            <td>
                                <?php
                                $status_class = 'novarax-status-' . $module->status;
                                $status_color = $module->status === 'active' ? '#46b450' : '#dc3232';
                                ?>
                                <span class="novarax-status-badge" style="background-color: <?php echo $status_color; ?>;">
                                    <?php echo esc_html(ucfirst($module->status)); ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="button button-small edit-module" data-module-id="<?php echo $module->id; ?>">
                                    <?php _e('Edit', 'novarax-tenant-manager'); ?>
                                </button>
                                
                                <?php if ($module->status === 'active') : ?>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=novarax-tenants-modules&action=deactivate&module_id=' . $module->id), 'novarax_module_action'); ?>" class="button button-small">
                                        <?php _e('Deactivate', 'novarax-tenant-manager'); ?>
                                    </a>
                                <?php else : ?>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=novarax-tenants-modules&action=activate&module_id=' . $module->id), 'novarax_module_action'); ?>" class="button button-small button-primary">
                                        <?php _e('Activate', 'novarax-tenant-manager'); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Module Modal -->
<div id="module-modal" style="display: none;">
    <div class="novarax-modal-overlay"></div>
    <div class="novarax-modal-content">
        <div class="novarax-modal-header">
            <h2 id="modal-title"><?php _e('Register New Module', 'novarax-tenant-manager'); ?></h2>
            <button type="button" class="novarax-modal-close">&times;</button>
        </div>
        <div class="novarax-modal-body">
            <form id="module-form">
                <input type="hidden" id="module-id" name="module_id" value="">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="module-name"><?php _e('Module Name', 'novarax-tenant-manager'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="module-name" name="module_name" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="module-slug"><?php _e('Module Slug', 'novarax-tenant-manager'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="module-slug" name="module_slug" class="regular-text" required>
                            <p class="description"><?php _e('Lowercase letters, numbers, and hyphens only', 'novarax-tenant-manager'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="plugin-path"><?php _e('Plugin Path', 'novarax-tenant-manager'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="plugin-path" name="plugin_path" class="regular-text" required placeholder="module-folder/module-file.php">
                            <p class="description"><?php _e('Relative path from wp-content/plugins/', 'novarax-tenant-manager'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="product-id"><?php _e('WooCommerce Product', 'novarax-tenant-manager'); ?></label>
                        </th>
                        <td>
                            <select id="product-id" name="product_id" class="regular-text">
                                <option value=""><?php _e('-- Select Product --', 'novarax-tenant-manager'); ?></option>
                                <?php foreach ($products as $product) : ?>
                                    <option value="<?php echo $product->ID; ?>">
                                        <?php echo esc_html($product->post_title); ?> (ID: <?php echo $product->ID; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="description"><?php _e('Description', 'novarax-tenant-manager'); ?></label>
                        </th>
                        <td>
                            <textarea id="description" name="description" rows="3" class="large-text"></textarea>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="version"><?php _e('Version', 'novarax-tenant-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="version" name="version" class="small-text" value="1.0.0">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="requires-php"><?php _e('Requires PHP', 'novarax-tenant-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="requires-php" name="requires_php" class="small-text" value="8.0">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="icon-url"><?php _e('Icon URL', 'novarax-tenant-manager'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="icon-url" name="icon_url" class="regular-text" placeholder="https://...">
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="novarax-modal-footer">
            <button type="button" class="button button-large" id="cancel-module"><?php _e('Cancel', 'novarax-tenant-manager'); ?></button>
            <button type="button" class="button button-primary button-large" id="save-module"><?php _e('Save Module', 'novarax-tenant-manager'); ?></button>
        </div>
    </div>
</div>

<style>
.novarax-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    z-index: 100000;
}

.novarax-modal-content {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.3);
    z-index: 100001;
    max-width: 700px;
    width: 90%;
    max-height: 90vh;
    overflow: auto;
}

.novarax-modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.novarax-modal-header h2 {
    margin: 0;
}

.novarax-modal-close {
    background: none;
    border: none;
    font-size: 30px;
    cursor: pointer;
    color: #999;
}

.novarax-modal-close:hover {
    color: #333;
}

.novarax-modal-body {
    padding: 20px;
}

.novarax-modal-footer {
    padding: 20px;
    border-top: 1px solid #ddd;
    text-align: right;
}

.novarax-modal-footer .button {
    margin-left: 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Open modal for new module
    $('#add-new-module, #add-first-module').on('click', function(e) {
        e.preventDefault();
        $('#module-id').val('');
        $('#module-form')[0].reset();
        $('#modal-title').text('<?php _e('Register New Module', 'novarax-tenant-manager'); ?>');
        $('#module-modal').fadeIn();
    });
    
    // Open modal for edit module
    $('.edit-module').on('click', function() {
        var moduleId = $(this).data('module-id');
        
        // Load module data via AJAX
        $.post(ajaxurl, {
            action: 'novarax_get_module',
            nonce: novaraxTM.nonce,
            module_id: moduleId
        }, function(response) {
            if (response.success) {
                var module = response.data.module;
                $('#module-id').val(module.id);
                $('#module-name').val(module.module_name);
                $('#module-slug').val(module.module_slug);
                $('#plugin-path').val(module.plugin_path);
                $('#product-id').val(module.product_id);
                $('#description').val(module.description);
                $('#version').val(module.version);
                $('#requires-php').val(module.requires_php);
                $('#icon-url').val(module.icon_url);
                
                $('#modal-title').text('<?php _e('Edit Module', 'novarax-tenant-manager'); ?>');
                $('#module-modal').fadeIn();
            }
        });
    });
    
    // Close modal
    $('.novarax-modal-close, #cancel-module').on('click', function() {
        $('#module-modal').fadeOut();
    });
    
    // Close on overlay click
    $('.novarax-modal-overlay').on('click', function() {
        $('#module-modal').fadeOut();
    });
    
    // Save module
    $('#save-module').on('click', function() {
        var $btn = $(this);
        var formData = $('#module-form').serializeArray();
        var data = {
            action: 'novarax_save_module',
            nonce: novaraxTM.nonce
        };
        
        $.each(formData, function(i, field) {
            data[field.name] = field.value;
        });
        
        $btn.prop('disabled', true).text('<?php _e('Saving...', 'novarax-tenant-manager'); ?>');
        
        $.post(ajaxurl, data, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data.message);
                $btn.prop('disabled', false).text('<?php _e('Save Module', 'novarax-tenant-manager'); ?>');
            }
        });
    });
});
</script>