<?php

namespace KCM\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Admin
{
    private Dashboard $dashboard;
    private Clients $clients;
    private Sync $sync;
    private Logs $logs;
    private Settings $settings;

    public function __construct()
    {
        $this->dashboard = new Dashboard();
        $this->clients   = new Clients();
        $this->sync      = new Sync();
        $this->logs      = new Logs();
        $this->settings  = new Settings();

        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_head', [$this, 'hideThirdPartyNotices'], 1);
    }

    public function hideThirdPartyNotices(): void
    {
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'kcm-') !== false) {
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
            remove_all_actions('user_admin_notices');
            remove_all_actions('network_admin_notices');
        }
    }

    public function registerMenu(): void
    {
        add_menu_page(
            'Kommo Client Manager',
            'Kommo',
            'manage_options',
            'kcm-dashboard',
            [$this->dashboard, 'render'],
            'dashicons-groups',
            26
        );

        add_submenu_page(
            'kcm-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'kcm-dashboard',
            [$this->dashboard, 'render']
        );

        add_submenu_page(
            'kcm-dashboard',
            'Clientes',
            'Clientes',
            'manage_options',
            'kcm-clients',
            [$this->clients, 'render']
        );

        add_submenu_page(
            'kcm-dashboard',
            'Sincronização',
            'Sincronização',
            'manage_options',
            'kcm-sync',
            [$this->sync, 'render']
        );

        add_submenu_page(
            'kcm-dashboard',
            'Logs',
            'Logs',
            'manage_options',
            'kcm-logs',
            [$this->logs, 'render']
        );

        add_submenu_page(
            'kcm-dashboard',
            'Configurações',
            'Configurações',
            'manage_options',
            'kcm-settings',
            [$this->settings, 'render']
        );
    }

    public function enqueueAssets(string $hook): void
    {
        if (strpos($hook, 'kcm-') === false) {
            return;
        }

        wp_enqueue_style(
            'kcm-admin-style',
            KCM_URL . 'assets/css/admin.css',
            [],
            KCM_VERSION
        );

        wp_enqueue_script(
            'kcm-admin-script',
            KCM_URL . 'assets/js/admin.js',
            ['jquery'],
            KCM_VERSION,
            true
        );
    }
}