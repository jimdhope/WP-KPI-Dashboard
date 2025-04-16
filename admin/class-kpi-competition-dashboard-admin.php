<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    KPI_Competition_Dashboard
 */

class KPI_Competition_Dashboard_Admin {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('wp_ajax_delete_campaign', array($this, 'delete_campaign'));
        add_action('wp_ajax_delete_pod', array($this, 'delete_pod'));
        add_action('wp_ajax_get_campaign_pods', array($this, 'get_campaign_pods_ajax'));
        add_action('wp_ajax_get_pod_users', array($this, 'get_pod_users_ajax'));
        add_action('wp_ajax_update_team_members', array($this, 'update_team_members_ajax'));
        add_action('wp_ajax_get_company_campaigns', array($this, 'get_company_campaigns_ajax'));
        add_action('wp_ajax_get_pod_agents', array($this, 'get_pod_agents_ajax'));
        add_action('wp_ajax_save_pod_agents', array($this, 'save_pod_agents_ajax'));
        add_action('wp_ajax_create_pod_user', array($this, 'create_pod_user_ajax'));
        add_action('wp_ajax_get_user_details', array($this, 'get_user_details_ajax'));
        add_action('wp_ajax_update_user_details', array($this, 'update_user_details_ajax'));
        add_action('wp_ajax_save_competition', array($this, 'save_competition_ajax'));
        add_action('wp_ajax_delete_competition', array($this, 'delete_competition_ajax'));
        add_action('wp_ajax_get_competition', array($this, 'get_competition_ajax'));
        add_action('wp_ajax_get_competition_details', [$this, 'get_competition_details']);
        add_action('wp_ajax_delete_competition_ajax', [$this, 'delete_competition_ajax']);
        $this->init_ajax_handlers();
    }

    /**
     * Run database migrations for the plugin.
     * Call this from your plugin activation hook.
     */
    public static function install_migrations() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Create pod_roles table first
        $pod_roles_table = $wpdb->prefix . 'kpi_pod_roles';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$pod_roles_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            role varchar(50) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            UNIQUE KEY unique_user_role (user_id, role)
        ) $charset_collate;";

        dbDelta($sql);

        // Modify pod_agents table
        $pod_agents_table = $wpdb->prefix . 'kpi_pod_agents';
        $sql = "CREATE TABLE IF NOT EXISTS {$pod_agents_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            pod_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            is_primary tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY pod_id (pod_id),
            KEY user_id (user_id),
            KEY is_primary (is_primary)
        ) $charset_collate;";

        dbDelta($sql);

        // Add is_primary column to pod_agents table if it doesn't exist
        $column = $wpdb->get_results("SHOW COLUMNS FROM `$pod_agents_table` LIKE 'is_primary'");
        if (empty($column)) {
            $wpdb->query("ALTER TABLE `$pod_agents_table` ADD `is_primary` TINYINT(1) NOT NULL DEFAULT 1 AFTER `role`");
        }

        // Companies table migrations
        $companies_table = $wpdb->prefix . 'kpi_companies';
        if($wpdb->get_var("SHOW TABLES LIKE '$companies_table'") == $companies_table) {
            // Add logo_url column if it doesn't exist
            $column = $wpdb->get_results("SHOW COLUMNS FROM `$companies_table` LIKE 'logo_url'");
            if (empty($column)) {
                $wpdb->query("ALTER TABLE `$companies_table` ADD `logo_url` VARCHAR(255) NULL DEFAULT NULL AFTER `name`");
            }

            // Add logo_id column if it doesn't exist
            $column = $wpdb->get_results("SHOW COLUMNS FROM `$companies_table` LIKE 'logo_id'");
            if (empty($column)) {
                $wpdb->query("ALTER TABLE `$companies_table` ADD `logo_id` BIGINT(20) NULL DEFAULT NULL AFTER `name`");
            }

            // Add description column if it doesn't exist
            $column = $wpdb->get_results("SHOW COLUMNS FROM `$companies_table` LIKE 'description'");
            if (empty($column)) {
                $wpdb->query("ALTER TABLE `$companies_table` ADD `description` TEXT NULL DEFAULT NULL AFTER `name`");
            }
        }

        // Campaigns table migrations
        $campaigns_table = $wpdb->prefix . 'kpi_campaigns';
        if($wpdb->get_var("SHOW TABLES LIKE '$campaigns_table'") == $campaigns_table) {
            // Add logo_id column if it doesn't exist
            $column = $wpdb->get_results("SHOW COLUMNS FROM `$campaigns_table` LIKE 'logo_id'");
            if (empty($column)) {
                $wpdb->query("ALTER TABLE `$campaigns_table` ADD `logo_id` BIGINT(20) NULL DEFAULT NULL AFTER `name`");
            }

            // Add logo_url column if it doesn't exist
            $column = $wpdb->get_results("SHOW COLUMNS FROM `$campaigns_table` LIKE 'logo_url'");
            if (empty($column)) {
                $wpdb->query("ALTER TABLE `$campaigns_table` ADD `logo_url` VARCHAR(255) NULL DEFAULT NULL AFTER `logo_id`");
            }
        }
    }

    private function get_pod_agents($pod_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kpi_pod_agents';
        $roles_table = $wpdb->prefix . 'kpi_pod_roles';

        // Check if tables exist
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $this->install_migrations();
        }

        $agents = $wpdb->get_results($wpdb->prepare(
            "SELECT pa.*, u.ID as user_id, u.display_name, u.user_email, u.user_login,
                    m1.meta_value as first_name, m2.meta_value as last_name,
                    GROUP_CONCAT(DISTINCT pr.role) as roles,
                    pa.is_primary
            FROM {$wpdb->prefix}kpi_pod_agents pa
            JOIN {$wpdb->users} u ON pa.user_id = u.ID
            LEFT JOIN {$wpdb->usermeta} m1 ON u.ID = m1.user_id AND m1.meta_key = 'first_name'
            LEFT JOIN {$wpdb->usermeta} m2 ON u.ID = m2.user_id AND m2.meta_key = 'last_name'
            LEFT JOIN {$roles_table} pr ON u.ID = pr.user_id
            WHERE pa.pod_id = %d
            GROUP BY pa.id
            ORDER BY u.display_name ASC",
            $pod_id
        ));

        if ($agents === null) {
            return array();
        }

        // Add avatar URLs and process roles
        foreach ($agents as $agent) {
            $agent->avatar_url = get_avatar_url($agent->user_id, array('size' => 50));
            $agent->roles = $agent->roles ? explode(',', $agent->roles) : ['agent'];
        }

        return $agents;
    }

    public function save_pod_agents_ajax() {
        check_ajax_referer('kpi_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $pod_id = intval($_POST['pod_id']);
        $agents = isset($_POST['agents']) ? $_POST['agents'] : array();
        
        global $wpdb;
        $table = $wpdb->prefix . 'kpi_pod_agents';
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Remove existing assignments for this pod
            $wpdb->delete($table, array('pod_id' => $pod_id), array('%d'));
            
            // Add new assignments
            foreach ($agents as $agent) {
                $result = $wpdb->insert(
                    $table,
                    array(
                        'pod_id' => $pod_id,
                        'user_id' => intval($agent['user_id']),
                        'role' => sanitize_text_field($agent['role'])
                    ),
                    array('%d', '%d', '%s')
                );
                
                if ($result === false) {
                    throw new Exception($wpdb->last_error);
                }
            }
            
            $wpdb->query('COMMIT');
            wp_send_json_success();
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error('Failed to save pod agents: ' . $e->getMessage());
        }
    }

    private function update_user_roles($user_id, $roles) {
        global $wpdb;
        $roles_table = $wpdb->prefix . 'kpi_pod_roles';

        // Delete existing roles
        $wpdb->delete(
            $roles_table,
            array('user_id' => $user_id),
            array('%d')
        );

        // Add new roles
        foreach ($roles as $role) {
            $wpdb->insert(
                $roles_table,
                array(
                    'user_id' => $user_id,
                    'role' => $role
                ),
                array('%d', '%s')
            );
        }
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/kpi-competition-dashboard-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts($hook) {
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/kpi-competition-dashboard-admin.js',
            array('jquery'),
            $this->version,
            false
        );
        
        wp_localize_script($this->plugin_name, 'kpiDashboard', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kpi_dashboard_nonce')
        ));

        wp_enqueue_script('sortablejs', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', array(), '1.15.0', true);
        wp_enqueue_script(
            'kpi-competitions',
            plugin_dir_url(__FILE__) . 'js/competitions.js',
            array('jquery', 'sortablejs'),
            $this->version,
            true
        );

        // Add jQuery UI
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-jquery-ui-dialog');
        
        // Add pod agents script
        wp_enqueue_script(
            'kpi-pod-agents',
            plugin_dir_url(__FILE__) . 'js/pod-agents.js',
            array('jquery', 'jquery-ui-dialog'),
            $this->version,
            true
        );

        // Add Dragula
        wp_enqueue_script(
            'dragula-js',
            'https://cdnjs.cloudflare.com/ajax/libs/dragula/3.7.3/dragula.min.js',
            array('jquery'),
            '3.7.3',
            true
        );
        
        wp_enqueue_style(
            'dragula-css',
            'https://cdnjs.cloudflare.com/ajax/libs/dragula/3.7.3/dragula.min.css',
            array(),
            '3.7.3'
        );
    }

    /**
     * Enqueue Dragula library.
     */
    public function enqueue_dragula() {
        wp_enqueue_script(
            'dragula',
            'https://cdnjs.cloudflare.com/ajax/libs/dragula/3.7.3/dragula.min.js',
            array('jquery'),
            '3.7.3',
            true
        );
        
        wp_enqueue_style(
            'dragula',
            'https://cdnjs.cloudflare.com/ajax/libs/dragula/3.7.3/dragula.min.css',
            array(),
            '3.7.3'
        );
    }

    /**
     * Add menu items for KPI Dashboard
     */
    public function add_plugin_admin_menu() {
        // Main menu
        add_menu_page(
            'KPI Competition Dashboard',
            'KPI Dashboard',
            'manage_options',
            'kpi-competition-dashboard',
            array($this, 'display_dashboard_page'),
            'dashicons-chart-bar',
            6
        );

        // Companies
        add_submenu_page(
            'kpi-competition-dashboard',
            'Companies',
            'Companies',
            'manage_options',
            'kpi-companies',
            array($this, 'display_companies_page')
        );

        // Campaigns
        add_submenu_page(
            'kpi-competition-dashboard',
            'Campaigns',
            'Campaigns',
            'manage_options',
            'kpi-campaigns',
            array($this, 'display_campaigns_page')
        );

        // Pods
        add_submenu_page(
            'kpi-competition-dashboard',
            'Pods',
            'Pods',
            'manage_options',
            'kpi-pods',
            array($this, 'display_pods_page')
        );

        // Weekly Competitions
        add_submenu_page(
            'kpi-competition-dashboard',
            'Competitions',
            'Competitions',
            'manage_options',
            'kpi-competitions',
            array($this, 'display_competitions_page')
        );

        // KPI Metrics
        add_submenu_page(
            'kpi-competition-dashboard',
            'KPI Metrics',
            'KPI Metrics',
            'manage_options',
            'kpi-metrics',
            array($this, 'display_metrics_page')
        );

        // Add Teams Management page
        add_submenu_page(
            'kpi-competition-dashboard',
            'Teams Management',
            'Teams Management',
            'manage_options',
            'kpi-teams-management',
            array($this, 'display_teams_management_page')
        );
    }

    public function display_dashboard_page() {
        include_once 'partials/dashboard/dashboard-display.php';
    }

    public function display_companies_page() {
        include_once 'partials/companies/companies-display.php';
    }

    public function display_campaigns_page() {
        include_once 'partials/campaigns/campaigns-display.php';
    }

    public function display_pods_page() {
        include_once 'partials/pods/pods-display.php';
    }

    public function display_competitions_page() {
        include_once 'partials/competitions/competitions-display.php';
    }

    public function display_metrics_page() {
        include_once 'partials/metrics/metrics-display.php';
    }

    public function display_teams_management_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/teams/teams-management.php';
    }

    // CRUD Operations for entities
    
    // Companies
    public function save_company($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'kpi_companies';

        $fields = array(
            'name' => $data['name']
        );
        $formats = array('%s');

        if (!empty($data['logo_id'])) {
            $fields['logo_id'] = $data['logo_id'];
            $formats[] = '%d';
            
            // Get the attachment URL and save it as logo_url
            $attachment_url = wp_get_attachment_url($data['logo_id']);
            if ($attachment_url) {
                $fields['logo_url'] = $attachment_url;
                $formats[] = '%s';
            }
        }
        
        return $wpdb->insert(
            $table,
            $fields,
            $formats
        );
    }

    public function update_company($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'kpi_companies';
        
        $fields = array(
            'name' => $data['name']
        );
        $formats = array('%s');

        if (isset($data['logo_id'])) {
            $fields['logo_id'] = $data['logo_id'];
            $formats[] = '%d';
            
            // Get the attachment URL and save it as logo_url
            $attachment_url = wp_get_attachment_url($data['logo_id']);
            if ($attachment_url) {
                $fields['logo_url'] = $attachment_url;
                $formats[] = '%s';
            }
        }
        
        return $wpdb->update(
            $table,
            $fields,
            array('id' => $id),
            $formats,
            array('%d')
        );
    }

    public function delete_company($company_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'kpi_companies';
        return $wpdb->delete(
            $table,
            array('id' => $company_id),
            array('%d')
        );
    }

    public function get_companies() {
        global $wpdb;
        $table = $wpdb->prefix . 'kpi_companies';
        // Include logo_id in the select
        return $wpdb->get_results("SELECT id, name, logo_url, logo_id FROM $table ORDER BY name ASC");
    }

    // Campaigns
    public function save_campaign($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'kpi_campaigns';
        
        $fields = array(
            'company_id' => $data['company_id'],
            'name' => $data['name']
        );
        $formats = array('%d', '%s');

        if (!empty($data['logo_id'])) {
            $fields['logo_id'] = $data['logo_id'];
            $formats[] = '%d';
            
            // Get the attachment URL and save it as logo_url
            $attachment_url = wp_get_attachment_url($data['logo_id']);
            if ($attachment_url) {
                $fields['logo_url'] = $attachment_url;
                $formats[] = '%s';
            }
        }
        
        return $wpdb->insert(
            $table,
            $fields,
            $formats
        );
    }

    public function get_campaign($campaign_id) {
        global $wpdb;
        
        $campaign = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}kpi_campaigns WHERE id = %d",
            $campaign_id
        ));
        
        return $campaign;
    }

    public function get_campaigns() {
        global $wpdb;
        
        $campaigns = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}kpi_campaigns ORDER BY name ASC"
        );
        
        return $campaigns;
    }

    public function delete_campaign() {
        check_ajax_referer('kpi_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $campaign_id = intval($_POST['campaign_id']);
        
        global $wpdb;
        $result = $wpdb->delete(
            $wpdb->prefix . 'kpi_campaigns',
            array('id' => $campaign_id),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error('Failed to delete campaign');
        }

        wp_send_json_success();
    }

    public function delete_pod() {
        check_ajax_referer('kpi_dashboard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $pod_id = intval($_POST['pod_id']);
        
        global $wpdb;
        $result = $wpdb->delete(
            $wpdb->prefix . 'kpi_pods',
            array('id' => $pod_id),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error('Failed to delete pod');
        }

        wp_send_json_success();
    }

    public function get_campaign_pods_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'get_campaign_pods')) {
            wp_send_json_error(['message' => 'Invalid security token']);
        }

        $campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;
        
        global $wpdb;
        $pods = $wpdb->get_results($wpdb->prepare(
            "SELECT id, name FROM {$wpdb->prefix}kpi_pods WHERE campaign_id = %d ORDER BY name",
            $campaign_id
        ));

        wp_send_json_success($pods);
    }

    public function get_pod_users_ajax() {
        check_ajax_referer('kpi_dashboard_nonce', 'nonce');
        
        if (!isset($_POST['pod_ids']) || !is_array($_POST['pod_ids'])) {
            wp_send_json_error('No pods selected');
        }

        $pod_ids = array_map('intval', $_POST['pod_ids']);
        
        global $wpdb;
        $users = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT u.ID, u.display_name 
            FROM {$wpdb->users} u 
            JOIN {$wpdb->prefix}kpi_pod_agents pa ON u.ID = pa.user_id 
            WHERE pa.pod_id IN (" . implode(',', array_fill(0, count($pod_ids), '%d')) . ")
            AND pa.role = 'agent'
            ORDER BY u.display_name",
            ...$pod_ids
        ));

        wp_send_json_success($users);
    }

    public function get_pod_agents_ajax() {
        check_ajax_referer('get_pod_agents', 'nonce');
        
        $pod_ids = isset($_POST['pod_ids']) ? array_map('intval', $_POST['pod_ids']) : array();
        
        if (empty($pod_ids)) {
            wp_send_json_error(['message' => 'No pods selected']);
            return;
        }

        global $wpdb;
        $agents = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT u.ID as id, u.display_name as name, um.meta_value as avatar 
            FROM {$wpdb->users} u 
            JOIN {$wpdb->prefix}kpi_pod_agents pa ON u.ID = pa.user_id 
            LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = %s
            WHERE pa.pod_id IN (" . implode(',', array_fill(0, count($pod_ids), '%d')) . ")",
            array_merge(['wp_user_avatar'], $pod_ids)
        ));

        foreach ($agents as $agent) {
            if (empty($agent->avatar)) {
                $agent->avatar = get_avatar_url($agent->id);
            }
        }

        wp_send_json_success($agents);
    }

    public function update_team_members_ajax() {
        check_ajax_referer('kpi_dashboard_nonce', 'nonce');
        $team_id = intval($_POST['team_id']);
        $user_ids = array_map('intval', $_POST['user_ids']);
        $result = $this->update_team_assignments($team_id, $user_ids);
        wp_send_json_success($result);
    }

    public function get_company_campaigns_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'get_company_campaigns')) {
            wp_send_json_error(['message' => 'Invalid security token']);
        }

        $company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;
        
        global $wpdb;
        $campaigns = $wpdb->get_results($wpdb->prepare(
            "SELECT id, name FROM {$wpdb->prefix}kpi_campaigns WHERE company_id = %d ORDER BY name",
            $company_id
        ));

        wp_send_json_success($campaigns);
    }

    public function create_pod_user_ajax() {
        check_ajax_referer('kpi_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $userData = $_POST['formData'];
        
        $userArgs = array(
            'user_login' => sanitize_user($userData['username']),
            'user_email' => sanitize_email($userData['email']),
            'user_pass' => $userData['password'],
            'first_name' => sanitize_text_field($userData['first_name']),
            'last_name' => sanitize_text_field($userData['last_name']),
            'role' => $userData['role']
        );

        $user_id = wp_insert_user($userArgs);

        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
        }

        wp_send_json_success($user_id);
    }

    public function get_user_details_ajax() {
        check_ajax_referer('kpi_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $user_id = intval($_POST['user_id']);
        $user = get_userdata($user_id);

        if (!$user) {
            wp_send_json_error('User not found');
        }

        wp_send_json_success(array(
            'ID' => $user->ID,
            'user_login' => $user->user_login,
            'user_email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'roles' => $user->roles
        ));
    }

    public function get_user_teams($user_id = null) {
        global $wpdb;
        
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        $sql = $wpdb->prepare(
            "SELECT t.*, p.name as pod_name, c.name as campaign_name
            FROM {$wpdb->prefix}kpi_team_members tm
            JOIN {$wpdb->prefix}kpi_teams t ON tm.team_id = t.id
            JOIN {$wpdb->prefix}kpi_pods p ON t.pod_id = p.id
            JOIN {$wpdb->prefix}kpi_campaigns c ON p.campaign_id = c.id
            WHERE tm.user_id = %d
            ORDER BY t.created_at DESC",
            $user_id
        );

        return $wpdb->get_results($sql);
    }

    private function get_pod_users($pod_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID, u.display_name 
            FROM {$wpdb->users} u 
            JOIN {$wpdb->prefix}kpi_team_members tm ON u.ID = tm.user_id 
            JOIN {$wpdb->prefix}kpi_teams t ON tm.team_id = t.id 
            WHERE t.pod_id = %d",
            $pod_id
        ));
    }

    // Competition methods
    protected function save_competition_rules($competition_id, $rules) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kpi_competition_rules';
        
        // First delete existing rules
        $wpdb->delete($table_name, ['competition_id' => $competition_id], ['%d']);
        
        // Insert new rules
        $rules_array = json_decode(stripslashes($rules), true);
        if (!empty($rules_array)) {
            foreach ($rules_array as $rule) {
                $wpdb->insert(
                    $table_name,
                    [
                        'competition_id' => $competition_id,
                        'name' => sanitize_text_field($rule['name']),
                        'emoji' => sanitize_text_field($rule['emoji']),
                        'points' => intval($rule['points'])
                    ],
                    ['%d', '%s', '%s', '%d']
                );
            }
        }
        return true;
    }

    public function save_competition_ajax() {
        check_ajax_referer('kpi_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $competition_data = array(
            'campaign_id' => isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0,
            'theme' => isset($_POST['theme']) ? sanitize_text_field($_POST['theme']) : '',
            'start_date' => isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '',
            'end_date' => isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '',
            'pods' => isset($_POST['pods']) ? array_map('intval', $_POST['pods']) : array(),
            'rules' => isset($_POST['rules']) ? json_decode(stripslashes($_POST['rules']), true) : array(),
            'team_count' => isset($_POST['team_count']) ? intval($_POST['team_count']) : 3,
            'team_names' => isset($_POST['team_names']) ? array_map('sanitize_text_field', $_POST['team_names']) : array()
        );

        global $wpdb;

        try {
            $wpdb->query('START TRANSACTION');
            
            // Save competition data
            if (!empty($competition_data['id'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'kpi_competitions',
                    [
                        'campaign_id' => $competition_data['campaign_id'],
                        'theme' => $competition_data['theme'],
                        'start_date' => $competition_data['start_date'],
                        'end_date' => $competition_data['end_date']
                    ],
                    ['id' => $competition_data['id']],
                    ['%d', '%s', '%s', '%s'],
                    ['%d']
                );
                $competition_id = $competition_data['id'];
            } else {
                $result = $wpdb->insert(
                    $wpdb->prefix . 'kpi_competitions',
                    [
                        'campaign_id' => $competition_data['campaign_id'],
                        'theme' => $competition_data['theme'],
                        'start_date' => $competition_data['start_date'],
                        'end_date' => $competition_data['end_date']
                    ],
                    ['%d', '%s', '%s', '%s']
                );
                $competition_id = $wpdb->insert_id;
            }
            
            if ($result === false) {
                throw new Exception('Failed to save competition');
            }
            
            // Save rules
            if (isset($_POST['rules'])) {
                $this->save_competition_rules($competition_id, $_POST['rules']);
            }
            
            $wpdb->query('COMMIT');
            wp_send_json_success(['message' => 'Competition saved successfully']);
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function get_pod_users_for_competition($competition_id) {
        global $wpdb;
        
        // Get all pods assigned to this competition
        $pod_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT pod_id 
            FROM {$wpdb->prefix}kpi_competition_pods 
            WHERE competition_id = %d",
            $competition_id
        ));

        if (empty($pod_ids)) {
            return array();
        }

        // Get all users with agent role from these pods
        return $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT u.*, pa.pod_id
            FROM {$wpdb->users} u
            JOIN {$wpdb->prefix}kpi_pod_agents pa ON u.ID = pa.user_id
            WHERE pa.pod_id IN (" . implode(',', array_fill(0, count($pod_ids), '%d')) . ")
            AND pa.role = 'agent'
            ORDER BY u.display_name",
            ...$pod_ids
        ));
    }

    private function create_competition_teams($competition_id, $team_count, $team_names = array()) {
        global $wpdb;
        $teams = array();

        // Create teams
        for ($i = 0; $i < $team_count; $i++) {
            $team_name = !empty($team_names[$i]) ? $team_names[$i] : "Team " . ($i + 1);
            $team_id = $this->create_competition_team($competition_id, $team_name);
            if ($team_id) {
                $teams[] = $team_id;
            }
        }

        // Get users to distribute
        $users = $this->get_pod_users_for_competition($competition_id);
        if (empty($users) || empty($teams)) {
            return false;
        }

        // Shuffle users randomly
        shuffle($users);
        
        // Distribute users across teams
        foreach ($users as $index => $user) {
            $team_index = $index % count($teams);
            $this->assign_user_to_team($teams[$team_index], $user->ID);
        }

        return true;
    }

    private function create_competition_team($competition_id, $name) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'kpi_teams',
            array(
                'competition_id' => $competition_id,
                'name' => $name,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    private function assign_user_to_team($team_id, $user_id) {
        global $wpdb;
        
        return $wpdb->insert(
            $wpdb->prefix . 'kpi_team_members',
            array(
                'team_id' => $team_id,
                'user_id' => $user_id,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s')
        );
    }

    public function update_team_assignments($team_id, $user_ids) {
        global $wpdb;
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Remove existing assignments
            $wpdb->delete(
                $wpdb->prefix . 'kpi_team_members',
                array('team_id' => $team_id),
                array('%d')
            );

            // Add new assignments
            foreach ($user_ids as $user_id) {
                $this->assign_user_to_team($team_id, $user_id);
            }

            $wpdb->query('COMMIT');
            return true;

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return false;
        }
    }

    public function get_competitions($campaign_id = null) {
        global $wpdb;
        
        $where = '';
        $params = [];
        
        if ($campaign_id) {
            $where = 'WHERE campaign_id = %d';
            $params[] = $campaign_id;
        }
        
        $query = "SELECT * FROM {$wpdb->prefix}kpi_competitions $where ORDER BY start_date DESC";
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, ...$params);
        }
        
        return $wpdb->get_results($query);
    }

    // KPI Metrics
    public function save_metric($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'kpi_metrics';
        
        return $wpdb->insert(
            $table,
            array(
                'user_id' => $data['user_id'],
                'competition_id' => $data['competition_id'],
                'metric_name' => $data['metric_name'],
                'metric_value' => $data['metric_value'],
                'points_earned' => $data['points_earned'],
                'date_recorded' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%f', '%f', '%s')
        );
    }

    public function get_team_metrics($team_id, $competition_id) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT m.*, u.display_name, t.name as team_name
            FROM {$wpdb->prefix}kpi_metrics m
            JOIN {$wpdb->prefix}kpi_team_members tm ON m.user_id = tm.user_id
            JOIN {$wpdb->prefix}kpi_teams t ON tm.team_id = t.id
            JOIN {$wpdb->users} u ON m.user_id = u.ID
            WHERE tm.team_id = %d AND m.competition_id = %d
            ORDER BY m.date_recorded DESC",
            $team_id,
            $competition_id
        );
        
        return $wpdb->get_results($sql);
    }

    public function get_competition_leaderboard($competition_id) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT t.id as team_id, t.name as team_name, 
                    SUM(m.points_earned) as total_points,
                    COUNT(DISTINCT m.user_id) as participants
            FROM {$wpdb->prefix}kpi_metrics m
            JOIN {$wpdb->prefix}kpi_team_members tm ON m.user_id = tm.user_id
            JOIN {$wpdb->prefix}kpi_teams t ON tm.team_id = t.id
            WHERE m.competition_id = %d
            GROUP BY t.id
            ORDER BY total_points DESC",
            $competition_id
        );
        
        return $wpdb->get_results($sql);
    }

    /**
     * Get user's metrics/activity records
     * 
     * @param int $user_id The user ID to get metrics for
     * @param int|null $competition_id Optional competition ID to filter by
     * @param int|null $limit Optional limit for number of records to return
     * @return array Array of metric records
     */
    public function get_user_metrics($user_id, $competition_id = null, $limit = null) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT m.*, c.theme as competition_theme, mt.name as metric_name 
            FROM {$wpdb->prefix}kpi_metrics m 
            LEFT JOIN {$wpdb->prefix}kpi_competitions c ON m.competition_id = c.id 
            LEFT JOIN {$wpdb->prefix}kpi_metric_types mt ON m.metric_type_id = mt.id 
            WHERE m.user_id = %d",
            $user_id
        );
        
        if ($competition_id) {
            $query .= $wpdb->prepare(" AND m.competition_id = %d", $competition_id);
        }
        
        $query .= " ORDER BY m.date_recorded DESC";
        
        if ($limit) {
            $query .= $wpdb->prepare(" LIMIT %d", $limit);
        }
        
        return $wpdb->get_results($query);
    }

    /**
     * Get user's total points across all competitions
     *
     * @param int $user_id The user ID to get total points for
     * @return float Total points earned by the user
     */
    public function get_user_total_points($user_id) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT SUM(points_earned) as total_points
            FROM {$wpdb->prefix}kpi_metrics
            WHERE user_id = %d",
            $user_id
        );
        
        $result = $wpdb->get_var($sql);
        return floatval($result) ?: 0;
    }

    /**
     * Get pods from the database
     * 
     * @param int|null $campaign_id Optional campaign ID to filter pods
     * @return array Array of pod objects
     */
    public function get_pods($campaign_id = null) {
        global $wpdb;
        
        $sql = "SELECT p.*, c.name as campaign_name, 
                COALESCE((SELECT meta_value 
                         FROM {$wpdb->postmeta} 
                         WHERE post_id = p.logo_id 
                         AND meta_key = '_wp_attached_file' 
                         LIMIT 1), '') as logo_url
                FROM {$wpdb->prefix}kpi_pods p
                LEFT JOIN {$wpdb->prefix}kpi_campaigns c ON p.campaign_id = c.id";
        
        if ($campaign_id) {
            $sql .= $wpdb->prepare(" WHERE p.campaign_id = %d", $campaign_id);
        }
        
        $sql .= " ORDER BY p.campaign_id, p.name";
        
        $pods = $wpdb->get_results($sql);
        
        // Convert relative logo paths to URLs
        foreach ($pods as $pod) {
            if (!empty($pod->logo_url)) {
                $pod->logo_url = wp_get_attachment_url($pod->logo_id);
            }
        }
        
        return $pods;
    }

    public function save_pod($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'kpi_pods';
        
        $fields = array(
            'campaign_id' => $data['campaign_id'],
            'name' => $data['name']
        );
        
        if (!empty($data['logo_id'])) {
            $fields['logo_id'] = $data['logo_id'];
        }

        if (isset($data['id'])) {
            // Update existing pod
            return $wpdb->update(
                $table,
                $fields,
                array('id' => $data['id']),
                array('%d', '%s', '%d'),
                array('%d')
            );
        } else {
            // Insert new pod
            return $wpdb->insert(
                $table,
                $fields,
                array('%d', '%s', '%d')
            );
        }
    }

    /**
     * Delete a competition via AJAX
     */
    public function delete_competition_ajax() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delete_competition_' . $_POST['id'])) {
            wp_send_json_error(['message' => 'Invalid security token']);
            return;
        }

        $competition_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$competition_id) {
            wp_send_json_error(['message' => 'Invalid competition ID']);
            return;
        }

        try {
            $result = $this->delete_competition($competition_id);
            if ($result) {
                wp_send_json_success(['message' => 'Competition deleted successfully']);
            } else {
                wp_send_json_error(['message' => 'Failed to delete competition']);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Error deleting competition: ' . $e->getMessage()]);
        }
    }

    /**
     * Get competition details via AJAX
     */
    public function get_competition_details() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'edit_competition_' . $_POST['id'])) {
            wp_send_json_error(['message' => 'Invalid security token']);
            return;
        }

        $competition_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$competition_id) {
            wp_send_json_error(['message' => 'Invalid competition ID']);
            return;
        }

        try {
            $competition = $this->get_competition($competition_id);
            if (!$competition) {
                wp_send_json_error(['message' => 'Competition not found']);
                return;
            }

            $rules = $this->get_competition_rules($competition_id);
            
            wp_send_json_success([
                'competition' => $competition,
                'rules' => $rules
            ]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Error retrieving competition: ' . $e->getMessage()]);
        }
    }

    /**
     * Get competition details via AJAX
     */
    public function get_competition_ajax() {
        $competition_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'edit_competition_' . $competition_id)) {
            wp_send_json_error(['message' => 'Invalid security token']);
        }

        global $wpdb;
        
        // Get competition data
        $competition = $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, 
                    camp.company_id,
                    GROUP_CONCAT(r.id, ':', r.name, ':', r.emoji, ':', r.points) as rules
             FROM {$wpdb->prefix}kpi_competitions c
             LEFT JOIN {$wpdb->prefix}kpi_campaigns camp ON c.campaign_id = camp.id
             LEFT JOIN {$wpdb->prefix}kpi_rules r ON c.id = r.competition_id
             WHERE c.id = %d
             GROUP BY c.id",
            $competition_id
        ));

        if (!$competition) {
            wp_send_json_error(['message' => 'Competition not found']);
            return;
        }

        // Format dates for form
        $competition->start_date = date('Y-m-d', strtotime($competition->start_date));
        $competition->end_date = date('Y-m-d', strtotime($competition->end_date));

        // Parse rules into array
        $rules = [];
        if ($competition->rules) {
            foreach (explode(',', $competition->rules) as $rule) {
                list($id, $name, $emoji, $points) = explode(':', $rule);
                $rules[] = [
                    'id' => $id,
                    'name' => $name,
                    'emoji' => $emoji,
                    'points' => $points
                ];
            }
        }
        $competition->rules = $rules;

        wp_send_json_success($competition);
    }

    /**
     * Get a single competition by ID
     */
    public function get_competition($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kpi_competitions';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $id
        ));
    }

    /**
     * Get competition rules by competition ID
     */
    public function get_competition_rules($competition_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kpi_competition_rules';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE competition_id = %d ORDER BY points DESC",
            $competition_id
        ));
    }

    /**
     * Delete a competition and its associated data
     */
    public function delete_competition($id) {
        global $wpdb;
        $competitions_table = $wpdb->prefix . 'kpi_competitions';
        $rules_table = $wpdb->prefix . 'kpi_competition_rules';

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Delete rules first (foreign key constraint)
            $wpdb->delete($rules_table, ['competition_id' => $id], ['%d']);
            
            // Delete the competition
            $result = $wpdb->delete($competitions_table, ['id' => $id], ['%d']);
            
            if ($result === false) {
                throw new Exception('Failed to delete competition');
            }

            $wpdb->query('COMMIT');
            return true;
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return false;
        }
    }

    /**
     * Initialize AJAX handlers
     */
    public function init_ajax_handlers() {
        add_action('wp_ajax_get_competition_details', [$this, 'get_competition_details']);
        add_action('wp_ajax_delete_competition_ajax', [$this, 'delete_competition_ajax']);
    }
}