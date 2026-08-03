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

    public static function sendVerificationCode(string $userEmail, string $userName, string $code): bool
    {
        $siteName = get_bloginfo('name');
        $subject  = sprintf('[%s] Seu Código de Verificação VIP: %s', $siteName, $code);

        $headers  = ['Content-Type: text/html; charset=UTF-8'];

        $message = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Código de Verificação VIP</title>
        </head>
        <body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; background-color: #f4f6f8; margin: 0; padding: 30px 10px;">
            <div style="max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 35px 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                <div style="text-align: center; margin-bottom: 25px;">
                    <h2 style="color: #1e293b; font-size: 22px; margin: 0 0 8px 0;">Código de Verificação VIP</h2>
                    <p style="color: #64748b; font-size: 14px; margin: 0;">' . esc_html($siteName) . '</p>
                </div>
                <p style="color: #334155; font-size: 15px; line-height: 1.5; margin-bottom: 20px;">
                    Olá, <strong>' . esc_html($userName ?: 'Cliente') . '</strong>!
                </p>
                <p style="color: #334155; font-size: 15px; line-height: 1.5; margin-bottom: 25px;">
                    Para confirmar sua identidade e cadastrar sua senha de acesso à Área VIP, utilize o código de verificação abaixo:
                </p>
                <div style="text-align: center; background: #f1f5f9; border-radius: 10px; padding: 20px; margin-bottom: 25px; border: 1px dashed #cbd5e1;">
                    <span style="font-family: monospace, Courier; font-size: 36px; font-weight: 700; letter-spacing: 8px; color: #2563eb;">' . esc_html($code) . '</span>
                </div>
                <p style="color: #64748b; font-size: 13px; line-height: 1.5; margin-bottom: 0; text-align: center;">
                    Este código é válido por <strong>15 minutos</strong>. Caso você não tenha solicitado este acesso, ignore este e-mail.
                </p>
            </div>
        </body>
        </html>';

        return wp_mail($userEmail, $subject, $message, $headers);
    }
}
