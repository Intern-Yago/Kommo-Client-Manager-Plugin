<?php
/**
 * Plugin Name: Kommo Client Manager
 * Description: Integração entre Kommo CRM e WordPress.
 * Version: 1.0.0
 * Author: Yago
 * License: GPL-2.0-or-later
 * Text Domain: kommo-client-manager
 */

if (!defined('ABSPATH')) {
    exit;
}

define('KCM_VERSION', '1.0.0');
define('KCM_PATH', plugin_dir_path(__FILE__));
define('KCM_URL', plugin_dir_url(__FILE__));

// PSR-4 Autoloader Setup
if (file_exists(KCM_PATH . 'vendor/autoload.php')) {
    require_once KCM_PATH . 'vendor/autoload.php';
}

spl_autoload_register(function ($class) {
    $prefix = 'KCM\\';
    $base_dir = KCM_PATH . 'src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Activation & Deactivation Hooks
register_activation_hook(__FILE__, ['KCM\Core\Activator', 'activate']);
register_deactivation_hook(__FILE__, ['KCM\Core\Deactivator', 'deactivate']);

// Boot Plugin
KCM\Plugin::boot();