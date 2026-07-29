<?php

namespace KCM\Admin;

use KCM\Models\Client;
use KCM\Models\Log;
use KCM\Services\SettingsService;
use KCM\Services\KommoService;

if (!defined('ABSPATH')) {
    exit;
}

class Dashboard
{
    public function render(): void
    {
        $totalClients  = Client::countClients();
        $lastSyncTime  = get_option('kcm_last_sync_time', 'Nunca');
        $lastSyncCount = get_option('kcm_last_sync_count', 0);
        $recentLogs    = Log::getLogs(5);
        $isConnected   = SettingsService::hasValidToken();

        ?>
        <div class="wrap kcm-wrap">
            <h1 class="wp-heading-inline" style="display:none;"></h1>
            <hr class="wp-header-end">

            <div class="kcm-header">
                <h1>
                    <span class="dashicons dashicons-groups"></span>
                    Kommo Client Manager
                </h1>
                <div>
                    <?php if ($isConnected) : ?>
                        <span class="kcm-badge">Conectado ao Kommo CRM</span>
                    <?php else : ?>
                        <span class="kcm-badge disconnected">Desconectado / Token Ausente</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="kcm-grid">
                <div class="kcm-card kcm-stat-card">
                    <div class="kcm-stat-icon">
                        <span class="dashicons dashicons-id"></span>
                    </div>
                    <div>
                        <div class="kcm-stat-val"><?php echo esc_html($totalClients); ?></div>
                        <div class="kcm-stat-lbl">Clientes Sincronizados</div>
                    </div>
                </div>

                <div class="kcm-card kcm-stat-card">
                    <div class="kcm-stat-icon">
                        <span class="dashicons dashicons-update"></span>
                    </div>
                    <div>
                        <div class="kcm-stat-val"><?php echo esc_html($lastSyncTime); ?></div>
                        <div class="kcm-stat-lbl">Última Sincronização</div>
                    </div>
                </div>

                <div class="kcm-card kcm-stat-card">
                    <div class="kcm-stat-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div>
                        <div class="kcm-stat-val"><?php echo esc_html($lastSyncCount); ?></div>
                        <div class="kcm-stat-lbl">Importados no Último Lote</div>
                    </div>
                </div>
            </div>

            <div class="kcm-card" style="margin-bottom: 24px;">
                <h2>Ações Rápidas</h2>
                <p>Gerencie sua integração rapidamente pelos atalhos abaixo:</p>
                <div style="display: flex; gap: 12px; margin-top: 15px;">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=kcm-sync')); ?>" class="button button-primary button-large">
                        <span class="dashicons dashicons-update" style="vertical-align: middle; margin-top: -2px;"></span> Executar Sincronização
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=kcm-settings')); ?>" class="button button-secondary button-large">
                        <span class="dashicons dashicons-admin-settings" style="vertical-align: middle; margin-top: -2px;"></span> Configurações API
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=kcm-logs')); ?>" class="button button-secondary button-large">
                        <span class="dashicons dashicons-list-view" style="vertical-align: middle; margin-top: -2px;"></span> Ver Logs de Sistema
                    </a>
                </div>
            </div>

            <div class="kcm-card">
                <h2>Últimas Atividades</h2>
                <?php if (empty($recentLogs)) : ?>
                    <p>Nenhuma atividade registrada até o momento.</p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Nível</th>
                                <th>Mensagem</th>
                                <th style="width: 180px;">Data/Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLogs as $log) : ?>
                                <tr>
                                    <td>
                                        <span class="kcm-level-<?php echo esc_attr($log['level']); ?>">
                                            <?php echo esc_html(strtoupper($log['level'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($log['message']); ?></td>
                                    <td><?php echo esc_html($log['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}