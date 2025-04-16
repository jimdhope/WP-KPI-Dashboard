<?php
/**
 * Competitions management page
 *
 * @package    KPI_Competition_Dashboard
 */

// Ensure file is accessed through WordPress
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
    return;
}

// Get existing competitions (Assuming $this points to the main plugin class or similar)
// Ensure $this is valid and the method exists before calling
$competitions = method_exists($this, 'get_competitions') ? $this->get_competitions() : [];
$companies = method_exists($this, 'get_companies') ? $this->get_companies() : [];

?>

<div class="wrap">
    <h1 class="wp-heading-inline">Competitions</h1>
    <a href="#" class="page-title-action" id="add-new-competition">Add New Competition</a>

    <!-- Add/Edit Competition Form -->
    <div class="competition-form-wrap" style="display: none;">
        <h2><span id="form-title">Add New</span> Competition</h2>
        <form method="post" action="" class="add-competition-form">
            <!-- Nonce field for security. Crucial for AJAX verification. -->
            <?php wp_nonce_field('save_competition_action', 'competition_nonce'); ?>
            <!-- Hidden field for competition ID during edits -->
            <input type="hidden" name="competition_id" id="competition_id" value="">

            <!-- Basic Information Section -->
            <div class="form-section">
                <h3>Basic Information</h3>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th><label for="company_id">Company <span class="description">(required)</span></label></th>
                            <td>
                                <select name="company_id" id="company_id" required>
                                    <option value="">Select Company</option>
                                    <?php if (!empty($companies)): ?>
                                        <?php foreach ($companies as $company): ?>
                                            <option value="<?php echo esc_attr($company->id); ?>">
                                                <?php echo esc_html($company->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="campaign_id">Campaign <span class="description">(required)</span></label></th>
                            <td>
                                <select name="campaign_id" id="campaign_id" required disabled>
                                    <option value="">Select Company First</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="theme">Theme <span class="description">(required)</span></label></th>
                            <td><input type="text" name="theme" id="theme" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="start_date">Start Date <span class="description">(required)</span></label></th>
                            <td>
                                <input type="date" name="start_date" id="start_date" required>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="end_date">End Date <span class="description">(required)</span></label></th>
                            <td>
                                <input type="date" name="end_date" id="end_date" required>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- KPI Rules Section -->
            <div class="form-section">
                <h3>KPI Rules</h3>
                <div class="kpi-rule-form">
                    <div class="rule-inputs">
                        <label for="rule-name" class="screen-reader-text">KPI Name</label>
                        <input type="text" id="rule-name" placeholder="KPI Name" class="regular-text">

                        <div class="emoji-input-group">
                             <label for="rule-emoji" class="screen-reader-text">Emoji</label>
                            <input type="text" id="rule-emoji" class="emoji-input" placeholder="Emoji (Optional)">
                            <span class="emoji-info-icon dashicons dashicons-info" title="Optional: Single emoji character for visual display."></span>
                        </div>

                        <label for="rule-points" class="screen-reader-text">Points</label>
                        <input type="number" id="rule-points" placeholder="Points" class="small-text" min="1">

                        <button type="button" class="button button-secondary" id="add-rule">Add Rule</button>
                    </div>
                     <p class="description">Define the Key Performance Indicators and their point values for this competition.</p>
                </div>
                <div class="kpi-rules-list" id="rules-container">
                    <!-- Rules will be added here dynamically -->
                </div>
                <!-- Hidden input to store the rules data as JSON -->
                <input type="hidden" name="rules" id="rules-data" value="[]">
            </div>

            <!-- Competition Setup Section -->
            <div class="form-section">
                <h3>Competition Setup</h3>
                <p class="description">Teams can be set up after creating the competition using the Teams Management page (if applicable).</p>
            </div>

            <?php submit_button('Create Competition', 'primary', 'submit-competition'); ?>
            <button type="button" class="button button-secondary cancel-form">Cancel</button>
        </form>
    </div>

    <!-- Competitions List -->
    <div id="competitions-list">
        <h2>Existing Competitions</h2>
        <?php if (!empty($competitions)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col">Theme</th>
                        <th scope="col">Campaign</th>
                        <th scope="col">Start Date</th>
                        <th scope="col">End Date</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($competitions as $competition):
                        // Ensure $this and methods exist before calling
                        $campaign = (method_exists($this, 'get_campaign') && $competition->campaign_id) ? $this->get_campaign($competition->campaign_id) : null;
                    ?>
                        <tr id="competition-row-<?php echo esc_attr($competition->id); ?>">
                            <td><?php echo esc_html($competition->theme); ?></td>
                            <td><?php echo esc_html($campaign ? $campaign->name : 'N/A'); ?></td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($competition->start_date))); ?></td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($competition->end_date))); ?></td>
                            <td>
                                <button type="button" class="button button-secondary button-small edit-competition"
                                        data-id="<?php echo esc_attr($competition->id); ?>"
                                        data-nonce="<?php echo wp_create_nonce('get_competition_details_action_' . $competition->id); ?>">
                                    Edit
                                </button>
                                <button type="button" class="button button-link-delete button-small delete-competition"
                                        data-id="<?php echo esc_attr($competition->id); ?>"
                                        data-nonce="<?php echo wp_create_nonce('delete_competition_action_' . $competition->id); ?>">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No competitions found. Click "Add New Competition" to create one.</p>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {

    // --- Global Variables / Config ---
    const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>'; // Standard WordPress AJAX URL

    // --- Helper Functions ---
    function showForm(isEdit = false, competitionData = null, rulesData = null) {
        const $form = $('.add-competition-form');
        const $formWrap = $('.competition-form-wrap');
        $form[0].reset(); // Reset form fields
        $('#competition_id').val(''); // Clear hidden ID field
        $('#campaign_id').html('<option value="">Select Company First</option>').prop('disabled', true); // Reset campaign dropdown
        $('#rules-container').empty(); // Clear rules list
        $('#rules-data').val('[]'); // Reset hidden rules data
        $form.find('.notice').remove(); // Remove previous notices

        if (isEdit && competitionData) {
            $('#form-title').text('Edit');
            $form.find('button[type="submit"]').text('Update Competition');
            $('#competition_id').val(competitionData.id);

            // Populate basic fields
            $('#theme').val(competitionData.theme);
            $('#start_date').val(competitionData.start_date ? competitionData.start_date.substring(0, 10) : '');
            $('#end_date').val(competitionData.end_date ? competitionData.end_date.substring(0, 10) : '');

            // Set company and trigger campaign load
            $('#company_id').val(competitionData.company_id);
            loadCampaigns(competitionData.company_id, competitionData.campaign_id); // Load campaigns and select the correct one

            // Populate rules
            if (rulesData && rulesData.length > 0) {
                rulesData.forEach(rule => {
                    addRuleElement(rule.name, rule.emoji, rule.points, false); // Add without clearing inputs
                });
                updateRulesDataInput(); // Ensure hidden input is synced
            }

        } else {
            $('#form-title').text('Add New');
            $form.find('button[type="submit"]').text('Create Competition');
        }

        $formWrap.show();
        $('#competitions-list').hide();
        $('html, body').animate({ scrollTop: 0 }, 'slow'); // Scroll to top
    }

    function hideForm() {
        $('.competition-form-wrap').hide();
        $('#competitions-list').show();
    }

    function displayNotice(message, type = 'success') {
        // Remove existing notices first
        $('.wrap > .notice').remove();
        const noticeHtml = `<div class="notice notice-${type} is-dismissible"><p>${message}</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>`;
        $('.wrap h1').first().after(noticeHtml);
         // Make the dismiss button work
        $('.notice.is-dismissible').on('click', '.notice-dismiss', function(event) {
            event.preventDefault();
            $(this).closest('.notice').fadeOut(100, function() { $(this).remove(); });
        });
    }

    function loadCampaigns(companyId, campaignToSelect = null) {
        const $campaignSelect = $('#campaign_id');
        $campaignSelect.html('<option value="">Loading...</option>').prop('disabled', true);

        if (!companyId) {
            $campaignSelect.html('<option value="">Select Company First</option>').prop('disabled', true);
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_company_campaigns_action', // Make sure this action matches your PHP hook
                company_id: companyId,
                nonce: '<?php echo wp_create_nonce("get_company_campaigns_nonce"); ?>' // Ensure this nonce action matches PHP check
            },
            success: function(response) {
                let options = '<option value="">Select Campaign</option>';
                if (response.success && response.data && response.data.length > 0) {
                    response.data.forEach(function(campaign) {
                        const selected = (campaignToSelect && campaign.id == campaignToSelect) ? ' selected' : '';
                        options += `<option value="${campaign.id}"${selected}>${campaign.name}</option>`;
                    });
                    $campaignSelect.html(options).prop('disabled', false);
                } else {
                    const message = response.data && response.data.message ? response.data.message : 'No campaigns found or error.';
                    $campaignSelect.html(`<option value="">${message}</option>`).prop('disabled', true);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX Error loading campaigns:', textStatus, errorThrown, jqXHR.responseText);
                $campaignSelect.html('<option value="">Error loading campaigns</option>').prop('disabled', true);
            }
        });
    }

    function addRuleElement(name, emoji, points, clearInputs = true) {
        console.log('Attempting to add rule:', { name, emoji, points });

        // --- Validation ---
        const trimmedName = name ? name.trim() : '';
        const trimmedEmoji = emoji ? emoji.trim() : ''; // Emoji is optional now
        const pointsNum = parseInt(points, 10);

        if (trimmedName.length === 0) {
            alert('Please enter a KPI Name.');
            $('#rule-name').focus();
            return false;
        }
        if (isNaN(pointsNum) || pointsNum <= 0) {
            alert('Please enter a valid positive number for Points.');
             $('#rule-points').focus();
            return false;
        }
        // Basic emoji validation (single character, could be more complex)
        if (trimmedEmoji.length > 2) { // Allow for variation selectors, but basic check
             alert('Emoji should ideally be a single character.');
             $('#rule-emoji').focus();
             // return false; // Decide if this should block adding
        }

        const safeEmoji = trimmedEmoji || '🏆'; // Default emoji if none provided
        const ruleHtml = `
            <div class="rule-item" data-name="${escapeHtml(trimmedName)}" data-emoji="${escapeHtml(safeEmoji)}" data-points="${pointsNum}">
                <span class="rule-emoji-display">${escapeHtml(safeEmoji)}</span>
                <span class="rule-name-display">${escapeHtml(trimmedName)}</span> -
                <span class="rule-points-display">${pointsNum}</span> points
                <button type="button" class="button button-link-delete delete-rule" aria-label="Remove rule ${escapeHtml(trimmedName)}">Remove</button>
            </div>
        `;
        $('#rules-container').append(ruleHtml);
        updateRulesDataInput(); // Update the hidden input field

        if (clearInputs) {
            $('#rule-name').val('');
            $('#rule-emoji').val('');
            $('#rule-points').val('');
            $('#rule-name').focus(); // Focus back on name for next entry
        }
        return true;
    }

    function updateRulesDataInput() {
        const rules = [];
        $('#rules-container .rule-item').each(function() {
            rules.push({
                name: $(this).data('name'), // Use data attributes for cleaner retrieval
                emoji: $(this).data('emoji'),
                points: parseInt($(this).data('points'), 10) || 0
            });
        });
        $('#rules-data').val(JSON.stringify(rules));
        console.log('Updated rules data:', $('#rules-data').val()); // Debug log
    }

    // Simple HTML escaping function
    function escapeHtml(unsafe) {
        if (typeof unsafe !== 'string') return unsafe;
        return unsafe
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }


    // --- Event Handlers ---
    $('#add-new-competition').on('click', function(e) {
        e.preventDefault();
        showForm(false); // Show form for adding new
    });

    $('.cancel-form').on('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to cancel? Any unsaved changes will be lost.')) {
            hideForm();
        }
    });

    // Handle Company Change -> Load Campaigns
    $('#company_id').on('change', function() {
        const companyId = $(this).val();
        loadCampaigns(companyId);
    });

    // Add Rule Button Click
    $('#add-rule').on('click', function() {
        const name = $('#rule-name').val();
        const emoji = $('#rule-emoji').val();
        const points = $('#rule-points').val();
        addRuleElement(name, emoji, points, true); // Add rule and clear inputs
    });

    // Delete Rule Button Click (Event Delegation)
    $('#rules-container').on('click', '.delete-rule', function() {
        $(this).closest('.rule-item').remove();
        updateRulesDataInput(); // Update hidden input
    });

    // Main Form Submission (Create/Update Competition)
    $('.add-competition-form').on('submit', function(e) {
        e.preventDefault(); // Prevent traditional form submission

        // Client-side validation (basic)
        let isValid = true;
        $(this).find('[required]').each(function() {
            if (!$(this).val()) {
                alert('Please fill in all required fields (marked with *)');
                $(this).focus();
                isValid = false;
                return false; // Exit .each loop
            }
        });
        if (!isValid) return;

        // Ensure rules data is up-to-date (should be, but belt-and-suspenders)
        updateRulesDataInput();

        const $submitButton = $(this).find('button[type="submit"]');
        const originalButtonText = $submitButton.text();
        $submitButton.prop('disabled', true).text('Saving...');

        // Use FormData to gather all fields, including the nonce and files if any
        const formData = new FormData(this);

        // IMPORTANT: Ensure the 'action' parameter matches your wp_ajax hook in PHP
        formData.append('action', 'save_competition_action');

        // The nonce 'competition_nonce' should be included automatically by FormData
        // from the hidden input generated by wp_nonce_field('save_competition_action', 'competition_nonce')
        console.log('Submitting form data:');
        for (let [key, value] of formData.entries()) {
            console.log(key, value);
        }


        $.ajax({
            url: ajaxurl, // WordPress AJAX handler URL
            type: 'POST',
            data: formData,
            processData: false, // Important for FormData
            contentType: false, // Important for FormData
            success: function(response) {
                console.log('AJAX Success Response:', response);
                if (response.success) {
                    displayNotice(response.data.message || 'Competition saved successfully!', 'success');
                    // Option 1: Reload the page to see the updated list
                    // location.reload();
                    // Option 2: Hide form and potentially update list dynamically (more complex)
                    hideForm();
                    // TODO: Add logic here to refresh or update the competitions list table without full reload if desired
                } else {
                    // Display specific error from server if available
                    displayNotice('Error: ' + (response.data.message || 'Could not save competition. Please check the details and try again.'), 'error');
                    $submitButton.prop('disabled', false).text(originalButtonText);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // Log detailed error info to the console for debugging
                console.error('AJAX Error:', textStatus, errorThrown);
                console.error('Response Text:', jqXHR.responseText);
                console.error('Status Code:', jqXHR.status);
                console.error('Headers:', jqXHR.getAllResponseHeaders());

                // Display a user-friendly error message
                let errorMsg = 'An unexpected error occurred while saving. Please try again.';
                if (jqXHR.status === 400) {
                    errorMsg = 'Error: The server indicated a Bad Request (400). This might be due to invalid data or a configuration issue. Check console for details.';
                } else if (jqXHR.status === 403) {
                     errorMsg = 'Error: You might not have permission for this action (403 Forbidden). This could be a nonce issue.';
                } else if (jqXHR.status === 500) {
                     errorMsg = 'Error: The server encountered an internal error (500). Check server logs and console for details.';
                } else if (textStatus === 'timeout') {
                    errorMsg = 'Error: The request timed out.';
                }
                displayNotice(errorMsg, 'error');
                $submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });

    // Edit Competition Button Click
    $('#competitions-list').on('click', '.edit-competition', function(e) {
        e.preventDefault();
        const competitionId = $(this).data('id');
        const nonce = $(this).data('nonce'); // Nonce specific to getting details

        console.log(`Editing competition ID: ${competitionId}, Nonce: ${nonce}`);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_competition_details_action', // Action to fetch details
                id: competitionId,
                nonce: nonce // Send the specific nonce for this action
            },
            success: function(response) {
                console.log('Edit Details Response:', response);
                if (response.success && response.data) {
                    showForm(true, response.data.competition, response.data.rules);
                } else {
                    alert('Error fetching competition details: ' + (response.data ? response.data.message : 'Unknown error'));
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX Error fetching details:', textStatus, errorThrown, jqXHR.responseText);
                alert('Error fetching competition data: ' + textStatus);
            }
        });
    });

    // Delete Competition Button Click
    $('#competitions-list').on('click', '.delete-competition', function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this competition? This cannot be undone.')) {
            return;
        }

        const competitionId = $(this).data('id');
        const nonce = $(this).data('nonce'); // Nonce specific to deletion
        const $button = $(this);
        const $row = $button.closest('tr');

        $button.prop('disabled', true).text('Deleting...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_competition_action', // Action for deletion
                id: competitionId,
                nonce: nonce // Send the specific nonce for deletion
            },
            success: function(response) {
                console.log('Delete Response:', response);
                if (response.success) {
                    $row.fadeOut(300, function() { $(this).remove(); });
                    displayNotice(response.data.message || 'Competition deleted successfully.', 'success');
                } else {
                    alert('Error deleting competition: ' + (response.data ? response.data.message : 'Unknown error'));
                    $button.prop('disabled', false).text('Delete');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX Error deleting:', textStatus, errorThrown, jqXHR.responseText);
                alert('Error sending delete request: ' + textStatus);
                $button.prop('disabled', false).text('Delete');
            }
        });
    });

});

/**
 * AJAX handler for deleting a competition.
 * Hooked to 'wp_ajax_delete_competition_action'.
 */
add_action('wp_ajax_delete_competition_action', 'kpi_dashboard_delete_competition_ajax_handler');

/**
 * Handles the actual deletion of a competition via AJAX.
 * Verifies nonce and user capabilities before deleting.
 */
function kpi_dashboard_delete_competition_ajax_handler() {

    // 1. Verify Nonce - Crucial for security
    // Check if ID and nonce are set in the POST data sent by AJAX
    if (!isset($_POST['id']) || !isset($_POST['nonce'])) {
        wp_send_json_error(['message' => 'Missing required data (ID or Nonce).'], 400);
        // wp_die(); // Terminate execution
    }

    // Sanitize the ID
    $competition_id = intval($_POST['id']);
    if ($competition_id <= 0) {
         wp_send_json_error(['message' => 'Invalid competition ID provided.'], 400);
        // wp_die();
    }

    // Construct the specific nonce action string that was used to create it
    // This MUST match the string used in wp_create_nonce() in competitions-display.php
    $nonce_action = 'delete_competition_action_' . $competition_id;

    // Verify the nonce using the specific action string and the key 'nonce'
    // check_ajax_referer() will die with -1 response body if the nonce is invalid.
    if (!check_ajax_referer($nonce_action, 'nonce', false)) {
         // Nonce is invalid or expired
         wp_send_json_error(['message' => 'Security check failed (Nonce mismatch). Please refresh and try again.'], 403);
        // wp_die();
    }

    // 2. Check User Capabilities - Also crucial for security
    if (!current_user_can('manage_options')) { // Use the appropriate capability for managing competitions
        wp_send_json_error(['message' => 'You do not have sufficient permissions to delete competitions.'], 403);
        // wp_die();
    }

    // --- Nonce and capabilities are valid ---

    // 3. Perform the Deletion Logic
    global $wpdb;
    // Replace 'kpi_competitions' with your actual database table name if different
    $table_name = $wpdb->prefix . 'kpi_competitions';

    $deleted = $wpdb->delete(
        $table_name,
        ['id' => $competition_id], // WHERE clause: delete the row with this ID
        ['%d']                    // Format for the WHERE value (integer)
    );

    // 4. Send Response
    if ($deleted !== false) {
        // Deletion successful (returns number of rows deleted, or 0 if row didn't exist)
        wp_send_json_success(['message' => 'Competition deleted successfully.']);
    } else {
        // Database error occurred during deletion
        // You might want to log $wpdb->last_error here for debugging
        error_log("KPI Dashboard DB Error: Failed to delete competition ID {$competition_id}. Error: " . $wpdb->last_error);
        wp_send_json_error(['message' => 'Database error: Could not delete the competition.']);
    }

    // wp_die(); // wp_send_json_* functions include wp_die() by default
}

</script>

<style>
/* Basic Form Styling */
.competition-form-wrap {
    background: #fff;
    padding: 20px;
    margin-top: 15px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}
.form-section {
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px dashed #ddd;
}
.form-section:last-of-type {
    border-bottom: none;
    margin-bottom: 15px;
}
.form-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 1.2em;
}
.form-table th {
    width: 150px; /* Adjust as needed */
}
.form-table .description {
    font-size: 13px;
    color: #646970;
}
span.description { /* Inline descriptions */
    font-style: italic;
    color: #777;
    margin-left: 5px;
}

/* KPI Rules Styling */
.kpi-rule-form .rule-inputs {
    display: flex;
    flex-wrap: wrap; /* Allow wrapping on smaller screens */
    gap: 10px; /* Spacing between input elements */
    align-items: center; /* Align items vertically */
    margin-bottom: 10px;
}
.kpi-rule-form .rule-inputs input[type="text"],
.kpi-rule-form .rule-inputs input[type="number"] {
    margin-bottom: 0; /* Remove default WP margin */
}
.kpi-rule-form .emoji-input-group {
    display: inline-flex; /* Use flex for inline alignment */
    align-items: center;
    gap: 5px;
}
.kpi-rule-form .emoji-input {
    width: 100px; /* Adjust width as needed */
}
.kpi-rule-form .emoji-info-icon {
    cursor: help;
    color: #0073aa;
}

.kpi-rules-list {
    margin-top: 15px;
    max-height: 200px; /* Example max height */
    overflow-y: auto; /* Add scroll if list gets long */
    border: 1px solid #eee;
    padding: 10px;
    background: #f9f9f9;
}
.kpi-rules-list .rule-item {
    background: #fff;
    border: 1px solid #ededed;
    padding: 8px 12px;
    margin-bottom: 5px;
    border-radius: 3px;
    display: flex;
    align-items: center;
    gap: 8px; /* Space between elements inside the rule item */
}
.rule-emoji-display {
    font-size: 1.2em;
    min-width: 20px; /* Ensure space even if emoji is missing */
    text-align: center;
}
.rule-name-display {
    flex-grow: 1; /* Allow name to take up available space */
}
.kpi-rules-list .delete-rule {
    margin-left: auto; /* Push delete button to the right */
    color: #a00;
    text-decoration: none;
    border: none;
    background: none;
    cursor: pointer;
    padding: 2px 5px;
}
.kpi-rules-list .delete-rule:hover {
    color: #f00;
    background-color: #fdeaea;
}

/* General UI */
.button-link-delete {
    color: #a00 !important;
    border-color: transparent !important;
    background: none !important;
    box-shadow: none !important;
    padding: 0 !important; /* Adjust as needed */
    vertical-align: baseline;
}
.button-link-delete:hover {
    color: #f00 !important;
    text-decoration: underline;
}
</style>
