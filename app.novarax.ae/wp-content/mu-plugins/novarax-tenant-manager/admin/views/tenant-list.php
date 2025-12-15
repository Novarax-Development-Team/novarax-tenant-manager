<?php
if (!defined('ABSPATH')) exit;

// Create instance of list table
$list_table = new NovaRax_Tenant_List_Table();
$list_table->prepare_items();
$list_table->process_bulk_action();

// Get current UI preference
$current_ui = get_user_meta(get_current_user_id(), 'novarax_ui_mode', true);
$is_modern = ($current_ui === 'modern' || $current_ui === ''); // Default to modern
?>

<div class="wrap">
    <div class="novarax-header-bar">
        <div class="novarax-header-left">
            <h1 class="wp-heading-inline"><?php _e('All Tenants', 'novarax-tenant-manager'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=novarax-tenants-add'); ?>" class="page-title-action">
                <?php _e('Add New', 'novarax-tenant-manager'); ?>
            </a>
        </div>
        
        <div class="novarax-header-right">
            <!-- Beautiful UI Toggle Button -->
            <div class="novarax-ui-toggle-container">
                <span class="novarax-toggle-label">
                    <span class="dashicons dashicons-list-view"></span>
                    Classic
                </span>
                <label class="novarax-toggle-switch">
                    <input type="checkbox" id="novarax-ui-toggle" <?php checked($is_modern, true); ?>>
                    <span class="novarax-toggle-slider"></span>
                </label>
                <span class="novarax-toggle-label novarax-toggle-label-active">
                    <span class="dashicons dashicons-screenoptions"></span>
                    Modern
                </span>
            </div>
        </div>
    </div>
    
    <hr class="wp-header-end">
    
    <form method="get" id="novarax-tenants-form" class="<?php echo $is_modern ? 'novarax-modern-ui' : 'novarax-classic-ui'; ?>">
        <input type="hidden" name="page" value="novarax-tenants-list">
        <?php
        $list_table->views();
        $list_table->search_box(__('Search Tenants', 'novarax-tenant-manager'), 'tenant');
        $list_table->display();
        ?>
    </form>
</div>