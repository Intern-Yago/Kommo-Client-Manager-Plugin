<?php

namespace KCM\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Deactivator
{
    public static function deactivate(): void
    {
        // Clear scheduled sync event
        $timestamp = wp_next_scheduled('kcm_scheduled_sync');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'kcm_scheduled_sync');
        }

        flush_rewrite_rules();
    }
}
