<?php

namespace KCM\Services;

if (!defined('ABSPATH')) {
    exit;
}

class UserService
{
    public static function createOrMatchUser(array $clientData): ?int
    {
        $email = trim($clientData['email'] ?? '');
        if (empty($email) || !is_email($email)) {
            return null;
        }

        // Check if WP User exists by email
        $user = get_user_by('email', $email);
        if ($user) {
            return (int) $user->ID;
        }

        // Check if auto creation is enabled
        $autoCreate = SettingsService::get('auto_create_user', 'no');
        if ($autoCreate !== 'yes') {
            return null;
        }

        // Generate username from email
        $username = sanitize_user(current(explode('@', $email)));
        if (username_exists($username)) {
            $username = $username . '_' . wp_rand(100, 999);
        }

        $password = wp_generate_password(12, true);

        $nameParts = explode(' ', trim($clientData['name'] ?? ''), 2);
        $firstName = $nameParts[0] ?? '';
        $lastName  = $nameParts[1] ?? '';

        $userId = wp_create_user($username, $password, $email);

        if (is_wp_error($userId)) {
            LogService::error('Erro ao criar usuário WP', [
                'error' => $userId->get_error_message(),
                'email' => $email,
            ]);
            return null;
        }

        wp_update_user([
            'ID'         => $userId,
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'role'       => 'subscriber',
        ]);

        LogService::info('Usuário WP criado com sucesso para cliente Kommo', [
            'user_id' => $userId,
            'email'   => $email,
        ]);

        return (int) $userId;
    }
}
