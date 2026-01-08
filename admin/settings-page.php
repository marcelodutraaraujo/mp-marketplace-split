<?php

if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    add_menu_page(
        'MP Marketplace',
        'MP Marketplace',
        'manage_options',
        'mp-marketplace',
        'mpm_settings_page'
    );
});

function mpm_settings_page() {
    ?>
    <div class="wrap">
        <h1>Mercado Pago Marketplace</h1>

        <form method="post" action="options.php">
            <?php
            settings_fields('mpm_settings');
            do_settings_sections('mp-marketplace');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', function () {

    register_setting('mpm_settings', 'mpm_access_token');
    register_setting('mpm_settings', 'mpm_public_key');
    register_setting('mpm_settings', 'mpm_client_id');
    register_setting('mpm_settings', 'mpm_client_secret');
    register_setting('mpm_settings', 'mpm_application_fee');

    add_settings_section(
        'mpm_section',
        'Credenciais do Marketplace',
        null,
        'mp-marketplace'
    );

    $fields = [
        'mpm_public_key'     => 'Public Key',
        'mpm_access_token'   => 'Access Token',
        'mpm_client_id'      => 'Client ID',
        'mpm_client_secret'  => 'Client Secret',
        'mpm_application_fee'=> 'Taxa do Marketplace (R$)',
    ];

    foreach ($fields as $key => $label) {
        add_settings_field(
            $key,
            $label,
            function () use ($key) {
                $value = get_option($key);

                if ($key === 'mpm_application_fee') {
                    echo '<input type="number" step="0.01" min="0" name="'.$key.'" value="'.esc_attr($value).'" class="regular-text">';
                    echo '<p class="description">Valor fixo cobrado por venda</p>';
                } else {
                    echo '<input type="text" name="'.$key.'" value="'.esc_attr($value).'" class="regular-text">';
                }
            },
            'mp-marketplace',
            'mpm_section'
        );
    }
});
