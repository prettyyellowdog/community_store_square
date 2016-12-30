<?php defined('C5_EXECUTE') or die(_("Access Denied."));
  extract($vars);
?>

<script type="text/javascript" src="https://js.squareup.com/v2/paymentform"></script>

<script>

   var sqPaymentForm = new SqPaymentForm({

      // Application ID is defined/found in the settings (Store - Settings - Payments)
      applicationId: '<?= $publicAPIKey; ?>',
      inputClass: 'sq-input',
      inputStyles: [
        {
          fontSize: '15px'
        }
      ],
      cardNumber: {
        elementId: 'sq-card-number',
        placeholder: "0000 0000 0000 0000"
      },
      cvv: {
        elementId: 'sq-cvv',
        placeholder: 'CVV'
      },
      expirationDate: {
        elementId: 'sq-expiration-date',
        placeholder: 'MM/YY'
      },
      postalCode: {
        elementId: 'sq-postal-code',
        placeholder: 'Postal Code'
      },
      callbacks: {
        cardNonceResponseReceived: function(errors, nonce, cardData) {
          if (errors) {
            var errorDiv = document.getElementById('errors');
            errorDiv.innerHTML = "";
            errors.forEach(function(error) {
              var p = document.createElement('p');
              p.innerHTML = error.message;
              errorDiv.appendChild(p);
            });
            errorDiv.style.display = "block";
          } else {
            // Alert for debugging purposes only
            // alert('Nonce received! ' + nonce + ' ' + JSON.stringify(cardData));

            // Assign the value of the nonce to a hidden form element
            var nonceField = document.getElementById('card-nonce');
            nonceField.value = nonce;

            // Submit the form
            document.getElementById('store-checkout-form-group-payment').submit();
          }
        },
        unsupportedBrowserDetected: function() {
          // Alert the buyer that their browser is not supported

        }
      }
      });


  $(function() {
    // Alert for debugging purposes only
    // console.log('<?= $mode; ?>');
    // console.log('<?= $publicAPIKey; ?>');

  	$(".store-btn-complete-order").bind("click",function(event){
      // Alert for debugging purposes only
  		// console.log("Requesting nonce - validating credit card")
  		event.preventDefault();
          sqPaymentForm.requestCardNonce();
  		return false;
  	})

 });

</script>

<div class="store-credit-card-boxpanel panel panel-default">
    <div class="panel-body">
        <div style="display:none;" id="errors" class="store-payment-errors square-payment-errors">
        </div>
        <div class="row">
            <div class="col-xs-12">
                <div class="form-group">
                    <label for="sq-card-number"><?= t('Credit Card Number');?></label>
                    <div id="sq-card-number"></div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-7 col-md-7">
                <div class="form-group">
                    <label for="sq-expiration-date"><?= t('Expiration Date');?></label>
                    <div id="sq-expiration-date"></div>
                </div>
            </div>
            <div class="col-xs-5 col-md-5 pull-right">
                <div class="form-group">
                    <label for="sq-cvv"><?= t('CV Code');?></label>
                    <div id="sq-cvv"></div>
                </div>
            </div>
        </div>
        <div class="row">
          <div class="col-xs-12">
            <div class="form-group">
              <label for="sq-postal-code" >Your Postal Code</label>
              <div id="sq-postal-code"></div>
            </div>
          </div>
        </div>
    </div>
</div>
<input type="hidden" id="card-nonce" name="nonce">
<p></p>
