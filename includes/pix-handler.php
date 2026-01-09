<?php
if (!defined('ABSPATH')) exit;

function mpm_create_pix_payment($order) {
    $order_id = $order->id;
    $marketplace_token = get_option('mpm_access_token');
    $seller_id         = get_option('mpm_seller_id');

    if (!$seller_id || !$marketplace_token) {
        return ['error' => 'Conta Mercado Pago não conectada.'];
    }

    $order_total = (float) $order->get_total();
    $commission_percentage = mpm_get_commission_percentage();
    $application_fee = round($order_total * ($commission_percentage / 100), 2);

    $body = [
        'transaction_amount' => $order_total,
        'description'        => 'Pedido #' . $order->get_id(),
        'payment_method_id'  => 'pix',
        'payer' => [
            'email' => $order->get_billing_email(),
            'first_name' => $order->get_billing_first_name(),
            'last_name'  => $order->get_billing_last_name(),
        ],
        'application_fee' => $application_fee,
        'external_reference' => (string) $order->get_id(),
        //'seller_id' => (int) $seller_id,
    ];

    $response = wp_remote_post(
        'https://api.mercadopago.com/v1/payments',
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $marketplace_token,
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode($body),
            'timeout' => 60,
        ]
    );

    if (is_wp_error($response)) {
        return ['error' => $response->get_error_message()];
    }

    $result = json_decode(wp_remote_retrieve_body($response), true);
    error_log(print_r($result,true));
    if (!isset($result['id'])) {
        return ['error' => 'Erro ao criar pagamento PIX.'];
    }

    return $result;
}
