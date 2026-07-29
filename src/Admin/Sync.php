<?php

namespace KCM\Admin;

use KCM\Services\SyncService;
use KCM\Services\SettingsService;

if (!defined('ABSPATH')) {
    exit;
}

class Sync
{
    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Acesso negado. Permissão insuficiente.', 'kommo-client-manager'));
        }

        $syncResult = null;

        if (isset($_POST['kcm_do_sync']) && check_admin_referer('kcm_manual_sync_nonce')) {
            $syncResult = SyncService::syncAllContacts();
        }

        $lastSyncTime  = get_option('kcm_last_sync_time', 'Nunca executada');
        $lastSyncCount = get_option('kcm_last_sync_count', 0);
        $webhookUrl    = get_rest_url(null, 'kcm/v1/webhook');

        ?>
        <div class="wrap kcm-wrap">
            <h1 class="wp-heading-inline" style="display:none;"></h1>
            <hr class="wp-header-end">

            <h1>Sincronização Kommo CRM</h1>
            <hr>

            <?php if ($syncResult) : ?>
                <div class="notice notice-success is-dismissible kcm-notice">
                    <p><strong>Sincronização Concluída!</strong></p>
                    <p>Contatos importados/atualizados: <strong><?php echo esc_html($syncResult['total_synced']); ?></strong></p>
                    <?php if ($syncResult['total_errors'] > 0) : ?>
                        <p style="color: #d63638;">Erros durante a importação: <strong><?php echo esc_html($syncResult['total_errors']); ?></strong> (consulte a aba de Logs)</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="kcm-grid">
                <div class="kcm-card">
                    <h2>Sincronização Manual</h2>
                    <p>Clique no botão abaixo para buscar todos os contatos do Kommo CRM e atualizar o banco de dados do WordPress.</p>

                    <div style="margin-top: 20px; background: #f9f9f9; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                        <p><strong>Última Sincronização:</strong> <?php echo esc_html($lastSyncTime); ?></p>
                        <p><strong>Último Total Importado:</strong> <?php echo esc_html($lastSyncCount); ?> contatos</p>
                    </div>

                    <form method="post">
                        <?php wp_nonce_field('kcm_manual_sync_nonce'); ?>
                        <button type="submit" name="kcm_do_sync" value="1" class="button button-primary button-large">
                            <span class="dashicons dashicons-update" style="vertical-align: middle; margin-top: -2px;"></span> Iniciar Sincronização Manual Agora
                        </button>
                    </form>
                </div>

                <div class="kcm-card">
                    <h2>Sincronização Automática via Webhook</h2>
                    <p>Para receber atualizações de clientes em tempo real assim que eles forem cadastrados ou editados no Kommo CRM, cadastre a URL abaixo no painel do Kommo (em <em>Configurações > Integradores > Webhooks</em>):</p>

                    <div class="kcm-code-box">
                        <span id="kcm-webhook-url"><?php echo esc_html($webhookUrl); ?></span>
                        <button type="button" class="button button-small kcm-copy-btn" data-target="#kcm-webhook-url">Copiar URL</button>
                    </div>

                    <h4 style="margin-top: 20px;">Eventos recomendados no Kommo:</h4>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <li>Contato Adicionado (<em>Contact added</em>)</li>
                        <li>Contato Alterado (<em>Contact updated</em>)</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
}