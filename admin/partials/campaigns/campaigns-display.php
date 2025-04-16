<?php
/**
 * Campaigns management page
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['campaign_nonce'])) {
    if (!wp_verify_nonce($_POST['campaign_nonce'], 'save_campaign')) {
        wp_die('Invalid nonce specified');
    }
    
    $campaign_data = array(
        'company_id' => intval($_POST['company_id']),
        'name' => sanitize_text_field($_POST['campaign_name']),
        'logo_id' => intval($_POST['campaign_logo_id'])
    );
    
    if ($this->save_campaign($campaign_data)) {
        add_settings_error(
            'campaign_messages',
            'campaign_message',
            'Campaign saved successfully',
            'updated'
        );
    } else {
        add_settings_error(
            'campaign_messages',
            'campaign_message',
            'Error saving campaign',
            'error'
        );
    }
}

// Get company filter
$company_id = isset($_GET['company_id']) ? intval($_GET['company_id']) : null;

// Get existing campaigns
$campaigns = $this->get_campaigns($company_id);

// Get companies for dropdown
$companies = $this->get_companies();
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    <a href="#" class="page-title-action" id="add-new-campaign">Add New Campaign</a>
    
    <?php settings_errors('campaign_messages'); ?>
    
    <!-- Company Filter -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" action="">
                <input type="hidden" name="page" value="kpi-campaigns">
                <select name="company_id" id="company-filter">
                    <option value="">All Companies</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?php echo esc_attr($company->id); ?>"
                                <?php selected($company_id, $company->id); ?>>
                            <?php echo esc_html($company->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php submit_button('Filter', 'secondary', 'filter', false); ?>
            </form>
        </div>
    </div>
    
    <!-- Add/Edit Campaign Form -->
    <div class="campaign-form-wrap" style="display: none;">
        <form method="post" action="" class="add-campaign-form">
            <?php wp_nonce_field('save_campaign', 'campaign_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="company_id">Company</label>
                    </th>
                    <td>
                        <select name="company_id" id="company_id" required>
                            <option value="">Select Company</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?php echo esc_attr($company->id); ?>">
                                    <?php echo esc_html($company->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="campaign_name">Campaign Name</label>
                    </th>
                    <td>
                        <input type="text" 
                               name="campaign_name" 
                               id="campaign_name" 
                               class="regular-text" 
                               required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="campaign_logo">Logo</label>
                    </th>
                    <td>
                        <div id="campaign-logo-preview"></div>
                        <input type="hidden" name="campaign_logo_id" id="campaign_logo_id" value="">
                        <button type="button" class="button" id="upload-campaign-logo">Select Logo</button>
                        <button type="button" class="button" id="remove-campaign-logo" style="display:none;">Remove Logo</button>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Add Campaign'); ?>
            <button type="button" class="button button-secondary cancel-form">Cancel</button>
        </form>
    </div>

    <!-- Campaigns List -->
    <div class="campaigns-list-wrap">
        <?php if (!empty($campaigns)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Logo</th>
                        <th>Campaign Name</th>
                        <th>Company</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campaigns as $campaign): ?>
                        <tr>
                            <td>
                                <?php if (!empty($campaign->logo_url)): ?>
                                    <img src="<?php echo esc_url($campaign->logo_url); ?>" alt="Logo" style="max-width:60px;max-height:60px;">
                                <?php else: ?>
                                    <span>No Logo</span>
                                <?php endif; ?>
                            </td>
                            <td class="campaign-name">
                                <strong><?php echo esc_html($campaign->name); ?></strong>
                            </td>
                            <td class="company-name">
                                <?php echo esc_html($campaign->company_name); ?>
                            </td>
                            <td class="actions">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=kpi-pods&campaign_id=' . $campaign->id)); ?>" 
                                   class="button button-small">Manage Pods</a>
                                <button type="button" 
                                        class="button button-small edit-campaign" 
                                        data-id="<?php echo esc_attr($campaign->id); ?>"
                                        data-company="<?php echo esc_attr($campaign->company_id); ?>"
                                        data-name="<?php echo esc_attr($campaign->name); ?>"
                                        data-logo="<?php echo esc_attr($campaign->logo_id); ?>">
                                    Edit
                                </button>
                                <button type="button" 
                                        class="button button-small delete-campaign" 
                                        data-id="<?php echo esc_attr($campaign->id); ?>">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-data">No campaigns found.</p>
        <?php endif; ?>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Auto-submit company filter
    $('#company-filter').on('change', function() {
        $(this).closest('form').submit();
    });

    // Show/Hide add campaign form
    $('#add-new-campaign').on('click', function(e) {
        e.preventDefault();
        $('.campaign-form-wrap').slideDown();
    });

    $('.cancel-form').on('click', function() {
        $('.campaign-form-wrap').slideUp();
        $('.add-campaign-form')[0].reset();
    });

    // Handle edit campaign button
    $('.edit-campaign').on('click', function() {
        const $button = $(this);
        const companyId = $button.data('company');
        const campaignName = $button.data('name');
        const logoId = $button.data('logo');

        // Populate form
        $('#company_id').val(companyId);
        $('#campaign_name').val(campaignName);
        if (logoId) {
            $('#campaign_logo_id').val(logoId);
            $('#campaign-logo-preview').html('<img src="' + wp.media.attachment(logoId).get('url') + '" style="max-width:100px;max-height:100px;" />');
            $('#remove-campaign-logo').show();
        } else {
            $('#campaign_logo_id').val('');
            $('#campaign-logo-preview').empty();
            $('#remove-campaign-logo').hide();
        }
        
        // Update submit button text
        $('.add-campaign-form input[type="submit"]').val('Update Campaign');
        
        // Show form
        $('.campaign-form-wrap').slideDown();
        
        // Scroll to form
        $('html, body').animate({
            scrollTop: $('.campaign-form-wrap').offset().top - 100
        }, 500);
    });

    // WordPress Media Uploader for logo
    let campaignLogoFrame;
    $('#upload-campaign-logo').on('click', function(e) {
        e.preventDefault();
        // Ensure wp.media is available
        if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
            alert('The WordPress media uploader is not available. Please reload the page.');
            return;
        }
        if (campaignLogoFrame) {
            campaignLogoFrame.open();
            return;
        }
        campaignLogoFrame = wp.media({
            title: 'Select or Upload Logo',
            button: { text: 'Use this logo' },
            multiple: false
        });
        campaignLogoFrame.on('select', function() {
            const attachment = campaignLogoFrame.state().get('selection').first().toJSON();
            $('#campaign_logo_id').val(attachment.id);
            $('#campaign-logo-preview').html('<img src="' + attachment.url + '" style="max-width:100px;max-height:100px;" />');
            $('#remove-campaign-logo').show();
        });
        campaignLogoFrame.open();
    });
    $('#remove-campaign-logo').on('click', function(e) {
        e.preventDefault();
        $('#campaign_logo_id').val('');
        $('#campaign-logo-preview').empty();
        $(this).hide();
    });

    // Handle delete campaign button
    $('.delete-campaign').on('click', function() {
        if (!confirm('Are you sure you want to delete this campaign?')) {
            return;
        }

        const button = $(this);
        const campaignId = button.data('id');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_campaign',
                campaign_id: campaignId,
                nonce: '<?php echo wp_create_nonce('kpi_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    button.closest('tr').fadeOut();
                } else {
                    alert('Failed to delete campaign');
                }
            },
            error: function() {
                alert('Error occurred while deleting campaign');
            }
        });
    });
});
</script>