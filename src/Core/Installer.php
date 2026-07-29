<?php

namespace KCM\Core;

use KCM\Database\Database;

if (!defined('ABSPATH')) {
    exit;
}

class Installer
{
    public static function checkVersion(): void
    {
        $installedVersion = get_option('kcm_version');
        if ($installedVersion !== KCM_VERSION) {
            Database::init();
            update_option('kcm_version', KCM_VERSION);
        }
    }
}
