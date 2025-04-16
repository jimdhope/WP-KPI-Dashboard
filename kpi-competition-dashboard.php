<?php
/**
 * KPI Competition Dashboard
 *
 * @package     KPI_Competition_Dashboard
 * @author      Your Name
 * @copyright   2025 Your Name or Company Name
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: KPI Competition Dashboard
 * Plugin URI:  https://example.com/plugin-name
 * Description: A plugin to manage and display KPI competition metrics
 * Version:     1.0.0
 * Author:      Your Name
 * Author URI:  https://example.com
 * Text Domain: kpi-competition-dashboard
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('KPI_COMPETITION_VERSION', '1.0.0');
define('KPI_COMPETITION_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KPI_COMPETITION_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_kpi_competition_dashboard() {
    require_once KPI_COMPETITION_PLUGIN_DIR . 'includes/class-kpi-competition-dashboard-activator.php';
    require_once KPI_COMPETITION_PLUGIN_DIR . 'admin/class-kpi-competition-dashboard-admin.php';
    
    // Create tables
    KPI_Competition_Dashboard_Activator::activate();
    
    // Run migrations for existing tables
    KPI_Competition_Dashboard_Admin::install_migrations();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_kpi_competition_dashboard() {
    require_once KPI_COMPETITION_PLUGIN_DIR . 'includes/class-kpi-competition-dashboard-deactivator.php';
    KPI_Competition_Dashboard_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_kpi_competition_dashboard');
register_deactivation_hook(__FILE__, 'deactivate_kpi_competition_dashboard');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once KPI_COMPETITION_PLUGIN_DIR . 'includes/class-kpi-competition-dashboard.php';

/**
 * Begins execution of the plugin.
 */
function run_kpi_competition_dashboard() {
    $plugin = new KPI_Competition_Dashboard();
    $plugin->run();
}
run_kpi_competition_dashboard();