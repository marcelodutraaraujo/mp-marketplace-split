<?php
if (!defined('ABSPATH')) exit;
if (!class_exists('WC_Payment_Gateway')) return;

class MPM_Gateway_PIX extends WC_Payment_Gateway {

    public function __construct() {

        $this->id = 'mp_marketplace_pix';
        $this->method_title = 'Mercado Pago PIX - Marketplace (Split)';
        $this->title = 'PIX (Mercado Pago)';
        $this->method_description = 'Pagamento via PIX com Mercado Pago - by Marcelo Dutra';
        $this->has_fields = false;

        $this->init_form_fields();
        $this->init_settings();

        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            [$this, 'process_admin_options']
        );
    }

    public function process_payment($order_id) {

        if (!get_option('mpm_access_token')) {
            wc_add_notice('Pagamento PIX indisponível no momento.', 'error');
            return;
        }

        $order = wc_get_order($order_id);

        $payment = mpm_create_pix_payment($order);

        if (isset($payment['error'])) {
            wc_add_notice($payment['error'], 'error');
            return;
        }

        $order->update_status('pending', 'Aguardando pagamento PIX');
        $order->save();

        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }
}
