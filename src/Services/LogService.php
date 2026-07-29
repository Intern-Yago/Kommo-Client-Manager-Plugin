<?php

namespace KCM\Services;

use KCM\Models\Log;

if (!defined('ABSPATH')) {
    exit;
}

class LogService
{
    public static function info(string $message, array $context = []): void
    {
        Log::create('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        Log::create('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        Log::create('error', $message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        Log::create('debug', $message, $context);
    }
}
