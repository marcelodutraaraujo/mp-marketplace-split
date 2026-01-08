<?php
if (!defined('ABSPATH')) exit;
if (!class_exists('WC_Payment_Gateway')) return;

class MPM_Gateway_Card extends WC_Payment_Gateway {

    public function __construct() {

        $this->id = 'mp_marketplace_card';
        $this->method_title = 'Mercado Pago Cartão de Crédito - Marketplace (Split)';
        $this->title = 'Cartão de Crédito (Mercado Pago)';
        $this->method_description = 'Pagamento com cartão via Mercado Pago - by Marcelo Dutra';
        $this->has_fields = true;

        $this->init_form_fields();
        $this->init_settings();

        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            [$this, 'process_admin_options']
        );
    }

    public function payment_fields() {
        ?>
        <div id="mp-card-form">

            <p class="form-row form-row-wide">
                <input type="text" id="mp_cardholder_name" placeholder="Nome no cartão" />
            </p>

            <p class="form-row form-row-wide">
                <input type="text" id="mp_card_number" placeholder="Número do cartão" />
            </p>

            <p class="form-row form-row-first">
                <input type="text" id="mp_expiration_date" placeholder="MM/AA" />
            </p>

            <p class="form-row form-row-last">
                <input type="text" id="mp_security_code" placeholder="CVV" />
            </p>

            <p class="form-row form-row-wide">
                <select id="mp_issuer">
                    <option>Emissor</option>
                </select>
            </p>

            <p class="form-row form-row-wide">
                <select id="mp_installments_container">
                    <option>Parcelas</option>
                </select>
            </p>

            <p class="form-row form-row-wide">
                <input type="text" id="mp_identification_number" name="mp_identification_number" placeholder="CPF" />
            </p>

            <!-- Campos hidden usados no submit -->
            <input type="hidden" name="mp_token" id="mp_token" />
            <input type="hidden" name="mp_payment_method" id="mp_payment_method" />
            <input type="hidden" name="mp_installments" id="mp_installments" />
            <input type="hidden" name="mp_issuer_id" id="mp_issuer_id" />

        </div>
        <?php
    }

    public function process_payment( $order_id ) {

        $order = wc_get_order( $order_id );
		error_log("Aqui 01");
        $token        = sanitize_text_field( $_POST['mp_token'] ?? '' );
        $installments = intval( $_POST['mp_installments'] ?? 1 );
        $issuer_id    = sanitize_text_field( $_POST['mp_issuer_id'] ?? '' );
        $payment_method = sanitize_text_field( $_POST['mp_payment_method'] ?? '' );

        $access_token    = get_option('mpm_access_token');
        $application_fee = (float) get_option('mpm_application_fee', 0);
      	$customer_email = $order->get_billing_email();
		error_log("Aqui 02");
        // CPF vindo DIRETO do input visível
        $cpf = preg_replace(
            '/\D/',
            '',
            $_POST['mp_identification_number'] ?? ''
        );
		error_log("Aqui 03");
      	error_log(print_r($_POST,true));
        if ( empty( $cpf ) ) {
            wc_add_notice( 'CPF é obrigatório.', 'error' );
            return [
                'result' => 'failure'
            ];
        }
		error_log("Aqui 04");
        // Idempotency Key
        $existing_key = get_post_meta( $order_id, '_mp_idempotency_key', true );
        if ( empty( $existing_key ) ) {
            $existing_key = 'order_' . $order_id . '_' . wp_generate_uuid4();
            update_post_meta( $order_id, '_mp_idempotency_key', $existing_key );
        }
		error_log("Aqui 05");
        $payment_data = [
            'transaction_amount' => (float) $order->get_total(),
            'token'              => $token,
            'description'        => 'Pedido #' . $order_id,
            'installments'       => $installments,
            'issuer_id'          => $issuer_id,
            'payment_method_id'  => $payment_method,
            'payer' => [
                'email' => $customer_email,
                'identification' => [
                    'type'   => 'CPF',
                    'number' => $cpf
                ]
            ],
            'application_fee' => $application_fee,
            'binary_mode'     => true
        ];
		error_log("Aqui 06");
        $response = wp_remote_post(
            'https://api.mercadopago.com/v1/payments',
            [
                'headers' => [
                    'Authorization'     => 'Bearer ' . $access_token,
                    'Content-Type'      => 'application/json',
                    'X-Idempotency-Key' => $existing_key
                ],
                'body'    => json_encode( $payment_data ),
                'timeout' => 60
            ]
        );
		error_log("Aqui 07");
        if ( is_wp_error( $response ) ) {
            wc_add_notice( 'Erro de conexão com Mercado Pago.', 'error' );
            return [
                'result' => 'failure'
            ];
        }
		error_log("Aqui 08");
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
      error_log(print_r($body, true));

        if ( empty( $body['status'] ) || $body['status'] !== 'approved' ) {
            $message = $body['status_detail'] ?? 'Pagamento recusado.';
            wc_add_notice( $message, 'error' );
            return [
                'result' => 'failure'
            ];
        }

        // Pagamento aprovado
        $order->payment_complete( $body['id'] );
        $order->add_order_note(
            'Pagamento aprovado via Mercado Pago. ID: ' . $body['id']
        );

        WC()->cart->empty_cart();

        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url( $order )
        ];
    }
}
