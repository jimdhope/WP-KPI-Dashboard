<?php
/**
 * Companies management page
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['company_nonce']) && wp_verify_nonce($_POST['company_nonce'], 'save_company')) {
        $company_data = array(
            'name' => sanitize_text_field($_POST['company_name']),
            'logo_id' => intval($_POST['company_logo_id'])
        );
        
        if ($this->save_company($company_data)) {
            add_settings_error(
                'company_messages',
                'company_message',
                'Company saved successfully',
                'updated'
            );
        } else {
            add_settings_error(
                'company_messages',
                'company_message',
                'Error saving company',
                'error'
            );
        }
    } elseif (isset($_POST['company_edit_nonce']) && wp_verify_nonce($_POST['company_edit_nonce'], 'company_edit')) {
        $company_id = intval($_POST['company_id']);
        $company_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'logo_id' => intval($_POST['company_logo_id'])
        );

        if ($this->update_company($company_id, $company_data)) {
            add_settings_error(
                'company_messages',
                'company_message',
                'Company updated successfully',
                'updated'
            );
        } else {
            add_settings_error(
                'company_messages',
                'company_message',
                'Error updating company',
                'error'
            );
        }
    } elseif (isset($_POST['delete_company_nonce']) && wp_verify_nonce($_POST['delete_company_nonce'], 'delete_company')) {
        $company_id = intval($_POST['company_id']);
        if ($this->delete_company($company_id)) {
            add_settings_error(
                'company_messages',
                'company_message',
                'Company deleted successfully',
                'updated'
            );
        } else {
            add_settings_error(
                'company_messages',
                'company_message',
                'Error deleting company',
                'error'
            );
        }
    }
}

// Handle edit form display
$edit_company = null;
if (isset($_GET['action'], $_GET['company_id']) && $_GET['action'] === 'edit') {
    global $wpdb;
    $edit_company = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}kpi_companies WHERE id = %d",
        intval($_GET['company_id'])
    ));
}

// Get existing companies
$companies = $this->get_companies();
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    <a href="#" class="page-title-action" id="add-new-company">Add New Company</a>
    
    <?php settings_errors('company_messages'); ?>
    
    <!-- Add Company Form -->
    <div class="company-form-wrap" style="display: none;">
        <form method="post" action="" class="add-company-form">
            <?php wp_nonce_field('save_company', 'company_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="company_name">Company Name</label>
                    </th>
                    <td>
                        <input type="text" 
                               name="company_name" 
                               id="company_name" 
                               class="regular-text" 
                               required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="company_logo">Logo</label>
                    </th>
                    <td>
                        <div id="company-logo-preview"></div>
                        <input type="hidden" name="company_logo_id" id="company_logo_id" value="">
                        <button type="button" class="button" id="upload-company-logo">Select Logo</button>
                        <button type="button" class="button" id="remove-company-logo" style="display:none;">Remove Logo</button>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Add Company'); ?>
            <button type="button" class="button button-secondary cancel-form">Cancel</button>
        </form>
    </div>

    <!-- Edit Company Form -->
    <?php if ($edit_company): ?>
        <h2>Edit Company</h2>
        <form method="post">
            <input type="hidden" name="company_id" value="<?php echo esc_attr($edit_company->id); ?>">
            <?php wp_nonce_field('company_edit', 'company_edit_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="name">Name</label></th>
                    <td><input type="text" name="name" id="name" value="<?php echo esc_attr($edit_company->name); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="company_logo">Logo</label></th>
                    <td>
                        <div id="edit-company-logo-preview">
                            <?php if (!empty($edit_company->logo_url)): ?>
                                <img src="<?php echo esc_url($edit_company->logo_url); ?>" alt="Logo" style="max-width:100px;max-height:100px;">
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="company_logo_id" id="edit_company_logo_id" value="<?php echo !empty($edit_company->logo_id) ? esc_attr($edit_company->logo_id) : ''; ?>">
                        <button type="button" class="button" id="edit-upload-company-logo"><?php echo empty($edit_company->logo_url) ? 'Select Logo' : 'Change Logo'; ?></button>
                        <?php if (!empty($edit_company->logo_url)): ?>
                            <button type="button" class="button" id="edit-remove-company-logo">Remove Logo</button>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <p>
                <input type="submit" class="button button-primary" value="Save Company">
                <a href="<?php echo admin_url('admin.php?page=kpi-companies'); ?>" class="button">Cancel</a>
            </p>
        </form>
        <hr>
    <?php endif; ?>

    <!-- Companies List -->
    <div class="companies-list-wrap">
        <?php if (!empty($companies)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Logo</th>
                        <th>Company Name</th>
                        <th>Campaigns</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($companies as $company): ?>
                        <tr>
                            <td>
                                <?php if (!empty($company->logo_url)): ?>
                                    <img src="<?php echo esc_url($company->logo_url); ?>" alt="Logo" style="max-width:60px;max-height:60px;">
                                <?php else: ?>
                                    <span>No Logo</span>
                                <?php endif; ?>
                            </td>
                            <td class="company-name">
                                <strong><?php echo esc_html($company->name); ?></strong>
                            </td>
                            <td class="company-campaigns">
                                <?php 
                                $campaigns = $this->get_campaigns($company->id);
                                echo count($campaigns);
                                ?>
                            </td>
                            <td class="actions">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=kpi-campaigns&company_id=' . $company->id)); ?>" 
                                   class="button button-small">View Campaigns</a>
                                <a href="<?php echo admin_url('admin.php?page=kpi-companies&action=edit&company_id=' . intval($company->id)); ?>" 
                                   class="button button-small">Edit</a>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field('delete_company', 'delete_company_nonce'); ?>
                                    <input type="hidden" name="company_id" value="<?php echo esc_attr($company->id); ?>">
                                    <button type="submit" class="button button-small button-link-delete" onclick="return confirm('Are you sure you want to delete this company? This action cannot be undone.');">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-data">No companies found.</p>
        <?php endif; ?>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Auto-submit company filter
    $('#company-filter').on('change', function() {
        $(this).closest('form').submit();
    });

    // Show/Hide add company form
    $('#add-new-company').on('click', function(e) {
        e.preventDefault();
        $('.company-form-wrap').slideDown();
    });

    $('.cancel-form').on('click', function() {
        $('.company-form-wrap').slideUp();
        $('.add-company-form')[0].reset();
    });

    // Logo upload handling - Add form
    $('#upload-company-logo').on('click', function(e) {
        e.preventDefault();
        
        var frame = wp.media({
            title: 'Select or Upload Company Logo',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#company_logo_id').val(attachment.id);
            $('#company-logo-preview').html('<img src="' + attachment.url + '" style="max-width:100px;max-height:100px;" />');
            $('#remove-company-logo').show();
        });

        frame.open();
    });

    $('#remove-company-logo').on('click', function() {
        $('#company_logo_id').val('');
        $('#company-logo-preview').empty();
        $(this).hide();
    });

    // Logo upload handling - Edit form
    let editLogoFrame;
    $('#edit-upload-company-logo').on('click', function(e) {
        e.preventDefault();
        
        if (editLogoFrame) {
            editLogoFrame.open();
            return;
        }
        
        editLogoFrame = wp.media({
            title: 'Select or Upload Company Logo',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        editLogoFrame.on('select', function() {
            var attachment = editLogoFrame.state().get('selection').first().toJSON();
            $('#edit_company_logo_id').val(attachment.id);
            $('#edit-company-logo-preview').html('<img src="' + attachment.url + '" style="max-width:100px;max-height:100px;" />');
            $('#edit-remove-company-logo').show();
        });

        editLogoFrame.open();
    });

    $('#edit-remove-company-logo').on('click', function() {
        $('#edit_company_logo_id').val('');
        $('#edit-company-logo-preview').empty();
        $(this).hide();
    });
});
</script>