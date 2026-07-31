<?php

namespace KCM\Admin;

use KCM\Services\SettingsService;
use KCM\Services\KommoService;
use KCM\Api\KommoApi;

if (!defined('ABSPATH')) {
    exit;
}

class Settings
{
    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Acesso negado. Permissão insuficiente.', 'kommo-client-manager'));
        }

        $message = null;
        $messageType = 'updated';

        // 1. Process Settings Save or OAuth Exchange
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $isSettingsAction = isset($_POST['kcm_save_settings']) || isset($_POST['kcm_exchange_code']);
            $isNonceValid = (isset($_POST['kcm_settings_nonce']) && wp_verify_nonce($_POST['kcm_settings_nonce'], 'kcm_settings_action'))
                         || (isset($_POST['kcm_settings_nonce']) && wp_verify_nonce($_POST['kcm_settings_nonce'], 'kcm_save_settings_action'))
                         || (isset($_POST['kcm_code_nonce']) && wp_verify_nonce($_POST['kcm_code_nonce'], 'kcm_exchange_code_action'));

            if ($isSettingsAction && $isNonceValid) {
                if (isset($_POST['subdomain'])) {
                    $newSettings = [
                        'subdomain'            => sanitize_text_field($_POST['subdomain'] ?? ''),
                        'client_id'            => sanitize_text_field($_POST['client_id'] ?? ''),
                        'client_secret'        => sanitize_text_field($_POST['client_secret'] ?? ''),
                        'redirect_uri'         => esc_url_raw($_POST['redirect_uri'] ?? ''),
                        'sync_cron'            => sanitize_text_field($_POST['sync_cron'] ?? 'hourly'),
                        'auto_create_user'     => sanitize_text_field($_POST['auto_create_user'] ?? 'no'),
                        'vip_area_url'         => esc_url_raw($_POST['vip_area_url'] ?? ''),
                        'vip_set_password_url' => esc_url_raw($_POST['vip_set_password_url'] ?? ''),
                    ];

                    SettingsService::updateAll($newSettings);
                }

                if (isset($_POST['kcm_save_settings'])) {
                    $message = 'Configurações salvas com sucesso!';
                    $messageType = 'updated';
                }

                if (isset($_POST['kcm_exchange_code'])) {
                    $authCode = sanitize_text_field($_POST['auth_code'] ?? '');
                    $subdomain = SettingsService::get('subdomain');

                    if (empty($subdomain)) {
                        $message = 'Erro ao conectar: Subdomínio do Kommo não informado. Preencha o campo de subdomínio antes de conectar.';
                        $messageType = 'error';
                    } elseif (empty($authCode)) {
                        $message = 'Por favor, insira o Código de Autorização fornecido pelo Kommo.';
                        $messageType = 'error';
                    } else {
                        $api = new KommoApi();
                        $result = $api->exchangeAuthCode($authCode);
                        if ($result['success']) {
                            $message = $result['message'];
                            $messageType = 'updated';
                        } else {
                            $message = 'Erro ao conectar: ' . $result['message'];
                            $messageType = 'error';
                        }
                    }
                }
            }

            // 2. Test API Connection
            if (isset($_POST['kcm_test_connection']) && check_admin_referer('kcm_test_conn_action', 'kcm_conn_nonce')) {
                $testRes = KommoService::testConnection();
                if ($testRes['success']) {
                    $message = 'Conexão ativa com a conta: ' . esc_html($testRes['account_name']);
                    $messageType = 'updated';
                } else {
                    $message = 'Falha na conexão: ' . esc_html($testRes['message']);
                    $messageType = 'error';
                }
            }
        }

        $settings = SettingsService::getSettings();
        $hasToken = SettingsService::hasValidToken();

        ?>
        <div class="wrap kcm-wrap">
            <h1 class="wp-heading-inline" style="display:none;"></h1>
            <hr class="wp-header-end">

            <h1>Configurações da Integração Kommo</h1>
            <hr>

            <?php if ($message) : ?>
                <div class="notice notice-<?php echo esc_attr($messageType); ?> is-dismissible kcm-notice">
                    <p><?php echo esc_html($message); ?></p>
                </div>
            <?php endif; ?>

            <div class="kcm-grid" style="grid-template-columns: 2fr 1fr;">
                <div>
                    <form method="post" action="">
                        <?php wp_nonce_field('kcm_settings_action', 'kcm_settings_nonce'); ?>
                        
                        <div class="kcm-card" style="margin-bottom: 20px;">
                            <h2>1. Credenciais da API Kommo</h2>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label for="subdomain">Subdomínio do Kommo</label></th>
                                    <td>
                                        <input type="text" name="subdomain" id="subdomain" value="<?php echo esc_attr($settings['subdomain']); ?>" class="regular-text" placeholder="minhaempresa">
                                        <p class="description">Digite apenas o seu subdomínio (ex: <code>suaempresa</code>) ou URL completa (ex: <code>suaempresa.kommo.com</code>).</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><label for="client_id">ID da Integração (Client ID)</label></th>
                                    <td>
                                        <input type="text" name="client_id" id="client_id" value="<?php echo esc_attr($settings['client_id']); ?>" class="regular-text">
                                        <p class="description">ID gerado ao criar a integração no painel Kommo (em <em>Configurações > Integrações > Criar Integração</em>).</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><label for="client_secret">Chave Secreta (Client Secret)</label></th>
                                    <td>
                                        <input type="password" name="client_secret" id="client_secret" value="<?php echo esc_attr($settings['client_secret']); ?>" class="regular-text">
                                        <p class="description">Chave secreta fornecida nas chaves da sua integração no Kommo.</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><label for="redirect_uri">URI de Redirecionamento</label></th>
                                    <td>
                                        <input type="text" name="redirect_uri" id="redirect_uri" value="<?php echo esc_attr($settings['redirect_uri']); ?>" class="large-text" readonly>
                                        <p class="description">Copie esta URL e cole no campo <strong>Redirect URI</strong> das configurações da sua integração no Kommo.</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><label for="auto_create_user">Criar Usuário WP Automático?</label></th>
                                    <td>
                                        <select name="auto_create_user" id="auto_create_user">
                                            <option value="no" <?php selected($settings['auto_create_user'], 'no'); ?>>Não (Apenas registrar cliente no plugin)</option>
                                            <option value="yes" <?php selected($settings['auto_create_user'], 'yes'); ?>>Sim (Criar conta de Usuário no WordPress)</option>
                                        </select>
                                        <p class="description">Se ativado, cada novo cliente sincronizado criará um usuário assinante no WordPress.</p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="kcm-card" style="margin-bottom: 20px;">
                            <h2>2. Configurações da Área VIP</h2>
                            <p>Configure abaixo os links e páginas da sua Área VIP para o redirecionamento de primeiro acesso dos clientes.</p>

                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label for="vip_area_url">URL da Página da Área VIP</label></th>
                                    <td>
                                        <input type="url" name="vip_area_url" id="vip_area_url" value="<?php echo esc_attr($settings['vip_area_url'] ?? ''); ?>" class="large-text" placeholder="<?php echo esc_attr(home_url('/area-vip/')); ?>">
                                        <p class="description">Para onde o cliente será redirecionado imediatamente após criar a senha. Adicione o shortcode <code>[kcm_vip_area]Conteúdo VIP aqui...[/kcm_vip_area]</code> nesta página.</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><label for="vip_set_password_url">Página Customizada de Definição de Senha (Opcional)</label></th>
                                    <td>
                                        <input type="url" name="vip_set_password_url" id="vip_set_password_url" value="<?php echo esc_attr($settings['vip_set_password_url'] ?? ''); ?>" class="large-text" placeholder="<?php echo esc_attr(home_url('/definir-senha/')); ?>">
                                        <p class="description">Deixe em branco para usar a página automática. Se quiser usar uma página criada no Elementor/Gutenberg, crie a página e adicione o shortcode <code>[kcm_set_password]</code> nela.</p>
                                    </td>
                                </tr>
                            </table>

                            <p class="submit">
                                <button type="submit" name="kcm_save_settings" class="button button-primary button-large">Salvar Configurações</button>
                            </p>
                        </div>

                        <div class="kcm-card">
                            <h2>3. Autenticação OAuth (Código de Autorização)</h2>
                            <p>Após preencher suas credenciais acima e definir o Redirect URI no Kommo, abra o link de autorização no Kommo, conceda as permissões e cole o <strong>Authorization Code</strong> gerado abaixo:</p>

                            <div style="margin-top: 15px;">
                                <p>
                                    <input type="text" name="auth_code" class="large-text" placeholder="Cole o Código de Autorização do Kommo aqui...">
                                </p>
                                <p>
                                    <button type="submit" name="kcm_exchange_code" class="button button-secondary button-large">
                                        <span class="dashicons dashicons-key" style="vertical-align: middle; margin-top: -2px;"></span> Conectar Conta Kommo
                                    </button>
                                </p>
                            </div>
                        </div>
                    </form>
                </div>

                <div>
                    <div class="kcm-card">
                        <h3>Status da Conexão</h3>
                        <hr>
                        <?php if ($hasToken) : ?>
                            <p><span class="kcm-badge">CONECTADO</span></p>
                            <p>O token de acesso está configurado e pronto para uso.</p>

                            <form method="post" action="" style="margin-top: 15px;">
                                <?php wp_nonce_field('kcm_test_conn_action', 'kcm_conn_nonce'); ?>
                                <button type="submit" name="kcm_test_connection" class="button button-secondary">
                                    <span class="dashicons dashicons-admin-network" style="vertical-align: middle; margin-top: -2px;"></span> Testar Conexão API
                                </button>
                            </form>
                        <?php else : ?>
                            <p><span class="kcm-badge disconnected">DESCONECTADO</span></p>
                            <p style="color: #666; font-size: 13px;">Preencha as credenciais e informe o código de autorização para autenticar o plugin no Kommo CRM.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}