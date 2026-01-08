<?php

if (!defined('ABSPATH')) exit;

function mpm_get_marketplace_token() {
    return get_option('mpm_access_token');
}

function mpm_get_seller_id() {
    // No próximo passo isso virá do OAuth
    return get_option('mpm_seller_id');
}

function mpm_get_commission_percentage() {
    return 10; // Exemplo: 10%
}

add_action('woocommerce_thankyou', function ($order_id) {

    $order = wc_get_order($order_id);
    if (!$order) return;

    $qr_base64 = $order->get_meta('_mp_qr_code_base64');

    if (!$qr_base64) return;

    echo '<h2>Pague com PIX</h2>';
    echo '<p>Escaneie o QR Code abaixo para concluir o pagamento:</p>';
    echo '<img src="data:image/png;base64,' . esc_attr($qr_base64) . '" style="max-width:300px;">';
});

function mpm_get_payment_from_mp($payment_id) {

    $token = get_option('mpm_access_token');

    if (!$token) return false;

    $response = wp_remote_get(
        'https://api.mercadopago.com/v1/payments/' . $payment_id,
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
            'timeout' => 60,
        ]
    );

    if (is_wp_error($response)) {
        return false;
    }

    return json_decode(wp_remote_retrieve_body($response), true);
}

