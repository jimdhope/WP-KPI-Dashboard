<?php
/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    KPI_Competition_Dashboard
 */

class KPI_Competition_Dashboard {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      KPI_Competition_Dashboard_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if (defined('KPI_COMPETITION_VERSION')) {
            $this->version = KPI_COMPETITION_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'kpi-competition-dashboard';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        require_once KPI_COMPETITION_PLUGIN_DIR . 'includes/class-kpi-competition-dashboard-loader.php';
        require_once KPI_COMPETITION_PLUGIN_DIR . 'admin/class-kpi-competition-dashboard-admin.php';
        require_once KPI_COMPETITION_PLUGIN_DIR . 'public/class-kpi-competition-dashboard-public.php';

        $this->loader = new KPI_Competition_Dashboard_Loader();
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new KPI_Competition_Dashboard_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        
        // Add activation hook
        register_activation_hook(__FILE__, array('KPI_Competition_Dashboard_Admin', 'install_migrations'));
        
        // Add AJAX actions
        $this->loader->add_action('wp_ajax_delete_competition', $plugin_admin, 'delete_competition_ajax');
        $this->loader->add_action('wp_ajax_get_competition', $plugin_admin, 'get_competition_ajax');
        $this->loader->add_action('wp_ajax_get_company_campaigns', $plugin_admin, 'get_company_campaigns_ajax');
        $this->loader->add_action('wp_ajax_get_campaign_pods', $plugin_admin, 'get_campaign_pods_ajax');
        
        // Add Dragula
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_dragula');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new KPI_Competition_Dashboard_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Register shortcode
        add_shortcode('kpi_leaderboard', array($plugin_public, 'kpi_leaderboard_shortcode'));
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        if ($this->loader) {
            $this->loader->run();
        }
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}