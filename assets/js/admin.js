jQuery(document).ready(function($) {
    // Copy webhook URL to clipboard
    $('.kcm-copy-btn').on('click', function(e) {
        e.preventDefault();
        var target = $($(this).data('target')).text().trim();
        navigator.clipboard.writeText(target).then(function() {
            alert('URL do Webhook copiada para a área de transferência!');
        });
    });

    // Copy VIP activation link for clients
    $(document).on('click', '.kcm-copy-vip-btn', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var clientId = $btn.data('client-id');
        var originalHtml = $btn.html();

        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin" style="font-size: 14px; width: 14px; height: 14px;"></span> Gerando...');

        $.ajax({
            url: kcmAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'kcm_get_activation_link',
                nonce: kcmAdmin.nonce,
                client_id: clientId
            },
            success: function(response) {
                if (response.success && response.data.link) {
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(response.data.link).then(function() {
                            $btn.html('✓ Copiado!').css({'background': '#46b450', 'color': '#fff', 'border-color': '#46b450'});
                            setTimeout(function() {
                                $btn.prop('disabled', false).html(originalHtml).removeAttr('style');
                            }, 3000);
                        });
                    } else {
                        var tempInput = $('<input>');
                        $('body').append(tempInput);
                        tempInput.val(response.data.link).select();
                        document.execCommand('copy');
                        tempInput.remove();
                        $btn.html('✓ Copiado!').css({'background': '#46b450', 'color': '#fff', 'border-color': '#46b450'});
                        setTimeout(function() {
                            $btn.prop('disabled', false).html(originalHtml).removeAttr('style');
                        }, 3000);
                    }
                } else {
                    alert('Erro ao gerar link VIP: ' + (response.data.message || 'Erro desconhecido.'));
                    $btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function() {
                alert('Erro de conexão ao gerar link de primeiro acesso.');
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // Add or edit email for client to allow VIP link generation
    $(document).on('click', '.kcm-edit-email-btn', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var clientId = $btn.data('client-id');
        var clientName = $btn.data('client-name') || 'Cliente';

        var email = prompt('Digite o e-mail de "' + clientName + '" para poder gerar o Link VIP:');
        if (!email) return;

        var originalHtml = $btn.html();
        $btn.prop('disabled', true).text('Salvando...');

        $.ajax({
            url: kcmAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'kcm_save_client_email',
                nonce: kcmAdmin.nonce,
                client_id: clientId,
                email: email
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message || 'E-mail cadastrado com sucesso!');
                    location.reload();
                } else {
                    alert('Erro ao salvar e-mail: ' + (response.data.message || 'Erro desconhecido.'));
                    $btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function() {
                alert('Erro de conexão ao salvar e-mail.');
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
});

