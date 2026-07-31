<?php

namespace KCM\Core;

use KCM\Admin\Admin;
use KCM\Services\SyncService;

if (!defined('ABSPATH')) {
    exit;
}

class Loader
{
    public static function boot(): void
    {
        // Initialize Admin interface if in admin area or AJAX request
        if (is_admin()) {
            new Admin();
        }

        // Register REST API endpoints (Webhooks, etc.)
        add_action('rest_api_init', [self::class, 'registerRestRoutes']);

        // Initialize VIP area & password activation handlers
        \KCM\Services\VipService::init();

        // Register WP-Cron scheduled task for Sync
        add_action('kcm_scheduled_sync', [SyncService::class, 'runScheduledSync']);
    }

    public static function registerRestRoutes(): void
    {
        register_rest_route('kcm/v1', '/webhook', [
            'methods'  => 'POST',
            'callback' => [\KCM\Services\KommoService::class, 'handleWebhook'],
            'permission_callback' => '__return_true',
        ]);
    }
}