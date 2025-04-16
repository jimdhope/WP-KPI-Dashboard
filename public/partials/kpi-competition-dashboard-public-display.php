<?php if (!empty($results)): ?>
    <div class="kpi-leaderboard">
        <h3>KPI Leaderboard - <?php echo esc_html($atts['metric']); ?></h3>
        <div class="kpi-leaderboard-table">
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>User</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    foreach ($results as $result): 
                        $user_info = get_userdata($result->user_id);
                    ?>
                        <tr class="<?php echo ($rank <= 3) ? 'top-three rank-' . $rank : ''; ?>">
                            <td class="rank"><?php echo $rank; ?></td>
                            <td class="user"><?php echo esc_html($user_info->display_name); ?></td>
                            <td class="value"><?php echo number_format($result->total_value, 2); ?></td>
                        </tr>
                    <?php 
                        $rank++;
                    endforeach; 
                    ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <p class="no-metrics">No data available for this metric.</p>
<?php endif; ?>