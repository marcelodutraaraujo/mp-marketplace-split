<?php
/**
 * Plugin Name: MP Marketplace Split
 * Description: Plugin de Marketplace com Split de Pagamento usando Mercado Pago
 * Version: 1.0.0
 * Author: Marcelo Dutra
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MPM_PATH', plugin_dir_path(__FILE__));
define('MPM_URL', plugin_dir_url(__FILE__));

// Carregar arquivos
require_once MPM_PATH . 'admin/settings-page.php';
require_once MPM_PATH . 'includes/helpers.php';
require_once MPM_PATH . 'admin/oauth-page.php';
require_once MPM_PATH . 'admin/oauth-callback.php';
require_once MPM_PATH . 'includes/pix-handler.php';
require_once MPM_PATH . 'includes/webhook-handler.php';
require_once MPM_PATH . 'includes/card-handler.php';

add_action('wp_enqueue_scripts', function () {
    if (!is_checkout()) return;
    // SDK oficial do Mercado Pago
    wp_enqueue_script(
        'mercadopago-sdk',
        'https://sdk.mercadopago.com/js/v2',
        [],
        null,
        true
    );
    // assets / JS
    wp_enqueue_script(
        'mp-marketplace-card',
        MPM_URL . 'assets/js/mp-card.js',
        ['mercadopago-sdk'],
        '1.0',
        true
    );
    wp_localize_script(
        'mp-marketplace-card',
        'MP_CONFIG',
        [
            'public_key' => get_option('mpm_public_key'),
            'amount' => WC()->cart ? WC()->cart->total : '0.00',
            'email' => is_user_logged_in() ? wp_get_current_user()->user_email : WC()->customer->get_billing_email(),
        ]
    );
    // assets / css
    wp_enqueue_style( 
        'meu-plugin-style',
        MPM_URL . 'assets/css/styles.css',
        array(),
        '1.0.0',
        'all'
    );
});


// Registrar gateway no WooCommerce
add_action('plugins_loaded', function () {
    if (!class_exists('WC_Payment_Gateway')) return;

    require_once MPM_PATH . 'includes/class-gateway-pix.php';
    require_once MPM_PATH . 'includes/class-gateway-card.php';

    add_filter('woocommerce_payment_gateways', function ($gateways) {
        $gateways[] = 'MPM_Gateway_PIX';
        $gateways[] = 'MPM_Gateway_Card';
        return $gateways;
    });
});



