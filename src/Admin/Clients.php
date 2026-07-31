<?php

namespace KCM\Admin;

use KCM\Models\Client;
use KCM\Services\ImportService;
use KCM\Services\LogService;

if (!defined('ABSPATH')) {
    exit;
}

class Clients
{
    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Acesso negado. Permissão insuficiente.', 'kommo-client-manager'));
        }

        // Handle single client deletion
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && isset($_GET['_wpnonce'])) {
            $clientId = (int) $_GET['id'];
            if (wp_verify_nonce($_GET['_wpnonce'], 'kcm_delete_client_' . $clientId)) {
                Client::delete($clientId);
                echo '<div class="notice notice-success is-dismissible kcm-notice"><p>Cliente removido do BD local com sucesso.</p></div>';
            }
        }

        // Handle clearing all clients from local database
        if (isset($_POST['kcm_clear_all_clients']) && check_admin_referer('kcm_clear_clients_nonce')) {
            $count = Client::deleteAll();
            LogService::warning("Base de dados local de clientes foi esvaziada. Total de clientes removidos: {$count}.");
            echo '<div class="notice notice-success is-dismissible kcm-notice"><p><strong>Base local esvaziada com sucesso!</strong> ' . esc_html($count) . ' clientes foram removidos do banco de dados do plugin (dados no Kommo CRM intactos).</p></div>';
        }

        // Handle CSV / XLSX spreadsheet import
        if (isset($_POST['kcm_do_import']) && check_admin_referer('kcm_import_clients_nonce')) {
            if (!empty($_FILES['kcm_import_file']['tmp_name'])) {
                $file = $_FILES['kcm_import_file'];
                $createWpUsers = !empty($_POST['kcm_create_wp_users']);

                $result = ImportService::importSpreadsheet($file['tmp_name'], $file['name'], $createWpUsers);

                if ($result['success']) {
                    $msg = '<strong>Importação concluída com sucesso!</strong> ' . esc_html($result['imported']) . ' clientes importados/atualizados.';
                    if ($result['errors'] > 0) {
                        $msg .= ' (' . esc_html($result['errors']) . ' avisos/erros - verifique os logs).';
                    }
                    echo '<div class="notice notice-success is-dismissible kcm-notice"><p>' . $msg . '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible kcm-notice"><p><strong>Erro na importação:</strong> ' . esc_html($result['message']) . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-error is-dismissible kcm-notice"><p>Por favor, selecione um arquivo de planilha (.csv ou .xlsx) válido.</p></div>';
            }
        }

        $search   = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $page_num = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $limit    = 20;
        $offset   = ($page_num - 1) * $limit;

        $clients     = Client::getClients($limit, $offset, $search);
        $total_items = Client::countClients($search);
        $total_pages = ceil($total_items / $limit);

        ?>
        <div class="wrap kcm-wrap">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;">
                <h1 class="wp-heading-inline" style="margin: 0;">Clientes Sincronizados (<?php echo esc_html($total_items); ?>)</h1>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button type="button" class="button button-primary button-large" id="kcm-toggle-import-btn" onclick="jQuery('#kcm-import-card').slideToggle(200);">
                        <span class="dashicons dashicons-upload" style="vertical-align: middle; margin-top: -2px;"></span> Importar Planilha (CSV / XLSX)
                    </button>

                    <form method="post" style="display: inline;" onsubmit="return confirm('ATENÇÃO: Deseja realmente apagar TODOS os clientes do banco de dados local do plugin?\n\nEsta ação limpa somente o banco de dados local do WordPress. NENHUM cliente será apagado do Kommo CRM.');">
                        <?php wp_nonce_field('kcm_clear_clients_nonce'); ?>
                        <button type="submit" name="kcm_clear_all_clients" value="1" class="button button-secondary button-large" style="color: #d63638; border-color: #d63638;">
                            <span class="dashicons dashicons-trash" style="vertical-align: middle; margin-top: -2px;"></span> Apagar Base Local
                        </button>
                    </form>
                </div>
            </div>
            <hr class="wp-header-end">

            <!-- Import Card (Hidden by default, toggled via button or displayed if imported) -->
            <div id="kcm-import-card" class="kcm-card" style="display: none; margin-bottom: 25px; border-left: 4px solid #2271b1; background: #f6f7f7;">
                <h2 style="margin-top: 0;">
                    <span class="dashicons dashicons-upload" style="color: #2271b1;"></span>
                    Importar Clientes via Planilha (.csv ou .xlsx)
                </h2>
                <p>Selecione um arquivo de planilha em formato <strong>CSV</strong> ou <strong>XLSX (Excel)</strong> para inserir ou atualizar clientes em lote no plugin.</p>

                <form method="post" enctype="multipart/form-data" style="margin-top: 15px;">
                    <?php wp_nonce_field('kcm_import_clients_nonce'); ?>
                    
                    <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap; margin-bottom: 15px;">
                        <input type="file" name="kcm_import_file" accept=".csv, .xlsx" required style="background: #fff; padding: 6px 12px; border: 1px solid #c3c4c7; border-radius: 4px;" />
                        <button type="submit" name="kcm_do_import" value="1" class="button button-primary">
                            Processar e Importar Planilha
                        </button>
                        <?php
                        $sample_url = wp_nonce_url(
                            admin_url('admin.php?page=kcm-clients&action=download_sample'),
                            'kcm_download_sample_nonce'
                        );
                        ?>
                        <a href="<?php echo esc_url($sample_url); ?>" class="button button-secondary">
                            <span class="dashicons dashicons-download" style="vertical-align: middle; margin-top: -2px;"></span> Baixar Planilha Modelo (.xlsx)
                        </a>
                    </div>

                    <div style="margin-top: 10px;">
                        <label style="font-weight: 500;">
                            <input type="checkbox" name="kcm_create_wp_users" value="1" checked />
                            Criar/Vincular usuário no WordPress automaticamente para novos clientes (caso possuam e-mail)
                        </label>
                    </div>

                    <div style="margin-top: 15px; background: #ffffff; padding: 12px 16px; border-radius: 6px; border: 1px solid #dcdcde;">
                        <strong>Colunas aceitas na planilha (cabeçalho):</strong>
                        <ul style="margin: 5px 0 0 20px; list-style-type: disc; color: #50575e;">
                            <li><strong>Nome:</strong> <code>Nome</code>, <code>Name</code>, <code>Cliente</code> ou <code>Contato</code></li>
                            <li><strong>E-mail:</strong> <code>E-mail</code>, <code>Email</code> ou <code>Mail</code></li>
                            <li><strong>Telefone:</strong> <code>Telefone</code>, <code>Phone</code>, <code>Celular</code> ou <code>WhatsApp</code></li>
                            <li><strong>Empresa:</strong> <code>Empresa</code>, <code>Company</code> ou <code>Razão Social</code></li>
                            <li><strong>Status:</strong> <code>Status</code>, <code>Fase</code> ou <code>Etapa</code> <em>(opcional)</em></li>
                            <li><strong>Kommo ID:</strong> <code>Kommo ID</code> ou <code>ID</code> <em>(opcional, se não informado será gerado um ID interno)</em></li>
                        </ul>
                    </div>
                </form>
            </div>

            <!-- Search Form -->
            <form method="get" style="margin-bottom: 15px; margin-top: 15px;">
                <input type="hidden" name="page" value="kcm-clients" />
                <p class="search-box">
                    <label class="screen-reader-text" for="client-search-input">Buscar Clientes:</label>
                    <input type="search" id="client-search-input" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Nome, E-mail ou Empresa...">
                    <input type="submit" id="search-submit" class="button" value="Buscar Cliente">
                </p>
            </form>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 110px;">Kommo ID</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Telefone</th>
                        <th>Empresa</th>
                        <th>Usuário WP</th>
                        <th style="width: 160px;">Última Atualização</th>
                        <th style="width: 90px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clients)) : ?>
                        <tr>
                            <td colspan="8" style="padding: 20px; text-align: center; color: #646970;">
                                Nenhum cliente encontrado na base local do plugin.
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($clients as $client) : ?>
                            <tr>
                                <td><code>#<?php echo esc_html($client['kommo_id']); ?></code></td>
                                <td><strong><?php echo esc_html($client['name'] ?: 'Sem nome'); ?></strong></td>
                                <td><?php echo esc_html($client['email'] ?: '-'); ?></td>
                                <td><?php echo esc_html($client['phone'] ?: '-'); ?></td>
                                <td><?php echo esc_html($client['company'] ?: '-'); ?></td>
                                <td>
                                    <?php if ($client['wp_user_id']) : ?>
                                        <a href="<?php echo esc_url(get_edit_user_link($client['wp_user_id'])); ?>">
                                            #<?php echo esc_html($client['wp_user_id']); ?> (Ver Perfil)
                                        </a>
                                    <?php else : ?>
                                        <span style="color: #888;">Não vinculado</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($client['updated_at']); ?></td>
                                <td>
                                    <?php
                                    $delete_url = wp_nonce_url(
                                        admin_url('admin.php?page=kcm-clients&action=delete&id=' . $client['id']),
                                        'kcm_delete_client_' . $client['id']
                                    );
                                    ?>
                                    <a href="<?php echo esc_url($delete_url); ?>" class="button button-small button-link-delete" onclick="return confirm('Deseja realmente remover este cliente do BD local?');">
                                        Excluir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo esc_html($total_items); ?> itens</span>
                        <?php
                        echo paginate_links([
                            'base'      => add_query_arg('paged', '%#%'),
                            'format'    => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total'     => $total_pages,
                            'current'   => $page_num,
                        ]);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}