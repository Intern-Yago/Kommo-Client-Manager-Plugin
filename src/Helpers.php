<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('kcm_log')) {
    function kcm_log(string $message, array $context = [], string $level = 'info'): void
    {
        \KCM\Services\LogService::$level($message, $context);
    }
}

if (!function_exists('kcm_get_setting')) {
    function kcm_get_setting(string $key, $default = null)
    {
        return \KCM\Services\SettingsService::get($key, $default);
    }
}
