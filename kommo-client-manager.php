<?php
/**
 * Plugin Name: Kommo Client Manager
 * Plugin URI: https://intern-yago.github.io/Kommo-Client-Manager-Plugin/
 * Description: Integração entre Kommo CRM e WordPress.
 * Version: 1.1.1
 * Author: Yago
 * Author URI: https://github.com/intern-yago
 * License: GPL-2.0-or-later
 * Text Domain: kommo-client-manager
 */

if (!defined('ABSPATH')) {
    exit;
}

define('KCM_VERSION', '1.1.1');
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

// Plugin Action Links in wp-admin/plugins.php
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $custom_links = [
        '<a href="' . esc_url(admin_url('admin.php?page=kcm-settings')) . '">Configurações</a>',
        '<a href="https://intern-yago.github.io/Kommo-Client-Manager-Plugin/" target="_blank" rel="noopener noreferrer">Documentação</a>',
    ];
    return array_merge($custom_links, $links);
});

// Activation & Deactivation Hooks
register_activation_hook(__FILE__, ['KCM\Core\Activator', 'activate']);
register_deactivation_hook(__FILE__, ['KCM\Core\Deactivator', 'deactivate']);

// Boot Plugin
KCM\Plugin::boot();