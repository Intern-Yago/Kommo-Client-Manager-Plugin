<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('kcm_log')) {
    function kcm_log(string $message, array $context = [], string $level = 'info'): void
    {
        $allowedLevels = ['info', 'warning', 'error', 'debug'];
        $method = in_array(strtolower($level), $allowedLevels, true) ? strtolower($level) : 'info';
        \KCM\Services\LogService::$method($message, $context);
    }
}

if (!function_exists('kcm_get_setting')) {
    function kcm_get_setting(string $key, $default = null)
    {
        return \KCM\Services\SettingsService::get($key, $default);
    }
}
