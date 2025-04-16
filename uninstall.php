<?php
// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Drop all related tables
global $wpdb;

$tables = array(
    'kpi_metric_submissions',
    'kpi_competition_metrics',
    'kpi_competitions',
    'kpi_team_members',
    'kpi_teams',
    'kpi_pods',
    'kpi_campaigns',
    'kpi_companies'
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}$table");
}

// Delete plugin options
delete_option('kpi_competition_dashboard_db_version');
delete_option('kpi_competition_dashboard_version');
delete_option('kpi_competition_dashboard_delete_data');