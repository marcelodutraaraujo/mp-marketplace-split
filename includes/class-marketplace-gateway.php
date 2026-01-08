<?php

if (!defined('ABSPATH')) exit;

if (!class_exists('WC_Payment_Gateway')) {
    return;
}

class MPM_Marketplace_Gateway extends WC_Payment_Gateway {

    public function __construct() {
        $this->id = 'mp_marketplace';
        $this->method_title = 'Mercado Pago Marketplace';
        $this->method_description = 'Pagamento com split automático via Mercado Pago';
        $this->has_fields = false;

        $this->init_form_fields();
        $this->init_settings();

        // Credenciais
        $this->access_token  = $this->get_option( 'access_token' );
        $this->public_key    = $this->get_option( 'public_key' );
        $this->client_id     = $this->get_option( 'client_id' );
        $this->client_secret = $this->get_option( 'client_secret' );

        // Taxa do marketplace
        $this->application_fee = (float) $this->get_option( 'application_fee', 0 );

        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            [$this, 'process_admin_options']
        );

        add_action('wp_enqueue_scripts', function () {
            if (is_checkout()) {
                wp_enqueue_script(
                    'mp-card',
                    MPM_URL . 'assets/js/mp-card.js',
                    ['jquery'],
                    '1.0',
                    true
                );
        
                wp_localize_script('mp-card', 'MP_PUBLIC_KEY', get_option('mpm_public_key'));
            }
        });

    }

    public function process_payment($order_id) {

        $order = wc_get_order($order_id);
    
        $payment = mpm_create_pix_payment($order);
    
        if (isset($payment['error'])) {
            wc_add_notice($payment['error'], 'error');
            return;
        }

        if ($_POST['payment_method'] === 'mp_marketplace') {

            if (!empty($_POST['mp_token'])) {
        
                $payment = mpm_create_card_payment(
                    $order,
                    sanitize_text_field($_POST['mp_token']),
                    intval($_POST['mp_installments']),
                    sanitize_text_field($_POST['mp_payment_method']),
                    intval($_POST['mp_issuer'])
                );
        
                if (isset($payment['error'])) {
                    wc_add_notice($payment['error'], 'error');
                    return;
                }
        
                $order->update_meta_data('_mp_payment_id', $payment['id']);
                $order->save();
        
                if ($payment['status'] === 'approved') {
                    $order->payment_complete($payment['id']);
                } else {
                    $order->update_status('pending', 'Pagamento com cartão em análise.');
                }
        
                return [
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order),
                ];
            }
        }        
    
        // Salvar dados do pagamento
        $order->update_meta_data('_mp_payment_id', $payment['id']);
        $order->update_meta_data('_mp_payment_status', $payment['status']);
    
        if (isset($payment['point_of_interaction']['transaction_data']['qr_code'])) {
            $order->update_meta_data(
                '_mp_qr_code',
                $payment['point_of_interaction']['transaction_data']['qr_code']
            );
    
            $order->update_meta_data(
                '_mp_qr_code_base64',
                $payment['point_of_interaction']['transaction_data']['qr_code_base64']
            );
        }
    
        $order->save();
    
        $order->update_status('pending', 'Aguardando pagamento PIX');
    
        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }

    public function payment_fields() {
        ?>
        <div id="mp-card-form">
            <input type="hidden" id="mp_token" name="mp_token">
            <input type="hidden" id="mp_payment_method" name="mp_payment_method">
            <input type="hidden" id="mp_installments_val" name="mp_installments">
            <input type="hidden" id="mp_issuer_val" name="mp_issuer">
    
            <div id="mp_card_number"></div>
            <div id="mp_expiration_date"></div>
            <div id="mp_security_code"></div>
            <div id="mp_cardholder_name"></div>
            <div id="mp_installments"></div>
            <div id="mp_identification_type"></div>
            <div id="mp_identification_number"></div>
            <div id="mp_issuer"></div>
        </div>
        <?php
    }
    
    
}
