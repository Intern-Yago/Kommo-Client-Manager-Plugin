<?php

namespace KCM\Core;

use KCM\Database\Database;

if (!defined('ABSPATH')) {
    exit;
}

class Activator
{
    public static function activate(): void
    {
        // 1. Create DB Tables
        Database::init();

        // 2. Set Version Option
        update_option('kcm_version', KCM_VERSION);

        // 3. Default Settings Option
        if (!get_option('kcm_settings')) {
            add_option('kcm_settings', [
                'subdomain'     => '',
                'client_id'     => '',
                'client_secret' => '',
                'redirect_uri'  => admin_url('admin.php?page=kcm-settings'),
                'access_token'  => '',
                'refresh_token' => '',
                'token_expires' => 0,
                'sync_cron'     => 'hourly',
                'auto_create_user' => 'no',
            ]);
        }

        // 4. Schedule Cron
        if (!wp_next_scheduled('kcm_scheduled_sync')) {
            wp_schedule_event(time(), 'hourly', 'kcm_scheduled_sync');
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
