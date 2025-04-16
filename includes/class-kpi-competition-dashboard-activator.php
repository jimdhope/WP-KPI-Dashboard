<?php
/**
 * Fired during plugin activation
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    KPI_Competition_Dashboard
 * @subpackage KPI_Competition_Dashboard/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    KPI_Competition_Dashboard
 * @subpackage KPI_Competition_Dashboard/includes
 * @author     Your Name <email@example.com>
 */
class KPI_Competition_Dashboard_Activator {

    /**
     * Creates all necessary database tables for the plugin
     *
     * @since    1.0.0
     */
    public static function activate() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();

        // Drop tables if they exist (to ensure clean installation)
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}kpi_metric_submissions");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}kpi_team_members");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}kpi_teams");

        // Create tables in correct order with matching column types
        $sql = [];

        // Teams table first (since it's referenced by others)
        $sql[] = "CREATE TABLE {$wpdb->prefix}kpi_teams (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            pod_id bigint(20) UNSIGNED NULL,
            competition_id bigint(20) UNSIGNED NOT NULL,
            name varchar(255) NOT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Team Members table
        $sql[] = "CREATE TABLE {$wpdb->prefix}kpi_team_members (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            team_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            role varchar(50) NOT NULL DEFAULT 'member',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY team_user (team_id, user_id),
            KEY team_id (team_id),
            KEY user_id (user_id),
            FOREIGN KEY (team_id) REFERENCES {$wpdb->prefix}kpi_teams(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
        ) $charset_collate;";

        // Metric Submissions table
        $sql[] = "CREATE TABLE {$wpdb->prefix}kpi_metric_submissions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            team_id bigint(20) UNSIGNED NOT NULL,
            metric_id bigint(20) UNSIGNED NOT NULL,
            value decimal(10,2) NOT NULL,
            submission_date date NOT NULL,
            submitted_by bigint(20) UNSIGNED NOT NULL,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY team_metric_date (team_id, metric_id, submission_date),
            KEY team_id (team_id),
            KEY metric_id (metric_id),
            KEY submitted_by (submitted_by),
            FOREIGN KEY (team_id) REFERENCES {$wpdb->prefix}kpi_teams(id) ON DELETE CASCADE,
            FOREIGN KEY (submitted_by) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
        ) $charset_collate;";

        // Execute the SQL statements
        foreach ($sql as $query) {
            dbDelta($query);
        }

        // Add foreign key constraints after tables are created
        $wpdb->query("ALTER TABLE {$wpdb->prefix}kpi_teams 
            ADD FOREIGN KEY (competition_id) REFERENCES {$wpdb->prefix}kpi_competitions(id) ON DELETE CASCADE,
            ADD FOREIGN KEY (pod_id) REFERENCES {$wpdb->prefix}kpi_pods(id) ON DELETE SET NULL");

        // Companies table
        $table_companies = $wpdb->prefix . 'kpi_companies';
        $sql_companies = "CREATE TABLE IF NOT EXISTS $table_companies (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            logo_id bigint(20) DEFAULT NULL,
            logo_url varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY name (name),
            KEY logo_id (logo_id)
        ) $charset_collate;";
        dbDelta($sql_companies);

        // Campaigns table
        $table_campaigns = $wpdb->prefix . 'kpi_campaigns';
        self::create_campaigns_table();

        // Pods table
        $table_pods = $wpdb->prefix . 'kpi_pods';
        $sql_pods = "CREATE TABLE IF NOT EXISTS $table_pods (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            logo_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY campaign_id (campaign_id),
            KEY logo_id (logo_id),
            FOREIGN KEY (campaign_id) REFERENCES $table_campaigns(id) ON DELETE CASCADE
        ) $charset_collate;";
        dbDelta($sql_pods);

        // Competitions table
        $table_competitions = $wpdb->prefix . 'kpi_competitions';
        $sql_competitions = "CREATE TABLE IF NOT EXISTS $table_competitions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) NOT NULL,
            theme varchar(255) NOT NULL,
            description text,
            start_date date NOT NULL,
            end_date date NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY campaign_id (campaign_id),
            FOREIGN KEY (campaign_id) REFERENCES $table_campaigns(id) ON DELETE CASCADE
        ) $charset_collate;";
        dbDelta($sql_competitions);

        // Competition Pods table (new)
        $table_competition_pods = $wpdb->prefix . 'kpi_competition_pods';
        $sql_competition_pods = "CREATE TABLE IF NOT EXISTS $table_competition_pods (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            competition_id bigint(20) NOT NULL,
            pod_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY competition_pod (competition_id, pod_id),
            FOREIGN KEY (competition_id) REFERENCES $table_competitions(id) ON DELETE CASCADE,
            FOREIGN KEY (pod_id) REFERENCES $table_pods(id) ON DELETE CASCADE
        ) $charset_collate;";
        dbDelta($sql_competition_pods);

        // KPI Rules table (new)
        $table_kpi_rules = $wpdb->prefix . 'kpi_rules';
        $sql_kpi_rules = "CREATE TABLE IF NOT EXISTS $table_kpi_rules (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            competition_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            emoji varchar(10),
            points decimal(10,2) NOT NULL DEFAULT '0.00',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            FOREIGN KEY (competition_id) REFERENCES $table_competitions(id) ON DELETE CASCADE
        ) $charset_collate;";
        dbDelta($sql_kpi_rules);

        // Competition Metrics table
        $table_competition_metrics = $wpdb->prefix . 'kpi_competition_metrics';
        $sql_competition_metrics = "CREATE TABLE IF NOT EXISTS $table_competition_metrics (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            competition_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            description text,
            points_per_unit decimal(10,2) NOT NULL DEFAULT '1.00',
            unit varchar(50),
            target_value decimal(10,2),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY competition_id (competition_id),
            FOREIGN KEY (competition_id) REFERENCES $table_competitions(id) ON DELETE CASCADE
        ) $charset_collate;";
        dbDelta($sql_competition_metrics);

        // Create pod agents table
        $table_pod_agents = $wpdb->prefix . 'kpi_pod_agents';
        $sql_pod_agents = "CREATE TABLE IF NOT EXISTS $table_pod_agents (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            pod_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            role varchar(50) NOT NULL DEFAULT 'agent',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY pod_user (pod_id, user_id),
            KEY pod_id (pod_id),
            KEY user_id (user_id),
            FOREIGN KEY (pod_id) REFERENCES $table_pods(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
        ) $charset_collate;";
        dbDelta($sql_pod_agents);

        // Create competition rules table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}kpi_competition_rules (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            competition_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            emoji varchar(10),
            points int NOT NULL,
            PRIMARY KEY  (id),
            KEY competition_id (competition_id),
            FOREIGN KEY (competition_id) 
                REFERENCES {$wpdb->prefix}kpi_competitions(id) 
                ON DELETE CASCADE
        ) $charset_collate;";
        
        dbDelta($sql);

        // Add version to options
        add_option('kpi_competition_dashboard_db_version', '1.0.0');
    }

    private static function create_campaigns_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}kpi_campaigns (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            company_id mediumint(9) NOT NULL,
            name varchar(100) NOT NULL,
            logo_id bigint(20),
            logo_url varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY company_id (company_id),
            KEY logo_id (logo_id),
            FOREIGN KEY (company_id) REFERENCES {$wpdb->prefix}kpi_companies(id) ON DELETE CASCADE
        ) $charset_collate;";
        dbDelta($sql);
    }

}