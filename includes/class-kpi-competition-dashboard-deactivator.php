<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    KPI_Competition_Dashboard
 * @subpackage KPI_Competition_Dashboard/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    KPI_Competition_Dashboard
 * @subpackage KPI_Competition_Dashboard/includes
 * @author     Your Name <email@example.com>
 */
class KPI_Competition_Dashboard_Deactivator {

    /**
     * Handles plugin deactivation tasks
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // For now, we'll keep the tables in case of accidental deactivation
        // Tables will be removed on uninstall instead
    }

}