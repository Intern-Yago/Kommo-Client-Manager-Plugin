<?php

namespace KCM\Models;

use KCM\Database\Database;

if (!defined('ABSPATH')) {
    exit;
}

class Client
{
    public static function save(array $data): int
    {
        global $wpdb;
        $table = Database::getClientsTableName();

        $kommo_id = isset($data['kommo_id']) ? (int) $data['kommo_id'] : 0;
        if (!$kommo_id) {
            return 0;
        }

        $existing = self::findByKommoId($kommo_id);

        $payload = [
            'kommo_id'   => $kommo_id,
            'name'       => sanitize_text_field($data['name'] ?? ''),
            'email'      => sanitize_email($data['email'] ?? ''),
            'phone'      => sanitize_text_field($data['phone'] ?? ''),
            'company'    => sanitize_text_field($data['company'] ?? ''),
            'status'     => sanitize_text_field($data['status'] ?? 'lead'),
            'wp_user_id' => !empty($data['wp_user_id']) ? (int) $data['wp_user_id'] : null,
            'updated_at' => current_time('mysql'),
        ];

        if ($existing) {
            $wpdb->update(
                $table,
                $payload,
                ['kommo_id' => $kommo_id]
            );
            return (int) $existing['id'];
        } else {
            $payload['created_at'] = current_time('mysql');
            $wpdb->insert($table, $payload);
            return (int) $wpdb->insert_id;
        }
    }

    public static function findByKommoId(int $kommoId): ?array
    {
        global $wpdb;
        $table = Database::getClientsTableName();
        $sql = $wpdb->prepare("SELECT * FROM $table WHERE kommo_id = %d LIMIT 1", $kommoId);
        $result = $wpdb->get_row($sql, ARRAY_A);
        return $result ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        global $wpdb;
        $table = Database::getClientsTableName();
        $sql = $wpdb->prepare("SELECT * FROM $table WHERE email = %s LIMIT 1", $email);
        $result = $wpdb->get_row($sql, ARRAY_A);
        return $result ?: null;
    }

    public static function getClients(int $limit = 20, int $offset = 0, string $search = ''): array
    {
        global $wpdb;
        $table = Database::getClientsTableName();

        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $sql = $wpdb->prepare(
                "SELECT * FROM $table WHERE name LIKE %s OR email LIKE %s OR company LIKE %s ORDER BY updated_at DESC LIMIT %d OFFSET %d",
                $like,
                $like,
                $like,
                $limit,
                $offset
            );
        } else {
            $sql = $wpdb->prepare(
                "SELECT * FROM $table ORDER BY updated_at DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            );
        }

        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    public static function countClients(string $search = ''): int
    {
        global $wpdb;
        $table = Database::getClientsTableName();

        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $sql = $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE name LIKE %s OR email LIKE %s OR company LIKE %s",
                $like,
                $like,
                $like
            );
            return (int) $wpdb->get_var($sql);
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
    }

    public static function delete(int $id): bool
    {
        global $wpdb;
        $table = Database::getClientsTableName();
        return $wpdb->delete($table, ['id' => $id]) !== false;
    }

    public static function deleteAll(): int
    {
        global $wpdb;
        $table = Database::getClientsTableName();
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $wpdb->query("DELETE FROM $table");
        return $count;
    }

    public static function generateSyntheticKommoId(): int
    {
        global $wpdb;
        $table = Database::getClientsTableName();
        $maxKommoId = (int) $wpdb->get_var("SELECT MAX(kommo_id) FROM $table WHERE kommo_id >= 900000000");
        if ($maxKommoId < 900000000) {
            return 900000001;
        }
        return $maxKommoId + 1;
    }
}
