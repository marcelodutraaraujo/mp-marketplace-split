<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    add_submenu_page(
        'mp-marketplace',
        'Conectar Mercado Pago',
        'Conectar Conta',
        'manage_options',
        'mp-marketplace-oauth',
        'mpm_oauth_page'
    );
});

function mpm_oauth_page() {

    $client_id = get_option('mpm_client_id');
    $redirect_uri = admin_url('admin.php?page=mp-marketplace-callback');

    $auth_url = 'https://auth.mercadopago.com.br/authorization'
        . '?client_id=' . urlencode($client_id)
        . '&response_type=code'
        . '&platform_id=mp'
        . '&redirect_uri=' . urlencode($redirect_uri);
    ?>

    <div class="wrap">
        <h1>Conectar Mercado Pago</h1>

        <?php if (get_option('mpm_seller_id')): ?>
            <p style="color:green;font-weight:bold;">
                ✅ Conta conectada com sucesso!
            </p>
            <p><strong>Seller ID:</strong> <?php echo esc_html(get_option('mpm_seller_id')); ?></p>
        <?php else: ?>
            <a href="<?php echo esc_url($auth_url); ?>" class="button button-primary">
                🔗 Conectar minha conta do Mercado Pago
            </a>
        <?php endif; ?>
    </div>
    <?php
}
