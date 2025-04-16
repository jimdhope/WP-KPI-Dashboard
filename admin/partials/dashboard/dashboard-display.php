<?php
/**
 * KPI Competition Dashboard Overview
 *
 * @package    KPI_Competition_Dashboard
 */

// Check user capabilities
if (!current_user_can('read')) {
    return;
}

$user_id = get_current_user_id();

// Get user's teams
$user_teams = $this->get_user_teams($user_id);

// Get active competitions for the user's teams
$active_competitions = array();
if (!empty($user_teams)) {
    foreach ($user_teams as $team) {
        $team_competitions = $this->get_team_competitions($team->id, 'active');
        $active_competitions = array_merge($active_competitions, $team_competitions);
    }
}

// Get user's recent activity
$recent_activity = $this->get_user_metrics($user_id, null, 5);

// Get user's total points across all active competitions
$total_points = $this->get_user_total_points($user_id);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="dashboard-overview">
        <!-- User Stats Summary -->
        <div class="stats-summary">
            <div class="stat-card total-points">
                <h3>Your Total Points</h3>
                <div class="stat-value"><?php echo number_format($total_points, 2); ?></div>
            </div>
            
            <div class="stat-card active-competitions">
                <h3>Active Competitions</h3>
                <div class="stat-value"><?php echo count($active_competitions); ?></div>
            </div>
            
            <div class="stat-card your-teams">
                <h3>Your Teams</h3>
                <div class="stat-value"><?php echo count($user_teams); ?></div>
            </div>
        </div>

        <!-- Active Competitions -->
        <div class="dashboard-section">
            <h2>Active Competitions</h2>
            <?php if (!empty($active_competitions)): ?>
                <div class="active-competitions-grid">
                    <?php foreach ($active_competitions as $competition): ?>
                        <div class="competition-card">
                            <div class="competition-header">
                                <h3><?php echo esc_html($competition->theme); ?></h3>
                                <p class="campaign-name"><?php echo esc_html($competition->campaign_name); ?></p>
                            </div>
                            
                            <div class="competition-dates">
                                <span class="date-label">Ends:</span>
                                <?php 
                                $days_left = ceil((strtotime($competition->end_date) - time()) / (60 * 60 * 24));
                                echo esc_html(date('M j, Y', strtotime($competition->end_date)));
                                echo " ({$days_left} days left)";
                                ?>
                            </div>
                            
                            <?php 
                            $user_rank = $this->get_user_competition_rank($user_id, $competition->id);
                            $total_participants = $this->get_competition_participant_count($competition->id);
                            ?>
                            <div class="competition-progress">
                                <div class="rank-info">
                                    <span class="rank-label">Your Rank:</span>
                                    <span class="rank-value"><?php echo $user_rank; ?></span>
                                    <span class="rank-total">of <?php echo $total_participants; ?></span>
                                </div>
                                
                                <?php 
                                $user_points = $this->get_user_competition_points($user_id, $competition->id);
                                $leader_points = $this->get_competition_leader_points($competition->id);
                                $progress = ($leader_points > 0) ? ($user_points / $leader_points) * 100 : 0;
                                ?>
                                <div class="points-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo esc_attr($progress); ?>%"></div>
                                    </div>
                                    <div class="points-info">
                                        <span class="your-points"><?php echo number_format($user_points, 2); ?> points</span>
                                        <span class="leader-points"><?php echo number_format($leader_points, 2); ?> points (leader)</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="competition-actions">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=kpi-metrics&competition_id=' . $competition->id)); ?>" 
                                   class="button button-primary">Record Metrics</a>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=kpi-leaderboard&competition_id=' . $competition->id)); ?>" 
                                   class="button">View Leaderboard</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-data">You are not participating in any active competitions.</p>
            <?php endif; ?>
        </div>

        <!-- User's Teams -->
        <div class="dashboard-section">
            <h2>Your Teams</h2>
            <?php if (!empty($user_teams)): ?>
                <div class="teams-grid">
                    <?php foreach ($user_teams as $team): ?>
                        <div class="team-card">
                            <div class="team-header">
                                <h3><?php echo esc_html($team->name); ?></h3>
                                <p class="pod-name"><?php echo esc_html($team->pod_name); ?></p>
                            </div>
                            
                            <?php 
                            $team_stats = $this->get_team_competition_stats($team->id);
                            ?>
                            <div class="team-stats">
                                <div class="stat">
                                    <span class="stat-label">Active Competitions:</span>
                                    <span class="stat-value"><?php echo $team_stats->active_competitions; ?></span>
                                </div>
                                <div class="stat">
                                    <span class="stat-label">Team Members:</span>
                                    <span class="stat-value"><?php echo $team_stats->member_count; ?></span>
                                </div>
                                <div class="stat">
                                    <span class="stat-label">Total Points:</span>
                                    <span class="stat-value"><?php echo number_format($team_stats->total_points, 2); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($team_stats->active_competitions > 0): ?>
                                <div class="team-rankings">
                                    <h4>Current Rankings</h4>
                                    <?php 
                                    $team_rankings = $this->get_team_rankings($team->id);
                                    foreach ($team_rankings as $ranking): 
                                    ?>
                                        <div class="ranking-item">
                                            <span class="competition-name"><?php echo esc_html($ranking->competition_theme); ?></span>
                                            <span class="ranking">
                                                Rank <?php echo $ranking->rank; ?> 
                                                of <?php echo $ranking->total_teams; ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-data">You are not a member of any teams yet.</p>
            <?php endif; ?>
        </div>

        <!-- Recent Activity -->
        <div class="dashboard-section">
            <h2>Your Recent Activity</h2>
            <?php if (!empty($recent_activity)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Competition</th>
                            <th>Metric</th>
                            <th>Value</th>
                            <th>Points Earned</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_activity as $activity): ?>
                            <tr>
                                <td><?php echo esc_html(date('M j, Y g:i a', strtotime($activity->date_recorded))); ?></td>
                                <td><?php echo esc_html($activity->competition_theme); ?></td>
                                <td><?php echo esc_html($activity->metric_name); ?></td>
                                <td><?php echo esc_html($activity->metric_value); ?></td>
                                <td><?php echo number_format($activity->points_earned, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">No recent activity to display.</p>
            <?php endif; ?>
        </div>
    </div>
</div>