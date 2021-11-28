jQuery(function ($) {
  var cardContainer = "#square-payment-card-container",
    sq = window.Square,
    card = false,
    wasVisible,
    isVisible = function () {
      var $e = $(cardContainer);
      if ($e.length) {
        var visible = $e.is(":visible");
        if (visible != wasVisible) {
          wasVisible = visible;
          visible && card ? card.recalculateSize() : null;
        }
      }
      setTimeout(isVisible, 10);
    },
    initCardPayment = function () {
      var pay = sq.payments(CSSQP_KEY, CSSQP_LOCATION);
      pay
        .card()
        .then(function (c) {
          card = c;
          card.attach(cardContainer);
          setTimeout(isVisible, 10);
        })
        .catch(function () {});
    };
  if (!sq) {
    return;
  }
  initCardPayment();

  $(".store-btn-complete-order").on("click", function (e) {
    var currentpmid = $('input[name="payment-method"]:checked:first').data("payment-method-id");
    if (currentpmid != CSSQP_PMID) {
      return;
    }
    e.preventDefault();
    if (!card) {
      return;
    }
    $(".store-btn-complete-order").prop("disabled", true);
    card
      .tokenize()
      .then(function (t) {
        if (t.status == "OK") {
          $("#card-nonce").val(t.token);
          $("#store-checkout-form-group-payment").submit();
          return;
        }
        $(".store-btn-complete-order").prop("disabled", false);
      })
      .catch(function (e) {
        $(".store-btn-complete-order").prop("disabled", false);
      });
    return;
  });
});
