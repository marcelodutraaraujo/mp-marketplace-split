let cardFormInstance = null;
let mpReady = false;

function initCardForm() {

    if (window.mpCardInitialized) return;
    window.mpCardInitialized = true;

    const mp = new MercadoPago(MP_CONFIG.public_key);

    cardFormInstance = mp.cardForm({
        amount: Number(MP_CONFIG.amount).toFixed(2),
        autoMount: true,
        /*payer: {
            email: MP_CONFIG.email,
            identification: {
              type: "CPF",
              number: document.getElementById("mp_identification_number").value
          }
        },*/
        form: {
            id: "mp-card-form", // DIV
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
                if (error) console.error(error);
            }
        }
    });
}

jQuery(function ($) {
  	
  	if( $('form.checkout #payment_method_mp_marketplace_card').attr('checked') ){
  		setTimeout(initCardForm, 3000);
      console.log('testando aqui');
    }
  
  	$('form.checkout').on('change', 'input[name="payment_method"]', function () {
        if (this.value === 'mp_marketplace_card') {
            setTimeout(initCardForm, 300);
          console.log('testando aqui');
        }
    });

    /**
     * EVENTO CORRETO DO WOO
     */
    $('form.checkout').on(
        'submit',
        function (e) {
			e.preventDefault()
          	
            if ($('input[name="payment_method"]:checked').val() !== 'mp_marketplace_card') {
              return true;
          	}
          
            // Já temos token → libera checkout
            if (mpReady) {
                return true;
            }

            // Gera token antes do AJAX
          	//console.log('aquiiii01')
            cardFormInstance.createCardToken();
			//console.log('aquiiii02')
            const interval = setInterval(() => {

                const data = cardFormInstance.getCardFormData();
                console.log('MP DATA:', data);

                if (!data.token) return;

                clearInterval(interval);

                // Preenche inputs
                $('#mp_token').val(data.token);
                $('#mp_payment_method').val(data.paymentMethodId);
                $('#mp_installments').val(data.installments);
                $('#mp_issuer_id').val(data.issuerId);

                mpReady = true;

                // DISPARA O CHECKOUT DE NOVO
               	$('form.checkout').submit();

            }, 1500);

            // BLOQUEIA envio original
            return false;
        }
    );

});
