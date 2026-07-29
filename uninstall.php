<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Optionally clean up settings and database tables when plugin is deleted
delete_option('kcm_version');
// delete_option('kcm_settings'); // Keep commented or optional so data is preserved unless requested

global $wpdb;
// Uncomment below if complete table cleanup is desired on uninstall:
// $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}kcm_clients;");
// $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}kcm_logs;");
