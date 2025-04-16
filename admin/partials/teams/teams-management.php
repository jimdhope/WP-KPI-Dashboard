<?php
/**
 * Teams Management Page
 *
 * @package    KPI_Competition_Dashboard
 */

// Check user capabilities
if (!current_user_can('manage_options')) {
    return;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Teams Management</h1>

    <!-- Competition Selection -->
    <div class="competition-selector">
        <form id="select-competition-form">
            <select name="competition_id" id="competition_id" required>
                <option value="">Select Competition</option>
                <?php foreach ($this->get_active_competitions() as $comp): ?>
                    <option value="<?php echo esc_attr($comp->id); ?>">
                        <?php echo esc_html($comp->theme); ?> 
                        (<?php echo esc_html($this->get_campaign($comp->campaign_id)->name); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Teams Management Interface -->
    <div id="teams-management" style="display: none;">
        <div class="teams-actions">
            <button type="button" class="button button-secondary" id="add-team">Add Team</button>
            <button type="button" class="button button-secondary" id="random-teams">Random Teams</button>
            <button type="button" class="button button-primary" id="save-teams">Save Teams</button>
        </div>

        <div class="teams-container">
            <div class="available-agents-card">
                <h3>Available Agents</h3>
                <div class="search-box">
                    <input type="text" id="search-agents" placeholder="Search agents...">
                </div>
                <div id="available-agents"></div>
            </div>
            <div id="teams-grid"></div>
        </div>
    </div>
</div>
