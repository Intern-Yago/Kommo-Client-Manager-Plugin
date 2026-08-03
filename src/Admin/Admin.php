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

        add_action('admin_init', [$this, 'handleEarlyActions']);
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_head', [$this, 'hideThirdPartyNotices'], 1);
        add_action('wp_ajax_kcm_get_activation_link', [$this, 'ajaxGetActivationLink']);
        add_action('wp_ajax_kcm_save_client_email', [$this, 'ajaxSaveClientEmail']);
    }

    public function handleEarlyActions(): void
    {
        if (isset($_GET['page']) && $_GET['page'] === 'kcm-clients' && isset($_GET['action']) && $_GET['action'] === 'download_sample') {
            if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'kcm_download_sample_nonce')) {
                if (current_user_can('manage_options')) {
                    \KCM\Services\ImportService::outputSampleXlsx();
                }
            }
        }
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

        wp_localize_script('kcm-admin-script', 'kcmAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('kcm_admin_ajax_nonce'),
        ]);
    }

    public function ajaxGetActivationLink(): void
    {
        check_ajax_referer('kcm_admin_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permissão insuficiente.']);
        }

        $clientId = isset($_POST['client_id']) ? (int) $_POST['client_id'] : 0;
        if (!$clientId) {
            wp_send_json_error(['message' => 'ID do cliente inválido.']);
        }

        global $wpdb;
        $table = \KCM\Database\Database::getClientsTableName();
        $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d LIMIT 1", $clientId), ARRAY_A);

        if (!$client) {
            wp_send_json_error(['message' => 'Cliente não encontrado.']);
        }

        if (empty($client['email']) || !is_email($client['email'])) {
            wp_send_json_error(['message' => 'Este cliente não possui um e-mail válido cadastrado.']);
        }

        $link = \KCM\Services\UserService::getActivationLinkForClient($client);

        if ($link) {
            wp_send_json_success(['link' => $link]);
        } else {
            wp_send_json_error(['message' => 'Não foi possível gerar o link de acesso.']);
        }
    }

    public function ajaxSaveClientEmail(): void
    {
        check_ajax_referer('kcm_admin_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permissão insuficiente.']);
        }

        $clientId = isset($_POST['client_id']) ? (int) $_POST['client_id'] : 0;
        $email    = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

        if (!$clientId) {
            wp_send_json_error(['message' => 'ID do cliente inválido.']);
        }

        if (empty($email) || !is_email($email)) {
            wp_send_json_error(['message' => 'Por favor, informe um e-mail válido.']);
        }

        global $wpdb;
        $table = \KCM\Database\Database::getClientsTableName();
        $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d LIMIT 1", $clientId), ARRAY_A);

        if (!$client) {
            wp_send_json_error(['message' => 'Cliente não encontrado.']);
        }

        // Create or match WP User if needed
        $wpUserId = $client['wp_user_id'] ? (int) $client['wp_user_id'] : null;
        if (!$wpUserId) {
            $wpUserId = \KCM\Services\UserService::createOrMatchUser([
                'name'  => $client['name'],
                'email' => $email,
                'phone' => $client['phone'],
            ], true);
        }

        $client['email'] = $email;
        $client['wp_user_id'] = $wpUserId;

        \KCM\Models\Client::save($client);

        wp_send_json_success([
            'message' => 'E-mail cadastrado e usuário WP vinculado com sucesso!',
        ]);
    }
}