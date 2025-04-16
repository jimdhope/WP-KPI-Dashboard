<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    KPI_Competition_Dashboard
 */

class KPI_Competition_Dashboard_Public {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // Register shortcode
        add_shortcode('kpi_leaderboard', array($this, 'kpi_leaderboard_shortcode'));
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/kpi-competition-dashboard-public.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/kpi-competition-dashboard-public.js',
            array('jquery'),
            $this->version,
            false
        );

        // Add localization if needed
        wp_localize_script($this->plugin_name, 'kpiDashboard', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kpi_dashboard_nonce')
        ));
    }

    /**
     * Shortcode to display KPI leaderboard
     * Usage: [kpi_leaderboard metric="sales" limit="10"]
     */
    public function kpi_leaderboard_shortcode($atts) {
        // Normalize attribute keys to lowercase
        $atts = array_change_key_case((array)$atts, CASE_LOWER);
        
        // Override default attributes with user attributes
        $atts = shortcode_atts(array(
            'metric' => '',
            'limit' => 10,
            'title' => 'KPI Leaderboard'
        ), $atts, 'kpi_leaderboard');

        // Sanitize attributes
        $metric = sanitize_text_field($atts['metric']);
        $limit = absint($atts['limit']);
        $title = sanitize_text_field($atts['title']);

        if (empty($metric)) {
            return '<p class="kpi-error">Please specify a metric for the leaderboard.</p>';
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'kpi_metrics';
        
        // Get cached results
        $cache_key = 'kpi_leaderboard_' . md5($metric . $limit);
        $results = get_transient($cache_key);

        if (false === $results) {
            $query = $wpdb->prepare(
                "SELECT user_id, metric_name, SUM(metric_value) as total_value 
                FROM $table_name
                WHERE metric_name = %s
                GROUP BY user_id
                ORDER BY total_value DESC
                LIMIT %d",
                $metric,
                $limit
            );

            $results = $wpdb->get_results($query);
            
            // Cache results for 5 minutes
            set_transient($cache_key, $results, 5 * MINUTE_IN_SECONDS);
        }

        // Start output buffering
        ob_start();
        
        // Include the template
        include plugin_dir_path(__FILE__) . 'partials/kpi-competition-dashboard-public-display.php';
        
        // Return the buffered content
        return ob_get_clean();
    }

    /**
     * Register AJAX handlers if needed
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_get_kpi_metrics', array($this, 'get_kpi_metrics_ajax'));
        add_action('wp_ajax_nopriv_get_kpi_metrics', array($this, 'get_kpi_metrics_ajax'));
    }

    /**
     * AJAX handler for getting KPI metrics
     */
    public function get_kpi_metrics_ajax() {
        check_ajax_referer('kpi_dashboard_nonce', 'nonce');

        $metric = sanitize_text_field($_POST['metric']);
        $limit = absint($_POST['limit']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'kpi_metrics';
        
        $query = $wpdb->prepare(
            "SELECT user_id, metric_name, SUM(metric_value) as total_value 
            FROM $table_name
            WHERE metric_name = %s
            GROUP BY user_id
            ORDER BY total_value DESC
            LIMIT %d",
            $metric,
            $limit
        );

        $results = $wpdb->get_results($query);
        
        wp_send_json_success($results);
    }
}