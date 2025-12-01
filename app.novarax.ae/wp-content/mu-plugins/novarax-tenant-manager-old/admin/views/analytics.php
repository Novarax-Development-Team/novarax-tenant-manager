<?php
/**
 * Analytics Page
 * 
 * Advanced reporting and analytics dashboard with charts.
 *
 * @package NovaRax\TenantManager
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

$tenant_ops = new NovaRax_Tenant_Operations();
$module_manager = new NovaRax_Module_Manager();

// Get statistics
$total_tenants = $tenant_ops->get_tenant_count();
$active_tenants = $tenant_ops->get_tenant_count(array('status' => 'active'));
$module_stats = $module_manager->get_statistics();

// Calculate growth rate (last 7 days vs previous 7 days)
global $wpdb;
$last_7_days = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}novarax_tenants 
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
);
$previous_7_days = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}novarax_tenants 
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) 
     AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)"
);
$growth_rate = $previous_7_days > 0 ? (($last_7_days - $previous_7_days) / $previous_7_days) * 100 : 0;
?>

<div class="wrap">
    <h1><?php _e('Analytics & Reports', 'novarax-tenant-manager'); ?></h1>
    
    <!-- Period Selector -->
    <div style="margin: 20px 0;">
        <label for="analytics-period"><?php _e('Time Period:', 'novarax-tenant-manager'); ?></label>
        <select id="analytics-period" class="regular-text">
            <option value="7days"><?php _e('Last 7 Days', 'novarax-tenant-manager'); ?></option>
            <option value="30days" selected><?php _e('Last 30 Days', 'novarax-tenant-manager'); ?></option>
            <option value="90days"><?php _e('Last 90 Days', 'novarax-tenant-manager'); ?></option>
        </select>
        <button type="button" class="button" id="refresh-analytics">
            <span class="dashicons dashicons-update"></span> <?php _e('Refresh', 'novarax-tenant-manager'); ?>
        </button>
        <button type="button" class="button" id="export-analytics">
            <span class="dashicons dashicons-download"></span> <?php _e('Export Report', 'novarax-tenant-manager'); ?>
        </button>
    </div>
    
    <!-- Key Metrics -->
    <div class="novarax-stats-grid">
        <div class="novarax-stat-card novarax-stat-primary">
            <div class="novarax-stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="novarax-stat-content">
                <h3><?php echo number_format($total_tenants); ?></h3>
                <p><?php _e('Total Tenants', 'novarax-tenant-manager'); ?></p>
            </div>
            <div class="novarax-stat-footer">
                <small><?php echo number_format($last_7_days); ?> <?php _e('this week', 'novarax-tenant-manager'); ?></small>
            </div>
        </div>
        
        <div class="novarax-stat-card novarax-stat-success">
            <div class="novarax-stat-icon">
                <span class="dashicons dashicons-arrow-up-alt"></span>
            </div>
            <div class="novarax-stat-content">
                <h3><?php echo number_format($growth_rate, 1); ?>%</h3>
                <p><?php _e('Growth Rate', 'novarax-tenant-manager'); ?></p>
            </div>
            <div class="novarax-stat-footer">
                <small><?php _e('vs. last week', 'novarax-tenant-manager'); ?></small>
            </div>
        </div>
        
        <div class="novarax-stat-card novarax-stat-warning">
            <div class="novarax-stat-icon">
                <span class="dashicons dashicons-admin-plugins"></span>
            </div>
            <div class="novarax-stat-content">
                <h3><?php echo number_format($module_stats['active_activations']); ?></h3>
                <p><?php _e('Active Subscriptions', 'novarax-tenant-manager'); ?></p>
            </div>
            <div class="novarax-stat-footer">
                <small><?php echo $module_stats['active_modules']; ?> <?php _e('modules', 'novarax-tenant-manager'); ?></small>
            </div>
        </div>
        
        <div class="novarax-stat-card novarax-stat-info">
            <div class="novarax-stat-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="novarax-stat-content">
                <h3><?php echo number_format(($active_tenants / max($total_tenants, 1)) * 100, 1); ?>%</h3>
                <p><?php _e('Activation Rate', 'novarax-tenant-manager'); ?></p>
            </div>
            <div class="novarax-stat-footer">
                <small><?php echo $active_tenants; ?> <?php _e('active', 'novarax-tenant-manager'); ?></small>
            </div>
        </div>
    </div>
    
    <!-- Charts Row 1 -->
    <div class="novarax-dashboard-grid">
        <div class="novarax-dashboard-column">
            <div class="novarax-widget">
                <div class="novarax-widget-header">
                    <h2><?php _e('Tenant Growth', 'novarax-tenant-manager'); ?></h2>
                </div>
                <div class="novarax-widget-content">
                    <canvas id="tenant-growth-chart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <div class="novarax-dashboard-column">
            <div class="novarax-widget">
                <div class="novarax-widget-header">
                    <h2><?php _e('Status Distribution', 'novarax-tenant-manager'); ?></h2>
                </div>
                <div class="novarax-widget-content">
                    <canvas id="status-chart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row 2 -->
    <div class="novarax-dashboard-grid">
        <div class="novarax-dashboard-column">
            <div class="novarax-widget">
                <div class="novarax-widget-header">
                    <h2><?php _e('Module Activations', 'novarax-tenant-manager'); ?></h2>
                </div>
                <div class="novarax-widget-content">
                    <canvas id="module-activations-chart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <div class="novarax-dashboard-column">
            <div class="novarax-widget">
                <div class="novarax-widget-header">
                    <h2><?php _e('Recent Activity', 'novarax-tenant-manager'); ?></h2>
                </div>
                <div class="novarax-widget-content">
                    <div id="recent-activity-list" style="max-height: 400px; overflow-y: auto;">
                        <p class="description"><?php _e('Loading activity...', 'novarax-tenant-manager'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.novarax-stat-footer {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid rgba(255,255,255,0.2);
}

.novarax-stat-footer small {
    color: rgba(255,255,255,0.8);
}

#recent-activity-list {
    font-size: 13px;
}

.activity-item {
    padding: 10px;
    border-bottom: 1px solid #f0f0f0;
}

.activity-item:hover {
    background: #f9f9f9;
}

.activity-icon {
    display: inline-block;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    text-align: center;
    line-height: 30px;
    margin-right: 10px;
    vertical-align: middle;
}

.activity-icon.success { background: #d4edda; color: #155724; }
.activity-icon.warning { background: #fff3cd; color: #856404; }
.activity-icon.error { background: #f8d7da; color: #721c24; }
.activity-icon.info { background: #d1ecf1; color: #0c5460; }

.activity-time {
    float: right;
    color: #999;
    font-size: 12px;
}
</style>

<script>
jQuery(document).ready(function($) {
    let charts = {};
    
    // Load analytics data
    function loadAnalytics() {
        const period = $('#analytics-period').val();
        
        $.post(ajaxurl, {
            action: 'novarax_get_analytics_data',
            nonce: novaraxTM.nonce,
            period: period
        }, function(response) {
            if (response.success) {
                updateCharts(response.data);
            }
        });
    }
    
    // Update all charts
    function updateCharts(data) {
        // Tenant Growth Chart
        if (charts.tenantGrowth) {
            charts.tenantGrowth.destroy();
        }
        
        const growthCtx = document.getElementById('tenant-growth-chart').getContext('2d');
        charts.tenantGrowth = new Chart(growthCtx, {
            type: 'line',
            data: {
                labels: data.tenant_growth.labels,
                datasets: [{
                    label: '<?php _e('Total Tenants', 'novarax-tenant-manager'); ?>',
                    data: data.tenant_growth.values,
                    borderColor: '#0073aa',
                    backgroundColor: 'rgba(0, 115, 170, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
        
        // Status Distribution Chart
        if (charts.statusDist) {
            charts.statusDist.destroy();
        }
        
        const statusCtx = document.getElementById('status-chart').getContext('2d');
        charts.statusDist = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: data.status_distribution.labels,
                datasets: [{
                    data: data.status_distribution.values,
                    backgroundColor: [
                        '#46b450',
                        '#f0b849',
                        '#dc3232',
                        '#999999'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Module Activations Chart
        if (charts.moduleAct) {
            charts.moduleAct.destroy();
        }
        
        const moduleCtx = document.getElementById('module-activations-chart').getContext('2d');
        charts.moduleAct = new Chart(moduleCtx, {
            type: 'bar',
            data: {
                labels: data.module_activations.labels,
                datasets: [{
                    label: '<?php _e('Activations', 'novarax-tenant-manager'); ?>',
                    data: data.module_activations.values,
                    backgroundColor: '#0073aa'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
        
        // Recent Activity
        let activityHtml = '';
        data.recent_activity.forEach(function(activity) {
            const iconClass = activity.action.includes('created') ? 'success' : 
                            activity.action.includes('deleted') ? 'error' : 
                            activity.action.includes('suspended') ? 'warning' : 'info';
            
            const icon = activity.action.includes('created') ? '✓' : 
                        activity.action.includes('deleted') ? '×' : 
                        activity.action.includes('suspended') ? '!' : 'i';
            
            const timeAgo = moment(activity.created_at).fromNow();
            
            activityHtml += '<div class="activity-item">';
            activityHtml += '<span class="activity-icon ' + iconClass + '">' + icon + '</span>';
            activityHtml += '<strong>' + activity.action.replace(/_/g, ' ') + '</strong>';
            activityHtml += '<span class="activity-time">' + timeAgo + '</span>';
            activityHtml += '</div>';
        });
        
        $('#recent-activity-list').html(activityHtml || '<p class="description"><?php _e('No recent activity', 'novarax-tenant-manager'); ?></p>');
    }
    
    // Event handlers
    $('#analytics-period').on('change', loadAnalytics);
    $('#refresh-analytics').on('click', loadAnalytics);
    
    $('#export-analytics').on('click', function() {
        alert('<?php _e('Export functionality coming soon!', 'novarax-tenant-manager'); ?>');
    });
    
    // Initial load
    loadAnalytics();
});
</script>

<!-- Load Moment.js for time formatting -->
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>