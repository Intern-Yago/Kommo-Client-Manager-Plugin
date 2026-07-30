<?php

namespace KCM\Services;

if (!defined('ABSPATH')) {
    exit;
}

class SettingsService
{
    private const OPTION_NAME = 'kcm_settings';

    public static function getSettings(): array
    {
        $defaults = [
            'subdomain'        => '',
            'client_id'        => '',
            'client_secret'    => '',
            'redirect_uri'     => admin_url('admin.php?page=kcm-settings'),
            'access_token'     => '',
            'refresh_token'    => '',
            'token_expires'    => 0,
            'sync_cron'        => 'hourly',
            'auto_create_user' => 'no',
        ];

        $options = get_option(self::OPTION_NAME, []);
        return wp_parse_args($options, $defaults);
    }

    public static function get(string $key, $default = null)
    {
        $settings = self::getSettings();
        return $settings[$key] ?? $default;
    }

    public static function update(string $key, $value): bool
    {
        $settings = self::getSettings();
        $settings[$key] = $value;
        return update_option(self::OPTION_NAME, $settings);
    }

    public static function updateAll(array $newSettings): bool
    {
        $settings = array_merge(self::getSettings(), $newSettings);
        return update_option(self::OPTION_NAME, $settings);
    }

    public static function getBaseUrl(): string
    {
        $subdomain = trim(self::get('subdomain', ''));
        if (empty($subdomain)) {
            return '';
        }

        $subdomain = rtrim($subdomain, '/');

        if (preg_match('#^https?://#i', $subdomain)) {
            return preg_replace('#^http://#i', 'https://', $subdomain);
        }

        if (strpos($subdomain, '.') !== false) {
            return 'https://' . $subdomain;
        }

        return 'https://' . $subdomain . '.kommo.com';
    }

    public static function hasValidToken(): bool
    {
        $token = self::get('access_token');
        $expires = (int) self::get('token_expires', 0);
        return !empty($token) && ($expires > time() + 60);
    }

    public static function saveTokens(string $accessToken, string $refreshToken, int $expiresIn): void
    {
        self::updateAll([
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'token_expires' => time() + $expiresIn,
        ]);
    }
}
