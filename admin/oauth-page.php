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

        <?php if (isset($_GET['disconnected'])): ?>
            <div class="notice notice-success is-dismissible">
                <p>Conta desconectada com sucesso. Você pode conectar uma nova conta.</p>
            </div>
        <?php endif; ?>

        <?php if (get_option('mpm_seller_id')): ?>
            <p style="color:green;font-weight:bold;">
                ✅ Conta conectada com sucesso!
            </p>
            <p><strong>Seller ID:</strong> <?php echo esc_html(get_option('mpm_seller_id')); ?></p>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-top:15px;">
                <?php wp_nonce_field('mpm_disconnect_account'); ?>
                <input type="hidden" name="action" value="mpm_disconnect_account">

                <button type="submit"
                    class="button button-secondary"
                    onclick="return confirm('Tem certeza que deseja desconectar esta conta do Mercado Pago?');">
                    🔌 Desconectar conta
                </button>
            </form>

        <?php else: ?>
            <a href="<?php echo esc_url($auth_url); ?>" class="button button-primary">
                🔗 Conectar minha conta do Mercado Pago
            </a>
        <?php endif; ?>
    </div>
    <?php
}

add_action('admin_post_mpm_disconnect_account', function () {

    if (!current_user_can('manage_options')) {
        wp_die('Sem permissão');
    }

    check_admin_referer('mpm_disconnect_account');

    // Remove dados OAuth
    delete_option('mpm_seller_id');
    delete_option('mpm_access_token');
    delete_option('mpm_refresh_token');
    delete_option('mpm_token_expires_at');

    // Redireciona de volta
    wp_redirect(admin_url('admin.php?page=mp-marketplace-oauth&disconnected=1'));
    exit;
});

