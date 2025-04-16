<?php
/**
 * KPI Metrics recording page
 *
 * @package    KPI_Competition_Dashboard
 */

// Check user capabilities
if (!current_user_can('read')) {
    return;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['metric_nonce'])) {
    if (!wp_verify_nonce($_POST['metric_nonce'], 'save_metric')) {
        wp_die('Invalid nonce specified');
    }
    
    $competition_id = intval($_POST['competition_id']);
    $metric_name = sanitize_text_field($_POST['metric_name']);
    $metric_value = floatval($_POST['metric_value']);
    
    // Get the points for this metric
    $competition_metrics = $this->get_competition_metrics($competition_id);
    $points = 0;
    foreach ($competition_metrics as $metric) {
        if ($metric->metric_name === $metric_name) {
            $points = $metric->points * $metric_value;
            break;
        }
    }
    
    $metric_data = array(
        'user_id' => get_current_user_id(),
        'competition_id' => $competition_id,
        'metric_name' => $metric_name,
        'metric_value' => $metric_value,
        'points_earned' => $points
    );
    
    if ($this->save_metric($metric_data)) {
        add_settings_error(
            'metric_messages',
            'metric_message',
            'Metric recorded successfully',
            'updated'
        );
    } else {
        add_settings_error(
            'metric_messages',
            'metric_message',
            'Error recording metric',
            'error'
        );
    }
}

// Get active competitions
$active_competitions = $this->get_competitions();
$active_competitions = array_filter($active_competitions, function($comp) {
    return $comp->status === 'active';
});

// Get competition filter
$competition_id = isset($_GET['competition_id']) ? intval($_GET['competition_id']) : null;

// Get user's team
$user_id = get_current_user_id();
$user_teams = $this->get_user_teams($user_id);

// Get metrics for the selected competition
$competition_metrics = array();
if ($competition_id) {
    $competition_metrics = $this->get_competition_metrics($competition_id);
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors('metric_messages'); ?>
    
    <?php if (empty($active_competitions)): ?>
        <div class="notice notice-info">
            <p>There are no active competitions at the moment.</p>
        </div>
    <?php else: ?>
        <!-- Competition Filter -->
        <div class="competition-filter">
            <form method="get" action="">
                <input type="hidden" name="page" value="kpi-metrics">
                <select name="competition_id" id="competition-filter">
                    <option value="">Select Competition</option>
                    <?php foreach ($active_competitions as $competition): ?>
                        <option value="<?php echo esc_attr($competition->id); ?>"
                                <?php selected($competition_id, $competition->id); ?>>
                            <?php echo esc_html($competition->theme); ?>
                            (<?php echo esc_html($competition->campaign_name); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php submit_button('Filter', 'secondary', 'filter', false); ?>
            </form>
        </div>

        <?php if ($competition_id && !empty($competition_metrics)): ?>
            <!-- Record Metrics Form -->
            <div class="record-metrics-form">
                <form method="post" action="">
                    <?php wp_nonce_field('save_metric', 'metric_nonce'); ?>
                    <input type="hidden" name="competition_id" value="<?php echo esc_attr($competition_id); ?>">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="metric_name">Metric</label>
                            </th>
                            <td>
                                <select name="metric_name" id="metric_name" required>
                                    <option value="">Select Metric</option>
                                    <?php foreach ($competition_metrics as $metric): ?>
                                        <option value="<?php echo esc_attr($metric->metric_name); ?>"
                                                data-points="<?php echo esc_attr($metric->points); ?>"
                                                data-description="<?php echo esc_attr($metric->description); ?>">
                                            <?php echo esc_html($metric->metric_name); ?>
                                            (<?php echo esc_html($metric->points); ?> points)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description metric-description"></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="metric_value">Value</label>
                            </th>
                            <td>
                                <input type="number" 
                                       name="metric_value" 
                                       id="metric_value" 
                                       class="regular-text" 
                                       step="1"
                                       min="0"
                                       required>
                                <p class="description points-preview"></p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button('Record Metric'); ?>
                </form>
            </div>

            <!-- User's Recent Metrics -->
            <div class="recent-metrics">
                <h2>Your Recent Metrics</h2>
                <?php 
                $recent_metrics = $this->get_user_metrics($user_id, $competition_id, 10);
                if (!empty($recent_metrics)): 
                ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Metric</th>
                                <th>Value</th>
                                <th>Points Earned</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_metrics as $metric): ?>
                                <tr>
                                    <td><?php echo esc_html(date('M j, Y g:i a', strtotime($metric->date_recorded))); ?></td>
                                    <td><?php echo esc_html($metric->metric_name); ?></td>
                                    <td><?php echo esc_html($metric->metric_value); ?></td>
                                    <td><?php echo number_format($metric->points_earned, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">You haven't recorded any metrics for this competition yet.</p>
                <?php endif; ?>
            </div>

            <!-- Team Progress -->
            <?php if (!empty($user_teams)): ?>
                <div class="team-progress">
                    <h2>Team Progress</h2>
                    <?php foreach ($user_teams as $team): ?>
                        <div class="team-metrics">
                            <h3><?php echo esc_html($team->name); ?></h3>
                            <?php 
                            $team_metrics = $this->get_team_metrics($team->id, $competition_id);
                            if (!empty($team_metrics)): 
                            ?>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>Member</th>
                                            <th>Total Points</th>
                                            <th>Last Activity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($team_metrics as $member): ?>
                                            <tr>
                                                <td><?php echo esc_html($member->display_name); ?></td>
                                                <td><?php echo number_format($member->total_points, 2); ?></td>
                                                <td><?php echo esc_html(date('M j, Y g:i a', strtotime($member->last_activity))); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="no-data">No team metrics recorded for this competition yet.</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php elseif ($competition_id): ?>
            <div class="notice notice-warning">
                <p>No metrics have been defined for this competition yet.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Auto-submit competition filter
    $('#competition-filter').on('change', function() {
        $(this).closest('form').submit();
    });

    // Update metric description and points preview when metric changes
    $('#metric_name').on('change', function() {
        const $selected = $(this).find('option:selected');
        const description = $selected.data('description');
        const points = $selected.data('points');
        
        $('.metric-description').text(description);
        updatePointsPreview(points);
    });

    // Update points preview when value changes
    $('#metric_value').on('input', function() {
        const points = $('#metric_name').find('option:selected').data('points');
        updatePointsPreview(points);
    });

    function updatePointsPreview(points) {
        const value = $('#metric_value').val();
        if (value && points) {
            const totalPoints = (parseFloat(value) * parseFloat(points)).toFixed(2);
            $('.points-preview').text(`This will earn ${totalPoints} points`);
        } else {
            $('.points-preview').text('');
        }
    }
});
</script>