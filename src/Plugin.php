<?php

namespace KCM;

use KCM\Core\Loader;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin
{
    public static function boot(): void
    {
        Loader::boot();
    }
}