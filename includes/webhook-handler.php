<?php
if (!defined('ABSPATH')) exit;

/**
 * Endpoint do Webhook:
 * https://nomedosite.com.br/wp-json/mp-marketplace/v1/webhook
 */

add_action('rest_api_init', function () {
    register_rest_route('mp-marketplace/v1', '/webhook', [
        'methods'  => 'POST',
        'callback' => 'mpm_webhook_handler',
        'permission_callback' => '__return_true',
    ]);
});

function mpm_webhook_handler($request) {

    $data = $request->get_json_params();

    if (!isset($data['data']['id'])) {
        return new WP_REST_Response(['error' => 'ID não informado'], 400);
    }

    $payment_id = sanitize_text_field($data['data']['id']);

    // Buscar dados reais do pagamento no Mercado Pago
    $payment = mpm_get_payment_from_mp($payment_id);

    if (!$payment || !isset($payment['external_reference'])) {
        return new WP_REST_Response(['error' => 'Pagamento inválido'], 400);
    }

    $order_id = intval($payment['external_reference']);
    $order    = wc_get_order($order_id);

    if (!$order) {
        return new WP_REST_Response(['error' => 'Pedido não encontrado'], 404);
    }

    // Atualizar status conforme pagamento
    switch ($payment['status']) {

        case 'approved':
            if ($order->get_status() !== 'processing') {
                $order->payment_complete($payment_id);
                $order->add_order_note('Pagamento PIX aprovado via Mercado Pago.');
            }
            break;

        case 'pending':
            $order->update_status('pending', 'Pagamento PIX pendente.');
            break;

        case 'cancelled':
        case 'rejected':
        case 'expired':
            $order->update_status('cancelled', 'Pagamento PIX cancelado ou expirado.');
            break;
    }

    return new WP_REST_Response(['status' => 'ok'], 200);
}
