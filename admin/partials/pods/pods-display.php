<?php
/**
 * Pods management page
 *
 * @package    KPI_Competition_Dashboard
 */

// Check user capabilities
if (!current_user_can('manage_options')) {
    return;
}

// Enqueue WordPress media uploader
if (is_admin()) {
    wp_enqueue_media();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pod_nonce'])) {
    if (!wp_verify_nonce($_POST['pod_nonce'], 'save_pod')) {
        wp_die('Invalid nonce specified');
    }
    
    $pod_data = array(
        'id' => intval($_POST['id']),
        'campaign_id' => intval($_POST['campaign_id']),
        'name' => sanitize_text_field($_POST['pod_name']),
        'logo_id' => intval($_POST['pod_logo_id'])
    );
    
    if ($this->save_pod($pod_data)) {
        add_settings_error(
            'pod_messages',
            'pod_message',
            'Pod saved successfully',
            'updated'
        );
    } else {
        add_settings_error(
            'pod_messages',
            'pod_message',
            'Error saving pod',
            'error'
        );
    }
}

// Get campaign filter
$campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : null;

// Get existing pods
$pods = $this->get_pods($campaign_id);

// Get campaigns for dropdown
$campaigns = $this->get_campaigns();

// Get the current campaign if filtered
$current_campaign = null;
if ($campaign_id) {
    foreach ($campaigns as $campaign) {
        if ($campaign->id === $campaign_id) {
            $current_campaign = $campaign;
            break;
        }
    }
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php 
        if ($current_campaign) {
            echo esc_html("Pods - {$current_campaign->name}");
        } else {
            echo esc_html(get_admin_page_title());
        }
        ?>
    </h1>
    <a href="#" class="page-title-action" id="add-new-pod">Add New Pod</a>
    
    <?php settings_errors('pod_messages'); ?>
    
    <!-- Campaign Filter -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" action="">
                <input type="hidden" name="page" value="kpi-pods">
                <select name="campaign_id" id="campaign-filter">
                    <option value="">All Campaigns</option>
                    <?php foreach ($campaigns as $campaign): ?>
                        <option value="<?php echo esc_attr($campaign->id); ?>"
                                <?php selected($campaign_id, $campaign->id); ?>>
                            <?php echo esc_html($campaign->name); ?>
                            (<?php echo esc_html($campaign->company_name); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php submit_button('Filter', 'secondary', 'filter', false); ?>
            </form>
        </div>
    </div>
    
    <!-- Add/Edit Pod Form -->
    <div class="pod-form-wrap" style="display: none;">
        <form method="post" action="" class="pod-form">
            <?php wp_nonce_field('save_pod', 'pod_nonce'); ?>
            <input type="hidden" name="id" id="pod_id" value="">
            <input type="hidden" name="logo_id" id="pod_logo_id" value="">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="campaign_id">Campaign</label>
                    </th>
                    <td>
                        <select name="campaign_id" id="campaign_id" required>
                            <option value="">Select Campaign</option>
                            <?php foreach ($campaigns as $campaign): ?>
                                <option value="<?php echo esc_attr($campaign->id); ?>"
                                        <?php selected($campaign_id, $campaign->id); ?>>
                                    <?php echo esc_html($campaign->name); ?>
                                    (<?php echo esc_html($campaign->company_name); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="pod_name">Name</label>
                    </th>
                    <td>
                        <input type="text" 
                               name="pod_name" 
                               id="pod_name" 
                               class="regular-text" 
                               required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="pod_logo">Logo</label>
                    </th>
                    <td>
                        <div id="pod-logo-preview"></div>
                        <button type="button" class="button" id="upload-pod-logo">Select Logo</button>
                        <button type="button" class="button" id="remove-pod-logo" style="display:none;">Remove Logo</button>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Save Pod'); ?>
            <button type="button" class="button button-secondary cancel-form">Cancel</button>
        </form>
    </div>

    <!-- Pods List -->
    <div class="pods-list-wrap">
        <?php if (!empty($pods)): ?>
            <?php foreach ($pods as $pod): ?>
                <div class="pod-row" data-pod-id="<?php echo esc_attr($pod->id); ?>" data-pod-name="<?php echo esc_attr($pod->name); ?>">
                    <div class="pod-main">
                        <div class="pod-logo">
                            <?php if (!empty($pod->logo_url)): ?>
                                <img src="<?php echo esc_url($pod->logo_url); ?>" alt="Pod Logo" style="max-width: 50px;">
                            <?php endif; ?>
                        </div>
                        <div class="pod-info">
                            <strong><?php echo esc_html($pod->name); ?></strong>
                            <span class="pod-campaign"><?php echo esc_html($pod->campaign_name); ?></span>
                        </div>
                        <div class="pod-actions">
                            <button class="button button-small edit-pod" 
                                    data-id="<?php echo esc_attr($pod->id); ?>"
                                    data-name="<?php echo esc_attr($pod->name); ?>"
                                    data-campaign="<?php echo esc_attr($pod->campaign_id); ?>">
                                Edit
                            </button>
                            <button class="button button-small delete-pod" 
                                    data-id="<?php echo esc_attr($pod->id); ?>">
                                Delete
                            </button>
                        </div>
                        <div class="pod-toggle">
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </div>
                    </div>
                    <div class="pod-agents-section"></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-data">No pods found.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Pod Agents Dialog -->
<div id="pod-agents-dialog" style="display:none;">
    <h2>Managing Agents for: <span class="pod-name"></span></h2>
    
    <div class="pod-agents-wrapper">
        <div class="available-users">
            <h3>Available Users</h3>
            <input type="text" id="filter-users" placeholder="Filter users...">
            <div class="users-list"></div>
        </div>
        
        <div class="assigned-agents">
            <h3>Pod Agents</h3>
            <div class="agents-list"></div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Auto-submit campaign filter
    $('#campaign-filter').on('change', function() {
        $(this).closest('form').submit();
    });

    // Show/Hide add pod form
    $('#add-new-pod').on('click', function(e) {
        e.preventDefault();
        $('.pod-form-wrap').slideDown();
    });

    $('.cancel-form').on('click', function() {
        $('.pod-form-wrap').slideUp();
        $('.pod-form')[0].reset();
        $('#pod_logo_id').val('');
        $('#pod-logo-preview').empty();
        $('#remove-pod-logo').hide();
    });

    // Handle edit pod button
    $('.edit-pod').on('click', function() {
        const $button = $(this);
        const id = $button.data('id');
        const name = $button.data('name');
        const campaign = $button.data('campaign');
        const logoId = $button.data('logo-id');
        const logoUrl = $button.data('logo-url');

        // Populate form
        $('#pod_id').val(id);
        $('#pod_name').val(name);
        $('#campaign_id').val(campaign);

        if (logoUrl) {
            $('#pod_logo_id').val(logoId);
            $('#pod-logo-preview').html(`<img src="${logoUrl}" style="max-width:100px;max-height:100px;" />`);
            $('#remove-pod-logo').show();
        }

        // Update submit button text
        $('.pod-form input[type="submit"]').val('Update Pod');

        // Show form
        $('.pod-form-wrap').slideDown();

        // Scroll to form
        $('html, body').animate({
            scrollTop: $('.pod-form-wrap').offset().top - 100
        }, 500);
    });

    // WordPress Media Uploader for logo
    let podLogoFrame;
    $('#upload-pod-logo').on('click', function(e) {
        e.preventDefault();
        if (podLogoFrame) {
            podLogoFrame.open();
            return;
        }
        podLogoFrame = wp.media({
            title: 'Select or Upload Pod Logo',
            button: { text: 'Use this logo' },
            multiple: false
        });
        podLogoFrame.on('select', function() {
            const attachment = podLogoFrame.state().get('selection').first().toJSON();
            $('#pod_logo_id').val(attachment.id);
            $('#pod-logo-preview').html(`<img src="${attachment.url}" style="max-width:100px;max-height:100px;" />`);
            $('#remove-pod-logo').show();
        });
        podLogoFrame.open();
    });
    $('#remove-pod-logo').on('click', function(e) {
        e.preventDefault();
        $('#pod_logo_id').val('');
        $('#pod-logo-preview').empty();
        $(this).hide();
    });

    // Handle pod deletion
    $('.delete-pod').on('click', function() {
        if (!confirm('Are you sure you want to delete this pod?')) {
            return;
        }

        const button = $(this);
        const podId = button.data('id');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_pod',
                pod_id: podId,
                nonce: kpiDashboard.nonce
            },
            success: function(response) {
                if (response.success) {
                    button.closest('tr').fadeOut();
                } else {
                    alert('Failed to delete pod');
                }
            },
            error: function() {
                alert('Error occurred while deleting pod');
            }
        });
    });
});
</script>