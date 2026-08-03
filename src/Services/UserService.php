<?php

namespace KCM\Services;

use KCM\Models\Client;

if (!defined('ABSPATH')) {
    exit;
}

class UserService
{
    public static function createOrMatchUser(array $clientData, bool $forceCreate = false): ?int
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

        // Check if auto creation is enabled or force creation is requested
        $autoCreate = SettingsService::get('auto_create_user', 'no');
        if ($autoCreate !== 'yes' && !$forceCreate) {
            return null;
        }

        // Generate username from email
        $username = sanitize_user(current(explode('@', $email)));
        if (empty($username) || username_exists($username)) {
            $username = 'kcm_' . sanitize_user(current(explode('@', $email))) . '_' . wp_rand(100, 999);
        }

        $password = wp_generate_password(16, true);

        $rawName = trim($clientData['name'] ?? '');
        if (empty($rawName)) {
            $rawName = 'Cliente ' . current(explode('@', $email));
        }

        $nameParts = explode(' ', $rawName, 2);
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

        // Save phone to user meta if provided (optional)
        if (!empty($clientData['phone'])) {
            update_user_meta($userId, 'billing_phone', sanitize_text_field($clientData['phone']));
            update_user_meta($userId, 'kcm_phone', sanitize_text_field($clientData['phone']));
        }

        LogService::info('Usuário WP criado com sucesso para cliente Kommo', [
            'user_id' => $userId,
            'email'   => $email,
        ]);

        return (int) $userId;
    }

    public static function generateActivationToken(int $userId): string
    {
        $token = wp_generate_password(32, false);
        $hashedToken = wp_hash_password($token);

        update_user_meta($userId, '_kcm_activation_token', $hashedToken);
        // Token valid for 14 days
        update_user_meta($userId, '_kcm_activation_expires', time() + (14 * DAY_IN_SECONDS));

        return $token;
    }

    public static function validateActivationToken(int $userId, string $token): bool
    {
        if (empty($token) || $userId <= 0) {
            return false;
        }

        $hashedToken = get_user_meta($userId, '_kcm_activation_token', true);
        $expires     = (int) get_user_meta($userId, '_kcm_activation_expires', true);

        if (empty($hashedToken) || empty($expires)) {
            return false;
        }

        if (time() > $expires) {
            return false;
        }

        return wp_check_password($token, $hashedToken);
    }

    public static function setUserPassword(int $userId, string $newPassword): bool
    {
        if ($userId <= 0 || strlen($newPassword) < 6) {
            return false;
        }

        wp_set_password($newPassword, $userId);

        // Delete activation token & verification code metadata
        delete_user_meta($userId, '_kcm_activation_token');
        delete_user_meta($userId, '_kcm_activation_expires');
        delete_user_meta($userId, '_kcm_verification_code');
        delete_user_meta($userId, '_kcm_code_expires');

        // Flag user as VIP password set
        update_user_meta($userId, '_kcm_vip_active', 1);
        update_user_meta($userId, '_kcm_password_set_at', current_time('mysql'));

        LogService::info('Senha definida com sucesso pelo cliente', ['user_id' => $userId]);

        return true;
    }

    public static function sendVerificationCode(int $userId): bool
    {
        $user = get_userdata($userId);
        if (!$user || empty($user->user_email)) {
            return false;
        }

        $code = sprintf('%06d', wp_rand(100000, 999999));
        
        update_user_meta($userId, '_kcm_verification_code', wp_hash_password($code));
        update_user_meta($userId, '_kcm_code_expires', time() + (15 * MINUTE_IN_SECONDS));

        return EmailService::sendVerificationCode($user->user_email, $user->first_name ?: $user->display_name, $code);
    }

    public static function validateVerificationCode(int $userId, string $code): bool
    {
        $code = trim($code);
        if (empty($code) || $userId <= 0) {
            return false;
        }

        $hashedCode = get_user_meta($userId, '_kcm_verification_code', true);
        $expires    = (int) get_user_meta($userId, '_kcm_code_expires', true);

        if (empty($hashedCode) || empty($expires)) {
            return false;
        }

        if (time() > $expires) {
            return false;
        }

        return wp_check_password($code, $hashedCode);
    }

    public static function getActivationLinkForClient(array $client): ?string
    {
        $email = trim($client['email'] ?? '');
        if (empty($email) || !is_email($email)) {
            return null;
        }

        $userId = $client['wp_user_id'] ? (int) $client['wp_user_id'] : null;

        if (!$userId) {
            $userId = self::createOrMatchUser($client, true);
            if ($userId) {
                Client::save([
                    'kommo_id'   => $client['kommo_id'],
                    'name'       => $client['name'],
                    'email'      => $client['email'],
                    'phone'      => $client['phone'] ?? '',
                    'company'    => $client['company'] ?? '',
                    'wp_user_id' => $userId,
                ]);
            }
        }

        if (!$userId) {
            return null;
        }

        $token = self::generateActivationToken($userId);

        // Check if custom activation page URL is set
        $customPage = SettingsService::get('vip_set_password_url', '');

        if (!empty($customPage)) {
            return add_query_arg([
                'kcm_token' => $token,
                'uid'       => $userId,
            ], esc_url_raw($customPage));
        }

        return add_query_arg([
            'kcm_action' => 'set_password',
            'kcm_token'  => $token,
            'uid'        => $userId,
        ], home_url('/'));
    }
}

