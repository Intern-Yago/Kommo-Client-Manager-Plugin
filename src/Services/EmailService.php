<?php

namespace KCM\Services;

if (!defined('ABSPATH')) {
    exit;
}

class EmailService
{
    public static function sendSyncNotification(int $totalSynced, int $totalErrors): bool
    {
        $adminEmail = get_option('admin_email');
        $subject = sprintf('[%s] Relatório de Sincronização Kommo CRM', get_bloginfo('name'));
        
        $message  = "Olá,\n\n";
        $message .= "A sincronização entre o Kommo CRM e o seu WordPress foi concluída.\n\n";
        $message .= "• Clientes processados: " . $totalSynced . "\n";
        $message .= "• Erros encontrados: " . $totalErrors . "\n\n";
        $message .= "Acesse o painel do plugin para visualizar os detalhes: " . admin_url('admin.php?page=kcm-dashboard') . "\n";

        return wp_mail($adminEmail, $subject, $message);
    }
}
