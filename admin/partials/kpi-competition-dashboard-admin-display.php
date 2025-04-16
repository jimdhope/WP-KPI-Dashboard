<?php
/**
 * Admin area main display template
 *
 * @package    KPI_Competition_Dashboard
 */

// Check user capabilities
if (!current_user_can('manage_options')) {
    return;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="kpi-dashboard-container">
        <div class="kpi-metrics-table">
            <h2>KPI Metrics Overview</h2>
            <?php
            $metrics = $this->get_kpi_metrics();
            if ($metrics): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Metric</th>
                            <th>Value</th>
                            <th>Date Recorded</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($metrics as $metric): 
                            $user_info = get_userdata($metric->user_id);
                            ?>
                            <tr>
                                <td><?php echo esc_html($user_info->display_name); ?></td>
                                <td><?php echo esc_html($metric->metric_name); ?></td>
                                <td><?php echo esc_html($metric->metric_value); ?></td>
                                <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($metric->date_recorded))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No KPI metrics recorded yet.</p>
            <?php endif; ?>
        </div>

        <div class="kpi-dashboard-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=kpi-competition-add-metric')); ?>" class="button button-primary">Add New Metric</a>
        </div>
    </div>
</div>