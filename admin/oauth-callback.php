<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    add_submenu_page(
        null,
        'MP Callback',
        'MP Callback',
        'manage_options',
        'mp-marketplace-callback',
        'mpm_oauth_callback'
    );
});

function mpm_oauth_callback() {

    if (!isset($_GET['code'])) {
        wp_die('Código de autorização não encontrado.');
    }

    $code = sanitize_text_field($_GET['code']);

    $client_id     = get_option('mpm_client_id');
    $client_secret = get_option('mpm_client_secret');
    $redirect_uri  = admin_url('admin.php?page=mp-marketplace-callback');

    $response = wp_remote_post('https://api.mercadopago.com/oauth/token', [
        'body' => [
            'grant_type'    => 'authorization_code',
            'client_id'     => $client_id,
            'client_secret'=> $client_secret,
            'code'          => $code,
            'redirect_uri'  => $redirect_uri,
        ]
    ]);

    if (is_wp_error($response)) {
        wp_die('Erro ao conectar com Mercado Pago.');
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($body['access_token'])) {
        wp_die('Resposta inválida do Mercado Pago.');
    }

    // Salvar dados do vendedor (por site)
    update_option('mpm_seller_access_token', sanitize_text_field($body['access_token']));
    update_option('mpm_seller_id', intval($body['user_id']));

    wp_redirect(admin_url('admin.php?page=mp-marketplace-oauth'));
    exit;
}
