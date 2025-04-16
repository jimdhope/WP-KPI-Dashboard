<?php
/**
 * Add KPI Metric form template
 *
 * @package    KPI_Competition_Dashboard
 */

// Check user capabilities
if (!current_user_can('manage_options')) {
    return;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kpi_metric_nonce'])) {
    if (!wp_verify_nonce($_POST['kpi_metric_nonce'], 'save_kpi_metric')) {
        wp_die('Invalid nonce specified');
    }
    
    $user_id = intval($_POST['user_id']);
    $metric_name = sanitize_text_field($_POST['metric_name']);
    $metric_value = floatval($_POST['metric_value']);
    
    if ($this->save_kpi_metric($user_id, $metric_name, $metric_value)) {
        add_settings_error(
            'kpi_messages',
            'kpi_message',
            'KPI Metric saved successfully',
            'updated'
        );
    } else {
        add_settings_error(
            'kpi_messages',
            'kpi_message',
            'Error saving KPI metric',
            'error'
        );
    }
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors('kpi_messages'); ?>
    
    <div class="kpi-add-metric-form">
        <form method="post" action="">
            <?php wp_nonce_field('save_kpi_metric', 'kpi_metric_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="user_id">User</label>
                    </th>
                    <td>
                        <select name="user_id" id="user_id" required>
                            <option value="">Select a user</option>
                            <?php
                            $users = get_users(['role__in' => ['administrator', 'editor', 'author']]);
                            foreach ($users as $user) {
                                echo sprintf(
                                    '<option value="%s">%s</option>',
                                    esc_attr($user->ID),
                                    esc_html($user->display_name)
                                );
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="metric_name">Metric Name</label>
                    </th>
                    <td>
                        <input type="text" name="metric_name" id="metric_name" class="regular-text" required>
                        <p class="description">Enter the name of the KPI metric (e.g., Sales, Leads, Conversions)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="metric_value">Metric Value</label>
                    </th>
                    <td>
                        <input type="number" step="0.01" name="metric_value" id="metric_value" class="regular-text" required>
                        <p class="description">Enter the numeric value for this metric</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Add Metric'); ?>
        </form>
    </div>
</div>