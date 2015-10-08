<script type="text/javascript">

    {!! PagSeguro::jsSetSessionId() !!}

    function setSenderHash() {

        var form = document.querySelector('#pay');
        var hash = PagSeguroDirectPayment.getSenderHash();

        if (document.querySelector("input[name=senderHash]") == null) {
            var senderHash = document.createElement('input');
            senderHash.setAttribute('name', "senderHash");
            senderHash.setAttribute('type', "hidden");
            senderHash.setAttribute('value', hash);

            form.appendChild(senderHash);
        }
    }

    function setCardBrand() {

        $('#cardNumber').blur(function () {
            var cardNumber = document.querySelector('#cardNumber').value;
            if (cardNumber != null) {
                PagSeguroDirectPayment.getBrand({
                    cardBin: cardNumber.replace(/ /g, ''),
                    success: function (data) {

                        var form = document.querySelector('#pay');
                        var brand = JSON.stringify(data.brand.name).replace(/"/g, '');

                        if (document.querySelector("input[name=cardBrand]") == null) {
                            var cardBrand = document.createElement('input');
                            cardBrand.setAttribute('name', "cardBrand");
                            cardBrand.setAttribute('type', "hidden");
                            cardBrand.setAttribute('value', brand);

                            form.appendChild(cardBrand);
                        } else {
                            document.querySelector("input[name=cardBrand]").value = brand;
                        }
                    }
                });
            }
        });
    }

    function setCardToken() {

        var parametros = {

            cardNumber: document.getElementById('cardNumber').value,
            brand: document.querySelector("input[name=cardBrand]").value,
            cvv: document.getElementById('cvv').value,
            expirationMonth: document.querySelector('#expirationMonth option:checked').value,
            expirationYear: document.querySelector('#expirationYear option:checked').value,
            success: function (data) {

                var form = document.querySelector('#pay');
                var token = JSON.stringify(data.card.token).replace(/"/g, '');

                if (document.querySelector("input[name=cardToken]") == null) {
                    var cardToken = document.createElement('input');
                    cardToken.setAttribute('name', "cardToken");
                    cardToken.setAttribute('type', "hidden");
                    cardToken.setAttribute('value', token);

                    form.appendChild(cardToken);
                } else {
                    document.querySelector("input[name=cardToken]").value = token;
                }
            },
            error: function (data) {
                console.log(JSON.stringify(data));
            }
        };

        PagSeguroDirectPayment.createCardToken(parametros);
    }

    function setInstallmentAmount() {

        var brand = document.querySelector("input[name=cardBrand]").value;
        var form = document.querySelector('#pay');

        PagSeguroDirectPayment.getInstallments({
            amount: document.getElementById('amount').value,
            maxInstallmentNoInterest: 3,
            brand: brand,
            success: function (data) {
                var installment = document.querySelector('#installments option:checked').value;
                var installments = JSON.parse(JSON.stringify(data))['installments'];
                var amount = installments[brand][installment - 1]['installmentAmount'];

                if (document.querySelector("input[name=installmentAmount]") == null) {
                    var installmentAmount = document.createElement('input');
                    installmentAmount.setAttribute('name', "installmentAmount");
                    installmentAmount.setAttribute('type', "hidden");
                    installmentAmount.setAttribute('value', amount);

                    form.appendChild(installmentAmount);
                } else {
                    document.querySelector("input[name=installmentAmount]").value = amount;
                }
            }
        });
    }

    setCardBrand();

    document.querySelector('#pay').addEventListener('submit', function (event) {
        event.preventDefault();

        var form = document.querySelector('#pay');

        setSenderHash();
        setInstallmentAmount();
        setCardToken();

        $('#button').attr('disabled', 'disabled');
        document.getElementById('loading').style.display = 'block';

        setTimeout(function () {
            form.submit();
        }, 2000);

        return false;
    });

</script>
