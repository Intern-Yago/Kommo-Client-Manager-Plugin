jQuery(document).ready(function($) {
    // Copy webhook URL to clipboard
    $('.kcm-copy-btn').on('click', function(e) {
        e.preventDefault();
        var target = $($(this).data('target')).text().trim();
        navigator.clipboard.writeText(target).then(function() {
            alert('URL do Webhook copiada para a área de transferência!');
        });
    });
});
