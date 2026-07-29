<?php

namespace KCM\Admin;

use KCM\Models\Client;

if (!defined('ABSPATH')) {
    exit;
}

class Clients
{
    public function render(): void
    {
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && isset($_GET['_wpnonce'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'kcm_delete_client_' . $_GET['id'])) {
                Client::delete((int) $_GET['id']);
                echo '<div class="notice notice-success is-dismissible kcm-notice"><p>Cliente removido com sucesso.</p></div>';
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
            <h1 class="wp-heading-inline">Clientes Sincronizados</h1>
            <hr class="wp-header-end">

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
                        <th style="width: 100px;">Kommo ID</th>
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
                            <td colspan="8">Nenhum cliente encontrado.</td>
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