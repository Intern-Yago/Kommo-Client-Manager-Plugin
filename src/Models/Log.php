<?php

namespace KCM\Models;

use KCM\Database\Database;

if (!defined('ABSPATH')) {
    exit;
}

class Log
{
    public static function create(string $level, string $message, array $context = []): bool
    {
        global $wpdb;
        $table = Database::getLogsTableName();

        $result = $wpdb->insert(
            $table,
            [
                'level'      => sanitize_text_field($level),
                'message'    => sanitize_text_field($message),
                'context'    => !empty($context) ? wp_json_encode($context) : null,
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s']
        );

        return $result !== false;
    }

    public static function getLogs(int $limit = 50, int $offset = 0, string $level = ''): array
    {
        global $wpdb;
        $table = Database::getLogsTableName();

        if (!empty($level)) {
            $sql = $wpdb->prepare(
                "SELECT * FROM $table WHERE level = %s ORDER BY id DESC LIMIT %d OFFSET %d",
                $level,
                $limit,
                $offset
            );
        } else {
            $sql = $wpdb->prepare(
                "SELECT * FROM $table ORDER BY id DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            );
        }

        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    public static function countLogs(string $level = ''): int
    {
        global $wpdb;
        $table = Database::getLogsTableName();

        if (!empty($level)) {
            $sql = $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE level = %s", $level);
        } else {
            $sql = "SELECT COUNT(*) FROM $table";
        }

        return (int) $wpdb->get_var($sql);
    }

    public static function clearLogs(): bool
    {
        global $wpdb;
        $table = Database::getLogsTableName();
        return $wpdb->query("TRUNCATE TABLE $table") !== false;
    }
}
