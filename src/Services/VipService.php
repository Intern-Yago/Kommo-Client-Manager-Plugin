<?php

namespace KCM\Services;

if (!defined('ABSPATH')) {
    exit;
}

class VipService
{
    public static function init(): void
    {
        add_shortcode('kcm_set_password', [self::class, 'renderSetPasswordShortcode']);
        add_shortcode('kcm_vip_area', [self::class, 'renderVipAreaShortcode']);
        add_action('init', [self::class, 'handlePasswordSubmission']);
        add_action('template_redirect', [self::class, 'interceptSetPasswordPage']);
    }

    public static function handlePasswordSubmission(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['kcm_action_set_password'])) {
            return;
        }

        if (!isset($_POST['kcm_set_pwd_nonce']) || !wp_verify_nonce($_POST['kcm_set_pwd_nonce'], 'kcm_set_password_action')) {
            wp_die(__('Sessão expirada ou requisição inválida. Por favor, tente novamente.', 'kommo-client-manager'));
        }

        $userId   = isset($_POST['uid']) ? (int) $_POST['uid'] : 0;
        $token    = isset($_POST['kcm_token']) ? sanitize_text_field($_POST['kcm_token']) : '';
        $pass1    = isset($_POST['pass1']) ? $_POST['pass1'] : '';
        $pass2    = isset($_POST['pass2']) ? $_POST['pass2'] : '';

        if (!$userId || !$token || !UserService::validateActivationToken($userId, $token)) {
            wp_die(__('Este link de primeiro acesso é inválido ou expirou. Solicite um novo link.', 'kommo-client-manager'));
        }

        if (empty($pass1) || strlen($pass1) < 6) {
            wp_die(__('A senha deve ter pelo menos 6 caracteres.', 'kommo-client-manager'));
        }

        if ($pass1 !== $pass2) {
            wp_die(__('As senhas digitadas não coincidem. Por favor, volte e digite novamente.', 'kommo-client-manager'));
        }

        // Set Password
        $success = UserService::setUserPassword($userId, $pass1);

        if (!$success) {
            wp_die(__('Erro ao salvar a senha. Por favor, tente novamente.', 'kommo-client-manager'));
        }

        // Log user in automatically
        wp_set_current_user($userId);
        wp_set_auth_cookie($userId, true);

        // Redirect to VIP Page or home
        $vipUrl = SettingsService::get('vip_area_url', home_url('/'));
        $vipUrl = add_query_arg('kcm_welcome', '1', $vipUrl);

        wp_safe_redirect($vipUrl);
        exit;
    }

    public static function interceptSetPasswordPage(): void
    {
        if (isset($_GET['kcm_action']) && $_GET['kcm_action'] === 'set_password') {
            $userId = isset($_GET['uid']) ? (int) $_GET['uid'] : 0;
            $token  = isset($_GET['kcm_token']) ? sanitize_text_field($_GET['kcm_token']) : '';

            if ($userId && $token) {
                // If template isn't overridden by shortcode, render a full clean page
                add_filter('the_content', function ($content) use ($userId, $token) {
                    return self::renderSetPasswordHtml($userId, $token);
                }, 999);
            }
        }
    }

    public static function renderSetPasswordShortcode($atts = []): string
    {
        $userId = isset($_GET['uid']) ? (int) $_GET['uid'] : 0;
        $token  = isset($_GET['kcm_token']) ? sanitize_text_field($_GET['kcm_token']) : '';

        if (!$userId || !$token) {
            return '<div style="padding: 15px; background: #fff3cd; color: #856404; border: 1px solid #ffeeba; border-radius: 6px; text-align: center;">'
                . '<strong>Link Incompleto:</strong> Para definir sua senha, acesse o link de primeiro acesso fornecido pelo suporte.'
                . '</div>';
        }

        return self::renderSetPasswordHtml($userId, $token);
    }

    public static function renderSetPasswordHtml(int $userId, string $token): string
    {
        $isValid = UserService::validateActivationToken($userId, $token);
        $user    = get_userdata($userId);

        if (!$isValid || !$user) {
            return '<div style="max-width: 480px; margin: 40px auto; padding: 25px; background: #ffffff; border: 1px solid #f5c6cb; border-radius: 10px; text-align: center; font-family: system-ui, -apple-system, sans-serif; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">'
                . '<h3 style="color: #721c24; margin-top: 0;">Link Inválido ou Expirado</h3>'
                . '<p style="color: #495057;">Este link de primeiro acesso não é mais válido ou já expirou (validade de 14 dias).</p>'
                . '<p style="color: #6c757d; font-size: 14px;">Solicite um novo link de primeiro acesso para cadastrar sua senha.</p>'
                . '</div>';
        }

        ob_start();
        ?>
        <div class="kcm-set-password-card" style="max-width: 460px; margin: 40px auto; padding: 30px; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; box-shadow: 0 10px 25px rgba(0,0,0,0.06);">
            <div style="text-align: center; margin-bottom: 25px;">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 56px; background: #eff6ff; color: #2563eb; border-radius: 50%; margin-bottom: 12px;">
                    <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 002-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h2 style="margin: 0 0 8px 0; color: #1e293b; font-size: 22px; font-weight: 700;">Bem-vindo(a) à Área VIP!</h2>
                <p style="margin: 0; color: #64748b; font-size: 14px;">
                    Olá, <strong><?php echo esc_html($user->first_name ?: $user->display_name); ?></strong> (<?php echo esc_html($user->user_email); ?>). Crie sua senha de acesso abaixo:
                </p>
            </div>

            <form method="post" action="" style="display: flex; flex-direction: column; gap: 16px;">
                <?php wp_nonce_field('kcm_set_password_action', 'kcm_set_pwd_nonce'); ?>
                <input type="hidden" name="kcm_action_set_password" value="1">
                <input type="hidden" name="uid" value="<?php echo esc_attr($userId); ?>">
                <input type="hidden" name="kcm_token" value="<?php echo esc_attr($token); ?>">

                <div>
                    <label for="kcm_pass1" style="display: block; font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 6px;">Nova Senha:</label>
                    <input type="password" name="pass1" id="kcm_pass1" required minlength="6" placeholder="Mínimo de 6 caracteres" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; box-sizing: border-box; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#2563eb';" onblur="this.style.borderColor='#cbd5e1';">
                </div>

                <div>
                    <label for="kcm_pass2" style="display: block; font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 6px;">Confirme sua Senha:</label>
                    <input type="password" name="pass2" id="kcm_pass2" required minlength="6" placeholder="Digite a senha novamente" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; box-sizing: border-box; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#2563eb';" onblur="this.style.borderColor='#cbd5e1';">
                </div>

                <button type="submit" style="width: 100%; padding: 12px 20px; background: #2563eb; color: #ffffff; border: none; border-radius: 8px; font-weight: 600; font-size: 15px; cursor: pointer; transition: background-color 0.2s; margin-top: 8px;" onmouseover="this.style.background='#1d4ed8';" onmouseout="this.style.background='#2563eb';">
                    Cadastrar Senha e Entrar na Área VIP
                </button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function renderVipAreaShortcode($atts = [], $content = null): string
    {
        // Notice on successful login/setup
        $welcomeNotice = '';
        if (isset($_GET['kcm_welcome']) && $_GET['kcm_welcome'] == '1') {
            $welcomeNotice = '<div style="max-width: 800px; margin: 0 auto 20px auto; padding: 16px 20px; background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; border-radius: 8px; font-weight: 500; text-align: center;">'
                . '🎉 <strong>Sua senha foi cadastrada com sucesso!</strong> Seja muito bem-vindo(a) à sua Área VIP.'
                . '</div>';
        }

        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            return $welcomeNotice . '<div class="kcm-vip-wrapper">' . do_shortcode($content ?: '<p>Conteúdo VIP liberado!</p>') . '</div>';
        }

        // If not logged in, render Login Form
        ob_start();
        echo $welcomeNotice;
        ?>
        <div class="kcm-vip-login-card" style="max-width: 420px; margin: 40px auto; padding: 30px; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; box-shadow: 0 10px 25px rgba(0,0,0,0.06);">
            <div style="text-align: center; margin-bottom: 25px;">
                <h2 style="margin: 0 0 8px 0; color: #1e293b; font-size: 22px; font-weight: 700;">Acesso à Área VIP</h2>
                <p style="margin: 0; color: #64748b; font-size: 14px;">Insira suas credenciais para acessar seu conteúdo exclusivo.</p>
            </div>

            <?php
            wp_login_form([
                'echo'           => true,
                'redirect'       => get_permalink(),
                'form_id'        => 'kcm-vip-loginform',
                'label_username' => __('E-mail ou Usuário'),
                'label_password' => __('Senha'),
                'label_remember' => __('Lembrar-me'),
                'label_log_in'   => __('Entrar na Área VIP'),
                'remember'       => true,
            ]);
            ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
