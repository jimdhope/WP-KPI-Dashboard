<?php
/**
 * Competition Leaderboard Display
 *
 * @package    KPI_Competition_Dashboard
 */

// Check user capabilities
if (!current_user_can('read')) {
    return;
}

// Get competition filter
$competition_id = isset($_GET['competition_id']) ? intval($_GET['competition_id']) : null;

// Get active competitions for dropdown
$active_competitions = $this->get_competitions('active');

// Get leaderboard data if competition is selected
$leaderboard_data = array();
$competition = null;
if ($competition_id) {
    $competition = $this->get_competition($competition_id);
    $leaderboard_data = $this->get_competition_leaderboard($competition_id);
}

// Get metric breakdown if requested
$show_metrics = isset($_GET['show_metrics']) && $_GET['show_metrics'] === '1';
$metric_data = array();
if ($show_metrics && $competition_id) {
    $metric_data = $this->get_competition_metric_breakdown($competition_id);
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Competition Filter -->
    <div class="competition-filter">
        <form method="get" action="">
            <input type="hidden" name="page" value="kpi-leaderboard">
            <select name="competition_id" id="competition-filter">
                <option value="">Select Competition</option>
                <?php foreach ($active_competitions as $comp): ?>
                    <option value="<?php echo esc_attr($comp->id); ?>"
                            <?php selected($competition_id, $comp->id); ?>>
                        <?php echo esc_html($comp->theme); ?>
                        (<?php echo esc_html($comp->campaign_name); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            
            <?php if ($competition_id): ?>
                <label for="show_metrics">
                    <input type="checkbox" 
                           name="show_metrics" 
                           id="show_metrics" 
                           value="1"
                           <?php checked($show_metrics); ?>>
                    Show Metric Breakdown
                </label>
            <?php endif; ?>
            
            <?php submit_button('Filter', 'secondary', 'filter', false); ?>
        </form>
    </div>

    <?php if ($competition): ?>
        <div class="competition-info">
            <h2><?php echo esc_html($competition->theme); ?></h2>
            <p class="competition-details">
                Campaign: <?php echo esc_html($competition->campaign_name); ?><br>
                Duration: <?php echo esc_html(date('M j, Y', strtotime($competition->start_date))); ?> - 
                         <?php echo esc_html(date('M j, Y', strtotime($competition->end_date))); ?>
            </p>
        </div>

        <?php if (!empty($leaderboard_data)): ?>
            <!-- Overall Rankings -->
            <div class="leaderboard-section">
                <h3>Overall Rankings</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Team</th>
                            <th>Pod</th>
                            <th>Total Points</th>
                            <th>Participants</th>
                            <th>Last Activity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        foreach ($leaderboard_data as $team): 
                        ?>
                            <tr class="<?php echo ($rank <= 3) ? 'top-three rank-' . $rank : ''; ?>">
                                <td>
                                    <?php if ($rank <= 3): ?>
                                        <span class="rank-medal rank-<?php echo $rank; ?>">
                                            <?php echo $rank; ?>
                                        </span>
                                    <?php else: ?>
                                        <?php echo $rank; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo esc_html($team->team_name); ?>
                                    <?php if ($team->is_user_team): ?>
                                        <span class="your-team-badge">Your Team</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($team->pod_name); ?></td>
                                <td><?php echo number_format($team->total_points, 2); ?></td>
                                <td><?php echo $team->participant_count; ?></td>
                                <td><?php echo esc_html(date('M j, Y g:i a', strtotime($team->last_activity))); ?></td>
                            </tr>
                        <?php 
                            $rank++;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>

            <?php if ($show_metrics && !empty($metric_data)): ?>
                <!-- Metric Breakdown -->
                <div class="leaderboard-section metric-breakdown">
                    <h3>Metric Breakdown</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Metric</th>
                                <th>Leading Team</th>
                                <th>Best Value</th>
                                <th>Average</th>
                                <th>Your Team</th>
                                <th>Points Per Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($metric_data as $metric): ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html($metric->metric_name); ?>
                                        <div class="metric-description">
                                            <?php echo esc_html($metric->description); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo esc_html($metric->leading_team); ?>
                                        <?php if ($metric->is_user_team_leading): ?>
                                            <span class="your-team-badge">Your Team</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($metric->best_value, 2); ?></td>
                                    <td><?php echo number_format($metric->average_value, 2); ?></td>
                                    <td>
                                        <?php 
                                        if ($metric->user_team_value !== null) {
                                            echo number_format($metric->user_team_value, 2);
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo number_format($metric->points_per_unit, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Performance Graphs -->
                <div class="leaderboard-section performance-graphs">
                    <h3>Performance Trends</h3>
                    <div class="chart-container">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>

                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    const ctx = document.getElementById('performanceChart').getContext('2d');
                    const chartData = <?php echo json_encode($this->get_performance_chart_data($competition_id)); ?>;
                    
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: chartData.labels,
                            datasets: chartData.datasets
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                title: {
                                    display: true,
                                    text: 'Team Performance Over Time'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Points'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Date'
                                    }
                                }
                            }
                        }
                    });
                });
                </script>
            <?php endif; ?>
        <?php else: ?>
            <div class="notice notice-info">
                <p>No leaderboard data available for this competition yet.</p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="notice notice-info">
            <p>Select a competition to view its leaderboard.</p>
        </div>
    <?php endif; ?>
</div>