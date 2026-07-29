<?php

namespace KCM\Database;

if (!defined('ABSPATH')) {
    exit;
}

class Database
{
    public static function getClientsTableName(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'kcm_clients';
    }

    public static function getLogsTableName(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'kcm_logs';
    }

    public static function init(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // 1. Clients Table
        $clients_table = self::getClientsTableName();
        $sql_clients = "CREATE TABLE $clients_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            kommo_id bigint(20) UNSIGNED NOT NULL,
            name varchar(255) NOT NULL DEFAULT '',
            email varchar(255) NOT NULL DEFAULT '',
            phone varchar(50) NOT NULL DEFAULT '',
            company varchar(255) NOT NULL DEFAULT '',
            status varchar(50) NOT NULL DEFAULT 'lead',
            wp_user_id bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY kommo_id (kommo_id),
            KEY email (email),
            KEY wp_user_id (wp_user_id)
        ) $charset_collate;";

        dbDelta($sql_clients);

        // 2. Logs Table
        $logs_table = self::getLogsTableName();
        $sql_logs = "CREATE TABLE $logs_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            level varchar(20) NOT NULL DEFAULT 'info',
            message text NOT NULL,
            context longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY level (level),
            KEY created_at (created_at)
        ) $charset_collate;";

        dbDelta($sql_logs);
    }

    public static function dropTables(): void
    {
        global $wpdb;
        $clients_table = self::getClientsTableName();
        $logs_table = self::getLogsTableName();

        $wpdb->query("DROP TABLE IF EXISTS $clients_table;");
        $wpdb->query("DROP TABLE IF EXISTS $logs_table;");
    }
}
