<?php

namespace KCM\Admin;

use KCM\Models\Log;

if (!defined('ABSPATH')) {
    exit;
}

class Logs
{
    public function render(): void
    {
        if (isset($_POST['kcm_clear_logs']) && check_admin_referer('kcm_clear_logs_nonce')) {
            Log::clearLogs();
            echo '<div class="notice notice-success is-dismissible kcm-notice"><p>Logs do sistema limpados com sucesso.</p></div>';
        }

        $level    = isset($_GET['level']) ? sanitize_text_field($_GET['level']) : '';
        $page_num = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $limit    = 30;
        $offset   = ($page_num - 1) * $limit;

        $logs        = Log::getLogs($limit, $offset, $level);
        $total_items = Log::countLogs($level);
        $total_pages = ceil($total_items / $limit);

        ?>
        <div class="wrap kcm-wrap">
            <h1 class="wp-heading-inline">Logs do Sistema</h1>
            <hr class="wp-header-end">

            <div style="display: flex; justify-content: space-between; align-items: center; margin: 15px 0;">
                <ul class="subsubsub">
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=kcm-logs')); ?>" class="<?php echo empty($level) ? 'current' : ''; ?>">Todos (<?php echo Log::countLogs(); ?>)</a> |</li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=kcm-logs&level=info')); ?>" class="<?php echo $level === 'info' ? 'current' : ''; ?>">Info (<?php echo Log::countLogs('info'); ?>)</a> |</li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=kcm-logs&level=warning')); ?>" class="<?php echo $level === 'warning' ? 'current' : ''; ?>">Avisos (<?php echo Log::countLogs('warning'); ?>)</a> |</li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=kcm-logs&level=error')); ?>" class="<?php echo $level === 'error' ? 'current' : ''; ?>">Erros (<?php echo Log::countLogs('error'); ?>)</a></li>
                </ul>

                <form method="post">
                    <?php wp_nonce_field('kcm_clear_logs_nonce'); ?>
                    <button type="submit" name="kcm_clear_logs" value="1" class="button button-secondary" onclick="return confirm('Tem certeza que deseja apagar todos os registros de log?');">
                        <span class="dashicons dashicons-trash" style="vertical-align: middle; margin-top: -2px;"></span> Limpar Logs
                    </button>
                </form>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 70px;">ID</th>
                        <th style="width: 90px;">Nível</th>
                        <th>Mensagem</th>
                        <th>Detalhes (Contexto)</th>
                        <th style="width: 170px;">Data / Hora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)) : ?>
                        <tr>
                            <td colspan="5">Nenhum log registrado.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($logs as $log) : ?>
                            <tr>
                                <td><code>#<?php echo esc_html($log['id']); ?></code></td>
                                <td>
                                    <span class="kcm-level-<?php echo esc_attr($log['level']); ?>">
                                        <?php echo esc_html(strtoupper($log['level'])); ?>
                                    </span>
                                </td>
                                <td><strong><?php echo esc_html($log['message']); ?></strong></td>
                                <td>
                                    <?php if (!empty($log['context'])) : ?>
                                        <pre style="margin: 0; max-height: 80px; overflow-y: auto; background: #f6f7f7; padding: 6px; border-radius: 4px; font-size: 11px;"><?php echo esc_html($log['context']); ?></pre>
                                    <?php else : ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($log['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo esc_html($total_items); ?> registros</span>
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