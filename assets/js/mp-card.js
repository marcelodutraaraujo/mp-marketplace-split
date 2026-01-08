let cardFormInstance = null;
let submitting = false;

function initCardForm() {

    if (window.mpCardInitialized) return;
    window.mpCardInitialized = true;

    if (!MP_CONFIG.public_key) {
        console.error('Mercado Pago: Public Key não definida');
        return;
    }

    const mp = new MercadoPago(MP_CONFIG.public_key);

    cardFormInstance = mp.cardForm({
        amount: Number(MP_CONFIG.amount).toFixed(2),
        autoMount: true,
        payer: {
            email: MP_CONFIG.email
        },
        form: {
            id: "mp-card-form", 
            cardholderName: { id: "mp_cardholder_name" },
            cardNumber: { id: "mp_card_number" },
            expirationDate: { id: "mp_expiration_date" },
            securityCode: { id: "mp_security_code" },
            issuer: { id: "mp_issuer" },
            installments: { id: "mp_installments_container" },
            identificationNumber: { id: "mp_identification_number" }
        },
        callbacks: {

            onFormMounted: error => {
                if (error) {
                    console.error('Erro ao montar formulário:', error);
                }
            },

            // 🔥 ESTE onSubmit É DO MP (não do Woo)
            onSubmit: event => {
                event.preventDefault();

                if (submitting) return;

                const data = cardFormInstance.getCardFormData();
                console.log('MP data:', data);

                if (!data.token) {
                    alert('Não foi possível gerar o token do cartão');
                    submitting = false;
                    return;
                }

                // Preenche hidden inputs (já no form.checkout)
                jQuery('#mp_token').val(data.token);
                jQuery('#mp_payment_method').val(data.paymentMethodId);
                jQuery('#mp_installments').val(data.installments);
                jQuery('#mp_issuer_id').val(data.issuerId);

                submitting = true;

                // Agora sim envia o checkout real
                jQuery('form.checkout')[0].submit();
            }
        }
    });
}

jQuery(function ($) {

    setTimeout(initCardForm, 3000);

    $('form.checkout').on('change', 'input[name="payment_method"]', function () {
        if (this.value === 'mp_marketplace_card') {
            setTimeout(initCardForm, 300);
        }
    });

    // Intercepta submit do WooCommerce
    $('form.checkout').on('submit', function (e) {

        if ($('input[name="payment_method"]:checked').val() !== 'mp_marketplace_card') {
            return true;
        }

        if (submitting) {
            return true;
        }

        e.preventDefault();

        // GERA TOKEN (NÃO submit)
        cardFormInstance.createCardToken();
        
    });

});
